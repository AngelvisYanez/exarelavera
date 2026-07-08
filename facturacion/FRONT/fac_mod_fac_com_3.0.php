<?php

/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2015-07-22
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
/* Consulta del tipo de proveedores */
if (isset($provAjax)) {
    $obBD_con1->getPageGridJson(2, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
}
/* ver si exite un proveedor */
if (isset($provAjax2)) {
    $pers = $obBD_con1->getArrayConsulta(30, $Prs_Ced . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $per = array(0 => $pers[0]);
    if (count($pers) > 1)
        foreach ($pers as $p) {
            if ($p['Emp_Cod'] * 1 == $Ses_Emp_Cod * 1) {
                $per[0] = $p;
                break;
            }
        }
    $responce['rows'] = $per;
    $responce['total'] = count($per);
    $obBD_con1->echoJson($responce);
}

/* fuarda un nuevo proveedor */
if (isset($guardaProvAjax)) {
    $data = $_POST;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $obBD_con1->inicio_transaccion($obBD_conexion);
    if (empty($Prs_Cod)) {
        $obBD_con1->operacionobBD(31, $data, $obBD_conexion);
        $data['Prs_Cod'] = $obBD_con1->insercionid($obBD_conexion);
    }
    $obBD_con1->operacionobBD(32, $data, $obBD_conexion);
    $data['Prv_Cod'] = $obBD_con1->insercionid($obBD_conexion);
    $data['proveedor'] = trim($data['Prs_Ape'] . ' ' . $data['Prs_Nom']);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion);
    if ($obBD_con1->Error == 0) {
        $responce = array('success' => true, 'prov' => $data);
    } else {
        $responce = array('success' => false, 'message' => 'No se pudo realizar la transacción!', 'error' => $obBD_con1->MsgError);
    }
    $obBD_con1->echoJson($responce);
}
/* Consulta datos del documento si existe */
if (isset($ajaxCopNum)) {
    $resp = array('success' => true);
    if (!empty($Tic_Cod) && !empty($Cop_Num)) {
        $row_rs_CodDoc = $obBD_con1->getRowConsulta(7, $Prv_Cod . '*' . $Tic_Cod . '*' . $Cop_Num . '*' . $Cop_Cod, $obBD_conexion);
        if ($row_rs_CodDoc['Cop_Cod'] != "")
            $resp = array('success' => false, 'message' => 'El documento ya existe en el Sistema!');
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

/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(8, $Ses_Emp_Cod, $obBD_conexion);
$vendedor = $obBD_con1->getRowConsulta(10, $Ses_Suc_Cod . '*' . $Ses_Prs_Cod . '*' . '' /*$tipo_compr*/, $obBD_conexion);

/* buscar autorizaciones retencion */
if (isset($autorizaAjax)) {
    $obBD_con1->getPageGridJson(79, $vendedor['Pun_Cod'] . '*' . $tipo_compr . '*' . $Ret_Fec, $obBD_conexion, $page, $rows);
}


/* Cambiar Tipo de Pago */
if (isset($saveChangePago)) {
    $resp = array('success' => false);
    $obBD_conexion_set = new Class_Log_Conexion_Global($Ses_Dat_Dis);
    $obBD_con_set = new MysqlDatos;
    $obBD_con1->validaCierrePeriodo('compras', 'Cop_Fec', 'Cop_Cod', $Cop_Fec, $Cop_Cod, $obBD_conexion, 'S');
    $obBD_con_set->inicio_transaccion($obBD_conexion_set);
    try {
        //SE ENVIA CREDITO
        if ($For_Cod * 1 == 2) {
            //ESTABA A CREDITO
            if (isset($Cpp_Cod) && !empty($Cpp_Cod) && !is_null($Cpp_Cod)) {
                $obBD_con_set->operacionobBD('ccpp_pagar.update', array('Cpp_Ven' => $Cpp_Ven, 'Cpp_Obs' => $Cpp_Obs, 'where' => array('Cop_Cod' => $Cop_Cod, 'Cpp_Cod' => $Cpp_Cod, 'Com_Cod' => $Com_Cod)), $obBD_conexion_set);
            }
            //ESTABA A CONTADO
            else {

                $codigoRetencion = $obBD_con1->getArrayConsulta(995, array('Cop_Cod' => $Cop_Cod, 'Emp_Cod' => $Ses_Emp_Cod), $obBD_conexion);
                //existe codigo de retencion en el comprobante de compra (retenciones con valor de 0 no tienen)
                foreach ($codigoRetencion as $key) {
                    $codigoCuenta = $obBD_con1->getRowConsulta(9966, array('Com_Cod' => $Com_Cod, 'Pld_Cod' => $key['Pld_Cod']), $obBD_conexion);
                }

                if (!empty($codigoRetencion) && !is_null($codigoRetencion) && !empty($codigoCuenta) && !is_null($codigoCuenta)) {

                    $comprobanteCompra = $obBD_con1->getRowConsulta(996, array('Com_Cod' => $Com_Cod), $obBD_conexion);
                    $Com_Con_Ret = "RETENCION DE LA COMPRA NUMERO " . $Cop_Num;
                    $Tia_Asi_Ret = $obBD_con1->getRowConsulta(133, 15, $obBD_conexion);
                    $Com_Num_Ret = $obBD_con1->getComNumPecAuto($Tia_Asi_Ret['Tia_Cod'], $comprobanteCompra['Pec_Cod'], $comprobanteCompra['Com_Fec'], $obBD_conexion);
                    $campo = 'Prv_Cod';

                    /* Cabecera del Comprobante */
                    $obBD_con1->operacionobBD(14, $comprobanteCompra['Pec_Cod'] . '*' . $comprobanteCompra['Prv_Cod'] . '*' . $Com_Num_Ret . '*' . $codigoRetencion[0]['Ret_Fec'] . '*' . trim($Com_Con_Ret) . '*' . $Tia_Asi_Ret['Tia_Cod'] . '*' . '0' . '*' . 'RETENCION' . '*' . $campo, $obBD_conexion);
                    $Com_Cod_Ret = $obBD_con1->insercionid($obBD_conexion);

                    //update los asientos de la retencion con el nuevo comprobante creado
                    foreach ($codigoRetencion as $key) {
                        $obBD_con1->operacionobBD(997, array('Com_Cod' => $Com_Cod, 'Com_Ret' => $Com_Cod_Ret, 'Pld_Cod' => $key['Pld_Cod']), $obBD_conexion);
                    }
                    //Obtener el total de la retencion 
                    $total = $obBD_con1->getRowConsulta(998, array('Com_Cod' => $Com_Cod_Ret), $obBD_conexion);
                    //crear un asiento debe en el comprobante de retencion con el valor de la retencion y la nueva cuenta que se esta enviando de credito 
                    $obBD_con1->operacionobBD(999, array('Com_Cod' => $Com_Cod_Ret, 'Asi_Val' => $total['totalRetencion'], 'Pld_Cod' => $Pag_Pld, 'Cop_Num' => $Cop_Num), $obBD_conexion);
                    $Asi_Cod_Ret = $obBD_con1->insercionid($obBD_conexion);
                    //actualizar el valor del asiento del comprobante de compra en el debe mas el valor total de la retencion 
                    $obBD_con1->operacionobBD(1000, array('Com_Cod' => $Com_Cod, 'Asi_Val' => $total['totalRetencion'], 'Pld_Cod' => $Pag_Pld), $obBD_conexion);
                    //actualiza el valor del comprobante de retencion
                    $obBD_con1->operacionobBD(1001, array('Com_Cod' => $Com_Cod_Ret, 'Com_Val' => $total['totalRetencion']), $obBD_conexion);

                    $obBD_con1->operacionobBD(1002, array('Cpp_Ven' => $Cpp_Ven, 'Cpp_Obs' => $Cpp_Obs, 'Cop_Cod' => $Cop_Cod, 'Com_Cod' => $Com_Cod), $obBD_conexion);
                    $Cpp_Cod = $obBD_con1->insercionid($obBD_conexion);
                    //Crear detalle 
                    $obBD_con1->operacionobBD(255, array('Com_Cod' => $Com_Cod_Ret, 'Pag_Cod' => 50, 'Pag_Fec' => $codigoRetencion['Ret_Fec'], 'Pag_Val' => $total['totalRetencion'], 'Pag_Obs' => "ABONO POR RETENCION", 'Cpp_Cod' => $Cpp_Cod, 'Asi_Cod' => $Asi_Cod_Ret), $obBD_conexion);
                } else {
                    $obBD_con_set->operacionobBD('ccpp_pagar.insert', array('Cpp_Ven' => $Cpp_Ven, 'Cpp_Obs' => $Cpp_Obs, 'Cop_Cod' => $Cop_Cod, 'Com_Cod' => $Com_Cod), $obBD_conexion_set);
                    //actualizar la cuenta del asiento del comprobante de retencion
                    $obBD_con1->operacionobBD(10000, array('Com_Cod' => $Com_Cod, 'Pld_Cod' => $Pag_Pld), $obBD_conexion);
                }
            }
            //DE CREDITO A CONTADO
        } else if (isset($Cpp_Cod) && !empty($Cpp_Cod) && !is_null($Cpp_Cod)) {

            $onlyRetencion = false;
            $Pagos1 = $obBD_con1->getRowConsulta(57, $Cpp_Cod . '*' . 'A', $obBD_conexion);
            $retencionValue = $obBD_con1->getRowConsulta(577, $Cpp_Cod . '*' . 'A', $obBD_conexion);
            if (round($Pagos1['total'] * 1, 2) == round($retencionValue['total'] * 1, 2)) {
                $onlyRetencion = true;
            }
            if ($onlyRetencion) {

                $compRetencion = $obBD_con1->getRowConsulta(990, $Cpp_Cod, $obBD_conexion); //valor y codigoComp de la retencion
                $obBD_con1->operacionobBD(991, array('Com_Ret' => $compRetencion['Com_Cod']), $obBD_conexion); //elimino el asiento del debe del comprobante de retencion
                $obBD_con1->operacionobBD(9911, array('Pag_Val' => $compRetencion['Pag_Val'], 'Com_Cod' => $Com_Cod, 'Pld_Cod' => $Pag_Pld), $obBD_conexion); //actualizo el valor del haber del comprobante de retencion
                $obBD_con1->operacionobBD(992, array('Com_Cod' => $Com_Cod, 'Com_Ret' => $compRetencion['Com_Cod']), $obBD_conexion); //cambio de asientos del comprobante de retencion al de compra
                $obBD_con1->operacionobBD(993, array('Cpp_Cod' => $Cpp_Cod), $obBD_conexion); //elimina detalle de cuentas por pagar
                $obBD_con1->operacionobBD(994, array('Com_Ret' => $compRetencion['Com_Cod']), $obBD_conexion); //elimina el comprobante de la retencion
                $obBD_con_set->operacionobBD('ccpp_pagar.deleteWhere', array('where' => array('Cop_Cod' => $Cop_Cod, 'Cpp_Cod' => $Cpp_Cod, 'Com_Cod' => $Com_Cod)), $obBD_conexion_set);
            } else {
                $obBD_con_set->operacionobBD('ccpp_pagar.deleteWhere', array('where' => array('Cop_Cod' => $Cop_Cod, 'Cpp_Cod' => $Cpp_Cod, 'Com_Cod' => $Com_Cod)), $obBD_conexion_set);
            }
        }

        $obBD_con_set->operacionobBD('compras.update', array('Tri_Cod' => $Tri_Cod, 'Con_Cod' => isset($Con_Cod) && !empty($Con_Cod) ? $Con_Cod : null, 'where' => array('Cop_Cod' => $Cop_Cod)), $obBD_conexion_set);
        $obBD_con_set->operacionobBD('asientos.update', array('Pld_Cod' => $Pag_Pld, 'where' => array('Com_Cod' => $Com_Cod, 'Pld_Cod' => $Pld_Cod_Pag, 'Asi_Deh' => 'H')), $obBD_conexion_set);
    } catch (Exception $e) {
        $obBD_con_set->rollBack_nomsn($obBD_conexion_set);
        $resp['message'] = $e->getMessage();
        $obBD_con1->echoJson($resp);
    }

    // finalizo la transaccion y compruebo errores
    $resp['success'] = $obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set);
    if (!$resp['success']) $resp['error'] = $obBD_con_set->MsgError;
    $obBD_con_set->echoJson($resp);
}


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

/* busqueda de documentos */
if (isset($searchDocument)) {
    $data = $_GET;
    $data['Emp_Cod'] = $Ses_Emp_Cod;
    $data['search_compras'] = "C";
    $responce = $obBD_con1->getPageGrid(34, $data, $obBD_conexion);
    if ($responce['records'] > 0) {
        foreach ($responce['rows'] as &$row) {

            $row['Cpp_Edit'] = 'S';
            $row['Cpp_Min'] = 0;
            $row['Bod_Cod'] = $obBD_con1->getRowConsulta(969, $row['Cop_Cod'], $obBD_conexion);
            if (!empty($row['Cpp_Cod'])) {
                $Pagos1 = $obBD_con1->getRowConsulta(57, $row['Cpp_Cod'] . '*' . 'A', $obBD_conexion);
                if ($Pagos1['total'] * 1 > 0) {
                    $row['onlyRetencion'] = false;
                    //COMPROBAR SI SOLO TIENE EL PAGO DE RETENCION
                    $retencionValue = $obBD_con1->getRowConsulta(577, $row['Cpp_Cod'] . '*' . 'A', $obBD_conexion);
                    if (round($Pagos1['total'] * 1, 2) == round($retencionValue['total'] * 1, 2)) {
                        $row['onlyRetencion'] = true;
                        $row['Cpp_Det'] = 'N';
                        $row['Cpp_Edit'] = 'S'; //tiene pagos activos
                    } else {
                        $row['Cpp_Det'] = 'S';
                        $row['Cpp_Edit'] = 'N'; //tiene pagos activos
                    }
                    $Pagos1 = $obBD_con1->getRowConsulta(57, $row['Cpp_Cod'] . '*' . 'A' . '*' . 'SUM', $obBD_conexion);
                    $row['Cpp_Min'] = round($Pagos1['total'] * 1, 2);
                }
                /*  $Pagos2=$obBD_con1->getRowConsulta(57, $row['Cpp_Cod'], $obBD_conexion);
                if($Pagos2['total']*1>0) $row['Cpp_Edit']='N'; //tiene algun pago vinculado*/
            } else { // Caja Chica
                $caja = $obBD_con1->getRowConsulta(58, $row['Cop_Cod'], $obBD_conexion);
                if ($caja['total'] * 1 > 0) $row['Rcc_Det'] = 'S';
                $caja_pend = $obBD_con1->getRowConsulta(58, $row['Cop_Cod'] . '*' . 'P', $obBD_conexion);
                if ($caja_pend['total'] * 1 > 0) $row['Rcc_Pen'] = 'S';
            }
            if ($configs['Cof_Con'] == 'S' && !empty($row['Com_Cod'])) {
                $cuentas = $obBD_con1->getRowConsulta((!empty($row['Cpp_Cod']) ? (!empty($row['Rcc_Pen']) ? 70 : 37) : 39), $row['Com_Cod'], $obBD_conexion);
                $row['Pld_Cod_Pag'] = $cuentas['Pld_Cod'];
                $otras_comp = $obBD_con1->getRowConsulta(65, $row['Com_Cod'], $obBD_conexion);
                if ($otras_comp['total'] * 1 > 1) $row['Com_Edit'] = 'N';
                //CARGAR NEGOCIACIONES DE CAMARONERA
                $row['Num_Neg'] =  $row['Cod_Nd'] = "";
                if (!empty($row['Cod_Neg'])) {
                    $doc_camaronera = $obBD_con1->getRowConsulta(1008, $Ses_Emp_Cod . '*' . $row['Cop_Cod'], $obBD_conexion);
                    $row['Num_Neg'] = $doc_camaronera['Num_Neg'];
                    $row['Cod_Neg'] = $doc_camaronera['Cod_Neg'];
                    $row['Cod_Nd'] = $doc_camaronera['Cod_Nd'];
                }
            }
        }
        unset($row);
    }
    // tipo_compr.Tic_Cod!=4
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
    $resp = array('success' => true, 'Cop_Cod' => $Cop_Cod, 'Cop_Fec' => $Cop_Fec, 'Ret_Cod' => $Ret_Cod, 'rows' => array());
    if (!empty($Cop_Cod)) {
        $resp['items'] = $obBD_con1->getArrayConsulta(35, $Cop_Cod, $obBD_conexion);
        if (count($resp['items']) == 0)
            $resp = array('success' => false, 'message' => 'No se encontraron items en el detalle del documento!');
        else {
            foreach ($resp['items'] as $r) if ($r['Iva_Por'] * 1 > 0) {
                $resp['Iva_Cod'] = $r['Iva_Cod'];
                break;
            }
            if (!empty($Ret_Cod)) {
                $retencion = $obBD_con1->getArrayConsulta(59, $Ret_Cod, $obBD_conexion);
                foreach ($resp['items'] as &$it) {
                    foreach ($retencion as $r) if ($it['Cop_Int'] == $r['Ret_Int']) foreach ($r as $k => $v) $it[($r['Ren_Ret'] == 'R' ? 'Ret_' : 'Iva_') . $k] = $v;
                }
                unset($it);
                // Agregar Ret_Link para el botón de impresión
                $reportes = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
                $resp['Ret_Link'] = "" . (isset($reportes[2]) ? $reportes[2] : '') . "?Ret_Cod=$Ret_Cod";
            }
            if ($configs['Cof_Con'] == 'S' && !empty($Com_Cod)) {
                $iva = $obBD_con1->getRowConsulta(36, $Com_Cod, $obBD_conexion);
                $resp['Pld_Cod'] = $iva['Pld_Cod'];
            }
        }
        $reemb = $obBD_con1->getArrayConsulta('compra_reembolsos.selectWhere', array('Cop_Cod' => $Cop_Cod), $obBD_conexion);
        if (count($reemb) > 0) {
            $resp['reembolsos'] = $reemb;
        }
    } else $resp['success'] = false;
    $obBD_con1->echoJson($resp);
}


/* Guardar documento */
if (isset($saveDocument)) {
    $obBD_con1->validaCierrePeriodo('compras', 'Cop_Fec', 'Cop_Cod', null, $Cop_Cod, $obBD_conexion, 'S');
    $responce = array('success' => false);
    /* Que sea vendedor */
    if (empty($vendedor['Vnd_Cod'])) {
        $responce['message'] = "No tiene permisos de Vendedor!";
    }
    $Vnd_Cod = $vendedor['Vnd_Cod'];
    $For_Cod = $For_Cod * 1;
    /* valida que no exista el documento */
    if ($Tic_Sri * 1 != 17) { // Condicion agregada xq se repite el numero de DAE
        $row_rs_CodDoc = $obBD_con1->getRowConsulta(7, $Prv_Cod . '*' . $Tic_Cod . '*' . $Cop_Num . '*' . $Cop_Cod, $obBD_conexion);
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
    // $responce['message'] = "No Existe Periodo para la Fecha: $Retencion";
    if ($Retencion) {
        foreach ($items as $i => $item) { //Verificar si tiene el codigo 332.. del sri
            $cod_sri =  $item['Ret_Ren_Sri'];
            if (!empty($Aut_Cod)  &&  ($cod_sri == "332" || $cod_sri == "332B" || $cod_sri == "332C" || $cod_sri == "332D" || $cod_sri == "332G" || $cod_sri == "332H" || $cod_sri == "332I")) {
                $Retencion = false;
            }
            if ($Tic_Cod == 2  &&  !empty($Aut_Cod)) {
                $Retencion = true;
            }
        }
    }
    // $responce['message'] = "No Existe Periodo para la Fecha: $Retencion";

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
            //if(!$obBD_con1->createUsuCliente($Ses_Emp_Cod, $Ses_Suc_Cod, $Prs_Cod, $Prs_Ced, $obBD_conexion)) $responce['message']='Error al crear usuario de <u>Comprobantes Electrónicos</u>!';
        } else $claveAcceso = $claveAccesoExt;
    }
    /* valida que no exista la retencion */
    if ($Retencion && $Ret_Num != 0) {
        $autor = $obBD_con1->getRowConsulta(61, $Aut_Cod, $obBD_conexion);
        $row_rs_RetDoc = $obBD_con1->getRowConsulta(76, $Ret_Num . '*' . $autor['Aut_Sri'] . '*' . $Ret_Cod . '*' . $Ses_Emp_Cod . '*' . $autor['Pun_Sri'], $obBD_conexion);
        //$obBD_con1->echoLog($Ret_Num.'*'.$Aut_Sri.'*'.$Ret_Cod.'*'.$Ses_Emp_Cod);
        //$obBD_con1->echoLog($row_rs_RetDoc);
        /* if (!empty($row_rs_RetDoc['Ret_Cod'])) {
            $responce['message'] = "La Retencion No. $Ret_Num  con Autorizacion No. $Aut_Sri ya existe!";
        }*/
        if ($row_rs_RetDoc['Ret_Num'] != 0) { //Numero de retencion es igual a cero significa que no ha usado el numero de retencion
            if (!empty($row_rs_RetDoc['Ret_Cod'])) {  //Si no esta vacio  --- si se repite el codigo 
                $responce['message'] = "  La Retencion No. $Ret_Num  con Autorizacion No. $Aut_Sri ya existe!";
            }
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
    $obBD_ins1->debug(false);
    $obBD_ins1->inicio_transaccion($obBD_conexionIns);

    try {
        // //ChromePhp::log("AUT_CODLIQ:".$Aut_Codliq);
        if ($Tic_Cod == 3    && !empty($Aut_Codliq)) {
            require_once('../LOGICA/fac_log_electronica.php');
            // function getLiquidacionClaveAcceso($Aut_Cod, $Doc_Fec, $Doc_Num, $obBD)
            // $Cop_Aut =  $obBD_con1->getLiquidacionClaveAcceso($Ses_Emp_Cod, $Ses_Suc_Cod, $Aut_Cod,  $Cop_Fec, $Cop_Num, $obBD_conexion);
            $Cop_Aut =  $obBD_con1->getLiquidacionClaveAcceso($Aut_Codliq,  $Cop_Fec, $Cop_Num, $obBD_conexion);
            $claveAccesoliq = $Cop_Aut;
            // $Cop_Num =  $Pun_Sri . $Cop_Num;
        }
        // //ChromePhp::log(":::::::::::::::::".empty($Aut_Codliq));
        /* Cabecera de la factura de compra */
        $row_cop_old = $obBD_con1->getRowConsulta(40, $Cop_Cod, $obBD_conexion);
        $meseCop = explode('-', $Cop_Fec);
        if (substr($Cop_Fec, 0, 7) !== substr($row_cop_old['Cop_Fec'], 0, 7)) {
            $Cop_Sec = $obBD_con1->codigoSecMensualAuto($Pec_Cod, $meseCop[1], $obBD_conexion); // Secuencia de compras por mes
        } else $Cop_Sec = $row_cop_old['Cop_Sec'];
        /*Update Cop_Ide*/
        $obBD_ins1->operacionobBD(1009, array('Cop_Ide' => $Cop_Ide, 'Cop_Cod' => $Cop_Cod), $obBD_conexionIns);
        // $obBD_ins1->operacionobBD(11, $Tic_Cod . '*' . $Prv_Cod . '*' . $Ciu_Cod . '*' . trim($Cop_Num) . '*' . trim($Cop_Aut) . '*' . $Cop_Fec . '*' . $hoy . '*' . trim($Cop_Obs) . '*' . $Cop_Cad . '*' . $Cop_Imf . '*' . $Tri_Cod . '*' . $Cop_Des . '*' . $Pec_Cod . '*' . $Tpc_Cod . '*' . (isset($Cop_Ntd) ? $Cop_Ntd : '') . '*' . (isset($Cop_Nns) ? $Cop_Nns : '') . '*' . (isset($Cop_Nna) ? $Cop_Nna : '') . '*' . $Vnd_Cod . '*' . $Cop_Sec . '*' . $Con_Cod . '*' . $Cop_Irb . '*' . $Cop_Cod, $obBD_conexionIns);
        $obBD_ins1->operacionobBD(11, $Tic_Cod . '*' . $Prv_Cod . '*' . $Ciu_Cod . '*' . trim($Cop_Num) . '*' . trim($Cop_Aut) . '*'
            . $Cop_Fec . '*' . $hoy . '*' . trim($Cop_Obs) . '*' . $Cop_Cad . '*' . $Cop_Imf . '*' . $Tri_Cod . '*' . $Cop_Des . '*'
            . $Pec_Cod . '*' . $Tpc_Cod . '*' . (isset($Cop_Ntd) ? $Cop_Ntd : '') . '*' . (isset($Cop_Nns) ? $Cop_Nns : '') . '*'
            . (isset($Cop_Nna) ? $Cop_Nna : '') . '*' . $Vnd_Cod . '*' . $Cop_Sec . '*' . $Con_Cod . '*' . $Cop_Irb . '*'
            . (!empty($Aut_Codliq) ? $Aut_Codliq : 'E') . '*'  . $t_iva_pres . '*'  .  $t_imp_combustible  . '*' . $t_prop . '*' . $t_adic . '*' . $Cop_Cod . '*', $obBD_conexionIns);


        //REGISTRAR NEGOCIACION
        if (isset($Cod_Neg) && !empty($Cod_Neg) && $Cod_Neg != 0) {
            $obBD_ins1->operacionobBD(1007, $Cod_Neg . '*' . $Cop_Cod . '*' . 'CMP' . '*' . $Cod_Nd,  $obBD_conexionIns);
        }
        //Anular negociacion
        if (!empty($Cod_Nd) && empty($Cod_Neg) && empty($Num_Neg)) {
            $obBD_ins1->operacionobBD(1010, $Cod_Nd . '*' . 'CMP',  $obBD_conexionIns);
        }

        /* Cabecera de la Retención */
        if ($Retencion) {
            $Ret_Fec = (!empty($Ret_Fec) ? $Ret_Fec : $Cop_Fec);

            if ($Ret_Num == 0) {
                $claveAcceso = "";
            } //Si el numero de la retencion es igual a 0 entonces no va a tener clave de acceso

            if ($rs_infoEmpresa['Ret_Scom'] == "S"  &&  $Ret_Asu == "S") {
                $Ret_Num = 0;
                $claveAcceso = "";
            }

            $obBD_ins1->operacionobBD(53, $Cop_Cod . '*' . $Ret_Num . '*' . $Ret_Fec . '*' . trim($Cop_Obs) . '*' . $tipo_compr . '*' . $Vnd_Cod . '*' . $Aut_Cod . '*' . (isset($claveAcceso) ? $claveAcceso : '') . '*' . (!empty($Ret_Asu) ? $Ret_Asu : 'N') . '*' . $Ret_Uca . '*' . $Ret_Pca . '*' . $Ret_Cod, $obBD_conexionIns);

            if (empty($Ret_Cod))
                $Ret_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
            else
                $obBD_ins1->operacionobBD(62, $Ret_Cod, $obBD_conexionIns); // Elimina el detalle retencion
            if ($isClaveAccesoExterna)
                $obBD_ins1->operacionobBD(80, $Ret_Cod . '*' . $claveAcceso, $obBD_conexionIns);
        } else {
            if (!empty($Ret_Cod)) $obBD_ins1->operacionobBD(63, $Ret_Cod, $obBD_conexionIns);
        } // Elimina toda la retencion xq no hay codigos

        /* Creacion del comprobante contable */
        if ($configs['Cof_Con'] == 'S') {
            $Com_Con = $Cop_Obs;
            $Iva_Costo = 0;
            $Tia_Asi = $obBD_con1->getRowConsulta(13, ($For_Cod != 2 ? 1 : 2), $obBD_conexion);
            $meseCom = explode('-', $Com_Fec);
            if (substr($Com_Fec, 0, 7) !== substr($row_cop_old['Com_Fec'], 0, 7)) {
                $Com_Num = $obBD_con1->getComNumPecAuto($Tia_Asi['Tia_Cod'], $Pec_Cod, $Com_Fec, $obBD_conexion); // Secuencia de comprobante por mes y por tipo
            } else $Com_Num = $row_cop_old['Com_Num'];
            $campo = 'Prv_Cod';

            /* Cabecera del Comprobante */
            $obBD_ins1->operacionobBD(14, $Pec_Cod . '*' . $Prv_Cod . '*' . $Com_Num . '*' . $Com_Fec . '*' . ("P/R. $Tic_Des $Cop_Num ") . trim($Com_Con) . '*' . $Tia_Asi['Tia_Cod'] . '*' . $t_rubros . '*' . trim($Cop_Obs) . '*' . $campo . '*' . $Com_Cod, $obBD_conexionIns);
            if (empty($Com_Cod)) {
                $Com_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
                $obBD_ins1->operacionobBD(15, $Com_Cod . '*' . $Cop_Cod, $obBD_conexionIns); // relacion compra comprobante
            } else {
                //Cambiar el codigo del Asiento a NULL
                $obBD_ins1->operacionobBD(1015, $Cop_Cod, $obBD_conexionIns);
                //Eliminar los asientos del comprobante
                $obBD_ins1->operacionobBD(41, $Com_Cod, $obBD_conexionIns);
            } // Elimina el asiento anterior

            //ELIMINA EL ABONO DE LA RETENCION ANTERIOR CON EL COMPROBANTE CREADO 
            if (!empty($Cpp_Cod)) {
                $obBD_ins1->operacionobBD(966, $Cpp_Cod, $obBD_conexionIns);
            }
            /* CCPP Cuentas por pagar */
            if ($For_Cod * 1 == 2) {
                $obBD_ins1->operacionobBD(55, $Com_Cod . '*' . $Cop_Cod . '*' . $Cpp_Ven . '*' . trim($Cpp_Obs) . '*' . $Cpp_Cod, $obBD_conexionIns);
                if (empty($Cpp_Cod)) $Cpp_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
            } else {
                if (!empty($Cpp_Cod)) $obBD_ins1->operacionobBD(64, $Com_Cod . '*' . $Cop_Cod . '*' . $Cpp_Cod, $obBD_conexionIns);
            }

            /* Inserta datos en el detalle del asiento (por items) */
            foreach ($items as &$item) {
                $addIva = round(($item['Iva_Cos'] == 'S' && $item['Iva_Por'] * 1 > 0 ? (($item['Cop_Imp'] - ($Cop_Des > 0 ? $item['Cop_Imp'] * $Cop_Des / 100 : 0)) * $item['Iva_Por'] / 100) : 0), 2);
                $Iva_Costo = $Iva_Costo + $addIva;
                if (empty($item['Pld_Cod'])) {
                    $cuenta = $obBD_con1->getRowConsulta(16, $Pec_Cop['Pla_Cod'] . '*' . $item['Pro_Cod'] . '*' . 'C', $obBD_conexion);
                    if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) throw new Exception('Revisar la parametrizacion contable del producto: <u>' . $item['Ite_Lar'] . '</u>!');
                    $item['Pld_Cod'] = $cuenta['Pld_Cod'];
                    $item['Pld_Des'] = $cuenta['Pld_Des'];
                }

                $item['Cop_Imp'] = ($item['Cop_Dec'] > 0) ? $item['Cop_Can'] * $item['Cop_Pru'] : $item['Cop_Imp']; //si es verdadero registrar valor completo sin descuento.
                $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . 'D' . '*' . ($item['Cop_Imp'] + $addIva) . '*' . (isset($item['Pld_Des']) ? $item['Pld_Des'] : '') . '*' . $item['Ite_Lar'] . '*' . $item['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // Item
                $Asi_Cod = $obBD_ins1->insercionid($obBD_conexionIns);
                // Guardar los códigos Asi_Cod en un array temporal para registrar luego en det_compra
                if (!isset($array_asi_cod)) $array_asi_cod = array();
                $array_asi_cod[] = array('Asi_Cod' => $Asi_Cod, 'Pro_Cod' => $item['Pro_Cod'],   'Cop_Cod' => $Cop_Cod, 'Pld_Cod' => $cuenta['Pld_Cod']);
            }
            unset($item);

            /* Inserta datos en el detalle del asiento (por codigo retención) */
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
                } //registrar los descuentos
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
                //ChromePhp::log("3x100 :" . $t_imp_combustible);
                $cuenta = $obBD_con1->getRowConsulta(28, $Pec_Cop['Pla_Cod'] . '*' . 'IG', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>Impuesto a conbustibles 3/1000 en Compras</u>!');
                $obBD_ins1->operacionobBD(17, $Com_Cod . '*' . ('D') . '*' . $t_imp_combustible . '*' . 'Impuesto a conbustibles 3/1000' . '*' . 'Impuesto a conbustibles 3/1000' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);  // inserta asiento // ICE
            }

            if ($t_iva_pres * 1 > 0) {
                $cuenta = $obBD_con1->getRowConsulta(28, $Pec_Cop['Pla_Cod'] . '*' . 'IPS', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))  throw new Exception('Revisar la parametrizacion contable de: <u>IVA presuntivo en Compras</u>!');
                //ChromePhp::log("Iva presuntivo :" . $t_iva_pres);
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
        $caja_pend = $obBD_con1->getRowConsulta(58, $Cop_Cod . '*' . 'P', $obBD_conexion);
        if ($For_Cod * 1 == 3) {
            //if ($caja_pend['total'] * 1 == 0) $obBD_ins1->operacionobBD(69, $Cop_Cod . '*' . '0' . '*' . 'P', $obBD_conexionIns);
            $existe_reposi = $obBD_con1->getRowConsulta('det_reposicion.selectWhere', array('Cop_Cod' => $Cop_Cod), $obBD_conexion);
            if (empty($existe_reposi))  $obBD_ins1->operacionobBD('det_reposicion.insert', array('Cop_Cod' => $Cop_Cod, 'Dre_Int' => 1, 'Com_Cod' => (!empty($Com_Cod) ? $Com_Cod : null), 'Rep_Cod' => '0', 'Dre_Tip' => 'P'), $obBD_conexionIns);
        } else {
            if ($caja_pend['total'] * 1 > 0) $obBD_ins1->operacionobBD(71, $Cop_Cod . '*' . '0' . '*' . 'P', $obBD_conexionIns);
        }

        /*/* para eliminar el kardex anterior */
        if ($Tic_Sri * 1 != 0 || (isset($configs['Cof_Stk']) && $configs['Cof_Stk'] == 'S')) {
            $row_kard_old = $obBD_con1->getArrayConsulta(43, $Cop_Cod, $obBD_conexion);
            $obBD_Stock =  new Class_Log_Datos_Factu;
            $obBD_conexionStock = new Class_Log_Conexion_Factu($Ses_Dat_Dis);
            $obBD_Stock->inicio_transaccion($obBD_conexionStock);
            foreach ($row_kard_old as $row) {
                $row['IoE'] = 'I';
                $row['Kar_Can'] = $row['Kar_Can'] * -1;
                $row['Kar_Prs'] = $row['Kar_Prs'] * 1;
                $row['Kar_Ims'] = $row['Kar_Ims'] * -1;
                $obBD_Stock->updateStockProd($Ses_Suc_Cod, $row, false, $obBD_conexion, $obBD_conexionStock); //revierte el stock
            }
            $obBD_Stock->fin_transaccion_nomsn($obBD_conexionStock);
            if ($obBD_Stock->Error != 0) throw new Exception('Error al limpiar los antiguos valores del <u>KARDEX</u>!');
            $obBD_ins1->operacionobBD(44, $Cop_Cod, $obBD_conexionIns); // limpia el kardex
        }
        /* Inserta datos en el detalle de la compra */
        $obBD_ins1->operacionobBD(42, $Cop_Cod, $obBD_conexionIns); // Elimina el detalle anterior
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
            // //ChromePhp::log();
            /* Control de Inventarios */
            if (($Tic_Sri * 1 != 0 || (isset($configs['Cof_Stk']) && $configs['Cof_Stk'] == 'S')) && ($item['Adq_Cor'] == 'B' || $item['Adq_Cor'] == 'SM')) {
                $s_add = true;
                //  $imp = ((1) * $item['Cop_Imp'] - ($Cop_Des > 0 ? $item['Cop_Imp'] * $Cop_Des / 100 : 0));
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
            if ($Retencion) {
                // $des_indivi = ($Cop_Des > 0 ? ($item['Cop_Imp'] * $Cop_Des) / 100 : 0);
                if ($t_pdescuento > 0) {
                    $des_indivi = ($item['Cop_Decv'] > 0 ? $item['Cop_Decv'] : 0);
                } else {
                    $des_indivi = ($Cop_Des > 0 ? ($item['Cop_Imp'] * $Cop_Des) / 100 : 0);
                }
                if (!empty($item['Ret_Ren_Cod']))
                    $obBD_ins1->operacionobBD(54, $Ret_Cod . '*' . ($item['Cop_Imp'] * 1 - $des_indivi) . '*' . $item['Ret_Ren_Cod'] . '*' . 'R' . '*' . $item['Cop_Int'] . '*' . $item['Adq_Cod'], $obBD_conexionIns);
                if (!empty($item['Iva_Ren_Cod']) && $item['Iva_Por'] * 1 > 0) {
                    $Imp = $item['Cop_Pru'] * $item['Cop_Can'];
                    // $Dec = ($item['Cop_Dec'] * 1 > 0 ? ($Imp * $item['Cop_Dec']) / 100 : 0);
                    $ImpDes = $Imp /*- $Dec*/ - $des_indivi;
                    $Ice = ($item['Cop_Ice'] * 1 > 0 ? ($ImpDes * $item['Cop_Ice']) / 100 : 0);
                    $obBD_ins1->operacionobBD(54, $Ret_Cod . '*' . ("" .  formato_numero(($ImpDes + $Ice) * ($item['Iva_Por'] / 100), 2, 1)) . '*' . $item['Iva_Ren_Cod'] . '*' . 'I' . '*' . $item['Cop_Int'] . '*' . $item['Adq_Cod'], $obBD_conexionIns);
                }
            }
        }
        /* registro de kardex y stocks */
        foreach ($array_kardex as $i => $k) {
            $k['Kar_Int'] = $i + 1;
            $obBD_ins1->updateStockProd($Ses_Suc_Cod, $k, true, $obBD_conexion, $obBD_conexionIns, $Bod_Cod);
        }
        if (isset($reembolsos))
            $obBD_ins1->operacionobBD('compra_reembolsos.deleteWhere', array('Cop_Cod' => $Cop_Cod), $obBD_conexionIns);
        if (isset($reembolsos) && is_array($reembolsos) && count($reembolsos) > 0) {
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
        $responce = array('success' => true, 'Cop_Cod' => $Cop_Cod, 'Cop_Sec' => $Cop_Sec, 'Com_Cod' => $Com_Cod, 'Ret_Cod' => $Ret_Cod, 'Tic_Des' => $Tic_Des, 'Mes' => mes($meseCop[1], 1) . "/$meseCop[0]");
        $reportes = $obBD_con1->reportes($_SERVER['PHP_SELF'], $Ses_Emp_Cod, $obBD_conexion);
        // detalle del documento
        if (!empty($Cop_Cod)) {
            $responce['Cop_Data'] = array(
                'Tic_Des' => $Tic_Des,
                'proveedor' => $proveedor,
                'Cop_Num' => $Cop_Num,
                'Cop_Fec' => $Cop_Fec,
                'Cop_Aut' => $Cop_Aut
            );
            $responce['Cop_Rows'] = $obBD_con1->getArrayConsulta(26, $Cop_Cod, $obBD_conexion);
            $responce['Cop_Link'] = "" .
                ($Tic_Sri * 1 == 3 && !empty($reportes[3]) ? "$reportes[3]?Cop_Cod=" : baseUrl("../../facturacion/FRONT/fac_pri_fac_detallecompras_1.0.php?com_codigo")) . "=$Cop_Cod";
        }
        // detalle del asiento contable
        if (!empty($Com_Cod)) {
            $responce['Com_Data'] = array(
                'Com_Con' => $Cop_Obs,
                'Com_Fec' => $Com_Fec,
                'Com_Val' => $t_rubros,
                'Tia_Des' => $Tia_Asi['Tia_Des'],
                'Codigo' => $Tia_Asi['Tia_Abr'] . '-' . $meseCom[1] . '-' . $Com_Num
            );
            $responce['Com_Rows'] = $obBD_con1->getArrayConsulta(27, $Com_Cod, $obBD_conexion);
            $responce['Com_Link'] = "" .
                (!empty($reportes[1]) ? $reportes[1] : baseUrl("../../contabilidad/FRONT/con_pri_compr_2.1.php")) . "?codigo=$Com_Cod&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Asi[Tia_Cod]&Pec_Cod=$Pec_Cop[Pec_Cod]";
        }

        // detalle de la retencion
        if (!empty($Com_Cod_Ret)) {
            $responce['Com_Data_Ret'] = array(
                'Codigo_Ret' => $Com_Cod_Ret,
                'Tia_Des_Ret' => $Tia_Asi_Ret['Tia_Des'],
                'Com_Con_Ret' => $Com_Con_Ret,
                'Com_Fec_Ret' => $Ret_Fec,
                'Com_Val_Ret' => $Ren_Tot
            );
            $responce['Com_Rows_Ret'] = $obBD_con1->getArrayConsulta(27, $Com_Cod_Ret, $obBD_conexion);
            $responce['Com_Link_Ret'] = "" .
                (!empty($reportes[1]) ? $reportes[1] : baseUrl("../../contabilidad/FRONT/con_pri_compr_2.1.php")) . "?codigo=$Com_Cod_Ret&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Asi_Ret[Tia_Cod]&Pec_Cod=$Pec_Cop[Pec_Cod]";
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
        if (!empty($Com_Cod_Ret)) {
            $responce['Com_Data_Ret'] = array(
                'Codigo_Ret' => $Com_Cod_Ret,
                'Tia_Des_Ret' => $Tia_Asi_Ret['Tia_Des'],
                'Com_Con_Ret' => $Com_Con_Ret,
                'Com_Fec_Ret' => $Ret_Fec,
                'Com_Val_Ret' => $Ren_Tot
            );
            $responce['Com_Rows_Ret'] = $obBD_con1->getArrayConsulta(27, $Com_Cod_Ret, $obBD_conexion);
            $responce['Com_Link_Ret'] = "" . (!empty($reportes[1]) ? $reportes[1] : baseUrl("../../contabilidad/FRONT/con_pri_compr_2.1.php")) . "?codigo=$Com_Cod_Ret&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Asi_Ret[Tia_Cod]&Pec_Cod=$Pec_Cop[Pec_Cod]";
        }


        // //ChromePhp::log("liquidacion:" .$Cop_Aut   . "retencion: ".$claveAcceso  );

        if ($Tic_Cod == 3  && !empty($Aut_Codliq)) { //Genera xml si es una liquidacion de compras
            // //ChromePhp::log("Aut_Cod editar:" .$Cop_Cod);
            require_once('../LOGICA/fac_log_electronica.php');
            $obBD_elect_liq =  new Class_Log_Datos_LiquidacionCompras_Elect();
            $Cop_Num_aux =  $Cop_Cod;
            $claveAccesoliq = $Cop_Aut;
            // $Aut_Codliq
            $responce['xml'] = $obBD_elect_liq->createXmlLiquidacionCompra($Cop_Num_aux, $Aut_Codliq, $claveAccesoliq, $obBD_conexion);
            $responce['Ret_Xmls'] = baseUrl("../FRONT/" . $Ses_Emp_Cod . '/' . $claveAccesoliq . '.xml');
        }


        if (!empty($Ret_Cod)  ||  ($Ret_Num != 0)) {  //Evita que se genere un xml si la retencion no tiene numero (El numero es igual a Ret_Num=0)
            // if (!empty($Ret_Cod)) {
            $responce['Ret_Cod'] = $Ret_Cod;
            $responce['Ret_Link'] = "" . (isset($reportes[2]) ? $reportes[2] : '') . "?Ret_Cod=$Ret_Cod";
            if (/*$configs['Cof_Gce']=='S'*/$Aut_Tem == 'E' && $Ret_Num !== 0 && !$isClaveAccesoExterna) {
                $rs_infoEmpresa = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
                $rs_infoCliente = $obBD_con1->getRowConsulta(61, $Aut_Cod, $obBD_conexion);
                //$responce['xml']=$obBD_con1->retencionElectronica($Ses_Emp_Cod, $Ses_Suc_Cod, $Prs_Cod, array_merge($rs_infoCliente, array('Ret_Cod'=>$Ret_Cod, 'Ret_Fec'=>$Ret_Fec, 'Ret_Num'=>str_pad($Ret_Num, 9, "0", STR_PAD_LEFT))), $obBD_conexion, $Ret_Xml);
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
    }
    //ChromePhp::log($obBD_ins1->MsgError);
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

/* Valida numero de retención */
if (isset($validaRetNum)) {
    $autoriz = $obBD_con1->getRowConsulta(48, $vendedor['Pun_Cod'] . '*' . $tipo_compr . '*' . $Aut_Cod_Old, $obBD_conexion); //Consulta las autorizaciones de las retenciones
    //$rs_infEmpFacElec = $obBD_con1->getRowConsulta(49, $Ses_Suc_Cod, $obBD_conexion);
    $electronica = ($autoriz['Aut_Tem'] == 'E'); //($rs_infEmpFacElec['Cof_Gce']=='S');
    $row_max_codig = $obBD_con1->getRowConsulta(51, $Ses_Suc_Cod . '*' . $autoriz['Aut_Sri'] . '*' . $autoriz['Aut_Ini'] . '*' . $autoriz['Aut_Fin'] . '*' . $autoriz['Tic_Cod'] . '*' . $autoriz['Pun_Sri'], $obBD_conexion); //Consulta el maximo numero de retenciones en base a la autorizacion
    $Ret_Id_Man = ($row_max_codig['next']);
    // //ChromePhp::log($Ret_Id_Man);
    if (empty($vendedor['Pun_Cod']) || empty($autoriz['Aut_Cod'])) $resp = array('success' => false, 'message' => "No tiene autorizacion para generar Retenciones!", 'Ret_Num_Old' => 0, 'Ret_Num' => '');
    else {
        $resp = array_merge(array('success' => true, 'Ret_Num' => $Ret_Id_Man, 'Ret_Num_Old' => $Ret_Num, 'Ret_Cod' => $Ret_Cod), $autoriz);
        if (!empty($Ret_Num)) {
            $num_existe_gencod = $obBD_con1->getRowConsulta(50, $Ses_Suc_Cod . '*' . $autoriz['Aut_Sri'] . '*' . $Ret_Num . '*' . $Ret_Cod . '*' . $autoriz['Pun_Sri'], $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI
            if ($num_existe_gencod['total'] * 1 > 0) {
                $resp['success'] = false;
                $resp['message'] = "La Retención Número $Ret_Num ya Existe en el Sistema!";
            }
        } else $resp['success'] = false;
        $resp['Aut_Sri'] = ($electronica ? 'Electronica' : $autoriz['Aut_Sri']);
    }

    $obBD_con1->echoJson($resp);
}

//edita la observacion de la compra y el comprobante relacionado
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

/* buscar cuentas contables */
if (isset($cuenAjax)) {
    if (!empty($Cop_Fec)) $Pec_Cop = $obBD_con1->getRowConsulta(9, $Ses_Emp_Cod . '*' . $Cop_Fec, $obBD_conexion);
    else $Pec_Cop = array('Pla_Cod' => '');
    $responce = $obBD_con1->getPageGridJson('det_plan.selectWhere', array_merge($_GET, array('where' => array('det_plan.Pla_Cod' => $Pec_Cop['Pla_Cod']), 'setWhere' => array('isActive', 'isDetalle'))), $obBD_conexion);
}
$rs_tip_compr = $obBD_con1->getArrayConsulta('tipo_compr.selectWhere', array('clean' => true, 'where' => array('Tic_Est' => 'A')), $obBD_conexion);
$rs_periodo = $obBD_con1->getArrayConsulta(33, $Ses_Emp_Cod, $obBD_conexion);

// BUSCAR NEGOCIACIONES
/*if ($rs_infoEmpresa["Cof_NegCam"] == 'S') {
    if (isset($negociacionesAjax)) {
        $data_negociaciones = $obBD_con1->getArrayConsulta(1006,  $Ses_Emp_Cod, $obBD_conexion);
        $obBD_con1->echoJson($data_negociaciones);
    }
} */
// BUSCAR NEGOCIACIONES
if ($rs_infoEmpresa["Cof_NegCam"] == 'S') {
    $grupo_empresas = $obBD_con1->getRowConsulta(1013, $Ses_Emp_Cod, $obBD_conexion); //Solo si tiene grupo de ecomar
    if (isset($negociacionesAjax)) {
        $Emp_Cod = $Ses_Emp_Cod;
        if (!empty($grupo_empresas["Emp_Cod"])) {
            $empresas = array_merge((array)$Emp_Cod, (array)$grupo_empresas["Emp_Cod"]);
            $Emp_Cod = implode(",", $empresas);
        }
        $data_negociaciones = $obBD_con1->getArrayConsulta(1006,  $Emp_Cod, $obBD_conexion);
        $obBD_con1->echoJson($data_negociaciones);
    }
}

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
<HTML lang="es">

<head>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Compras Modificar [EXA]"; ?></TITLE>
    <meta charset="utf-8">
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
    <script type="text/javascript">
        var gridFact, index, Cof_Con = '<?php echo $configs['Cof_Con']; ?>',
            cod_banano = <?php echo $cod_banano; ?>;
    </script>
    <script>
        <?php $array_documentos = $obBD_con1->getArrayConsulta(1003, $vendedor['Pun_Cod'], $obBD_conexion);  ?>
        var array_documentos = <?php echo json_encode($array_documentos);  ?>;
        var edit_doc = <?php echo json_encode('S'); ?>;
    </script>

    <script language="javascript" src="../../framework/plugins/validadorCedulaRucFinal.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/fac_val_factu.js?gh=998"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.5/jszip.min.js"></script>

</head>

<body>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title">&raquo; Modificar Documentos de Compras</h3>
        </div>
        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="documentoSearch">
                <?php include '../COMPONENTES/facComFormSearch.php'; ?>
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
                                var id = rowData.Ret_Cod;
                                if (id) {
                                    pdfUrls.push(id);
                                }
                            }
                            var zip = new JSZip();
                            var promises = pdfUrls.map(function(pdfUrl, index) {
                                return new Promise(function(resolve) {
                                    var xhr = new XMLHttpRequest();
                                    var link = currentDomain + '/facturacion/COMPONENTES/tesPdfElectronicos.php?type=RETENC&Doc_Cod=' + pdfUrl;
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

                    function editDocument(doc) {
                        var inputField = document.getElementById('Ret_Num');
                        if (doc['Ret_Num'] == 0) {
                            inputField.readOnly = true;
                        } else {
                            inputField.readOnly = false;
                        }
                        $('#c_tresxmil').prop('checked', false);
                        if (doc['Cop_iva_pres'] > 0) {
                            console.log("ingresado a marcar check 3x100" + doc['Cop_iva_pres']);
                            $('#c_tresxmil').prop('checked', true); //Activar check para impuesto 3x1000
                            $('#t_imp_combustible').val(doc['Cop_imp_comb']);
                            $('#t_iva_pres').val(doc['Cop_iva_pres']);
                        }
                        $('#Ret_Cod').val('');
                        $('#t_descuento').val(0);
                        $('.validate').find('i').removeAttr('class');
                        $('#provFormTemp').setData({
                            op_opciones: 'c',
                            Cal_Inv: 'N'
                        });
                        $("#Num_Neg").val(doc['Num_Neg']);
                        $("#Cod_Neg").val(doc['Cod_Neg']);
                        $("#Cod_Nd").val(doc['Cod_Nd']);

                        if (!$.varValid(doc['Ret_Cod']) || doc['Ret_Cod'] === '') {
                            doc['Ret_Num'] = '';
                            doc['Ret_Fec'] = doc['Cop_Fec'];
                            doc['Aut_Cod'] = '';
                        }
                        $('.formDatos').setData(doc, false);
                        $('.Cop_Fec').val(doc['Cop_Fec']);
                        // Establecer Ret_Fec con la fecha actual solo si no existe fecha de retención
                        if (!$.varValid(doc['Ret_Fec']) || doc['Ret_Fec'] === '' || doc['Ret_Fec'] === doc['Cop_Fec']) {
                            var hoy = new Date();
                            var mes = (hoy.getMonth() + 1);
                            var dia = hoy.getDate();
                            var fechaActual = hoy.getFullYear() + '-' + (mes < 10 ? '0' : '') + mes + '-' + (dia < 10 ? '0' : '') + dia;
                            $('#Ret_Fec').val(fechaActual);
                            $('#autorizaForm').setData({
                                Ret_Fec: fechaActual
                            });
                        } else {
                            $('#autorizaForm').setData({
                                Ret_Fec: doc['Ret_Fec']
                            });
                        }

                        $.Search('autoriza');
                        $('#Cop_Num').data('old_num', doc['Cop_Num']);
                        $('#Aut_Codliq').val(doc['aut_cod_sri']);
                        $('#Cop_Des').val(doc['Cop_Des']);
                        $('#Ret_Num').data({
                            Ret_Num: doc['Ret_Num'],
                            Ret_Num_Mod: doc['Ret_Num'],
                            Aut_Cod: doc['Aut_Cod'],
                            Aut_Sri: doc['Aut_Sri'],
                            Aut_Sri_Num: doc['Aut_Sri_Num'],
                            Pun_Sri: doc['Pun_Sri']
                        }).fieldValid();
                        $('#Ret_Fec').data({
                            Aut_Fci: doc['Aut_Fci'],
                            Aut_Cad: doc['Aut_Cad']
                        });
                        $("#btnClaveExterna").css('display', doc['Aut_Tem'] === "E" ? "" : "none");
                        var edit_pago = doc['Cpp_Edit'] !== 'N' && doc['Cpp_Det'] !== 'S';
                        (edit_pago ? $('#For_Cod').removeAttr('disabled') : $('#For_Cod').attr('disabled', 'disabled'));
                        $('#Pag_Pld').data('disabled', !edit_pago);
                        if (!$.varValid(doc['Ret_Cod']) || doc['Ret_Cod'] === '') validaRetNum();
                        $.getDataJson('', {
                            docDetalle: true,
                            Cop_Cod: doc['Cop_Cod'],
                            Com_Cod: doc['Com_Cod'],
                            Cop_Fec: doc['Cop_Fec'],
                            Ret_Cod: doc['Ret_Cod']
                        }, function(resp) {
                            console.log(resp);
                            checkFechaIva(resp['Cop_Fec'], resp['Iva_Cod'], resp['Pld_Cod']);
                            $('#documento').setRows(resp['items']).startGridEdit();
                            $.each(resp['items'], function(i, v) {
                                updateRowItem({
                                    rowId: v['index']
                                });
                            });
                            addItem({});
                            $('#t_descuento').val($.toFixed($("#t_subtotal").val() * 1 * ('0' + $('#Cop_Des').val()) / 100));
                            updateDocument();
                            $('#documentoSearch').moveComp('#documentoMain').updateGridsSizes();
                            if ($.vv(resp['reembolsos']) && $.isArray(resp['reembolsos'])) {
                                $.each(resp['reembolsos'], function(i, v) {
                                    resp['reembolsos'][i]['Total'] = (v['Rem_Niv'].toNum() + v['Rem_Siv'].toNum() + v['Rem_Oiv'].toNum() + v['Rem_Eiv'].toNum() + v['Rem_Iva'].toNum() + v['Rem_Ice'].toNum());
                                });
                                reembolsos.setRows(resp['reembolsos']);
                                $('#Rem_Fec').datepicker("option", "maxDate", $('#Cop_Fec').val());
                            }
                        });
                        $("#Bod_Cod").val(doc && doc['Bod_Cod'] ? doc['Bod_Cod'].Bod_Cod : null);
                        $('#Ciu_Cod').trigger('chosen:updated');
                        var credito = ($.varValid(doc['Cpp_Cod']) && doc['Cpp_Cod'] !== '');
                        $('#For_Cod').val(credito ? 2 : $.varValid(doc['Rcc_Pen']) ? 3 : 1);
                        $('.pagoCredito')[credito ? 'show' : 'hide']();
                        checkCuentaPago(doc['Pld_Cod_Pag']);
                        $('#proForm').formSubmit();
                        selectProvee(doc);
                        $('#Cop_Imf').datepicker("option", "maxDate", doc['Cop_Fec']);
                        $('#Cop_Cad').datepicker("option", "minDate", doc['Cop_Fec']);
                        $('#Ret_Fec').datepicker("option", "minDate", doc['Cop_Fec']);
                        $('#Cpp_Ven').datepicker("option", "minDate", doc['Cop_Fec']);
                        if (Cof_Con === 'S') $('#Com_Fec').datepicker("option", "minDate", doc['Cop_Fec']);
                        $('#Aut_Cod').html(doc['Aut_Cod'] || '');
                        if (!$.varValid(doc['Ret_Num']) || doc['Ret_Num'] === '') validaRetNum();
                        //Marcar Check box
                        var checkbox = document.getElementById('Ret_Asu');
                        if (doc.Ret_Asu === 'S') {
                            checkbox.checked = true;
                            checkbox.value = 'S';
                        } else { //Caso contrario lo desactiva
                            checkbox.checked = false;
                        }
                        var ch_prop = document.getElementById('ch_prop');
                        var tprop = document.getElementById('t_prop');
                        if (doc.Cop_Prop && doc.Cop_Prop != "0.00") {
                            ch_prop.checked = true;
                            $('#t_prop').val(doc.Cop_Prop);
                        } else { //Caso contrario lo desactiva
                            ch_prop.checked = false;
                        }
                        var ch_adic = document.getElementById('ch_adic');
                        var inputAdic = document.getElementById('t_adic');
                        if (doc.Cop_Adic && doc.Cop_Adic != "0.00") {
                            ch_adic.checked = true;
                            $('#t_adic').val(doc.Cop_Adic);
                            inputAdic.removeAttribute('readonly'); // Remove readonly attribute
                        } else { //Caso contrario lo desactiva
                            ch_adic.checked = false;
                            inputAdic.setAttribute('readonly', 'readonly'); // Add readonly attribute
                        }
                        tipoComprobanteHide(1, doc.Tic_Cod);
                    }
                </script>
            </div>
            <div id="documentoMain" style="visibility: hidden;">
                <?php include '../COMPONENTES/facComFormEdit.php'; ?>
                <div class="row">
                    <div class="col-xs-12">
                        <button class="btn btn-sm btn-inverse" onclick="$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Atrás</button>
                        <button class="btn btn-sm btn-primary" onclick="$('#formDocumento').formSubmit();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
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
                            <legend class="Titulos2">Resultado de la Transacci&oacute;n</legend>
                            <div>
                                <h4 style="text-align: center; font-weight: 900;">El Documento se guardo con &eacute;xito!</h4>
                                <p class="form-control-static resp" name="Tic_Des"></p>
                                <p class="resp"><span>&raquo;Mes:</span><span style="color:coral;" class="databind" name="Mes"></span></p>
                                <p class="resp"><span>&raquo;Sec:</span><span style="color:teal;" class="databind" name="Cop_Sec"></span></p>
                                <p class="resp"><span>&raquo;Cod:</span><span style="color: #CE0000;" class="databind" name="Cop_Cod"></span></p>
                                <div style="padding-top: 15px; text-align: center;">
                                    <!-- <button class="btn btn-sm btn-success" onclick="$('#searchGrid').trigger('reloadGrid',[]); clearDocument();$('#documentoResult').moveComp('#documentoSearch').updateGridsSizes();"><i class="glyphicon glyphicon-arrow-left"></i> Volver</button> -->
                                    <button class="btn btn-sm btn-success" onclick="$('#searchGrid').trigger('reloadGrid',[]); clearDocument();$('#documentoResult').moveComp('#documentoSearch').updateGridsSizes();">
                                        <i class="glyphicon glyphicon-search"></i> Buscar Documento
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
                                <label class="col-xs-3 control-label label-xs">N&uacute;mero:</label>
                                <div class="col-xs-4"><span name="Cop_Num" type="text" class="form-control input-xs "></span></div>
                                <label class="col-xs-2 control-label label-xs">Autorizaci&oacute;n:</label>
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
                                    <label class="col-xs-3 control-label label-xs">Observaci&oacute;n:</label>
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
                                    <label class="col-xs-3 control-label label-xs">Observaci&oacute;n:</label>
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
            $('#documentoMain').css('visibility', '').hide();
            $('#documentoResult').css('visibility', '').hide();
        });
    </script>

    <!--INICIO DEL DIALOGO BUSCAR PRODUCTO-->
    <div id="proDialog" title="B&uacute;squeda de Productos">
        <form class="form-horizontal normal"><input type="text" name="Cop_Fec" class="Cop_Fec" style="display: none;" /></form>
    </div>
    <!-- FIN DEL DIALOGO PRODUCTO-->
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR-->
    <div id="provDialog" title="B&uacute;squeda de Proveedor">
        <form class="form-horizontal normal"> </form>
    </div>
    <script>
        function selectProvee(provee) {
            //console.log("Seleccionar Proveedor: ", provee["Cop_Ide"]);           
            $('#provFormTemp').setData($.extend(provee, {
                op_opciones: 'c'
            })).find('.dialogSearch').addClass('x');
            $('#Prv_Con').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Con'] === 'S' ? 'ok green' : 'remove blue'));
            $('#Prv_Esp').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Esp'] === 'S' ? 'ok green' : 'remove blue'));
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

            if (provee.op_ide === '01') {
                $('#op_ide1').prop('checked', true).trigger('change');
                $('#op_ide1').val(1)
            }
            if (provee.op_ide === '02') {
                $('#op_ide2').prop('checked', true).trigger('change');
                $('#op_ide1').val(2)
            }
            if (provee.op_ide === '03') {
                $('#op_ide3').prop('checked', true).trigger('change');
                $('#op_ide1').val(3)
            }
            checkLiquidacion();
            tipoComprobanteHide(1);
            validaCopNum();
        }

        function clearDocument() {
            $('.formDatos').setData({
                op_opciones: 'c',
                Cal_Inv: 'N'
            });
            $('#docuFormTemp').setData({
                For_Cod: 1,
                Tri_Cod: 2,
                Cop_Fec: '<?php echo $hoy; ?>',
                Com_Fec: '<?php echo $hoy; ?>'
            }).find(':input').attr('readonly');
            // Establecer Ret_Fec con la fecha actual al limpiar el documento
            $('#Ret_Fec').val('<?php echo $hoy; ?>');
            $('#Cop_Fec').trigger('change');
            $('#Ciu_Cod').trigger('chosen:updated');
            $('.validate').find('i').removeAttr('class');
            gridFact.clearGrid();
            $('#asumirRet').prop('checked', false).hide();
            addItem({});
            // console.log("ESTA PRUEBA " + validaRetNum());
            validaRetNum();
            // Limpiar los campos contenidos en footerFact
            $('#c_tresxmil').prop('checked', false);
            $('#t_imp_combustible').val("");
            $('#t_iva_pres').val("");
            $('#ch_prop').prop('checked', false);
            $('#t_prop').val("");
            $('#ch_adic').prop('checked', false);
            $('#t_adic').val("");
        }
    </script>
    <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
    <div id="codiDialog" title="B&uacute;squeda de C&oacute;digos Retenci&oacute;n">
        <form class="form-horizontal normal"><input type="text" name="Cop_Fec" class="Cop_Fec" style="display: none;" />
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
        <form class="form-horizontal normal" id="provCreateForm" action="javascript:if(ValidacionCedulaRucService.esIdentificacionValida($('#Prs_Ced').val())['success']){ guardaProvee(); }else{ $('#Prs_Ced').flyout('show').focus() }">
            <input name="Prs_Cod" type="text" class="hidden" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos del Proveedor</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">C&oacute;dula/RUC:</label>
                    <div class="col-xs-5">
                        <div class="input-group input-group-xs">
                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs" onchange="if(ValidacionCedulaRucService.esIdentificacionValida(this.value)['success']&&ValidacionCedulaRucService.esIdentificacionValida(this.value)['tipo_abrev'] !== 'PA'){ $('#Ide_Cod').val(this.value.length===10?2:1); $('#Prv_Tic').val(ValidacionCedulaRucService.esIdentificacionValida(this.value)['tipo_abrev']==='NA'?'N':'J').trigger('change'); $(this).fieldValid(true); searchProvee(this.value); }else{ searchProvee(this.value); $('#Ide_Cod').val(3); $('#Prv_Tic').val('');};" required="" />
                            <span class="input-group-addon validate"><i></i></span>
                        </div>
                    </div>
                    <div class="col-xs-4">
                        <div class="checkbox check-big" style="position:absolute;">
                            <label><input type="checkbox" name="Prv_Esp" value="S" offval="N">Contrib. Especial</label>
                            <label><input type="checkbox" name="Prv_Ris" value="S" offval="N" disabled>RISE</label>
                            <label><input type="checkbox" name="Prv_Reg" value="S" offval="N" disabled>Reg. Micro.</label>
                            <label><input type="checkbox" name="Prv_Con" value="S" offval="N">Obligado Contab.</label>
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
                                //echo "<option value='$row[Ide_Cod]'>$row[Ide_Des]</option>";
                                echo "<option value='{$row['Ide_Cod']}'>" . mb_convert_encoding($row['Ide_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";
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
                    <label class="col-xs-3 control-label label-xs required">G&eacute;nero:</label>
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
            </fieldset>
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos de Ubicaci&oacute;n</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Ciudad:</label>
                    <div class="col-xs-4">
                        <select name="Ciu_Cod" class="form-control input-xs" required="">
                            <option value=""></option>
                            <?php foreach ($rs_ciudad as $row) {
                                //echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>";
                                echo "<option value='{$row['Ciu_Cod']}' data-prov='{$row['Pro_Nom']}'>" . mb_convert_encoding($row['Ciu_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";
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
    <div id="autorizaDialog" title="B&uacute;squeda de Autorizaciones">
        <form class="form-horizontal normal" id="autorizaForm"> <input type="text" name="Ret_Fec" class="hidden" /> </form>
    </div>
    <div id="changePagoDialog" title="Cambiar Forma de Pago" style="display:none;">
        <form class="form-horizontal normal" id="changePagoForm" action="javascript:saveChangePago();">
            <input type="text" name="Cop_Cod" class="hidden" />
            <input type="text" name="Cpp_Cod" class="hidden" />
            <input type="text" name="Com_Cod" class="hidden" />
            <input type="text" name="Pld_Cod_Pag" class="hidden" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Forma de Pago</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Tipo:</label>
                    <div class="col-xs-10"><span name="Tic_Des" class="form-control input-xs"></span></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs required">Sustento:</label>
                    <div class="col-xs-10">
                        <select name="Tri_Cod" class="form-control input-xs" tabindex="3" required="">
                            <option value="">Seleccione...</option>
                            <?php foreach ($rs_sustento as $row) {
                                echo "<option value='{$row['Tri_Cod']}' " . ($row['Tri_Cod'] == 2 ? 'selected' : '') . ">" . mb_convert_encoding($row['Tri_Sri'], 'UTF-8', 'ISO-8859-1') . " - " . mb_convert_encoding($row['Tri_Des'], 'UTF-8', 'ISO-8859-1') . "</option>";
                                // echo "<option value='{$row['Tri_Cod']}' " . ($row['Tri_Cod'] == 2 ? 'selected' : '') . ">$row[Tri_Sri] - $row[Tri_Des]</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Proveedor:</label>
                    <div class="col-xs-10"><span name="proveedor" class="form-control input-xs"></span></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Secuencia:</label>
                    <div class="col-xs-5"><span name="Cop_Num" class="form-control input-xs"></span></div>
                    <label class="col-xs-2 control-label label-xs">Fecha:</label>
                    <div class="col-xs-3"><span name="Cop_Fec" class="form-control input-xs"></span></div>
                </div>
                <?php $cen_cons = $obBD_con1->getArrayConsulta('consumo.selectWhere', array('clean' => true, 'where' => array('Emp_Cod' => $Ses_Emp_Cod, 'Con_Est' => 'A')), $obBD_conexion); ?>
                <div class="form-group" <?php if (count($cen_cons) == 0) echo 'style="display:none; "'; ?>>
                    <label class="col-xs-2 control-label label-xs">Consumo:</label>
                    <div class="col-xs-6">
                        <select name="Con_Cod" class="form-control input-xs">
                            <option value="" selected="">NINGUNO</option>
                            <?php if (count($cen_cons) > 0) foreach ($cen_cons as $row) {
                                echo "<option value='$row[Con_Cod]'>$row[Con_Des]</option>";
                            } ?>
                        </select>
                    </div>
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
                        <input id="Cpp_Ven2" name="Cpp_Ven" type="text" class="form-control input-xs datepickers" />
                    </div>
                    <label class="col-xs-2 control-label label-xs">Observaci&oacute;n:</label>
                    <div class="col-xs-5">
                        <textarea name="Cpp_Obs" class="form-control input-xs"></textarea>
                    </div>
                </div>
            </fieldset>
            <div class="center">
                <div clas="separator"></div>
                <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
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
                        <label class="col-sm-2 control-label">B&uacute;squeda:</label>
                        <div class="col-sm-10">
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
    <script>
        function validaRetFec() {
            var Ret_Fec = $('#Ret_Fec').val(),
                Aut_Cad = $('#Ret_Fec').data('Aut_Cad');
            if ($.varValid(Aut_Cad) && Aut_Cad.length > 0) {
                /*if (Ret_Fec > Aut_Cad) {
                    $('#Ret_Fec').createFlyout('No puede ser mayor a <u class="orange">' + Aut_Cad + '</u> !', {
                        icon: 'exclamation',
                        placement: 'right_bottom'
                    });
                    $('#Ret_Fec').val($('#Cop_Fec').val()).flyout('show');
                    Ret_Fec = Aut_Cad;
                }*/
            }
            $('#autorizaForm').setData({
                Ret_Fec: Ret_Fec
            });
            $.Search('autoriza');
        }

        function selectAut(auto) {
            var ret = $('#Ret_Num').data();
            //console.log(auto);            
            $('#reteFormTemp').setData($.extend(auto, {
                Ret_Num: ret.Pun_Sri == auto.Pun_Sri && ret.Aut_Sri_Num == auto.Aut_Sri ? $('#Ret_Num').val() : ''
            }), false);
            $('#Aut_Cod').html(auto['Aut_Cod']);
            validaRetNum(true);
            $('#autorizaDialog').dialog('close');
        }
        $.createSearchDialog('autorizaDialog', [{
                label: 'C&oacute;d.Aut.',
                name: 'Aut_Cod',
                key: true,
                width: 15,
                align: "center",
                hidden: true
            },
            {
                label: 'Pun./Imp.',
                name: 'Pun_Cod',
                hidden: true,
                width: 50
            },
            {
                label: 'Autoriza.',
                name: 'Aut_Sri',
                width: 70
            },
            {
                label: 'Items/Doc.',
                name: 'Aut_Ima',
                width: 50
            },
            {
                label: 'Inicio',
                name: 'Aut_Fci',
                width: 80
            },
            {
                label: 'Caduca',
                name: 'Aut_Cad',
                width: 80
            },
            {
                label: '#Desde',
                name: 'Aut_Ini',
                width: 40
            },
            {
                label: '#Hasta',
                name: 'Aut_Fin',
                width: 40
            },
            {
                label: 'Tipo/Doc.',
                name: 'Tic_Des',
                width: 60
            },
            {
                label: '&nbsp;',
                name: 'Aut_Estado',
                align: "center",
                formatter: 'truefalse',
                formatoptions: {
                    yesMsg: 'Activo',
                    noMsg: 'Inactivo'
                },
                width: 30
            },
            {
                label: '&nbsp;',
                name: 'act1',
                width: 30,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: selectAut,
                    conditional: function(o) {
                        return o['Cad'] === 'C';
                    },
                    caseFalse: function(o) {
                        return $('<i class="glyphicon glyphicon-lock red"/>').attr('title', 'No disponible para la fecha: <u class="orange">' + o['Ret_Fec'] + '</u>').prop('outerHTML');
                    }
                }
            }
        ], null, null, null, null, {
            title: 'B&uacute;squeda',
            options: []
        });
    </script>
    <!-- FIN DEL DIALOGO PROVEEDOR-->
    <?php include("../COMPONENTES/facComReembolsos.php"); ?>
    <div id="cuenDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script>
        $.clearValidate();
        //Solo se puede seleccionar RIMPE Emprendedor o Negocio popular
        function toggleCheckbox(otherCheckboxName) {
            const otherCheckbox = document.querySelector(`input[name="${otherCheckboxName}"]`);
            otherCheckbox.checked = false;
        }
        //Cargar negociaciones
        $('#negDialog').dialog({
            autoOpen: false
        });
        var containerNegoci = $("#containerNegoci");
        $(function() {
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
                footerrow: false,
            });
        }

        function selectNego(data) {
            $('#Num_Neg').val(data['Num_Neg']);
            $('#Cod_Neg').val(data['Cod_Neg']);
            $('#negDialog').dialog('close');
        }
    </script>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
    <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
    <script type="text/javascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />
</body>

</HTML>