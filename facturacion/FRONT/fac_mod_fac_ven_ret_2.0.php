<?php

/**
 * @abstract Permite realizar la modificacion de docuemntos de venta
 * @author Erick Cordova
 * @version 1.0
 * Fecha de creación  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_factura.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

ini_set('max_execution_time', 500);
set_time_limit(500);




$tipo_compr = 6; //Tipo de comprobante de la retencion 
$cod_banano = 338; //Codigo de Retencion del Banano

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_facturaVenta;

//$obBD_con1->debug(true);
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");


if (isset($getDateServ)) {
    $resp['hoy'] = date("Y-m-d");
    $obBD_con1->echoJson($resp);
}


if (isset($clieAjax)) {
    $obBD_con1->getPageGridJson(1, $Prs_Ced . '*' . $Ses_Emp_Cod . '*' . $op_opciones, $obBD_conexion, $page, $rows);
}

//Secci&oacute;n para obtener el n&uacute;mero de secuencia
if (isset($numeroSec)) {
    $response = $obBD_con1->getRowConsulta(9, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod . '*' . $Tic_Cod . '*' . $Aut_Cod, $obBD_conexion);
    if (isset($Aut_Sri))
        $response['Aut_Sri'] = $Aut_Sri;
    $siguiente = $obBD_con1->getRowConsulta(10, $response['Aut_Ini'] . '*' . $response['Aut_Fin'] . '*' . $response['Aut_Sri'] . '*' . $Tic_Cod . '*' . $Ses_Suc_Cod . '*' . $Pun_Sri, $obBD_conexion);
    $response['Vet_Num'] = $siguiente['siguiente'];
    $response['contador'] = $siguiente['contador'];
    echo json_encode($response);
    exit();
}


if (isset($existeNumdoc)) {
    if (!isset($Vet_Cod))
        $Vet_Cod = "";
    $rs_numdocumento = $obBD_con1->getRowConsulta(11, $Ses_Suc_Cod . '*' . $Aut_Sri . '*' . $Vet_Num . '*' . $Vet_Cod . '*' . $Pun_Sri, $obBD_conexion);
    if ($rs_numdocumento['total'] * 1 > 0) {
        $response['existe'] = true;
    } else {
        $response['existe'] = false;
    }
    echo json_encode($response);
    exit();
}

if (isset($buscarCuentas)) {
    if (!isset($Pag_Cod))
        $Pag_Cod = '';
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
    $responce['rows'] = $obBD_con1->getArrayConsulta(30, $Prs_Ced . '*' . $Ses_Emp_Cod, $obBD_conexion);
    $responce['total'] = count($responce['rows']);
    $obBD_con1->echoJson($responce);
}

/* Consulta datos del documento si existe */
if (isset($ajaxCopNum)) {
    $resp = array('success' => true);
    if (!empty($Tic_Cod) && !empty($Cop_Num)) {
        $row_rs_CodDoc = $obBD_con1->getRowConsulta(7, $Prv_Cod . '*' . $Tic_Cod . '*' . $Cop_Num . '*' . $Cop_Cod, $obBD_conexion);
        if ($row_rs_CodDoc['Cop_Cod'] != "")
            $resp = array('success' => false, 'message' => 'El documento ya Existe en el Sistema!');
    } else
        $resp['success'] = '';
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

$ivas = $obBD_con1->getArrayConsulta(16, "", $obBD_conexion);      //Secci&oacute;n para obtener los ivas de la tabla iva


/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(12, $Ses_Emp_Cod, $obBD_conexion);
$vendedor = $obBD_con1->getRowConsulta(85, $Ses_Suc_Cod . '*' . $Ses_Prs_Cod, $obBD_conexion);

/* Consulta del tipo de productos */
if (isset($proAjax)) {
    if (!empty($Caj_Fec))
        $Pec_Cop = $obBD_con1->getRowConsulta(78, $Ses_Emp_Cod . '*' . $Caj_Fec, $obBD_conexion);
    else
        $Pec_Cop = array('Pla_Cod' => null);
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
                if (!empty($cuenta['Pld_Cod']))
                    $r = array_merge($r, $cuenta);
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
                if (!empty($cuenta['Pld_Cod']))
                    $r = array_merge($r, $cuenta);
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
    $obBD_con1->echoLog($data);
    $responce = $obBD_con1->getPageGrid(34, $data, $obBD_conexion);
    if ($responce['total'] > 0) {
        foreach ($responce['rows'] as &$row) {
            $row['Cpc_Edit'] = 'S';
            $row['Cpc_Min'] = 0;
            //Verificar si la venta tiene manifiesto
            $tot = $obBD_con1->getRowConsulta(1822, $row['Vet_Cod'], $obBD_conexion);
            $row['Tot_Man'] = $tot['total'];

            if (!empty($row['Cpc_Cod'])) {
                $Pagos1 = $obBD_con1->getRowConsulta(57, $row['Cpc_Cod'] . '*' . 'A', $obBD_conexion);
                if ($Pagos1['total'] * 1 > 0) {
                    $row['Cpc_Det'] = 'S'; //tiene pagos activos
                    $Pagos1 = $obBD_con1->getRowConsulta(57, $row['Cpc_Cod'] . '*' . 'A' . '*' . 'SUM', $obBD_conexion);
                    $row['Cpc_Min'] = round($Pagos1['total'] * 1, 2);
                }
                $Pagos2 = $obBD_con1->getRowConsulta(57, $row['Cpc_Cod'], $obBD_conexion);
                if ($Pagos2['total'] * 1 > 0)
                    $row['Cpc_Edit'] = 'N'; //tiene algun pago vinculado
            }
            if ($configs['Cof_Con'] == 'S' && !empty($row['Com_Cod'])) {
                $cuentas = $obBD_con1->getRowConsulta(39, $row['Com_Cod'], $obBD_conexion);
                $row['Pld_Cod_Pag'] = $cuentas['Pld_Cod'];
                $otras_comp = $obBD_con1->getRowConsulta(65, $row['Com_Cod'], $obBD_conexion);
                if ($otras_comp['total'] * 1 > 1)
                    $row['Com_Edit'] = 'N';
            }
        }
        unset($row);
    }
    $obBD_con1->echoLog($responce);
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
    $responce['ivas'] = $obBD_con1->getArrayConsulta(18, '', $obBD_conexion);
    //    else
    $responce['iva_activo'] = $obBD_con1->getRowConsulta(19, $Cop_Fec, $obBD_conexion);
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

/* Valida numero de retenci&oacute;n */
if (isset($validaRetNum)) {
    $autoriz = $obBD_con1->getRowConsulta(48, $vendedor['Pun_Cod'] . '*' . $tipo_compr, $obBD_conexion); //Consulta las autorizaciones de las retenciones
    $electronica = ($autoriz['Aut_Tem'] == 'E'); //($rs_infEmpFacElec['Cof_Gce']=='S');
    $row_max_codig = $obBD_con1->getRowConsulta(51, $Ses_Suc_Cod . '*' . $autoriz['Aut_Sri'] . '*' . $autoriz['Aut_Ini'] . '*' . $autoriz['Aut_Fin'] . '*' . $autoriz['Tic_Cod'], $obBD_conexion); //Consulta el maximo numero de retenciones en base a la autorizacion    
    $Ret_Id_Man = ($row_max_codig['next']);
    if (empty($vendedor['Pun_Cod']) || empty($autoriz['Aut_Cod']))
        $resp = array('success' => false, 'message' => "No tiene autorizaci&oacute;n para generar retenciones!", 'Ret_Num_Old' => 0, 'Ret_Num' => '');
    else {
        $resp = array_merge(array('success' => true, 'Ret_Num' => $Ret_Id_Man, 'Ret_Num_Old' => $Ret_Num, 'Ret_Cod' => $Ret_Cod), $autoriz);
        if (!empty($Ret_Num)) {
            $num_existe_gencod = $obBD_con1->getRowConsulta(50, $Ses_Suc_Cod . '*' . $autoriz['Aut_Sri'] . '*' . $Ret_Num . '*' . $Ret_Cod, $obBD_conexion); //Consulto si ya existe un codigo generado en las retenciones basado en una autorizacion otorgada por el SRI 
            if ($num_existe_gencod['total'] * 1 > 0) {
                $resp['success'] = false;
                $resp['message'] = "La Retención Número $Ret_Num ya Existe en el Sistema!";
            }
        } else
            $resp['success'] = false;
        $resp['Aut_Sri'] = ($electronica ? 'Electronica' : $autoriz['Aut_Sri']);
    }
    $obBD_con1->echoJson($resp);
}

if (isset($autorizaAjax)) {
    $obBD_con1->getPageGridJson(100, $rs_Punto['Pun_Cod'] . '*' . $Tic_Cod, $obBD_conexion, $page, $rows);
}

if (isset($cargarDocumentos)) {
    if ($Aut_Cod == '')
        $Aut_Cod = 0;
    if ($Tic_Cod == '')
        $Tic_Cod = 0;
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
    //Cantidad de manifiesto si existen
    $responce['Bod_Cod'] = $obBD_con1->getRowConsulta(154, $vet_cod, $obBD_conexion);
    $manifiesto_cargar = $obBD_con1->getRowConsulta(187, $vet_cod, $obBD_conexion);
    if (!empty($manifiesto_cargar['Pla_Cod'])) {
        $ant_exist = $obBD_con1->getRowConsulta(190, $vet_cod, $obBD_conexion);
        if (!empty($ant_exist['Com_Cod'])) {
            $responce['Com_Cod_Ant'] = $ant_exist['Com_Cod'];
            $responce['Ama_Cod_Ant'] = $ant_exist['Ama_Cod'];
            $responce['Ant_Cod_Ant'] = $ant_exist['Ant_Cod'];
            $responce['Com_Num_Ant'] = isset($ant_exist['Com_Num']) ? $ant_exist['Com_Num'] : '';
        }
    }
    $responce['items'] = $obBD_con1->getArrayConsulta(93, $vet_cod, $obBD_conexion);
    foreach ($responce['items'] as $r)
        if ($r['Iva_Por'] * 1 > 0) {
            $responce['Iva_Por'] = $r['Iva_Por'];
            break;
        }
    $responce['pagos'] = $obBD_con1->getArrayConsulta(92, $vet_cod, $obBD_conexion);
    if ($Aut_Cod == '')
        $Aut_Cod = 0;
    if ($Tic_Cod == '')
        $Tic_Cod = 0;
    $array_documentos = $obBD_con1->getArrayConsulta(8, $rs_Punto['Pun_Cod'] . '*' . $Aut_Cod, $obBD_conexion);
    if ($Tic_Cod > 0) {
        $array_doc = $obBD_con1->getArrayConsulta(101, $rs_Punto['Pun_Cod'] . '*' . $Aut_Cod . '*' . $Tic_Cod, $obBD_conexion);
        $array_documentos = array_merge($array_documentos, $array_doc);
    }
    $responce['documentos'] = $array_documentos;
    //$obBD_con1->echoLog($responce['documentos']);
    $responce['success'] = true;
    $obBD_con1->echoJson($responce);
}






/* Secci�n para realizar el guardado */
/* Comprueba si se envió la solicitud de guardado desde el formulario (POST) */

if (isset($saveDocument)) {
    /* Creación del objeto de conexión a BD para inserciones/actualizaciones de venta */
    $obBD_conexionIns = new Class_Log_Conexion_facturaVenta($Ses_Dat_Dis);
    /* Creación del objeto que ejecuta las operaciones de datos (SQL) de factura venta */
    $obBD_conIns = new Class_Log_Datos_facturaVenta;
    $obBD_con1->validaCierrePeriodo('ventas', 'Caj_Fec', 'Vet_Cod', $Caj_Fec, $editDoc['Vet_Cod'], $obBD_conexion, 'S');
    /* Inicia transacción en BD para poder hacer rollback si algo falla */
    $obBD_conIns->inicio_transaccion($obBD_conexionIns->conexion);
    /* Verifica que el usuario tenga código de vendedor asignado */
    if (empty($vendedor['Vnd_Cod'])) { /* Asigna mensaje de error si no tiene permisos de vendedor */
        $responce['message'] = "No tiene permisos de Vendedor!";
    }
    /* Toma el código de vendedor del usuario logueado */
    $Vnd_Cod = $vendedor['Vnd_Cod'];
    /* Toma el código del documento de venta que se está editando */
    $Vet_Cod = $editDoc['Vet_Cod'];
    if (is_string($items))
        $items = json_decode(stripslashes($items), true);
    /* Bloque try para capturar excepciones y hacer rollback si falla algo */
    try {
        /* Inicializa código de caja vacío (no se usa caja en este flujo) */
        $Caj_Cod = '';
        /* Consulta si ya existe una retención con misma sucursal, autorización SRI, número y punto */
        $num_existe_gencod = $obBD_con1->getRowConsulta(50, $Ses_Suc_Cod . '*' . $Aut_Sri . '*' . $Vet_Num . '*' . $Vet_Cod . '*' . $Pun_Sri, $obBD_conexion);
        /* Si el número ya existe y es distinto al anterior, marca error de duplicado */
        if ($num_existe_gencod['total'] * 1 > 0 && $Vet_Num != $Vet_Num_Ant) {
            $responce['message'] = "El doc. $Tic_Des No. $Vet_Num ya existe!";
        }
        /* Indica si es tipo nota de venta (RISE): tipo SRI 2 o 9 */
        $rise = ($Tic_Sri * 1 == 2 || $Tic_Sri * 1 == 9);
        /* Para RISE obtiene el registro de IVA con tarifa 0 */
        if ($rise)
            $iva_cero = $obBD_con1->getRowConsulta(68, '0', $obBD_conexion);
        /* Si hubo error en validaciones anteriores, devuelve JSON y termina */
        if (!empty($responce['message'])) {
            echo json_encode($responce);
            exit();
        }

         /* BLOQUEO (solo si la venta está ligada a un manifiesto):
         * si el anticipo de esta retención ya está usado/consumido, no permitir crear otra retención */
        $venta_tiene_manifiesto = false;
        if (!empty($Vet_Cod) && ($Vet_Cod * 1) > 0) {
            $manifiesto_chk = $obBD_con1->getRowConsulta(1844, $Vet_Cod, $obBD_conexion);
            $venta_tiene_manifiesto = (!empty($manifiesto_chk) && !empty($manifiesto_chk['Man_Cod']));
        }
        if ($venta_tiene_manifiesto) {
            $row_ant_ocupado = $obBD_conIns->getRowConsulta(1845, $Vet_Cod, $obBD_conexionIns);
            if (!empty($row_ant_ocupado) && !empty($row_ant_ocupado['Ant_Cod'])) {
                $responce['message'] = "El anticipo " . $row_ant_ocupado['Ant_Cod'] . " que se generó con el valor de esta retención ya está ocupado, Para mas información comuníquese con el soporte.";
                echo json_encode($responce);
                exit();
           
            }
        }

        /* ANULAR TODO PRIMERO: antes de cualquier registro, anular todo lo relacionado con esta retención (anticipo, pagos con ese anticipo, manifiesto_anticipo). Después se volverá a registrar. */
        $acabamos_de_anular_anticipos = false;
        if ($configs['Cof_Con'] == 'S') {
            $anticipos_venta = $obBD_con1->getArrayConsulta(264, $Vet_Cod, $obBD_conexion);
            if (!empty($anticipos_venta)) {
                $acabamos_de_anular_anticipos = true;
                /* Anular comprobantes de cobro (pagos) que usaron el anticipo de esta retención */
                $comprobantes_pago_anticipo = $obBD_con1->getArrayConsulta(266, $Vet_Cod, $obBD_conexion);
                if (!empty($comprobantes_pago_anticipo)) {
                    foreach ($comprobantes_pago_anticipo as $row_pago) {
                        $Com_Cod_Pago_Ant = isset($row_pago['Com_Cod']) ? $row_pago['Com_Cod'] : 0;
                        if ($Com_Cod_Pago_Ant > 0) {
                            $obBD_conIns->operacionobBD(261, $Com_Cod_Pago_Ant, $obBD_conexionIns);
                            $obBD_conIns->operacionobBD(262, $Com_Cod_Pago_Ant, $obBD_conexionIns);
                        }
                    }
                }
                /* Anular cada anticipo, su comprobante y manifiesto_anticipo si tenía */
                foreach ($anticipos_venta as $row_ant) {
                    $Com_Cod_Ant = isset($row_ant['Com_Cod']) ? $row_ant['Com_Cod'] : 0;
                    $Ant_Cod = isset($row_ant['Ant_Cod']) ? $row_ant['Ant_Cod'] : 0;
                    $Ama_Cod_Ant = isset($row_ant['Ama_Cod']) ? $row_ant['Ama_Cod'] : 0;
                    if ($Com_Cod_Ant > 0) {
                        $obBD_conIns->operacionobBD(261, $Com_Cod_Ant, $obBD_conexionIns);
                    }
                    if ($Ant_Cod > 0) {
                        $obBD_conIns->operacionobBD(263, $Ant_Cod, $obBD_conexionIns);
                    }
                    if (!empty($Ama_Cod_Ant) && ($Ama_Cod_Ant * 1) > 0) {
                        $obBD_conIns->operacionobBD(268, $Ama_Cod_Ant, $obBD_conexionIns);
                    }
                }
                /* Limpiar referencias para que al registrar más abajo se cree nuevo anticipo/comprobante/manifiesto */
                $editDoc['Com_Cod_Ant'] = '';
                $editDoc['Ant_Cod_Ant'] = '';
                $editDoc['Ama_Cod_Ant'] = '';
                $editDoc['Com_Num_Ant'] = '';
            }

             /* Compatibilidad con registros antiguos:
             * Si existe pago activo por retención directa (Pag_Cod=50) en det_ccpp_c,
             * anular ese pago y su comprobante antes de reconstruir la retención con anticipos. */
            if (!empty($editDoc['Cpc_Cod']) && ($editDoc['Cpc_Cod'] * 1) > 0) {
                $pagos_ret_directos = $obBD_con1->getArrayConsulta(271, array('Cpc_Cod' => $editDoc['Cpc_Cod']), $obBD_conexion);
                if (!empty($pagos_ret_directos)) {
                    foreach ($pagos_ret_directos as $row_ret_pago) {
                        $Com_Cod_Ret_Pago = isset($row_ret_pago['Com_Cod']) ? (int)$row_ret_pago['Com_Cod'] : 0;
                        if ($Com_Cod_Ret_Pago > 0) {
                            $obBD_conIns->operacionobBD(262, array($Com_Cod_Ret_Pago), $obBD_conexionIns); // Cpc_Est='I'
                            $obBD_conIns->operacionobBD(261, array($Com_Cod_Ret_Pago), $obBD_conexionIns); // Com_Est='I'
                        }
                    }
                }
            }


        }

        /* Asigna fecha de retención: si hay retenciones y no hay fecha, usa fecha de caja */
        if (isset($rets)) {
            if (empty($Ret_Fec) && count($rets) > 0) {
                $Ret_Fec = $Caj_Fec;
            }
        } else {  /* Si no hay retenciones, fecha de retención queda NULL */
            $Ret_Fec = NULL;
        }
        /* Asegura que claveAcceso exista (por defecto vacío) */
        if (!isset($claveAcceso)) {
            $claveAcceso = '';
        }
        /* Asegura que Vet_Aut (autorización electrónica) exista */
        if (!isset($Vet_Aut)) {
            $Vet_Aut = '';
        }
        /* Actualiza/inserta cabecera de la factura de venta (op 102): tipo, cliente, ciudad, caja, vendedor, número, obs, autorización, etc. */
        $obBD_conIns->operacionobBD(102, $Tic_Cod . '*' . $Cli_Cod . '*' . $Ciu_Cod . '*' . $Caj_Cod . '*' . $rs_Punto['Vnd_Cod'] . '*' .
            $Vet_Num . '*' . $Vet_Obs . '*' . $Aut_Cod . '*' . $Vet_Des . '*' . $hora . '*' . $claveAcceso . '*' . $Vet_Aut . '*' . $Ret_Num . '*' . $Ret_Fec . '*' . $Ret_Aut_Sri . '*' . $Tpc_Cod . '*' . $Vet_Cod, $obBD_conexionIns);
        /* Elimina todos los ítems actuales del documento (op 97) antes de insertar los nuevos */
        $obBD_conIns->operacionobBD(97, $Vet_Cod, $obBD_conexionIns);
        /* Array auxiliar para productos (uso interno) */
        $cod_pro_unique = array();
        /* Estructura base del kardex: tipo E(salida), fecha, hora, documento, vendedor */
        $kardex = array('IoE' => 'E', 'Kar_Fec' => $hoy, 'Kar_Hor' => date("H:i:s"), 'Vet_Cod' => $Vet_Cod, 'Vnd_Cod' => $Vnd_Cod);
        /* Array donde se acumulan los movimientos de kardex por producto */
        $array_kardex = array();
        /* Bandera: true si el producto debe agregarse como nuevo en kardex */
        $s_add = true;
        /* Recorre cada ítem del documento para insertarlo y armar kardex */
        foreach ($items as $i => $item) {
            /* Asigna código de venta al ítem */
            $item['Vet_Cod'] = $Vet_Cod;
            /* Asigna número de línea del ítem (1, 2, 3...) */
            $item['Vet_Ite'] = $i + 1;
            /* Para nota de venta (RISE) fuerza IVA 0 en el ítem */
            if ($rise)
                $item['Iva_Cod'] = $iva_cero['Iva_Cod'];
            /* Si el ítem tiene retención (Ret_Mod > 0), busca o crea el código de retención en el plan */
            if ($item['Ret_Mod'] * 1 > 0) {
                /* Busca si ya existe la retención en el plan por código SRI y porcentaje */
                $referencia = $obBD_con1->getRowConsulta(122, array('Pla_Cod' => $Plan_Cod, 'Ren_Sri' => $item['Ret_Ren_Sri'], 'Ren_Por' => $item['Ret_Ren_Por']), $obBD_conexion);
                if (count($referencia) > 0) {
                    /* Usa el Ren_Cod existente */
                    $item['Ret_Ren_Cod'] = $referencia['Ren_Cod'];
                } else {
                    /* Busca por Ren_Sri y arma registro para insertar nueva retención en plan */
                    $referencia = $obBD_con1->getRowConsulta(122, array('Pla_Cod' => $Plan_Cod, 'Ren_Sri' => $item['Ret_Ren_Sri']), $obBD_conexion);
                    $referencia['Ren_Por'] = $item['Ret_Ren_Por'];
                    $referencia['Ren_Est'] = 'I';
                    /* Inserta nueva retención en plan (op 123) */
                    $obBD_conIns->operacionobBD(123, $referencia, $obBD_conexionIns);
                    /* Toma el ID del registro insertado */
                    $item['Ret_Ren_Cod'] = $obBD_conIns->insercionid($obBD_conexionIns);
                }
            }
            /* Inserta el ítem del documento en BD (op 86) */
            $obBD_conIns->operacionobBD(86, $item, $obBD_conexionIns);
            /* Control de inventario: agrupa por producto para kardex */
            $s_add = true;
            /* Revisa si el producto ya está en array_kardex para sumar cantidad/importe */
            foreach ($array_kardex as &$k) {
                if ($k['Pro_Cod'] == $item['Pro_Cod']) {
                    $s_add = false;
                    /* Acumula cantidad vendida */
                    $k['Kar_Sal'] += (1) * $item['Vet_Can'];
                    /* Acumula importe */
                    $k['Kar_Ime'] += (1) * $item['Vet_Imp'];
                    /* Recalcula precio promedio */
                    $k['Kar_Pre'] = $k['Kar_Ime'] / $k['Kar_Sal'];
                    break;
                }
            }
            unset($k);
            /* Si el producto no estaba en kardex, agrega un nuevo registro */
            if ($s_add == true) {
                /* Arma registro de kardex: línea, IVA, producto, cantidad, precio, importe */
                $kardexIE = array_merge($kardex, array(
                    'Kar_Int' => $i + 1,
                    'Iva_Cod' => $item['Iva_Cod'],
                    'Pro_Cod' => $item['Pro_Cod'],
                    'Kar_Sal' => (1) * $item['Vet_Can'],
                    'Kar_Pre' => $item['Vet_Pru'] * 1,
                    'Kar_Ime' => (1) * $item['Vet_Imp'],
                ));
                array_push($array_kardex, $kardexIE);
            }
        }
        /* Borra todos los pagos actuales del documento (op 95) antes de insertar los nuevos */
        $obBD_conIns->operacionobBD(95, $Vet_Cod, $obBD_conexionIns);
        /* Registra cada forma de pago del documento */
        foreach ($pagos as $i => &$pag) {
            /* Asigna número de documento al pago */
            $pag['Vet_Num'] = $Vet_Num;
            /* Asigna código de venta al pago */
            $pag['Vet_Cod'] = $Vet_Cod;
            /* Inserta el pago de venta en BD (op 72) */
            $obBD_conIns->operacionobBD(72, $pag, $obBD_conexionIns);
        }
        unset($pag);
        $manifiesto = array(); /* Se rellena dentro del bloque de contabilidad si aplica; con manifiesto solo se crea comprobante de anticipo */
        /* Solo crea comprobante contable si la empresa tiene contabilidad (Cof_Con=S) y es comprobante electrónico */
        if ($configs['Cof_Con'] == 'S' && $Tic_Sri * 1 != 0) {
            /* Concepto del comprobante: REG. VENTA + número */
            $Com_Con = 'REG. VENTA ' . $Vet_Num;
            /* Fecha del comprobante = fecha de caja */
            $Com_Fec = $Caj_Fec;
            /* Obtiene tipo de asiento contable (consulta 80, tipo 7 = ventas) */
            $Tia_Asi = $obBD_con1->getRowConsulta(80, 7, $obBD_conexion);
            /* Separa año-mes-día de la fecha para obtener el mes */
            $meseCom = explode('-', $Com_Fec);
            /* Consulta si la venta tiene manifiesto: con manifiesto solo se crea comprobante de anticipo, no el de retención */
            $manifiesto = $obBD_con1->getRowConsulta(1844, $Vet_Cod, $obBD_conexion);
            /* Reglas para NO afectar el comprobante de la venta en edición:
             * - Si hay pago a crédito (Forma_Cod=2) y ya existe comprobante, no se re-genera el asiento.
             * - Si es al contado con retenciones y ya existe comprobante, tampoco se re-genera (la retención va a anticipo).
             * En registro nuevo (sin Com_Cod) sí se crea el comprobante para que exista asiento. */
            $tiene_credito = false;
            foreach ($pagos as $p) {
                if (isset($p['Forma_Cod']) && ($p['Forma_Cod'] * 1) == 2) {
                    $tiene_credito = true;
                    break;
                }
            }
            $solo_contado = !$tiene_credito;
            $existe_comprobante_venta = (!empty($editDoc['Com_Cod']) && ($editDoc['Com_Cod'] * 1) > 0);
            $no_editar_asiento_venta = (
                $existe_comprobante_venta
                && (
                    $tiene_credito
                    || ($solo_contado && isset($rets) && count($rets) > 0 && ($Ren_Tot * 1) > 0)
                )
            );
            if (!$no_editar_asiento_venta) {
            /* Obtiene siguiente número de comprobante por mes y tipo (secuencia automática) */
            $Com_Num = $obBD_con1->codigoComprAuto($Tia_Asi['Tia_Cod'], $Pec_Cod, $meseCom[1], $obBD_conexion);
            /* Ajusta al número actual del comprobante (antes del incremento) */
            $Com_Num = $Com_Num - 1;
            /* Campo de referencia: cliente */
            $campo = 'Cli_Cod';
            /* Inserta/actualiza cabecera del comprobante contable (op 70) */
            $obBD_conIns->operacionobBD(70, $Pec_Cod . '*' . $Cli_Cod . '*' . $Com_Num . '*' . $Com_Fec . '*' . trim($Com_Con) . '*' . $Tia_Asi['Tia_Cod'] . '*' . $t_rubros . '*' . trim($Vet_Obs) . '*' . $campo . '*' . $editDoc['Com_Cod'], $obBD_conexionIns);
            /* Toma código de comprobante del documento editado */
            $Com_Cod = $editDoc['Com_Cod'];
            /* Si no existía comprobante, toma el ID insertado y crea relación venta-comprobante */
            if (empty($Com_Cod) || ($Com_Cod * 1) <= 0) {
                $Com_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                /* Crea relación entre venta y comprobante (op 83) */
                $obBD_conIns->operacionobBD(83, $Com_Cod . '*' . $Vet_Cod, $obBD_conexionIns);
            } else
                /* Si ya existía comprobante, elimina el asiento anterior para reemplazarlo (op 41) */
                $obBD_conIns->operacionobBD(41, $Com_Cod, $obBD_conexionIns);
            /* Por cada ítem: obtiene cuenta contable del producto e inserta línea HABER del asiento */
            foreach ($items as &$item) {
                /* Obtiene cuenta contable del producto para ventas (consulta 84) */
                $cuenta = $obBD_con1->getRowConsulta(84, $Plan_Cod . '*' . $item['Pro_Cod'] . '*' . 'V', $obBD_conexion);
                /* Valida que el producto tenga cuenta contable parametrizada */
                if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod']))
                    throw new Exception('Revisar la parametrizacion contable del producto: <u>' . $item['Ite_Lar'] . '</u>!');
                $item['Pld_Cod'] = $cuenta['Pld_Cod'];
                /* Inserta línea del asiento: HABER por importe del ítem (op 87) */
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . 'H' . '*' . ($item['Vet_Imp']) . '*' . $cuenta['Pld_Des'] . '*' . $item['Ite_Lar'] . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);
            }
            /* Si hay IVA total, inserta línea HABER por IVA cobrado */
            if ($t_iva * 1 > 0) {
                /* Obtiene cuenta de IVA cobrado (consulta 88) */
                $cuenta = $obBD_con1->getRowConsulta(88, $Plan_Cod, $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod']))
                    throw new Exception('Revisar la parametrizaci&oacute;n contable de: <u>Iva Cobrado</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('H') . '*' . $t_iva . '*' . 'IVA' . '*' . 'IVA' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);
            }
            /* Si hay descuento total, inserta línea DEBE por descuentos en ventas */
            if ($t_descuento > 0) {
                /* Obtiene cuenta de descuentos en ventas (DV) */
                $cuenta = $obBD_con1->getRowConsulta(28, $Plan_Cod . '*' . 'DV', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod']))
                    throw new Exception('Revisar la parametrizaci&oacute;n contable de: <u>Descuentos en Ventas</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('D') . '*' . $t_descuento . '*' . 'DESCUENTO' . '*' . 'DESCUENTO' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);
            }
            /* Si hay ICE total, inserta línea HABER por ICE en ventas */
            if ($t_ice * 1 > 0) {
                /* Obtiene cuenta ICE ventas (ICV) */
                $cuenta = $obBD_con1->getRowConsulta(28, $Plan_Cod . '*' . 'ICV', $obBD_conexion);
                if (!isset($cuenta['Pld_Cod']) && empty($cuenta['Pld_Cod']))
                    throw new Exception('Revisar la parametrizaci&oacute;n contable de: <u>ICE en Ventas</u>!');
                $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('H') . '*' . $t_ice . '*' . 'ICE' . '*' . 'ICE' . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);
            }
            /* Por cada pago: inserta línea DEBE en la cuenta del pago o en cuentas por cobrar */
            foreach ($pagos as $pag) {
                /* Si forma de pago es crédito (Forma_Cod=2), usa cuentas por cobrar */
                if ($pag['Forma_Cod'] * 1 == 2) {
                    if (!empty($editDoc['Cpc_Cod'])) {
                        /* Operación específica para actualizar Cpc (op 966) */
                        $obBD_conIns->operacionobBD(966, $editDoc['Cpc_Cod'], $obBD_conexionIns);
                        /* Comprobante: solo el valor del pago (no se suma la retención; la retención va a anticipo) */
                        $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('D') . '*' . $pag['Vet_Tot'] . '*' . $pag['Vet_Num'] . '*' . ('Doc.' . $Vet_Num) . '*' . $pag['Pag_Pld'], $obBD_conexionIns);
                        /* Registra relación comprobante-venta-cuenta por cobrar (op 55) */
                        $obBD_conIns->operacionobBD(55, $Com_Cod . '*' . $Vet_Cod . '*' . $pag['Cpc_Ven'] . '*' . (isset($pag['Cpc_Obs']) ? $pag['Cpc_Obs'] : '') . '*' . $editDoc['Cpc_Cod'], $obBD_conexionIns);
                    } else {
                        /* Comprobante: solo el valor del pago (no se suma la retención; la retención va a anticipo) */
                        $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('D') . '*' . $pag['Vet_Tot'] . '*' . $pag['Vet_Num'] . '*' . ('Doc.' . $Vet_Num) . '*' . $pag['Pag_Pld'], $obBD_conexionIns);
                        $obBD_conIns->operacionobBD(55, $Com_Cod . '*' . $Vet_Cod . '*' . $pag['Cpc_Ven'] . '*' . (isset($pag['Cpc_Obs']) ? $pag['Cpc_Obs'] : ''), $obBD_conexionIns);
                    }
                } else {
                    //inserta un asiento el el comprobante  
                    /* Pago al contado: inserta línea DEBE por el monto del pago */
                    $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . ('D') . '*' . $pag['Vet_Tot'] . '*' . $pag['Vet_Num'] . '*' . ('Doc.' . $Vet_Num) . '*' . $pag['Pag_Pld'], $obBD_conexionIns);
                }
            }
            /* Si el pago es a crédito (Forma_Cod=2) o al contado con retenciones: ya no se registra aquí la retención en el comprobante de venta, se va directo a anticipos */
            if ($pag['Forma_Cod'] * 1 == 2) {
                if (isset($rets) && empty($manifiesto['Pla_Cod']) && $pag['Forma_Cod'] * 1 != 2 && false) {
                    /* Obtiene tipo de asiento para retención (tipo 17) */
                    $Tia_Asi_Ret = $obBD_con1->getRowConsulta(80, 17, $obBD_conexion);
                    /* Número de comprobante de retención (secuencia por mes) */
                    $Com_Num_Ret = $obBD_con1->codigoComprAuto($Tia_Asi_Ret['Tia_Cod'], $Pec_Cod, $meseCom[1], $obBD_conexion);
                    /* Concepto del comprobante de retención */
                    $Com_Con_Ret = 'RETENCION DE ' . $Com_Con;
                    /* Inserta cabecera del comprobante de retención (op 70) */
                    $obBD_conIns->operacionobBD(70, $Pec_Cod . '*' . $Cli_Cod . '*' . $Com_Num_Ret . '*' . $Ret_Fec . '*' . trim($Com_Con_Ret) . '*' . $Tia_Asi_Ret['Tia_Cod'] . '*' . $Ren_Tot . '*' . 'RETENCION' . '*' . $campo, $obBD_conexionIns);
                    /* Código del comprobante de retención recién insertado */
                    $Com_Cod_Ret = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                    /* Por cada retención: inserta línea DEBE en la cuenta de la retención */
                    $asientos_retencion_credito = array(); // Array para almacenar los Asi_Cod de cada retención
                    foreach ($rets as $ret) {
                        /* Retención 338 (banano) usa consulta 103; el resto usa consulta 52 */
                        if ($ret['Ren_Sri'] * 1 === 338 && ($ret['Ren_Sri'] * 1 != 1.0 && $ret['Ren_Sri'] * 1 != 1.25 && $ret['Ren_Sri'] * 1 != 1.5 && $ret['Ren_Sri'] * 1 != 2.0)) {
                            $cuenta = $obBD_con1->getRowConsulta(103, $Plan_Cod . '*' . $ret['Ren_Sri'] . '*' . 'V', $obBD_conexion);
                        } else {
                            $cuenta = $obBD_con1->getRowConsulta(52, $Plan_Cod . '*' . $ret['Ren_Cod'] . '*' . 'V', $obBD_conexion);
                        }
                        if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) {
                            throw new Exception('Revisar la parametrizacion contable del Codigo: <u>' . $ret['Ren_Sri'] . '</u>!');
                        }
                        /* Inserta línea DEBE por valor de la retención en comprobante de retención */
                        $obBD_conIns->operacionobBD(87, $Com_Cod_Ret . '*' . 'D' . '*' . $ret['Ren_Val'] . '*' . $cuenta['Pld_Des'] . '*' . $ret['Ren_Con'] . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);
                        // Capturar el Asi_Cod del asiento recién insertado
                        $Asi_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                        $asientos_retencion_credito[] = array('Asi_Cod' => $Asi_Cod, 'Ren_Val' => $ret['Ren_Val'], 'Ren_Con' => $ret['Ren_Con']);
                    }
                    /* Por cada pago: inserta línea HABER en el comprobante de retención (contrapartida) */
                    foreach ($pagos as $pag) {
                        $obBD_conIns->operacionobBD(87, $Com_Cod_Ret . '*' . ('H') . '*' . $Ren_Tot . '*' . $pag['Vet_Num'] . '*' . ('Doc.' . $Vet_Num) . '*' . $pag['Pag_Pld'], $obBD_conexionIns);
                    }
                    /* Crea abono en cuentas por cobrar por el monto de la retención (op 1133) */
                    $obBD_conIns->operacionobBD(1133, array('Com_Cod' => $Com_Cod_Ret, 'Pag_Cod' => 50, 'Cpc_Fec' => $Ret_Fec, 'Cpc_Val' => $Ren_Tot, 'Cpc_Obs' => "ABONO POR RETENCION", 'Cpc_Cod' => $editDoc['Cpc_Cod']), $obBD_conexionIns);
                    /* Si hay un anticipo relacionado con esta venta, guardar en pagos_anticipo_cli */
                    if (!empty($editDoc['Ant_Cod_Ant']) && ($editDoc['Ant_Cod_Ant'] * 1) > 0) {
                        // Eliminar registros anteriores de pagos_anticipo_cli si se está actualizando
                        $obBD_conIns->operacionobBD(195, $editDoc['Ant_Cod_Ant'], $obBD_conexionIns);
                        // Obtener el código de tipo de pago para retenciones
                        $cuenta_pag = $obBD_con1->getRowConsulta(175, 'RET', $obBD_conexion);
                        $Pag_Cod_Ret = isset($cuenta_pag['Pag_Cod']) ? $cuenta_pag['Pag_Cod'] : 50; // Valor por defecto si no existe
                        // Insertar registros en pagos_anticipo_cli para cada retención
                        if (count($asientos_retencion_credito) > 0) {
                            foreach ($asientos_retencion_credito as $asiento_ret) {
                                $data_pag_ant = array(
                                    'Pac_Cto' => '',
                                    'Pac_Ctd' => '',
                                    'Pac_Val' => $asiento_ret['Ren_Val'],
                                    'Ant_Cod' => $editDoc['Ant_Cod_Ant'],
                                    'Che_Cod' => null,
                                    'Pac_Obs' => 'Pago de retención: ' . $asiento_ret['Ren_Con'],
                                    'Pac_Num' => '',
                                    'Pag_Cod' => $Pag_Cod_Ret,
                                    'Asi_Cod' => $asiento_ret['Asi_Cod']
                                );
                                $obBD_conIns->operacionobBD(194, $data_pag_ant, $obBD_conexionIns);
                            }
                        }
                    }
                }
            }
            /* Si el pago es al contado con retenciones: ya no se registra en el comprobante de venta, se va a anticipos */ else {
                if (isset($rets) && false) {
                    foreach ($rets as $ret) {
                        /* Obtiene cuenta contable de la retención (103 para 338, 52 para el resto) */
                        if ($ret['Ren_Sri'] * 1 === 338 && ($ret['Ren_Sri'] * 1 != 1.0 && $ret['Ren_Sri'] * 1 != 1.25 && $ret['Ren_Sri'] * 1 != 1.5 && $ret['Ren_Sri'] * 1 != 2.0)) {
                            $cuenta = $obBD_con1->getRowConsulta(103, $Plan_Cod . '*' . $ret['Ren_Sri'] . '*' . 'V', $obBD_conexion);
                        } else {
                            $cuenta = $obBD_con1->getRowConsulta(52, $Plan_Cod . '*' . $ret['Ren_Cod'] . '*' . 'V', $obBD_conexion);
                        }
                        if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) {
                            throw new Exception('Revisar la parametrizaci&oacute;n contable del c&oacute;digo: <u>' . $ret['Ren_Sri'] . '</u>!');
                        }
                        if ($ret['Ren_Val'] == null) {
                            $ret['Ren_Val'] = 0;
                        }
                        /* Inserta línea DEBE por valor de retención en comprobante de venta */
                        $obBD_conIns->operacionobBD(87, $Com_Cod . '*' . 'D' . '*' . $ret['Ren_Val'] . '*' . $cuenta['Pld_Des'] . '*' . $ret['Ren_Con'] . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);
                    }
                }
            }
            }
        } else {
            /* Empresa sin contabilidad o comprobante no electrónico: si existía comprobante, lo borra */
            if ($editDoc['Com_Exi'] == 'S' && $editDoc['Cpc_Min'] <= 0) {
                /* Borra líneas del asiento (op 41) */
                $obBD_conIns->operacionobBD(41, $editDoc['Com_Cod'], $obBD_conexionIns);
                /* Borra relación venta-comprobante (op 99) */
                $obBD_conIns->operacionobBD(99, $editDoc['Com_Cod'] . "*" . $editDoc['Vet_Cod'], $obBD_conexionIns);
                /* Borra cabecera del comprobante (op 98) */
                $obBD_conIns->operacionobBD(98, $editDoc['Com_Cod'], $obBD_conexionIns);
            }
        }
        /* Si hay retenciones: registrar o actualizar valor de retención en anticipos clientes (la anulación ya se hizo al inicio del guardado) */
        $ant_cod_actualizar = null; /* Si se encuentra anticipo por Vet_Cod, se actualiza en lugar de crear otro */
        $Ama_Cod = null; /* Inicializar Ama_Cod por si no hay manifiesto */
        $Ama_Cod_nuevo_manifiesto = null; /* Si la venta tiene manifiesto y el anticipo no tenía Ama_Cod, al crear manifiesto_anticipo se enlaza aquí */
        if (isset($rets) && count($rets) > 0 && ($Ren_Tot * 1) > 0 && $configs['Cof_Con'] == 'S' && isset($meseCom)) {
            $tip_asien = $obBD_con1->getRowConsulta(1855, 0, $obBD_conexion);
            $campo_ant = 'Cli_Cod';
           // $Com_Con_Ant = 'Anticipo por concepto de retencion Nro ' . $Ret_Num;
            $Com_Con_Ant = 'RETENCION DE REG. VENTA NRO ' . $Vet_Num;
            $Cli_Cod_Ant = !empty($manifiesto['Cli_Cod']) ? $manifiesto['Cli_Cod'] : $Cli_Cod;
            $Pla_Cod_Ant = !empty($manifiesto['Pla_Cod']) ? $manifiesto['Pla_Cod'] : $Plan_Cod;
            $tiene_manifiesto_venta = (is_array($manifiesto) && count($manifiesto) > 0); /* venta con relación a manifiesto (consulta 1844 devolvió fila); si no trae Pla_Cod usamos Plan_Cod en Pla_Cod_Ant */
            /* Si acabamos de anular, no reutilizar editDoc ni ant_por_veta: crear siempre anticipo/comprobante nuevo (la otra conexión podría seguir viendo el anticipo anulado como activo) */
            if (!empty($editDoc['Com_Cod_Ant']) && ($editDoc['Com_Cod_Ant'] * 1) > 0 && !$acabamos_de_anular_anticipos) {
                /* Segunda vez o más: actualizar comprobante y asientos (como el comprobante de venta: actualizar cabecera, borrar asientos, insertar nuevos) y actualizar anticipo */
                $ultimo_comprobante = $editDoc['Com_Cod_Ant'];
                $Com_Num_Ant = isset($editDoc['Com_Num_Ant']) ? $editDoc['Com_Num_Ant'] : '';
                if (!empty($editDoc['Ama_Cod_Ant'])) {
                    /* La venta tiene manifiesto y el anticipo ya estaba en manifiesto_anticipo: actualizar valor/obs */
                    $obBD_conIns->operacionobBD(191, array('Ama_Cod' => $editDoc['Ama_Cod_Ant'], 'Ama_Val' => $Ren_Tot, 'Ama_Doc' => $Ret_Num, 'Ama_Fec' => date('Y-m-d'), 'Ama_Obs' => 'Acreditacion por valor de retencion.'), $obBD_conexionIns);
                } elseif ($tiene_manifiesto_venta) {
                    /* La venta está relacionada con manifiesto pero el anticipo no tenía Ama_Cod: registrar en manifiesto_anticipo solo cuando hay manifiesto */
                    $cuenta_pag_edit = $obBD_con1->getRowConsulta(175, 'RET', $obBD_conexion);
                    $data_ant_man = array(
                        'Ban_Cod' => '',
                        'Bak_Cod' => '',
                        'Usu_Cod' => $Ses_Usu_Cod,
                        'Cli_Cod' => $Cli_Cod_Ant,
                        'Pag_Cod' => $cuenta_pag_edit['Pag_Cod'],
                        'Pla_Cod' => $Pla_Cod_Ant,
                        'Ama_Val' => $Ren_Tot,
                        'Ama_Tde' => $cuenta_pag_edit['Pag_Cod'],
                        'Ama_Tip' => 'A',
                        'Ama_Doc' => $Ret_Num,
                        'Ama_Fec' => date('Y-m-d'),
                        'Ama_Obs' => "Acreditación por valor de retención. Venta Nro: $Vet_Num",
                        'Ama_Img' => '',
                        'Ama_Est' => 'A'
                    );
                    $obBD_conIns->operacionobBD(1833, $data_ant_man, $obBD_conexionIns);
                    $Ama_Cod_nuevo_manifiesto = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                }
                $obBD_conIns->operacionobBD(70, $Pec_Cod . '*' . $Cli_Cod_Ant . '*' . $Com_Num_Ant . '*' . $Caj_Fec . '*' . trim($Com_Con_Ant) . '*' . $tip_asien['Tia_Cod'] . '*' . $Ren_Tot . '*' . 'Anticipo con el valor de la retencion' . '*' . $campo_ant . '*' . $editDoc['Com_Cod_Ant'], $obBD_conexionIns);
                $obBD_conIns->operacionobBD(41, $editDoc['Com_Cod_Ant'], $obBD_conexionIns);
            } else {
                /* Verificar si ya existe un anticipo con este Vet_Cod: si existe, actualizar en lugar de crear otro (no usar si acabamos de anular: la otra conexión podría devolver el anulado) */
                $ant_por_veta = $acabamos_de_anular_anticipos ? array() : $obBD_con1->getRowConsulta(193, $Vet_Cod, $obBD_conexion);
                if (!empty($ant_por_veta['Ant_Cod']) && !empty($ant_por_veta['Com_Cod'])) {
                    /* Ya existe anticipo por esta venta: actualizar comprobante y asientos */
                    $ultimo_comprobante = $ant_por_veta['Com_Cod'];
                    $Com_Num_Ant = isset($ant_por_veta['Com_Num']) ? $ant_por_veta['Com_Num'] : '';
                    $ant_cod_actualizar = $ant_por_veta['Ant_Cod'];

                    if (!empty($ant_por_veta['Ama_Cod'])) {
                        $obBD_conIns->operacionobBD(191, array('Ama_Cod' => $ant_por_veta['Ama_Cod'], 'Ama_Val' => $Ren_Tot, 'Ama_Doc' => $Ret_Num, 'Ama_Fec' =>      /*$Caj_Fec*/    $Ret_Fec  /*date('Y-m-d')*/, 'Ama_Obs' => 'Acreditacion por valor de retencion.'), $obBD_conexionIns);
                    } elseif ($tiene_manifiesto_venta) {
                        /* La venta está relacionada con manifiesto pero el anticipo no tenía Ama_Cod: registrar en manifiesto_anticipo solo cuando hay manifiesto */
                        $cuenta_pag_ant = $obBD_con1->getRowConsulta(175, 'RET', $obBD_conexion);
                        $data_ant_man = array(
                            'Ban_Cod' => '',
                            'Bak_Cod' => '',
                            'Usu_Cod' => $Ses_Usu_Cod,
                            'Cli_Cod' => $Cli_Cod_Ant,
                            'Pag_Cod' => $cuenta_pag_ant['Pag_Cod'],
                            'Pla_Cod' => $Pla_Cod_Ant,
                            'Ama_Val' => $Ren_Tot,
                            'Ama_Tde' => $cuenta_pag_ant['Pag_Cod'],
                            'Ama_Tip' => 'A',
                            'Ama_Doc' => $Ret_Num,
                            'Ama_Fec' => date('Y-m-d'),
                            'Ama_Obs' => "Acreditación por valor de retención. Venta Nro: $Vet_Num",
                            'Ama_Img' => '',
                            'Ama_Est' => 'A'
                        );
                        $obBD_conIns->operacionobBD(1833, $data_ant_man, $obBD_conexionIns);
                        $Ama_Cod_nuevo_manifiesto = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                    }
                    $obBD_conIns->operacionobBD(70, $Pec_Cod . '*' . $Cli_Cod_Ant . '*' . $Com_Num_Ant . '*' . $Caj_Fec . '*' . trim($Com_Con_Ant) . '*' . $tip_asien['Tia_Cod'] . '*' . $Ren_Tot . '*' . 'Anticipo con el valor de la retencion' . '*' . $campo_ant . '*' . $ant_por_veta['Com_Cod'], $obBD_conexionIns);
                    $obBD_conIns->operacionobBD(41, $ant_por_veta['Com_Cod'], $obBD_conexionIns);
                } else {
                    /* Primera vez: crear manifiesto_anticipo (solo si la venta tiene relación con manifiesto), comprobante y anticipo */
                    $cuenta_pag = $obBD_con1->getRowConsulta(175, 'RET', $obBD_conexion);
                    if ($tiene_manifiesto_venta) {
                        $data_ant_man = array(
                            'Ban_Cod' => "",
                            'Bak_Cod' => "",
                            'Usu_Cod' => $Ses_Usu_Cod,
                            'Cli_Cod' => $Cli_Cod_Ant,
                            'Pag_Cod' => $cuenta_pag['Pag_Cod'],
                            'Pla_Cod' => $Pla_Cod_Ant,
                            'Ama_Val' => $Ren_Tot,
                            'Ama_Tde' => $cuenta_pag['Pag_Cod'],
                            'Ama_Tip' => "A",
                            'Ama_Doc' => $Ret_Num,
                            'Ama_Fec' => date('Y-m-d'),
                            'Ama_Obs' => "Acreditación por valor de retención. Venta Nro: $Vet_Num",
                            'Ama_Img' => "",
                            'Ama_Est' => "A"
                        );
                        $obBD_conIns->operacionobBD(1833, $data_ant_man, $obBD_conexionIns);
                        $Ama_Cod = $obBD_conIns->insercionid($obBD_conexionIns);
                    }
                    $Com_Num_Ant = $obBD_con1->codigoComprAuto($tip_asien['Tia_Cod'], $Pec_Cod, $meseCom[1], $obBD_conexion);
                    $data_comp_ant = array(
                        'Pec_Cod' => $Pec_Cod,
                        'Prv_Cod' => NULL,
                        'Cli_Cod' => $Cli_Cod_Ant,
                        'Com_Num' => $Com_Num_Ant,
                        'Com_Fec' => $Ret_Fec  /*$Caj_Fec*/,   //fecha de emision de la factura
                        'Com_Con' => $Com_Con_Ant,
                        'Com_Tip' => 'I',
                        'Com_Val' => $Ren_Tot,
                        'Com_Obs' => 'Anticipo con el valor de la retención, Vnta.Nro:' . $Vet_Num,
                        'Com_Tipo' => '',
                        'Tia_Cod' => $tip_asien['Tia_Cod'],
                        'Com_Est' => 'A',
                        'Usu_Cod' => $Ses_Usu_Cod,
                        'Com_Gen' => 'A'
                    );
                    $obBD_conIns->operacionobBD(1866, $data_comp_ant, $obBD_conexionIns);
                    $ultimo_comprobante = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                }
            }
            /* Cuenta parametrizada anticipo cliente (ANC) → HABER; DEBE = Pld_Cod de cada retención/IVA */
            $cnta_ant_cli = $obBD_con1->getRowConsulta(67, $Ses_Emp_Cod . '*' . 'ANC', $obBD_conexion);
            if (!isset($cnta_ant_cli['Pld_Cod']) || empty($cnta_ant_cli['Pld_Cod'])) {
                throw new Exception('Revisar la parametrizaci&oacute;n contable de: <u>Anticipo Cliente (ANC)</u>!');
            }
            $desc_asiento = $tiene_manifiesto_venta ? 'Manifiesto Anticipo' : 'Anticipo por Retención';
            $obBD_conIns->operacionobBD(87, $ultimo_comprobante . '*' . 'H' . '*' . $Ren_Tot . '*' . $desc_asiento . '*' . ('DOC.RET: ' . $Ret_Num) . '*' . $cnta_ant_cli['Pld_Cod'], $obBD_conexionIns);
            //En el haber debe haber una cuenta que haga referencia a las retenciones
            $asientos_retencion = array(); // Array para almacenar los Asi_Cod de cada retención
            foreach ($rets as $ret) {
                if ($ret['Ren_Sri'] * 1 === 338 && ($ret['Ren_Sri'] * 1 != 1.0 && $ret['Ren_Sri'] * 1 != 1.25 && $ret['Ren_Sri'] * 1 != 1.5 && $ret['Ren_Sri'] * 1 != 2.0)) {
                    $cuenta = $obBD_con1->getRowConsulta(103, $Plan_Cod . '*' . $ret['Ren_Sri'] . '*' . 'V', $obBD_conexion);
                } else {
                    $cuenta = $obBD_con1->getRowConsulta(52, $Plan_Cod . '*' . $ret['Ren_Cod'] . '*' . 'V', $obBD_conexion);
                }
                if (!isset($cuenta['Pld_Cod']) || empty($cuenta['Pld_Cod'])) {
                    throw new Exception('Revisar la parametrizaci&oacute;n contable del c&oacute;digo retenci&oacute;n: <u>' . $ret['Ren_Sri'] . '</u>!');
                }
                $ret_val = isset($ret['Ren_Val']) && $ret['Ren_Val'] !== null ? $ret['Ren_Val'] : 0;
                $obBD_conIns->operacionobBD(87, $ultimo_comprobante . '*' . 'D' . '*' . $ret_val . '*' . $cuenta['Pld_Des'] . '*' . (isset($ret['Ren_Con']) ? $ret['Ren_Con'] : $ret['Ren_Cod']) . '*' . $cuenta['Pld_Cod'], $obBD_conexionIns);
                // Capturar el Asi_Cod del asiento recién insertado
                $Asi_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                $asientos_retencion[] = array('Asi_Cod' => $Asi_Cod, 'Ren_Val' => $ret_val, 'Ren_Con' => (isset($ret['Ren_Con']) ? $ret['Ren_Con'] : $ret['Ren_Cod']));
            }
            // Obtener el Ant_Cod final después de crear/actualizar el anticipo
            $Ant_Cod_Final = null;
            if (!empty($editDoc['Com_Cod_Ant']) && ($editDoc['Com_Cod_Ant'] * 1) > 0 && !empty($editDoc['Ant_Cod_Ant'])) {
                $Ant_Cod_Final = $editDoc['Ant_Cod_Ant'];
                $obBD_conIns->operacionobBD(192, array('Ant_Cod' => $editDoc['Ant_Cod_Ant'], 'Ant_Val' => $Ren_Tot, 'Ant_Obs' => 'Anticipo cliente. Acreditacion por valor de retencion ', 'Ant_Doc' => $Ret_Num), $obBD_conexionIns);
                // Eliminar registros anteriores de pagos_anticipo_cli si se está actualizando
                $obBD_conIns->operacionobBD(195, $Ant_Cod_Final, $obBD_conexionIns);
            } elseif (!empty($ant_cod_actualizar)) {
                /* Anticipo existente encontrado por Vet_Cod: actualizar valor/obs/doc */
                $Ant_Cod_Final = $ant_cod_actualizar;
                $obBD_conIns->operacionobBD(192, array('Ant_Cod' => $ant_cod_actualizar, 'Ant_Val' => $Ren_Tot, 'Ant_Obs' => 'Anticipo cliente. Acreditacion por valor de retencion ', 'Ant_Doc' => $Ret_Num), $obBD_conexionIns);
                // Eliminar registros anteriores de pagos_anticipo_cli si se está actualizando
                $obBD_conIns->operacionobBD(195, $Ant_Cod_Final, $obBD_conexionIns);
            } else {
                $data_ant = array(
                    'Ant_Fec' =>  /*$Caj_Fec */    $Ret_Fec  /*date('Y-m-d')*/,
                    'Ant_Val' => $Ren_Tot,
                    'Ant_Est' => 'A',
                    'Ant_Doc' => $Ret_Num,
                    'Ant_Obs' => 'Anticipo cliente. Acreditacion por valor de retencion, Vnta Nro ' . $Vet_Num,
                    'Cli_Cod' => $Cli_Cod_Ant,
                    'Com_Cod' => $ultimo_comprobante,
                    'Ant_Tip' => "A",
                    'Ama_Cod' => $Ama_Cod,
                    'Vet_Cod' => $Vet_Cod
                );
                $obBD_conIns->operacionobBD(1811, $data_ant, $obBD_conexionIns);
                $Ant_Cod_Final = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
            }
            /* Si se creó manifiesto_anticipo en esta edición (venta con manifiesto y anticipo sin Ama_Cod), enlazar anticipo con manifiesto_anticipo */
            if (!empty($Ama_Cod_nuevo_manifiesto) && !empty($Ant_Cod_Final)) {
                $obBD_conIns->operacionobBD(267, array('Ant_Cod' => $Ant_Cod_Final, 'Ama_Cod' => $Ama_Cod_nuevo_manifiesto), $obBD_conexionIns);
            }
            // Obtener el código de tipo de pago para retenciones
            $cuenta_pag = $obBD_con1->getRowConsulta(175, 'RET', $obBD_conexion);
            $Pag_Cod_Ret = isset($cuenta_pag['Pag_Cod']) ? $cuenta_pag['Pag_Cod'] : 50; // Valor por defecto si no existe
            // Insertar registros en pagos_anticipo_cli para cada retención
            if (!empty($Ant_Cod_Final) && count($asientos_retencion) > 0) {
                foreach ($asientos_retencion as $asiento_ret) {
                    $data_pag_ant = array(
                        'Pac_Cto' => '',
                        'Pac_Ctd' => '',
                        'Pac_Val' => $asiento_ret['Ren_Val'],
                        'Ant_Cod' => $Ant_Cod_Final,
                        'Che_Cod' => null,
                        'Pac_Obs' => 'Pago de retención: ' . $asiento_ret['Ren_Con'],
                        'Pac_Num' => '',
                        'Pag_Cod' => $Pag_Cod_Ret,
                        'Asi_Cod' => $asiento_ret['Asi_Cod']
                    );
                    $obBD_conIns->operacionobBD(194, $data_pag_ant, $obBD_conexionIns);
                }
            }
            $Com_Cod_Ret = $ultimo_comprobante;
        }
        //Hasta este punto todo esta registrado como anticipo,  ya sea las facturas que son al contado o a credito
        //En los anticpos se registra el Vet_Cod de la factura que se uso para construir el anticipo, ahora necesito que 
        // Se cubra el total que falta pagar de esta factura con el anticpo que se genero, se puede usar ya sea una parte del 
        // anticipo o todo el anticpo para cubrir el total de la factura. HAZLO EN ESTA SECCION YA QUE ES LO ULTIMO QUE SE DEBE HACER
        //RECUERDA QUE ESTO SOLO SE HACE CON LAS FACTURAS QUE NO TIENen RELACION CON MANIFIESTOS
        // Verificar si la factura tiene relación con manifiestos
        $tiene_manifiesto = $obBD_con1->getRowConsulta(1822, $Vet_Cod, $obBD_conexion);
        // Solo aplicar anticipo/cobro si NO tiene manifiesto Y hay retenciones (si quitó todos los códigos de retención: solo se anuló arriba, no se vuelve a guardar nada aquí)
        if ((empty($tiene_manifiesto['total']) || $tiene_manifiesto['total'] * 1 == 0) && isset($rets) && count($rets) > 0 && ($Ren_Tot * 1) > 0) {
            // Verificar si el pago de la venta es a CRÉDITO (Forma_Cod = 2)
            $es_credito = false;
            if (!empty($pagos)) {
                foreach ($pagos as $pag) {
                    if ($pag['Forma_Cod'] * 1 == 2) {
                        $es_credito = true;
                        break;
                    }
                }
            }
            // REFRESCAR O ASIGNAR EL ANTICIPO: Priorizamos los datos en memoria para evitar el doble guardado
            if (!empty($Ant_Cod_Final)) {
                $anticipo_factura = array('Ant_Cod' => $Ant_Cod_Final, 'Ant_Val' => $Ren_Tot);
            } else {
                $anticipo_factura = $obBD_con1->getRowConsulta(193, $Vet_Cod, $obBD_conexion);
            }
            // Solo procedemos si es CRÉDITO y tenemos un anticipo de retención
            if ($es_credito && !empty($anticipo_factura['Ant_Cod'])) {
                // VERIFICAR SI YA EXISTEN PAGOS: Si ya hay un cobro registrado con este anticipo, detenemos la edición
                $sql_check_pagos = "SELECT COUNT(*) as total FROM det_ant_cccc WHERE Ant_Cod = " . (int)$anticipo_factura['Ant_Cod'];
                $res_check = $obBD_conIns->consulta($sql_check_pagos, $obBD_conexionIns->conexion);
                $row_check = $obBD_con1->fetch_assoc($res_check);
                // Si acabamos de crear/actualizar el anticipo arriba, total será 0.
                // Si ya existía de antes y tiene pagos, lanzamos el aviso.
                if ($row_check['total'] > 0 && empty($Ant_Cod_Final)) {
                    throw new Exception("Ya existe un anticipo generado por esta retención que tiene pagos aplicados. Por favor, elimine primero el cobro y el anticipo correspondiente para poder editar la retención.");
                }
                // Total factura (rubros) y total ya pagado desde det_ccpp_c (usar conexión de inserción para ver pagos anulados en esta misma transacción)
                $pag_factura = $obBD_con1->getRowConsulta(196, $Vet_Cod, $obBD_conexionIns);
                $total_factura = 0;
                if (!empty($pagos)) {
                    foreach ($pagos as $pag) {
                        $total_factura += isset($pag['Vet_Tot']) ? (float)$pag['Vet_Tot'] : 0;
                    }
                }
                // Agregar el valor de las retenciones si existen (ya que son parte de la deuda de la factura que se movió a anticipos)
                if (isset($Ren_Tot) && $Ren_Tot > 0) {
                    $total_factura += (float)$Ren_Tot;
                }
                $total_pagar_fac =  $total_factura  - (isset($pag_factura['tot_pago']) ? (float)$pag_factura['tot_pago'] : 0);
                // Obtener el valor disponible del anticipo
                $valor_anticipo = isset($anticipo_factura['Ant_Val']) ? (float)$anticipo_factura['Ant_Val'] : 0;
                // El monto a pagar será el menor entre el saldo de la factura y el valor del anticipo
                $pago = min($total_pagar_fac, $valor_anticipo);
                // Si hay pago y contabilidad activa: registrar pago con anticipo (comprobante + det_ccpp_c + det_ant_cccc + cambio estado anticipo)
                if ($pago > 0 && $configs['Cof_Con'] == 'S' && $Tic_Sri * 1 != 0) {
                    // Obtener Cpc_Cod y Com_Cod de la factura (ccpp_cobrar)
                    $sql_ccpp = "SELECT Cpc_Cod, Com_Cod FROM ccpp_cobrar WHERE Vet_Cod = " . (int)$Vet_Cod . " LIMIT 1";
                    $result_ccpp = $obBD_conIns->consulta($sql_ccpp, $obBD_conexionIns->conexion);
                    $row_ccpp = $obBD_con1->fetch_assoc($result_ccpp);
                    if (!empty($row_ccpp) && !empty($row_ccpp['Cpc_Cod'])) {
                        $Cpc_Cod_Pago = $row_ccpp['Cpc_Cod'];
                        $Com_Cod_Venta = $row_ccpp['Com_Cod'];
                        // Cuenta contable DEBE (cuentas por cobrar cliente) del comprobante de la venta
                        $row_pld_ccpp = $obBD_con1->getRowConsulta(399, $Com_Cod_Venta, $obBD_conexion);
                        if (empty($row_pld_ccpp['Pld_Cod'])) {
                            throw new Exception('Revisar parametrización: cuenta por cobrar cliente del comprobante de venta.');
                        }
                        $Pld_Cod_Cpc = $row_pld_ccpp['Pld_Cod'];
                        // Cuenta contable HABER (anticipo cliente ANC)
                        $cnta_ant_cli = $obBD_con1->getRowConsulta(67, $Ses_Emp_Cod . '*' . 'ANC', $obBD_conexion);
                        if (empty($cnta_ant_cli['Pld_Cod'])) {
                            throw new Exception('Revisar parametrización: Anticipo Cliente (ANC).');
                        }
                        $Pld_Cod_Ant = $cnta_ant_cli['Pld_Cod'];
                        // Tipo asiento para recibo de cobro (IN = ingresos / RC = recibo caja)
                        $Tia_Cobro = $obBD_con1->getRowConsulta(1855, '', $obBD_conexion);
                        if (empty($Tia_Cobro['Tia_Cod'])) {
                            $Tia_Cobro = $obBD_con1->getRowConsulta(80, 8, $obBD_conexion);
                        }
                        if (empty($Tia_Cobro['Tia_Cod'])) {
                            throw new Exception('Revisar tipo de asiento para cobros (RC/IN).');
                        }
                        $Com_Fec_Pago =     /*$Caj_Fec */    $Ret_Fec; //CON LA FECHA DE LA RETENCION
                        $meseComPago = explode('-', $Com_Fec_Pago);
                        $Com_Num_Pago = $obBD_con1->codigoComprAuto($Tia_Cobro['Tia_Cod'], $Pec_Cod, $meseComPago[1], $obBD_conexion);
                        $Com_Num_Pago = $Com_Num_Pago - 1;
                        $Com_Con_Pago = 'COBRO CON ANTICIPO - DOC. ' . $Vet_Num . ' (RETENCIÓN)';
                        $Com_Obs_Pago = 'Pago con anticipo por retención. Factura ' . $Vet_Num;
                        ChromePhp::log("Numero comprobante : " . $Com_Num_Pago);
                        // 1) Insertar comprobante del cobro
                        $data_comp = array(
                            'Pec_Cod' => $Pec_Cod,
                            'Cli_Cod' => $Cli_Cod,
                            'Com_Num' => $Com_Num_Pago,
                            'Com_Fec' => $Com_Fec_Pago,
                            'Com_Con' => $Com_Con_Pago,
                            'Com_Val' => $pago,
                            'Com_Obs' => $Com_Obs_Pago,
                            'Tia_Cod' => $Tia_Cobro['Tia_Cod'],
                            'Usu_Cod' => $Ses_Usu_Cod
                        );
                        $obBD_conIns->operacionobBD(1866, $data_comp, $obBD_conexionIns);
                        $Com_Cod_Pago = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                        // 2) Asiento DEBE: anticipo cliente (disminuye pasivo)
                        $obBD_conIns->operacionobBD(87, $Com_Cod_Pago . '*' . 'D' . '*' . $pago . '*' . $Com_Con_Pago . '*' . $Com_Obs_Pago . '*' . $Pld_Cod_Ant, $obBD_conexionIns);
                        // 3) Asiento HABER: cuentas por cobrar (disminuye activo)
                        $obBD_conIns->operacionobBD(87, $Com_Cod_Pago . '*' . 'H' . '*' . $pago . '*' . $Com_Con_Pago . '*' . $Com_Obs_Pago . '*' . $Pld_Cod_Cpc, $obBD_conexionIns);
                        $Asi_Cod_Haber = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                        // 4) Insertar det_ccpp_c (pago aplicado a la factura)
                        $Pag_Cod_Ant = $obBD_con1->getRowConsulta(175, 'ANT', $obBD_conexion);
                        $Pag_Cod_Ant = isset($Pag_Cod_Ant['Pag_Cod']) ? $Pag_Cod_Ant['Pag_Cod'] : 20;
                        $data_det_ccpp = array(
                            'Cpc_Cod' => $Cpc_Cod_Pago,
                            'Com_Cod' => $Com_Cod_Pago,
                            'Pag_Cod' => $Pag_Cod_Ant,
                            'Cpc_Fec' => $Com_Fec_Pago,
                            'Cpc_Val' => $pago,
                            'Cpc_Obs' => $Com_Obs_Pago,
                            'Asi_Cod' => $Asi_Cod_Haber
                        );
                        $obBD_conIns->operacionobBD(255, $data_det_ccpp, $obBD_conexionIns);
                        $Dcc_Cod = $obBD_conIns->insercionid($obBD_conexionIns->conexion);
                        // 5) Consumir anticipo: det_ant_cccc y actualizar Pac_Est / Ant_Est
                        $ctsCli = array();
                        $sql_pac = "SELECT Pac_Cod, Pac_Val, Pac_Est, Ant_Cod FROM pag_anticipo_cli WHERE Ant_Cod = " . (int)$anticipo_factura['Ant_Cod'] . " ORDER BY Pac_Cod";
                        // Usamos la misma conexión de inserción para asegurar visibilidad en el primer guardado
                        $r_pac = $obBD_conIns->consulta($sql_pac, $obBD_conexionIns->conexion);
                        while ($rp = $obBD_con1->fetch_assoc($r_pac)) {
                            $ctsCli[] = $rp;
                        }
                        // Si no hay líneas de pago en el anticipo, es un error de integridad
                        if (empty($ctsCli)) {
                            throw new Exception('El anticipo ' . $anticipo_factura['Ant_Cod'] . ' no tiene movimientos (pag_anticipo_cli) para consumir.');
                        }
                        $saldo_a_distribuir = $pago;
                        $anticipo_quedara_saldo = ($valor_anticipo - $pago) > 0.009;
                        foreach ($ctsCli as $ctsc) {
                            if ($saldo_a_distribuir <= 0) {
                                break;
                            }
                            if (isset($ctsc['Pac_Est']) && $ctsc['Pac_Est'] == 'C') {
                                continue;
                            }
                            $valor_pac = isset($ctsc['Pac_Val']) ? (float)$ctsc['Pac_Val'] : 0;
                            $aplicar_esta_linea = min($saldo_a_distribuir, $valor_pac);
                            if ($aplicar_esta_linea <= 0) {
                                continue;
                            }
                            $obBD_conIns->operacionobBD(199, array(
                                'Ddc_Val' => $aplicar_esta_linea,
                                'Ddc_Obs' => $Com_Obs_Pago,
                                'Ant_Cod' => $anticipo_factura['Ant_Cod'],
                                'Dcc_Cod' => $Dcc_Cod,
                                'Pac_Cod' => $ctsc['Pac_Cod'],
                                'Com_Cod' => $Com_Cod_Pago
                            ), $obBD_conexionIns);
                            $obBD_conIns->operacionobBD(198, array(
                                'Pac_Cod' => $ctsc['Pac_Cod'],
                                'Ant_Cod' => $anticipo_factura['Ant_Cod'],
                                'Pac_Est' => ($aplicar_esta_linea >= $valor_pac - 0.009) ? 'C' : 'U'
                            ), $obBD_conexionIns);
                            $saldo_a_distribuir -= $aplicar_esta_linea;
                        }
                        $obBD_conIns->operacionobBD(197, array(
                            'Ant_Cod' => $anticipo_factura['Ant_Cod'],
                            'Ant_Est' => $anticipo_quedara_saldo ? 'U' : 'C'
                        ), $obBD_conexionIns);
                    }
                }
            }
        }
        /* Si ocurrió alguna excepción: revierte la transacción y devuelve error */
    } catch (Exception $ex) {
        /* Revierte todos los cambios de la transacción */
        $obBD_conIns->rollBack_nomsn($obBD_conexionIns);
        /* Mensaje de error de la excepción */
        $responce['message'] = $ex->getMessage();
        echo json_encode($responce);
        exit();
    }

    /* Confirma la transacción (COMMIT) si no hubo excepción */
    $obBD_conIns->fin_transaccion_nomsn($obBD_conexionIns->conexion);
    /* Si no hubo error en el objeto de datos, arma respuesta exitosa */
    if ($obBD_conIns->Error == 0) {
        /* Obtiene URLs de reportes (impresión) para la empresa */
        $reportes = $obBD_con1->reportes('fac_alt_fac_ven_3.1.php', $Ses_Emp_Cod, $obBD_conexion);
        /* Respuesta JSON: éxito, enlace impresión, código venta, número, fecha, tipo documento */
        $response = array(
            'success' => true,
            'Vet_Impr' => "" . (!empty($reportes[1]) ? "$reportes[1]?Vet_Cod=" : "") . "$Vet_Cod",
            'Vet_Cod' => $Vet_Cod,
            'Vet_Num' => $Vet_Num,
            'Vet_Fec' => $Caj_Fec,
            'Tic_Des' => $Tic_Txt
        );
        /* Si hay documento de venta: agrega datos, filas e enlace de impresión a la respuesta */
        if (!empty($Vet_Cod)) {
            $response['Vet_Data'] = array('Tic_Des' => $Tic_Txt, 'cliente' => $cliente, 'Vet_Num' => $Vet_Num, 'Vet_Fec' => $Caj_Fec, 'Vet_Aut' => $Aut_Sri);
            /* Obtiene filas del documento para mostrar en pantalla (consulta 79) */
            $response['Vet_Rows'] = $obBD_con1->getArrayConsulta(79, $Vet_Cod, $obBD_conexion);
            $response['Vet_Link'] = "" . (!empty($reportes[1]) ? "$reportes[1]?Vet_Cod=" : "") . "$Vet_Cod";
        }
        /* Si se generó comprobante contable: agrega datos, filas y enlace del comprobante */
        if (!empty($Com_Cod)) {
            $response['Com_Data'] = array('Codigo' => $Com_Cod, 'Tia_Des' => $Tia_Asi['Tia_Des'], 'Com_Con' => $Vet_Obs, 'Com_Fec' => $Caj_Fec, 'Com_Val' => $t_rubros);
            /* Obtiene filas del asiento contable (consulta 27) */
            $response['Com_Rows'] = $obBD_con1->getArrayConsulta(27, $Com_Cod, $obBD_conexion);
            $response['Com_Link'] = "" . (!empty($reportes[2]) ? "$reportes[2]?codigo=" : "") . "$Com_Cod";
        }
        /* Si hay retenciones: agrega código, datos, filas y enlace para imprimir comprobante de retención */
        if (isset($rets)) {
            $response['Ret_Cod'] = $Ret_Cod;
            $response['Ret_Data'] = array('Ret_Num' => $Ret_Num, 'Aut_Sri' => $Ret_Aut_Sri, 'Ret_Fec' => $Ret_Fec, 'Ren_Tot' => $Ren_Tot, 'Iva_Ren_Tot' => $Iva_Ren_Tot, 'Ret_Ren_Tot' => $Ret_Ren_Tot);
            $response['Ret_Rows'] = $rets;
            /* Enlace para imprimir comprobante de retención (usa Com_Cod_Ret si existe, sino Com_Cod) */
            $com_cod_aux = !empty($Com_Cod_Ret) ? $Com_Cod_Ret : $Com_Cod;
            $response['Com_Link_Ret'] = "" . (!empty($reportes[2]) ? "$reportes[2]?codigo=" : "") . "$com_cod_aux";
        }
    } else {
        /* Si hubo error en el objeto: respuesta con success false y mensaje de error */
        $response = array("success" => false, "message" => "No se ha logrado realizar la Transaccion", "error" => $obBD_conIns->MsgError);
    }
    /* Devuelve la respuesta en JSON y termina el script */
    echo json_encode($response);
    exit();
}





$rs_tip_compr = $obBD_con1->getArrayConsulta(30, '', $obBD_conexion);
$rs_periodo = $obBD_con1->getArrayConsulta(33, $Ses_Emp_Cod, $obBD_conexion);
?>
<!DOCTYPE html>
<HTML>

<HEAD>
    <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
    <TITLE><?Php echo "Ventas Agregar Retención [EXA]"; ?></TITLE>
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

    <script type="text/ecmascript" src="../VALIDACIONES/fac_val_factura.js?a=283">
    </script>
    <script>
        edit_reten = true;
        inicializarDocVenta();
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

        .icon-grey {
            opacity: 0.5;
            filter: alpha(opacity=50);
            /* For IE8 and earlier */
        }
    </style>
</HEAD>

<BODY>
    <div class="panel panel-main">
        <div class="panel-heading exa-header">
            <h3 class="panel-title ">&raquo; Modificar Retenciones de Ventas</h3>
            <p id="cabeceraPuntoImp" class="text-right col-xs-12  " style="margin-top:-15px;">punto de impresion</p>
        </div>

        <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
            <div id="documentoSearch">
                <form id="serachDocDorm" class="form-horizontal normal"
                    action="javascript:$('#searchGrid').Search('#serachDocDorm','searchDocument');">
                    <div class="row">
                        <input name="order" type="hidden" value="" />
                        <input name="fecha_inicio" type="hidden" value="" />
                        <input name="fecha_fin" type="hidden" value="" />


                        <input type="hidden" name="Tot_Man" id="Tot_Man">

                        <div class="col-xs-6">
                            <fieldset class="exa-fieldset">
                                <legend class="Titulos2">B&uacute;squeda</legend>
                                <div class="form-group">

                                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                                    <div class="col-xs-10 radioset opt_search">
                                        <input id="radsc1" name="op_opciones" type="radio" value="p" checked=""
                                            onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label
                                            for="radsc1">&nbsp;&nbsp;&nbsp;Proveedor&nbsp;&nbsp;&nbsp;</label>
                                        <input id="radsc2" name="op_opciones" type="radio" value="c"
                                            onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label
                                            for="radsc2">&nbsp;&nbsp;&nbsp;C&eacute;dula/RUC&nbsp;&nbsp;&nbsp;</label>
                                        <input id="radsc3" name="op_opciones" type="radio" value="d"
                                            onclick="setOpt(this.value); setfocus(this.form.search)" alt="" /><label
                                            for="radsc3">&nbsp;&nbsp;No. Documento&nbsp;&nbsp;</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                                    <div class="col-xs-7">
                                        <div class="input-group">
                                            <input name="search"
                                                onkeydown="if (event.keyCode === 13) this.form.submit()" type="text"
                                                size="50" maxlength="50" placeholder="Ingrese búsqueda..." autofocus
                                                class="form-control input-sm clearable submit" />
                                            <span class="input-group-btn"><button type="button"
                                                    onclick="this.form.submit()" class="btn btn-success btn-sm"
                                                    title="Buscar Documento" tabindex="-1"><span
                                                        class="glyphicon glyphicon-search"></span>
                                                    <span>Buscar</span></button></span>
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
                                                if ($row['Tic_Sri'] != 4 && $row['Tic_Sri'] != 5 && $row['Tic_Sri'] != 7 && $row['Tic_Sri'] != 23 && $row['Tic_Sri'] != 24)
                                                    echo "<option value='$row[Tic_Cod]' data-ticsri='$row[Tic_Sri]'>$row[Tic_Sri] - $row[Tic_Des]</option>";
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-4 control-label label-xs">Per&iacute;odo:</label>
                                    <div class="col-xs-3">
                                        <select name="Pec_Cod" class="form-control input-xs search_pec"
                                            onchange="if(this.value==='') $('#Cmb_Mes').attr('disabled','disabled'); else $('#Cmb_Mes').removeAttr('disabled');">
                                            <option value="">
                                                << TODOS>>
                                            </option>
                                            <?php $rs_perio = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion);
                                            foreach ($rs_perio as $row) { ?>
                                                <option value="<?php echo $row['Pec_Cod']; ?>"
                                                    data-inicio="<?php echo $row['Pec_Fei']; ?>"
                                                    data-fin="<?php echo $row['Pec_Fef']; ?>"><?php echo $row['Anio']; ?>
                                                </option>
                                            <?php } ?>

                                        </select>

                                    </div>
                                    <label class="col-xs-2 control-label label-xs">Mes:</label>
                                    <div class="col-xs-3">
                                        <select id="Cmb_Mes" name="Cmb_Mes" class="form-control input-xs search_pec"
                                            disabled="disabled">
                                            <option value="">
                                                << TODOS>>
                                            </option>
                                            <?Php for ($i = 1; $i <= 12; $i++) { ?>
                                                <option <?php if ($i == $mes) {
                                                            echo "selected=''";
                                                        } ?>
                                                    value="<?Php echo $i; ?>"><?php echo mes($i, 1); ?></option><?Php } ?>
                                        </select>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </form>
                <div style="min-height: 270px;">
                    <table id="searchGrid"></table>
                    <table id="searchGridPager"></table>
                    <div class="Titulos2"><span id="plan-footer"><strong>Leyenda:</strong> <span
                                class="glyphicon glyphicon-stop green"></span> Contiene Pagos | <span
                                class="glyphicon glyphicon-remove red"></span> Anulados/Inactivos | <span
                                class="fa fa-globe green"></span> Retención Electronica Validada | <span
                                class="glyphicon glyphicon-lock orange"></span> Formato Anterior</div>
                </div>
                <script>
                    function setOpt(val) {
                        if (val === 'd') $('.search_pec').attr('disabled', 'disabled');
                        else $('.search_pec').removeAttr('disabled');
                    }


                    function cargarDoc(doc) {
                        items.clearGridData();
                        vet_num_ant = doc['Vet_Num'];
                        tic_cod_ant = doc['Tic_Cod'];
                        editDoc = true;
                        AutCod = doc['Aut_Cod'];
                        TicCod = doc['Tic_Cod'];
                        $('#editDoc').setData({});
                        $('#Pec_Cod').attr('disabled', true);
                        $('#Tpc_Cod').val(doc['Tpc_Cod'] * 1);
                        //Verificar si la factura tiene manifiesto
                        $('#Tot_Man').val(doc['Tot_Man']);

                        $.getDataJson('', {
                            'cargarDoc': true,
                            'vet_cod': doc['Vet_Cod'],
                            'Aut_Cod': AutCod,
                            'Tic_Cod': TicCod
                        }, function(resp) {
                            $('#editDoc').setData($.extend({}, doc, {
                                Com_Cod_Ant: resp['Com_Cod_Ant'] || '',
                                Ama_Cod_Ant: resp['Ama_Cod_Ant'] || '',
                                Ant_Cod_Ant: resp['Ant_Cod_Ant'] || '',
                                Com_Num_Ant: resp['Com_Num_Ant'] || ''
                            }));
                            // console.log($('#editDoc').getData());
                            $('#For_Cod').val(resp['For_Cod']).trigger('change');
                            $('#clieFormTemp').setData({
                                'Prs_Ced': doc['Prs_Ced']
                            });
                            $.SearchOrDialog('#clieDialog', selectCliente);
                            if (doc['Pec_Cod']) {
                                $('#Pec_Cod').val(doc['Pec_Cod']);
                            } else {
                                var periodo_selec = doc['Vet_Fec'].split("-")[0];
                                $("#Pec_Cod").find('option:contains("' + periodo_selec + '")').prop('selected', true);
                            }
                            //$('#Pec_Cod').trigger('change');
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

                            aBorrar = addItem({});
                            pagos.clearGridData();
                            var aCobrar = $('#Val_Pcc_2').val() * 1;
                            $.each(resp['pagos'], function(x, pago) {
                                //                                    if(resp['pagos'].length*1===1&&aCobrar<0){
                                //                                        pago['Vet_Tot']+=aCobrar*-1;
                                //                                        console.log(pago);
                                //                                    }
                                addPago(pago, true);
                            });
                            if (resp['Iva_Por'] * 1 > 0)
                                $('#Iva_Cod').val($('#Iva_Cod').find('option[data-ivapor=' + resp['Iva_Por'] + ']').val());
                            //updateDocument();
                            items.jqGrid('delRowData', aBorrar);
                            $('#Ret_Fec').val(doc['Ret_Fec']);
                            var botones_pagos = $('#pagosPager_left').find('td.btn-success');
                            var btn_pagos_activos = $('.porCobrar').find('span.input-group-btn');




                            /* Tener pagos activos no impide registrar la retención; se guarda pago_min para el cálculo de abono/excedente */
                            if ((doc['Cpc_Min'] * 1) <= 0) {
                                btn_pagos_activos.addClass('hidden');
                                pago_min = 0;
                            } else {
                                pago_min = doc['Cpc_Min'] * 1;
                                btn_pagos_activos.addClass('hidden');
                            }
                            botones_pagos.removeClass('hidden');
                            //carga de documentos
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
                            var elementos = Object.keys($('#docuFormTemp').getData());
                            [...elementos, "t_descuento", "Iva_Cod", "Vet_Obs"].map(disabledComponentes);
                            igualarPagos();
                        });

                    }
                    $('#searchGrid').createGrid({
                        caption: 'Resultado de la Búsqueda',
                        height: 270,
                        datatype: "local",
                        caption: 'Resultados <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="order by caja_aper.Caj_Fec DESC ">Fecha Venta</option><option value="order by Vet_Num DESC ">Num. Documento</option><select>&nbsp;</div>',
                        colModel: [{
                                label: 'C&oacute;d. Int.',
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

                    $('#OrderBy').on('change', function() {
                        $('input[name=order]').val($(this).val());
                        $('#serachDocDorm').formSubmit();
                    });
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
            <div id="documentoMain">
                <div class="row">
                    <div class="col-xs-12" id="panelVentas">
                        <div class="row">
                            <div id="pagosDialog" title="Agregar Pagos">
                                <form id="pagosForm" class="form-horizontal normal"
                                    action="javascript:addPago($('#pagosForm').getData())">
                                    <div class="form-group">
                                        <label class="col-xs-3 control-label label-xs required">Forma:</label>
                                        <div class="col-xs-6">
                                            <?php $rs_forma = $obBD_con1->getArrayConsulta(89, '', $obBD_conexion); ?>
                                            <select id="For_Cod" name="For_Cod" class="form-control input-xs readOnly"
                                                data-trigger="" required="">
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
                                            <select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs readOnly"
                                                data-trigger="" required="">
                                                <?php
                                                echo "<option value='' data-forcod=''>Seleccione...</option>";
                                                foreach ($rs_tipo as $row) {
                                                    if (!endsWith(strtoupper(trim($row['Pag_Des'])), 'PAGAR') && !startsWith(strtoupper(trim($row['Pag_Des'])), 'CRUCE'))
                                                        echo "<option value='$row[Pag_Cod]' data-forcod='$row[For_Cod]' " . (strtoupper(trim($row['Pag_Des'])) == 'CHEQUE' ? 'disabled=""' : '') . ">$row[Pag_Des]</option>";
                                                } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <?php if ($configs['Cof_Con'] == 'S') { ?>
                                        <div class="form-group cuenta_pago">
                                            <label class="col-xs-3 control-label label-xs">Cuenta:</label>
                                            <div class="col-xs-9">
                                                <select id="Pag_Pld" name="Pag_Pld" class="form-control input-xs readOnly"
                                                    required=""></select>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <!-- bancos en la base de datos -->
                                    <div class="form-group bancos">
                                        <label class="col-xs-3 control-label label-xs required">Banco:</label>
                                        <div class="col-xs-6">
                                            <?php $rs_bancos = $obBD_con1->getArrayConsulta(18, '', $obBD_conexion); ?>
                                            <select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs readOnly"
                                                required="">
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
                                            <select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs readOnly"
                                                data-trigger="" required="">
                                                <?php foreach ($rs_banco as $row) {
                                                    echo "<option value='$row[Ban_Cod]' data-pldcod='$row[Pld_Cod]' data-bancue='$row[Ban_Cue]'>$row[Pld_Des]</option>";
                                                } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group cuen_ban" style="display: none;">
                                        <label class="col-xs-3 control-label label-xs required">Cta&nbsp;Banco:</label>
                                        <div class="col-xs-9">
                                            <input type="text" id="Vet_Cue" name="Vet_Cue" onchange=""
                                                class="form-control input-xs readOnly">
                                        </div>
                                    </div>
                                    <div class="form-group cuen_ban" style="display: none;">
                                        <label class="col-xs-3 control-label label-xs required">N&uacute;mero:</label>
                                        <div class="col-xs-6">
                                            <div class="input-group input-group-xs">
                                                <input type="text" id="Vet_Che" name="Vet_Che" onchange=""
                                                    class="form-control input-xs">
                                                <span class="input-group-addon validate"><i
                                                        class="glyphicon glyphicon-ok green"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($configs['Cof_Con'] == 'S') { ?>
                                        <div class="form-group pagoCredito" style="display: none;">
                                            <input type="text" name="Cpc_Min" style="display:none" />
                                            <label class="col-xs-3 control-label label-xs required">Vencimiento:</label>
                                            <div class="col-xs-6">
                                                <input id="Cpc_Ven" name="Cpc_Ven" type="text"
                                                    class="form-control input-xs datepickers" />
                                            </div>
                                        </div>
                                        <div class="form-group pagoCredito obs_credito" style="display: none;">
                                            <label class="col-xs-3 control-label label-xs">Observaci&oacute;n:</label>
                                            <div class="col-xs-9">
                                                <textarea name="Cpc_Obs" class="form-control input-xs"></textarea>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <div class="form-group saldos">
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-addon bold alert-warning"
                                                    style="width:140px;">Saldo a Cobrar&nbsp;&nbsp;<i
                                                        class="glyphicon glyphicon-arrow-right pull-right"></i></span>
                                                <span class="input-group-addon bold alert-success"><i
                                                        class="glyphicon glyphicon-usd"></i></span>
                                                <input id='saldo_pago' name="Vet_Tot" type="text"
                                                    class="form-control bold span"
                                                    style="text-align: right;font-size: 15px;padding-right: 20px;"
                                                    required="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group saldos">
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-addon bold alert-info"
                                                    style="width:140px;">Monto Dinero&nbsp;&nbsp;<i
                                                        class="glyphicon glyphicon-arrow-right pull-right"></i></span>
                                                <span class="input-group-addon bold alert-success"><i
                                                        class="glyphicon glyphicon-usd"></i></span>
                                                <input id='monto_pago' name="Vet_Mon" type="text"
                                                    class="form-control bold span clearable"
                                                    style="text-align: right;font-size: 15px;padding-right: 20px;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group saldos">
                                        <div class="col-xs-12">
                                            <div class="input-group input-group-sm">
                                                <span id='cam_sal' class="input-group-addon bold alert-danger"
                                                    style="width:140px;"><b>Por Cobrar</b>&nbsp;&nbsp;<i
                                                        class="glyphicon glyphicon-arrow-right pull-right"></i></span>
                                                <span class="input-group-addon bold alert-success"><i
                                                        class="glyphicon glyphicon-usd"></i></span>
                                                <input id='cambio_pago' name="Vet_Cam" type="text"
                                                    class="form-control bold span"
                                                    style="text-align: right;font-size: 15px;padding-right: 20px;"
                                                    readonly="">
                                            </div>
                                            <input class='hidden' id='Vet_Num_Ant' readonly="" />
                                        </div>
                                    </div>
                                    <div class="form-group center">
                                        <button class="btn btn-sm btn-primary"><i
                                                class="glyphicon glyphicon-floppy-disk"></i> Agregar</button>
                                    </div>
                                </form>
                            </div>
                            <form id="formDocumento" class="form-horizontal normal formDatos"
                                action="javascript:validaDocument(true);">
                                <!--ivas-->
                                <select id="Def_Ivas" name="Def_Ivas" class="form-control input-xs"
                                    style="display: none;">
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
                                <select id="pag_cod" name="pag_cod" class="form-control input-xs"
                                    style="display: none;">
                                    <?php if (isset($tipospago))
                                        foreach ($tipospago as $row) { ?>
                                        <option value="<?php echo $row['Pag_Cod']; ?>"
                                            data-forcod="<?php echo $row['For_Cod']; ?>">
                                            <?php echo utf8_decode($row['Pag_Des']); ?>
                                        </option><?php } ?>
                                </select>

                                <!--bancos-->
                                <select id="bak_cod" name="bak_cod" class="form-control input-xs"
                                    style="display: none;">
                                    <?php if (isset($bankos))
                                        foreach ($bankos as $row) { ?>
                                        <option value="<?php echo $row['Bak_Cod']; ?>">
                                            <?php echo utf8_decode($row['Bak_Des']); ?>
                                        </option><?php } ?>
                                </select>

                                <!--cuentas contado=1, credito=2-->
                                <select id="pld_cod" name="pld_cod" class="form-control input-xs"
                                    style="display: none;"></select>

                                <div class="col-md-5 col-xs-12">
                                    <fieldset class="exa-fieldset" id="clieFormTemp" disabled="disabled">
                                        <legend class="Titulos2">Datos del Cliente</legend>
                                        <div class="form-group">
                                            <label
                                                class="col-xs-2 control-label label-xs required">C&eacute;dula/RUC:</label>
                                            <div class="col-xs-7">
                                                <input name="Prs_Cod" type="text" style="display:none;" />
                                                <input name="Prs_Cor" type="text" style="display:none;" />
                                                <input name="Cli_Cod" type="text" style="display:none;" />
                                                <input name="op_opciones" type="text" value="c" style="display: none;">
                                                <div class="input-group input-group-xs">
                                                    <input name="Prs_Ced"
                                                        onkeydown="if (event.keyCode === 13) $.SearchOrDialog('#clieDialog',selectCliente);"
                                                        type="text" placeholder="Ingrese Cliente..."
                                                        class="form-control input-xs datatrigger dialogSearch"
                                                        tabindex="1" required="" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Cliente:</label>
                                            <div class="col-xs-10"><span name="cliente"
                                                    class="form-control input-xs databind datatitle"></span></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Direcci&oacute;n:</label>
                                            <div class="col-xs-4"><span name="Prs_Dir" type="text"
                                                    class="form-control input-xs databind datatitle"></span></div>
                                            <label class="col-xs-1 control-label label-xs">Correo:</label>
                                            <div class="col-xs-5"><span name="Prs_Cor" type="text"
                                                    class="form-control input-xs databind datatitle"></span></div>
                                        </div>

                                    </fieldset>

                                    <?php $bodegas = $obBD_con1->getArrayConsulta('bodega.1', array('Suc_Cod' => $Ses_Suc_Cod, 'Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion); ?>
                                    <fieldset class="exa-fieldset" <?php if (count($bodegas) == 0) echo 'style="display:none; "'; ?>>
                                        <legend class="Titulos2"></legend>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs">Bodega:</label>
                                            <div class="col-xs-10">
                                                <select id="Bod_Cod" name="Bod_Cod" class="form-control input-xs"
                                                    disabled="true">
                                                    <?php if (count($bodegas) > 0)
                                                        foreach ($bodegas as $row) {
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
                                            <label class="col-xs-2 control-label label-xs">Per&iacute;odo:</label>
                                            <div class="col-xs-2">
                                                <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs">
                                                    <?php $rs_perio = $obBD_con1->getArrayConsulta(5, $Ses_Emp_Cod, $obBD_conexion);
                                                    foreach ($rs_perio as $row) { ?>
                                                        <option value="<?php echo $row['Pec_Cod']; ?>"
                                                            data-inicio="<?php echo $row['Pec_Fei']; ?>"
                                                            data-fin="<?php echo $row['Pec_Fef']; ?>"
                                                            data-PlaCod="<?php echo $row['Pla_Cod']; ?>">
                                                            <?php echo $row['Anio']; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <label class="col-xs-1 control-label label-xs">Fecha:</label>
                                            <div class="col-xs-3">
                                                <input type="text" id="Caj_Fec" name="Caj_Fec"
                                                    class="form-control input-xs datepickers readOnly">
                                            </div>
                                            <label class="col-xs-1 control-label label-xs">Ciudad:</label>
                                            <div class="col-xs-3">
                                                <?php $Ciu_Des = $obBD_con1->getRowConsulta(6, $Ses_Usu_Cod, $obBD_conexion); ?>
                                                <input type="hidden" id="Ciu_Cod" name="Ciu_Cod"
                                                    value="<?php echo $Ciu_Des['Ciu_Cod'] ?>">
                                                <span name="Ciu_Des"
                                                    class="form-control input-xs"><?php echo $Ciu_Des['Ciu_Des'] ?></span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">Docum.:</label>
                                            <div class="col-xs-10">
                                                <select id="Tic_Cod" name="Tic_Cod"
                                                    class="form-control input-xs readOnly" required=""></select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label
                                                class="col-xs-2 control-label label-xs required">N&uacute;mero:</label>
                                            <div class="col-xs-5">
                                                <div class="input-group input-group-xs">
                                                    <span id="Pun_Sri" name="Pun_Sri"
                                                        class="input-group-addon alert-info"></span>
                                                    <input type="text" id="Vet_Num" name="Vet_Num"
                                                        onchange="validarTic_Cod()"
                                                        class="form-control input-xs trigger" tabindex="5" required=""
                                                        data-container="body" data-toggle="popover" />
                                                    <span class="input-group-addon validate"><i></i></span>
                                                </div>
                                            </div>

                                            <label class="col-xs-1 control-label label-xs">Aut.:</label>
                                            <div class="col-xs-3 input-group input-group-xs">
                                                <span id="Aut_Sri" name="Aut_Sri"
                                                    class="form-control input-xs databind"></span>
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
                                        <legend class="Titulos2">Datos de la Retenci&oacute;n</legend>
                                        <input type="text" name="Ret_Cod" style="display: none;" id="Ret_Cod" />
                                        <input type="text" name="Ret_Xml" style="display: none;" />
                                        <input type="text" name="Aut_Cod" style="display: none;" id="Aut_Cod_Old" />
                                        <div class="form-group">
                                            <label
                                                class="col-xs-2 control-label label-xs required">N&uacute;mero:</label>
                                            <div class="col-xs-4">
                                                <input type="text" name="Aut_Tem" style="display: none;" required="" />
                                                <div class="input-group input-group-xs">
                                                    <input id="Ret_Num" name="Ret_Num" type="text"
                                                        class="form-control input-xs ret_field" />
                                                    <span class="input-group-addon validate"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-xs-2 control-label label-xs required">Autoriza:</label>
                                            <div class="col-xs-4">
                                                <input name="Ret_Aut_Sri" class="form-control input-xs ret_field"
                                                    required="" />
                                            </div>
                                            <label class="col-xs-2 control-label label-xs required">Fecha:</label>
                                            <div class="col-xs-4">
                                                <div class="input-group">
                                                    <input id="Ret_Fec" name="Ret_Fec" type="text"
                                                        class="form-control input-xs readOnly ret_field datepickers"
                                                        required=""
                                                        pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                                                    <span class="input-group-addon input-xs"
                                                        title="Fecha de la Retención"><i
                                                            class="glyphicon glyphicon-info-sign blue"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group reteTot cod_banano" style="display:none;">
                                            <label class="col-xs-2 control-label label-xs required">Banano:</label>
                                            <div class="col-xs-8">
                                                <div class="input-group input-group-xs">
                                                    <span class="input-group-addon bold alert-warning">&nbsp;Cod.
                                                        338&nbsp;&nbsp;<i
                                                            class="glyphicon glyphicon-arrow-right"></i>&nbsp;</span>
                                                    <span class="input-group-addon bold alert-success"
                                                        title="Cajas de Banano">Cajas:</span>
                                                    <input name="Ret_Uca" type="text" class="form-control span"
                                                        style="text-align: right;" pattern="\d*" placeholder="0" />
                                                    <span class="input-group-addon bold alert-success"
                                                        title="Precio Unitario por Caja">P.Unit.:</span>
                                                    <input name="Ret_Pca" type="text" class="form-control span"
                                                        style="text-align: right;" pattern="\d*" placeholder="0.00" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group reteTot">
                                            <label class="col-xs-2 control-label label-xs"></label>
                                            <div class="col-xs-10">
                                                <div class="input-group input-group-xs">
                                                    <span class="input-group-addon bold alert-info">Renta:</span>
                                                    <input name="Ret_Ren_Tot" type="text" class="form-control span"
                                                        style="text-align: right;" readonly="" />
                                                    <span class="input-group-addon bold alert-info">+&nbsp;IVA:</span>
                                                    <input name="Iva_Ren_Tot" type="text" class="form-control span"
                                                        style="text-align: right;" readonly="" />
                                                    <span
                                                        class="input-group-addon bold alert-info">=&nbsp;Retenido:</span>
                                                    <input id="Ren_Tot" name="Ren_Tot" type="text"
                                                        class="form-control span" style="text-align: right;"
                                                        readonly="" />
                                                    <span class="input-group-btn">
                                                        <button type="button"
                                                            onclick="$('#retDetaDialog').dialog('open')"
                                                            class="btn btn-info" title="Ver Detalle Retención"
                                                            tabindex="-1"><span
                                                                class="glyphicon glyphicon-eye-open"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group reteTot">
                                            <label class="col-xs-5 control-label label-xs"></label>
                                            <div class="col-xs-7">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-addon bold alert-warning">Monto a
                                                        Pagar&nbsp;&nbsp;<i
                                                            class="glyphicon glyphicon-arrow-right"></i></span>
                                                    <span class="input-group-addon bold alert-success"><i
                                                            class="glyphicon glyphicon-usd"></i></span>
                                                    <input id="Val_Pcc" name="Val_Pcc" type="text"
                                                        class="form-control bold span"
                                                        style="text-align: right;font-size: 15px; background-color: white;"
                                                        readonly="" />
                                                    <span id="infoLiquida" class="input-group-addon validate"
                                                        style="display:none;"><i></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                </form>
                            </div>
                            <div class="col-md-5 col-xs-12">
                                <form id="pagoFormTemp" action="javascript:" class="formDatos form-horizontal normal">
                                    <fieldset class="exa-fieldset">
                                        <legend class="Titulos2">Forma de Pago</legend>
                                        <input type="text" name="Cpc_Cod" style="display: none;" />
                                        <div class="form-group pagoSri">
                                            <label
                                                class="col-xs-3 control-label label-xs required">Pago&nbsp;SRI:</label>
                                            <div class="col-xs-9">
                                                <?php $rs_pag_sri = $obBD_con1->getArrayConsulta(45, '', $obBD_conexion); ?>
                                                <select id="Tpc_Cod" name="Tpc_Cod" defaultValue=1
                                                    class="form-control input-xs readOnly" disabled="disabled"
                                                    required="" onchange="">
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
                                                    <span class="input-group-addon bold alert-warning">Por
                                                        Cobrar&nbsp;&nbsp;<i
                                                            class="glyphicon glyphicon-arrow-right"></i></span>
                                                    <span class="input-group-addon bold alert-success"><i
                                                            class="glyphicon glyphicon-usd"></i></span>
                                                    <input id="Val_Pcc_2" name="Val_Pcc_2" type="text"
                                                        class="form-control bold span"
                                                        style="text-align: right;font-size: 15px; background-color: white;"
                                                        readonly="" tabindex="-1">

                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-info"
                                                            onclick="$('.porCobrar').find('span.input-group-btn').flyout('show').focus();"
                                                            tabindex="-1"><span
                                                                class="fa fa-money white"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>


                                    <div class="alert alert-success"> <i class="fa fa-info-circle" style="font-size:1.7em;"></i>   
                                    Los valores correspondientes a las retenciones se registran automáticamente como anticipos 
                                    del cliente y se aplican de forma automática cuando la factura presenta un saldo pendiente de pago. </div>


                                </form>
                            </div>


                        </div>
                        <div class="row center-block">
                            <div class="col-md-7 col-xs-12">
                                <div class="condensed /*hidden*/" style="min-height: 100px; padding-bottom: 5px;">
                                    <table id="pagos"></table>
                                    <div id="pagosPager"></div>
                                </div>
                                <div>
                                    <button class="black btn btn-sm btn-inverse"
                                        onclick="clearDocument();$('#searchGrid').trigger('reloadGrid');$('#documentoMain').moveComp('#documentoSearch').updateGridsSizes();"><i
                                            class="glyphicon glyphicon-arrow-left"></i>Atr&aacute;s</button>
                                    <button class="btn btn-sm btn-primary"
                                        onclick="$('#formDocumento').formSubmit();"><i
                                            class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 Titulos2">
                        <hr><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span
                            class="required"></span>) son campos obligatorios.
                    </div>
                </div>
            </div>
            <div id="documentoResult" class="form-horizontal normal" style="visibility: hidden;">
                <div class="row">
                    <div class="col-xs-6" id="resultContent">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Resultado De la Transacci&oacute;n</legend>
                            <div>
                                <h4 style="text-align: center; font-weight: 900;">El Documento se guardo con
                                    &eacute;xito!</h4>
                                <p class="form-control-static resp" name="Tic_Des"></p>
                                <p class="resp"><span>&raquo;Fec:</span><span style="color:coral;" class="databind"
                                        name="Vet_Fec"></span></p>
                                <p class="resp"><span>&raquo;Num:</span><span style="color:teal;" class="databind"
                                        name="Vet_Num"></span></p>
                                <p class="resp"><span>&raquo;Cod:</span><span style="color: #CE0000;" class="databind"
                                        name="Vet_Cod"></span></p>
                                <div style="padding-top: 15px; text-align: center;">
                                    <button class="btn btn-sm btn-success"
                                        onclick="clearDocument();$('#searchGrid').trigger('reloadGrid');$('#documentoResult').moveComp('#documentoSearch').updateGridsSizes();"><i
                                            class="glyphicon glyphicon-search"></i> Buscar Documento</button>
                                    <button class="btn btn-sm btn-success" name="Vet_Impr" id="Vet_Impr"
                                        onclick="$.imprimirUrl($(this).data('url'))"><i
                                            class="glyphicon glyphicon-print"></i> Imprimir Documento</button>
                                    <!--button class="btn btn-sm btn-success" name="btnComPrint" id="btnComPrint" onclick="$.imprimirUrl($(this).data('url'))"><i class="glyphicon glyphicon-print"></i> Imprimir Documento</button-->
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
                label: 'C&oacute;dula/RUC',
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

    <div id="autorizaDialog" title="B&uacute;squeda de Autorizaciones">
        <form class="form-horizontal normal" id="autorizaForm">
            <input type="text" name="Tic_Cod" class="hidden" />
            <input type="text" name="Pun_Cod" class="hidden" />
        </form>
    </div>

    <!-- Inicio del di�logo para registrar clientes -->
    <div id="clieCreateDialog" title="Registrar Cliente" style="display:none;">
        <form class="form-horizontal normal" id="clieCreateForm"
            action="javascript:if(validaNoIdentif($('#Prs_Ced').val())['success']){ guardaProvee(); }else{ $('#Prs_Ced').flyout('show').focus() }">
            <input name="Prs_Cod" type="text" class="hidden" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos del Cliente</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">C&eacute;dula/RUC:</label>
                    <div class="col-xs-5">
                        <div class="input-group input-group-xs">
                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs"
                                onchange="if(validaNoIdentif(this.value)['success']){ $('#Ide_Cod').val(this.value.length===10?2:1); $('#Cli_Tic').val(validaNoIdentif(this.value)['tipo_abrev']==='NA'?'N':'J').trigger('change'); $(this).fieldValid(true); searchCliente(this.value); }else{ $('#Ide_Cod').val(''); $('#Cli_Tic').val(''); $(this).fieldValid(false,validaNoIdentif(this.value)['message']); };"
                                required="" />
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
                        <select id="Cli_Tic" name="Cli_Tic" class="form-control input-xs" required=""
                            onchange="if(this.value==='N'){ $('.juridico').hide();$('.natural').show(); }else{ $('.natural').hide();$('.juridico').show(); }">
                            <option value="N">NATURAL</option>
                            <option value="J">JURIDICO</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span
                            class='juridico' style="display: none;">Razón Social:</span></label>
                    <div class="col-xs-9"><input name="Prs_Ape" type="text" class="form-control input-xs" required="" />
                    </div>
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
                    <div class="col-xs-9"><input name="Prs_Dir" type="text" class="form-control input-xs" required="" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Tel&eacute;fono:</label>
                    <div class="col-xs-4"><input name="Prs_Tel" type="text" class="form-control input-xs" required=""
                            pattern="\d*" /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Mail:</label>
                    <div class="col-xs-5"><input name="Prs_Cor" type="mail" class="form-control input-xs" required="" />
                    </div>
                </div>
            </fieldset>
            <div class="center">
                <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i>
                    Guardar</button>
            </div>
            <div class="Titulos2">
                <hr><b>NOTA:</b> Los campos marcados con un asterisco ( <span class="required"></span>) son campos
                obligatorios.
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
                label: 'Categor&iacute;a',
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
    <div id="changeReteDialog" title="Cambiar valor de Retenci&oacute;n" style="display:none;">
        <form class="form-horizontal normal" id='form_change_rete' action="javascript:CambiarRetencion(this)">
            <input type="text" name="index" class="hidden">

            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Valor:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs"><span class="input-group-addon">$</span><input
                            class="form-control input-xs " name="Ret_Valor" id="valor_ret"
                            onkeyup="calcularPorcentaje(this)" type="text" size="10" maxlength="12"
                            style="text-align:right" onkeypress="return  validar_decimal(event)" placeholder="0.00" />
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label label-xs required">Porcentaje:</label>
                <div class="col-xs-8">
                    <div class="input-group input-group-xs"><input class="form-control input-xs nospin"
                            name="Ret_Ren_Por" id="porcentaje_ret" type="number" size="10" maxlength="12"
                            style="text-align:right" onkeypress="return  validar_decimal(event)" required
                            placeholder="0.00" min=1 max=2 step=any /><span class="input-group-addon">%</span></div>
                </div>
            </div>
            <div class="center">
                <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i>
                    Guardar</button>
            </div>
        </form>
    </div>
    <!-- FIN DEL DIALOGO PRODUCTO-->
    <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR-->
    <div id="provDialog" title="B&uacute;squeda de Proveedor">
        <form class="form-horizontal normal"> </form>
    </div>
    <script>
        function selectProvee(provee) {
            $('#clieFormTemp').setData($.extend(provee, {
                op_opciones: 'c'
            })).find('.dialogSearch').addClass('x');
            $('#Prv_Con').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Con'] === 'S' ? 'ok green' : 'remove blue'));
            $('#Prv_Esp').removeAttr('class').addClass('glyphicon glyphicon-' + (provee['Prv_Esp'] === 'S' ? 'ok green' : 'remove blue'));
            $('#provDialog').dialog('close');
            checkLiquidacion();
            validaCopNum();
        }
    </script>
    <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
    <div id="codiDialog" title="B&uacute;squeda de C&oacute;digos Retenci&oacute;n">
        <form class="form-horizontal normal"><input type="text" name="Pla_Cod" class="placod" style="display: none;" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                    <div class="col-xs-7 radioset">
                        <input id="radc3" name="op_opciones" type="radio" value="p" onclick="setfocus(this.form.search)"
                            alt="" data-trigger="" /><label for="radc3">&nbsp;&nbsp;Porcentaje %&nbsp;&nbsp;</label>
                        <input id="radc1" name="op_opciones" type="radio" value="d" checked=""
                            onclick="setfocus(this.form.search)" alt="" /><label
                            for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
                        <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)"
                            alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>
                    </div>
                    <div class="col-xs-3" style="text-align: right;">
                        <input type="text" name="tipo" class="hidden" />
                        <input type="text" name="index" class="hidden" />
                        <div class="checkbox check-big">
                            <label><input name="checkRentaIva" type="checkbox" value="S" offval="N">Aplicar a
                                Todos</label>
                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
    <script>
        $.createSearchDialog('codiDialog', [{
                label: 'C&oacute;d.Int.',
                name: 'Ren_Cod',
                key: true,
                width: 25,
                align: "center"
            },
            {
                label: 'C&oacute;digo',
                name: 'Ren_Sri',
                width: 25,
                align: "center"
            },
            {
                label: 'Descripci&oacute;n',
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
                    action: agregaRetencion2,
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
    <div id="provCreateDialog" title="Registrar Proveedor" style="display:none;">
        <form class="form-horizontal normal" id="provCreateForm"
            action="javascript:if(validaNoIdentif($('#Prs_Ced').val())['success']){ guardaProvee(); }else{ $('#Prs_Ced').flyout('show').focus() }">
            <input name="Prs_Cod" type="text" class="hidden" />
            <fieldset class="exa-fieldset">
                <legend class="Titulos2">Datos del Proveedor</legend>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">C&eacute;dula/RUC:</label>
                    <div class="col-xs-5">
                        <div class="input-group input-group-xs">
                            <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-xs"
                                onchange="if(validaNoIdentif(this.value)['success']){ $('#Ide_Cod').val(this.value.length===10?2:1); $('#Prv_Tic').val(validaNoIdentif(this.value)['tipo_abrev']==='NA'?'N':'J').trigger('change'); $(this).fieldValid(true); searchProvee(this.value); }else{ $('#Ide_Cod').val(''); $('#Prv_Tic').val(''); $(this).fieldValid(false,validaNoIdentif(this.value)['message']); };"
                                required="" />
                            <span class="input-group-addon validate"><i></i></span>
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
                        <select id="Prv_Tic" name="Prv_Tic" class="form-control input-xs" required=""
                            onchange="if(this.value==='N'){ $('.juridico').hide();$('.natural').show(); }else{ $('.natural').hide();$('.juridico').show(); }">
                            <option value="N">NATURAL</option>
                            <option value="J">JURIDICO</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required"><span class='natural'>Apellidos:</span><span
                            class='juridico' style="display: none;">Razón Social:</span></label>
                    <div class="col-xs-9"><input name="Prs_Ape" type="text" class="form-control input-xs" required="" />
                    </div>
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
                    <label class="col-xs-3 control-label label-xs required">Nomb.Comerc.:</label>
                    <div class="col-xs-9"><input name="Prv_Com" type="text" class="form-control input-xs" required="" />
                    </div>
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
                                echo "<option value='$row[Ciu_Cod]' data-prov='$row[Pro_Nom]'>$row[Ciu_Des]</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Direcci&oacute;n:</label>
                    <div class="col-xs-9"><input name="Prs_Dir" type="text" class="form-control input-xs" required="" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Tel&oacute;fono:</label>
                    <div class="col-xs-4"><input name="Prs_Tel" type="text" class="form-control input-xs" required=""
                            pattern="\d*" /></div>
                </div>
                <div class="form-group">
                    <label class="col-xs-3 control-label label-xs required">Mail:</label>
                    <div class="col-xs-5"><input name="Prs_Cor" type="mail" class="form-control input-xs" required="" />
                    </div>
                </div>
            </fieldset>
            <div class="center">
                <button type="submit" class="btn btn-sm btn-success no"><i class="glyphicon glyphicon-floppy-disk"></i>
                    Guardar</button>
            </div>
            <div class="Titulos2">
                <hr><b>NOTA:</b> Los campos marcados con un asterisco ( <span class="required"></span>) son campos
                obligatorios.
            </div>
        </form>

    </div>
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
            height: 430
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
            };

            var opts = {
                height: 75,
                caption: 'Detalle Retención',
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
        });
    </script>
    <!-- DIALOGO seleccion Autorizacion -->
    <!--Fin DETALLE Autorizaciones -->
    <!-- DIALOGO DETALLE DOCUMENTO -->
    <div id="docDetaDialog" title="Documento">
        <fieldset class="exa-fieldset">
            <legend class="Titulos2">Documento:</legend>
            <div class="form-horizontal normal" style="padding: 0 4px;">
                <div class="form-group">
                    <label class="col-xs-2 control-label label-xs">C&oacute;dula/RUC:</label>
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
                    <div class="col-xs-12" style="text-align: right;font-size: 8px;padding-top: 2px;"><b>CREACIÓN:</b>
                        <span name="Vet_Sys" class="databind"></span> &nbsp;&nbsp;-&nbsp;&nbsp; <b>USUARIO:</b> <span
                            name="vendedor_per" class="databind"></span>
                    </div>
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
                    <label class="col-xs-2 control-label label-xs">Autorizaci&oacute;n.:</label>
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
    <script>
        $(function() {
            var opts = {
                height: 75,
                postData: {
                    CheListAjax: true
                },
                caption: 'Detalle Venta',
                colModel: [{
                        label: 'C&oacute;d.Int.',
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
        // $('#detaDocu').createGrid($.extend(opts,{height:'auto',width:550,responsive:false,caption:null,rownumbers:false}),true);           
    </script>
    <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
    <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
    <script>
        $.clearValidate();
    </script>
    <link rel="stylesheet" type="text/css" media="screen"
        href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script type="text/javascript"
        src="../../framework/jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
    <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.min.css" />
    <script type="text/javascript" src="../../framework/jquery/bootstrap/popover/jquery.flyout.min.js"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
    <link type="text/css" rel="stylesheet" href="../../mascaras/model1/estilos/print.css" media="print" />
</BODY>

</HTML>