<?php

/**
 * @abstract Permite realizar la modificacion de Anticipos Manuales
 * @author Edison Moya
 * @version 1.0
 * Fecha de creacion  2017-12-06
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cccc_lotes_2.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion_set = new Class_Log_Conexion_Cccc($Ses_Dat_Dis);
$obBD_con_set =  new Class_Log_Datos_Cccc;

// $obBD_con_set->getPageGrid("asientos.selectWhere",array('rows'=>1000,'page'=>1,'where'=>array('Com_Cod'=>10933),'order'=>'asientos.Asi_Deh asc'), $obBD_conexion_set, true);
// var_dump($obBD_con_set->select("asientos.selectWhere",array('Com_Cod'=>10933,'order'=>'asientos.Asi_Deh asc'), $obBD_conexion_set));

$obBD_conexion_get = new Class_Log_Conexion_Cccc($Ses_Dat_Dis);
$obBD_con_get =  new Class_Log_Datos_Cccc;

//fecha y mes actuales
$hoy = date("Y-m-d");
$mes = date("m");

//para obtener planes de cuenta para agregar aportaciones
if (isset($cuentasAjax)) {
  $obBD_con_get->getPageGridJson(14, $_GET, $obBD_conexion_get);
}

//seccion para obtener los clientes registrados en la empresa
if (isset($clientesAjax)) {
  $obBD_con_get->getPageGridJson(1, $_GET, $obBD_conexion_get);
}

// obtenemos las facturas por cobrar de el cliente seleccionado
if (isset($ajaxComprobante)) {
  if (empty($_GET['Cli_Cod'])) {
    $responce['rows'] = array();
    $responce['records'] = 0;
    $obBD_con_get->echoJson($responce);
    exit();
  }
  $responce['rows'] = $obBD_con_get->getArrayConsulta(2, $_GET, $obBD_conexion_get);
  if (!is_array($responce['rows'])) {
      $responce['rows'] = array();
  }
  // foreach ($responce['rows'] as $key => $item){
  //     if ($item['Abono'] == $item['Asi_Val']) unset($responce['rows'][$key]);
  // }
  // $responce['rows']=array_values($responce['rows']);
  $responce['records'] = count($responce['rows']);
  $obBD_con_get->echoJson($responce);
  exit();
}

//obtenemos todos los pagos de una factura de un cliente
if (isset($abonosDetAjax)) {
  $responce['rows'] = $obBD_con_get->getArrayConsulta(3, $abonosDetAjax, $obBD_conexion_get);
  $responce['records'] = count($responce['rows']);
  $obBD_con_get->echoJson($responce);
  exit();
}

if (isset($getPagoIniAjax)) {
  try {
    $response['success'] = false;

    // $response['data'] = $obBD_con_get->getRowConsulta(8, "", $obBD_conexion_get);
    $response['data'] = $obBD_con_get->getRowConsulta(8, $Pld_Cdc, $obBD_conexion_get);

    if ($obBD_con_get->Error == 0) {
      $response['success'] = true;
    }
  } catch (Exception $e) {
    $obBD_con_set->rollBack_nomsn($obBD_conexion_set);
    $response['success'] = false;
    $response['message'] = '<span class="red">ERROR:</span> ' . $e->getMessage();
  }

  $obBD_con_get->echoJson($response);
  exit();
}

//verificamos si el numero de un cheque ya esta registrado dentro de la tabla cheques
if (isset($verificarCheNum)) {
  $Bak_Cod = isset($_REQUEST['Bak_Cod']) ? $_REQUEST['Bak_Cod'] : null;
  $Cli_Cod = isset($_REQUEST['Cli_Cod']) ? $_REQUEST['Cli_Cod'] : null;
  $Che_Num = isset($_REQUEST['Che_Num']) ? $_REQUEST['Che_Num'] : null;

  //Se obtiene el socio seleccionado
  $response['numero_che'] = false;
  $num_Ches = $obBD_con_get->getArrayConsulta(11, array('Bak_Cod' => $Bak_Cod, 'Cli_Cod' => $Cli_Cod), $obBD_conexion_get);
  foreach ($num_Ches as $nch) {
    if ($nch['Che_Num'] == $Che_Num) {
      $response['numero_che'] = true;
    }
  }

  $obBD_con_set->echoJson($response);
  exit();
}

if (isset($getAsientosAbono)) {
  //$obBD_con_get->debug(true);
  $response['success'] = false;

  $response['data'] = $obBD_con_get->getArrayConsulta(12, array('Com_Cod' => $Com_Cod), $obBD_conexion_get);
  $response['data_che'] = $obBD_con_get->getArrayConsulta(13, array('Com_Cod' => $Com_Cod), $obBD_conexion_get);

  if ($obBD_con_get->Error == 0) {
    $response['success'] = true;
  }

  $obBD_con_get->echoJson($response);
  exit();
}

if (isset($enableDisableCampos)) {
  $tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : '';
  $Cli_Cod = isset($_REQUEST['Cli_Cod']) ? $_REQUEST['Cli_Cod'] : null;
  //Se obtiene el cuentas diferentes para tipos de pagos
  //$obBD_con_get->debug(true);
  $response['success'] = false;

  if ($tipo == "ANT") {
    $response['data'] = $obBD_con_get->getArrayConsulta(15, "", $obBD_conexion_get);

    //obtenemos los anticipos del cliente
    $anticipos_cnt = $obBD_con_get->getRowConsulta(18, array('Cli_Cod' => $Cli_Cod), $obBD_conexion_get);
    //obtenemos la cantidad de anticipos utilizados por el cliente
    $det_anticipos_cnt = $obBD_con_get->getRowConsulta(19, array('Cli_Cod' => $Cli_Cod), $obBD_conexion_get);
    if (count($anticipos_cnt) > 0) {
      $response['data_ant'] = $anticipos_cnt['tot_anti'] - $det_anticipos_cnt['tot_dac'];
    } else {
      $response['data_ant'] = 'none';
    }
  } elseif ($tipo == "CDC") {
    $response['data'] = $obBD_con_get->getArrayConsulta(16, "", $obBD_conexion_get);

    $prv_cli_cod = $obBD_con_get->getRowConsulta(20, array('Cli_Cod' => $Cli_Cod), $obBD_conexion_get);
    $ccc_cnt = $obBD_con_get->getArrayConsulta(21, array('Prv_Cod' => $prv_cli_cod['Prv_Cod']), $obBD_conexion_get);

    if (count($ccc_cnt) > 0) {
      $total = 0;
      $abono = 0;
      foreach ($ccc_cnt as $deu) {
        (float)$total += (float)$deu['Asi_Val'];
        (float)$abono += (float)$deu['Abono'];
      }
      $response['data_cdc'] = (float)$total - (float)$abono;
    } else {
      $response['data_cdc'] = 'none';
    }
  } elseif ($tipo == "EFE" || $tipo == "OTR" || $tipo == "NDC") {
    $response['data'] = $obBD_con_get->getArrayConsulta(17, array('Ban_Tip' => 'C'), $obBD_conexion_get);
  } elseif ($tipo == "TDC" || $tipo == "TRF" || $tipo == "DEP") {
    $response['data'] = $obBD_con_get->getArrayConsulta(17, array('Ban_Tip' => 'B'), $obBD_conexion_get);
  } elseif ($tipo == "CHE") {
    $response['data'] = $obBD_con_get->getArrayConsulta(17, array('Ban_Tip' => 'B'), $obBD_conexion_get);
  }

  if ($obBD_con_get->Error == 0) {
    $response['success'] = true;
  }
  $obBD_con_get->echoJson($response);
  exit();
}

//Guardar los pagos ingresados
if (isset($modPago)) {
  $save_p = isset($_REQUEST['save_p']) ? $_REQUEST['save_p'] : array();
  $save_cp = isset($_REQUEST['save_cp']) ? $_REQUEST['save_cp'] : array();
  $Com_Fec = isset($_REQUEST['Com_Fec']) ? $_REQUEST['Com_Fec'] : date('Y-m-d');
  $Pec_Cod = isset($_REQUEST['Pec_Cod']) ? $_REQUEST['Pec_Cod'] : null;
  $agg_Cli_Cod = isset($_REQUEST['agg_Cli_Cod']) ? $_REQUEST['agg_Cli_Cod'] : null;
  $Com_Cod = isset($_REQUEST['Com_Cod']) ? $_REQUEST['Com_Cod'] : null;
  $Com_Num = isset($_REQUEST['Com_Num']) ? $_REQUEST['Com_Num'] : '';
  $Tia_Cod = isset($_REQUEST['Tia_Cod']) ? $_REQUEST['Tia_Cod'] : null;
  $Tia_Cod_temp = isset($_REQUEST['Tia_Cod_temp']) ? $_REQUEST['Tia_Cod_temp'] : null;
  $Com_Con = isset($_REQUEST['Com_Con']) ? $_REQUEST['Com_Con'] : '';
  $Com_Val = isset($_REQUEST['Com_Val']) ? $_REQUEST['Com_Val'] : 0;
  $Com_Val_temp = isset($_REQUEST['Com_Val_temp']) ? $_REQUEST['Com_Val_temp'] : 0;
  $Com_Obs = isset($_REQUEST['Com_Obs']) ? $_REQUEST['Com_Obs'] : '';
  $Num_Doc = isset($_REQUEST['Num_Doc']) ? $_REQUEST['Num_Doc'] : null;
  $Dcc_Cod = isset($_REQUEST['Dcc_Cod']) ? $_REQUEST['Dcc_Cod'] : null;

  //$obBD_con_set->debug(true);
  $obBD_con_get->validaCierrePeriodo('det_ccpp_c', 'Cpc_Fec', 'Dcc_Cod', $Com_Fec, $Dcc_Cod, $obBD_conexion_set);
  try {
    $response['success'] = false;
    $obBD_con_set->inicio_transaccion($obBD_conexion_set->conexion);
    //generamos el numero de comprobante
    $var_mes = explode('-', $Com_Fec);
    if ($Tia_Cod != $Tia_Cod_temp) {
      $Com_Num = $obBD_con_get->codigoComprAuto($Tia_Cod, $Pec_Cod, $var_mes[1], $obBD_conexion_get);
    }

    $Proveedor = $obBD_con_get->getArrayConsulta(20, array('Cli_Cod' => $agg_Cli_Cod), $obBD_conexion_get);
    $Prv_Cod = 'null';
    if (count($Proveedor) > 0) {
      foreach ($Proveedor as $prv) {
        $Prv_Cod = $prv['Prv_Cod'];
      }
    }

    //modificamos en comprobante del pago
    $obBD_con_set->operacionobBD(38, array('Com_Cod' => $Com_Cod, 'Pec_Cod' => $Pec_Cod, 'Com_Num' => $Com_Num, 'Com_Fec' => $Com_Fec, 'Com_Con' => $Com_Con, 'Com_Val' => $Com_Val, 'Com_Obs' => $Com_Obs, 'Tia_Cod' => $Tia_Cod, 'Num_Doc' => $Num_Doc), $obBD_conexion_set);

    //eliminamos todos los asientos que no sean de un cheque protestado
    $obBD_con_set->operacionobBD(39, array('Com_Cod' => $Com_Cod), $obBD_conexion_set);

    //eliminamos todos los cheques que no sean protestados
    $obBD_con_set->operacionobBD(40, array('Com_Cod' => $Com_Cod), $obBD_conexion_set);

    //eliminamos todos los pagos de la factura ligados a este comprobante
    $obBD_con_set->operacionobBD(43, array('Com_Cod' => $Com_Cod), $obBD_conexion_set);

    $cntcpp = 0;
    foreach ($save_p as $pago) {
      if ($pago['grid_tipp'] == 'pago') {
        // insertamos un asiento por cada pago
        $obBD_con_set->operacionobBD(23, array('Com_Cod' => $Com_Cod, 'Asi_Deh' => 'D', 'Asi_Con' => $pago['concepto'], 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => $pago['Debe'], 'Pld_Cod' => $pago['Pld_Cod']), $obBD_conexion_set);
        $ultimo_asiento = $obBD_con_set->insercionid($obBD_conexion_set);

        //********************************************************************************************************************
        if ($pago['Pag_Abr'] == 'CHE') {
          //insertamos un cheque
          $obBD_con_set->operacionobBD(31, array('Bak_Cod' => $pago['Bak_Cod'], 'Cli_Cod' => $agg_Cli_Cod, 'Che_Cta' => $pago['Pac_Cto'], 'Che_Num' => $pago['Che_Num'], 'Che_Fec' => $pago['Che_Fec'], 'Che_Val' => $pago['Debe'], 'Che_Obs' => $pago['Glosa'], 'Che_Cli' => $pago['Che_Cli']), $obBD_conexion_set);
          $ultimo_cheque = $obBD_con_set->insercionid($obBD_conexion_set);
        }

        $var_pag = (float)$pago['Debe'];
        //var_dump($save_cp[''.intval($cntcpp)]['Cpc_Val']);

        //*********************************************************************************
        // Cuando se modifica el pago donde intervienen 2 o mas facturas y a su ves le asignamos un valor mayor al original,  
        // da Error en la reparticion del nuevo valor del pago, la pantalla le asigna todo el valor a la primera factura aun que esta tenga un valor inferior x cobrar
        if ($Com_Val * 1 > $Com_Val_temp * 1) {
          while ($var_pag != "none") {
            //echo "--> ".$save_cp[$cntcpp]['Cpc_Cod']." | ".(float)$save_cp[$cntcpp]['Cpc_Val']." | ".$pago['Pag_Cod']." | ".$var_pag."\n";
            // echo "-------\n";
            // var_dump((float)$save_cp[''.intval($cntcpp)]['Cpc_Val']);
            // var_dump((float)$save_cp[''.intval($cntcpp)]['Saldo']);
            // echo ((float)$save_cp[''.intval($cntcpp)]['Cpc_Val']+(float)$save_cp[''.intval($cntcpp)]['Saldo'])."\n";
            if ($save_cp['' . intval($cntcpp)]['Cpc_Val'] == 0) {
              $var_pag = "none";
            } elseif ($var_pag < ((float)$save_cp['' . intval($cntcpp)]['Cpc_Val'] + (float)$save_cp['' . intval($cntcpp)]['Saldo'])) {
              $obBD_con_set->operacionobBD(24, array('Cpc_Cod' => $save_cp['' . intval($cntcpp)]['Cpc_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => $var_pag, 'Cpc_Obs' => $Com_Con, 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
              $ultimo_dcc_cod = $obBD_con_set->insercionid($obBD_conexion_set);
              $valgrd = $var_pag;

              if ($pago['Pag_Abr'] == 'CHE') {
                $obBD_con_set->operacionobBD(30, array('Che_Cod' => $ultimo_cheque, 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
              }

              $save_cp['' . intval($cntcpp)]['Cpc_Val'] = (float)(((float)$save_cp['' . intval($cntcpp)]['Cpc_Val'] + (float)$save_cp['' . intval($cntcpp)]['Saldo']) - $var_pag);
              $var_pag = "none";
            } elseif ($var_pag == ((float)$save_cp['' . intval($cntcpp)]['Cpc_Val'] + (float)$save_cp['' . intval($cntcpp)]['Saldo'])) {
              $obBD_con_set->operacionobBD(24, array('Cpc_Cod' => $save_cp['' . $cntcpp]['Cpc_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => $var_pag, 'Cpc_Obs' => $Com_Con, 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
              $ultimo_dcc_cod = $obBD_con_set->insercionid($obBD_conexion_set);
              $valgrd = $var_pag;

              if ($pago['Pag_Abr'] == 'CHE') {
                $obBD_con_set->operacionobBD(30, array('Che_Cod' => $ultimo_cheque, 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
              }

              $var_pag = "none";
              $cntcpp++;
            } elseif ($var_pag > ((float)$save_cp['' . intval($cntcpp)]['Cpc_Val'] + (float)$save_cp['' . intval($cntcpp)]['Saldo'])) {
              $obBD_con_set->operacionobBD(24, array('Cpc_Cod' => $save_cp['' . intval($cntcpp)]['Cpc_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => ($var_pag - ($var_pag - ((float)$save_cp['' . intval($cntcpp)]['Cpc_Val'] + (float)$save_cp['' . intval($cntcpp)]['Saldo']))), 'Cpc_Obs' => $Com_Con, 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
              $ultimo_dcc_cod = $obBD_con_set->insercionid($obBD_conexion_set);
              $valgrd = ($var_pag - ($var_pag - ((float)$save_cp['' . intval($cntcpp)]['Cpc_Val'] + (float)$save_cp['' . intval($cntcpp)]['Saldo'])));

              if ($pago['Pag_Abr'] == 'CHE') {
                $obBD_con_set->operacionobBD(30, array('Che_Cod' => $ultimo_cheque, 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
              }

              $var_pag = $var_pag - ((float)$save_cp['' . intval($cntcpp)]['Cpc_Val'] + (float)$save_cp['' . intval($cntcpp)]['Saldo']);
              $cntcpp++;
            }

            if ($pago['Pag_Abr'] == 'ANT') {
              $anticipos_no_utl = $obBD_con_set->getArrayConsulta(25, array('Cli_Cod' => $agg_Cli_Cod), $obBD_conexion_set);
              $cnt_arr = 0;
              $contador_ant = 0;
              foreach ($anticipos_no_utl as $ant) {
                $ant_utilizados = $obBD_con_set->getRowConsulta(26, array('Cli_Cod' => $agg_Cli_Cod, 'Ant_Cod' => $ant['Ant_Cod']), $obBD_conexion_set);
                $ant_utl_val = (float)$ant['Ant_Val'] - (float)$ant_utilizados['tot_dac'];
                if ((float)$ant_utl_val < (float)$valgrd) {
                  $obBD_con_set->operacionobBD(27, array('Ant_Cod' => $ant['Ant_Cod'], 'Ant_Est' => "C"), $obBD_conexion_set);
                  $obBD_con_set->operacionobBD(28, array('Ddc_Val' => (float)$ant_utl_val, 'Ddc_Obs' => $pago['Glosa'], 'Ant_Cod' => $ant['Ant_Cod'], 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
                  $valgrd = (float)$valgrd - (float)$ant_utl_val;
                } elseif ((float)$ant_utl_val == (float)$valgrd) {
                  $obBD_con_set->operacionobBD(27, array('Ant_Cod' => $ant['Ant_Cod'], 'Ant_Est' => "C"), $obBD_conexion_set);
                  $obBD_con_set->operacionobBD(28, array('Ddc_Val' => (float)$valgrd, 'Ddc_Obs' => $pago['Glosa'], 'Ant_Cod' => $ant['Ant_Cod'], 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
                  break;
                } elseif ((float)$ant_utl_val > (float)$valgrd) {
                  $obBD_con_set->operacionobBD(27, array('Ant_Cod' => $ant['Ant_Cod'], 'Ant_Est' => "U"), $obBD_conexion_set);
                  $obBD_con_set->operacionobBD(28, array('Ddc_Val' => (float)$valgrd, 'Ddc_Obs' => $pago['Glosa'], 'Ant_Cod' => $ant['Ant_Cod'], 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
                  break;
                }
              }
            }
          }
        } //*************************************************************** 
        else {
          while ($var_pag != "none") {
            // echo "--> ".$save_cp[$cntcpp]['Cpc_Cod']." | ".(float)$save_cp[$cntcpp]['Cpc_Val']." | ".$pago['Pag_Cod']." | ".$var_pag."\n";
            if ($save_cp['' . intval($cntcpp)]['Cpc_Val'] == 0) {
              $var_pag = "none";
            } elseif ($var_pag < (float)$save_cp['' . intval($cntcpp)]['Cpc_Val']) {
              $obBD_con_set->operacionobBD(24, array('Cpc_Cod' => $save_cp['' . intval($cntcpp)]['Cpc_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => $var_pag, 'Cpc_Obs' => $Com_Con, 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
              $ultimo_dcc_cod = $obBD_con_set->insercionid($obBD_conexion_set);
              $valgrd = $var_pag;

              if ($pago['Pag_Abr'] == 'CHE') {
                $obBD_con_set->operacionobBD(30, array('Che_Cod' => $ultimo_cheque, 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
              }

              $save_cp['' . intval($cntcpp)]['Cpc_Val'] = (float)((float)$save_cp['' . intval($cntcpp)]['Cpc_Val'] - $var_pag);
              $var_pag = "none";
            } elseif ($var_pag == (float)$save_cp['' . intval($cntcpp)]['Cpc_Val']) {
              $obBD_con_set->operacionobBD(24, array('Cpc_Cod' => $save_cp['' . $cntcpp]['Cpc_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => $var_pag, 'Cpc_Obs' => $Com_Con, 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
              $ultimo_dcc_cod = $obBD_con_set->insercionid($obBD_conexion_set);
              $valgrd = $var_pag;

              if ($pago['Pag_Abr'] == 'CHE') {
                $obBD_con_set->operacionobBD(30, array('Che_Cod' => $ultimo_cheque, 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
              }

              $var_pag = "none";
              $cntcpp++;
            } elseif ($var_pag > (float)$save_cp['' . intval($cntcpp)]['Cpc_Val']) {
              $obBD_con_set->operacionobBD(24, array('Cpc_Cod' => $save_cp['' . intval($cntcpp)]['Cpc_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Cpc_Fec' => $Com_Fec, 'Cpc_Val' => ($var_pag - ($var_pag - (float)$save_cp['' . intval($cntcpp)]['Cpc_Val'])), 'Cpc_Obs' => $Com_Con, 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
              $ultimo_dcc_cod = $obBD_con_set->insercionid($obBD_conexion_set);
              $valgrd = ($var_pag - ($var_pag - (float)$save_cp['' . intval($cntcpp)]['Cpc_Val']));

              if ($pago['Pag_Abr'] == 'CHE') {
                $obBD_con_set->operacionobBD(30, array('Che_Cod' => $ultimo_cheque, 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
              }

              $var_pag = $var_pag - (float)$save_cp['' . intval($cntcpp)]['Cpc_Val'];
              $cntcpp++;
            }

            if ($pago['Pag_Abr'] == 'ANT') {
              $anticipos_no_utl = $obBD_con_set->getArrayConsulta(25, array('Cli_Cod' => $agg_Cli_Cod), $obBD_conexion_set);
              $cnt_arr = 0;
              $contador_ant = 0;
              foreach ($anticipos_no_utl as $ant) {
                $ant_utilizados = $obBD_con_set->getRowConsulta(26, array('Cli_Cod' => $agg_Cli_Cod, 'Ant_Cod' => $ant['Ant_Cod']), $obBD_conexion_set);
                $ant_utl_val = (float)$ant['Ant_Val'] - (float)$ant_utilizados['tot_dac'];
                if ((float)$ant_utl_val < (float)$valgrd) {
                  $obBD_con_set->operacionobBD(27, array('Ant_Cod' => $ant['Ant_Cod'], 'Ant_Est' => "C"), $obBD_conexion_set);
                  $obBD_con_set->operacionobBD(28, array('Ddc_Val' => (float)$ant_utl_val, 'Ddc_Obs' => $pago['Glosa'], 'Ant_Cod' => $ant['Ant_Cod'], 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
                  $valgrd = (float)$valgrd - (float)$ant_utl_val;
                } elseif ((float)$ant_utl_val == (float)$valgrd) {
                  $obBD_con_set->operacionobBD(27, array('Ant_Cod' => $ant['Ant_Cod'], 'Ant_Est' => "C"), $obBD_conexion_set);
                  $obBD_con_set->operacionobBD(28, array('Ddc_Val' => (float)$valgrd, 'Ddc_Obs' => $pago['Glosa'], 'Ant_Cod' => $ant['Ant_Cod'], 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
                  break;
                } elseif ((float)$ant_utl_val > (float)$valgrd) {
                  $obBD_con_set->operacionobBD(27, array('Ant_Cod' => $ant['Ant_Cod'], 'Ant_Est' => "U"), $obBD_conexion_set);
                  $obBD_con_set->operacionobBD(28, array('Ddc_Val' => (float)$valgrd, 'Ddc_Obs' => $pago['Glosa'], 'Ant_Cod' => $ant['Ant_Cod'], 'Dcc_Cod' => $ultimo_dcc_cod), $obBD_conexion_set);
                  break;
                }
              }
            }
          }
        }

        //en caso de ser pago con cruce de cuentas se genera los debidos detalles de cuentas por cobrar
        if ($pago['Pag_Abr'] == 'CDC') {
          $ccc_cnt = $obBD_con_set->getArrayConsulta(21, array('Prv_Cod' => $Prv_Cod), $obBD_conexion_set);
          (float)$cont_ccc = 0;
          $cnt_destino = (float)$pago['Debe'];
          foreach ($ccc_cnt as $deu) {
            if ($cont_ccc < $cnt_destino) {
              if (($cont_ccc + (float)$deu['saldo']) <= $cnt_destino) {
                //insertamos un pago a cuentas por cobrar
                $obBD_con_set->operacionobBD(29, array('Cpp_Cod' => $deu['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => $deu['saldo'], 'Pag_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
                $cont_ccc += (float)$deu['saldo'];
              }
              if (($cont_ccc + (float)$deu['saldo']) > $cnt_destino && ($cnt_destino - $cont_ccc) * 1 > 0) {
                //insertamos un pago a cuentas por cobrar
                $obBD_con_set->operacionobBD(29, array('Cpp_Cod' => $deu['Cpp_Cod'], 'Pag_Cod' => $pago['Pag_Cod'], 'Com_Cod' => $Com_Cod, 'Pag_Fec' => $Com_Fec, 'Pag_Val' => ($cnt_destino - $cont_ccc), 'Pag_Obs' => $pago['Glosa'], 'Asi_Cod' => $ultimo_asiento), $obBD_conexion_set);
                $cont_ccc += ($cnt_destino - $cont_ccc);
              }
            }
          }
        }
      } else {
        // insertamos un asiento por defecto para el pago a clientes
        $obBD_con_set->operacionobBD(23, array('Com_Cod' => $Com_Cod, 'Asi_Deh' => 'H', 'Asi_Con' => $pago['concepto'], 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => $pago['Haber'], 'Pld_Cod' => $pago['Pld_Cod']), $obBD_conexion_set);
      }
    }

    $response['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$Com_Cod&tabla=clientes&campo=Cli_Cod&tipo=$Tia_Cod&Pec_Cod=$Pec_Cod";
    $response['link2'] = "./tes_pri_recibocobro_1.1.php?Com_Cod=$Com_Cod";

    $obBD_con_set->fin_transaccion_nomsn($obBD_conexion_set->conexion);

    if ($obBD_con_set->Error == 0) {
      $response['success'] = true;
      $response['error'] = $obBD_con_set->MsgError;
    }
    //throw new Exception('Prueba SQL!');
  } catch (Exception $e) {
    $obBD_con_set->rollBack_nomsn($obBD_conexion_set);
    $response['success'] = false;
    $response['message'] = '<span class="red">ERROR:</span> ' . $e->getMessage();
  }
  $obBD_con_set->echoJson($response);
  exit();
}

//obtenemos las facturas incluidas en el pago a un determinado abono por lotes o individual
if (isset($getFactsAbono)) {
  $response['success'] = false;
  $response['message'] = "No se ha logrado realizar la Transaccion";

  $response['data'] = $obBD_con_get->getArrayConsulta(36, array('Com_Cod' => $Com_Cod, 'Prv_Cod' => $Prv_Cod), $obBD_conexion_get);

  if ($obBD_con_get->Error == 0) {
    $response['success'] = true;
  }

  $obBD_con_get->echoJson($response);
  exit();
}

//obtenemos todos los pagos de un abono
if (isset($getPagsAbono)) {
  //$obBD_con_get->debug(true);
  $response['success'] = false;

  $response['data'] = $obBD_con_get->getArrayConsulta(37, array('Com_Cod' => $Com_Cod), $obBD_conexion_get);

  $anticipos_cnt = $obBD_con_get->getRowConsulta(18, array('Cli_Cod' => $Cli_Cod), $obBD_conexion_get);
  $det_anticipos_cnt = $obBD_con_get->getRowConsulta(19, array('Cli_Cod' => $Cli_Cod), $obBD_conexion_get);
  if (count($anticipos_cnt) > 0) {
    $response['data_ant'] = $anticipos_cnt['tot_anti'] - $det_anticipos_cnt['tot_dac'];
  } else {
    $response['data_ant'] = 'none';
  }

  $prv_cli_cod = $obBD_con_get->getRowConsulta(20, array('Cli_Cod' => $Cli_Cod), $obBD_conexion_get);
  $ccc_cnt = $obBD_con_get->getArrayConsulta(21, array('Prv_Cod' => $prv_cli_cod['Prv_Cod'], 'Pec_Cod' => $Pec_Cod), $obBD_conexion_get);
  if (count($ccc_cnt) > 0) {
    $total = 0;
    $abono = 0;
    foreach ($ccc_cnt as $deu) {
      (float)$total += (float)$deu['Asi_Val'];
      (float)$abono += (float)$deu['Abono'];
    }
    $response['data_ccc'] = (float)$total - (float)$abono;
  } else {
    $response['data_ccc'] = 'none';
  }

  if ($obBD_con_get->Error == 0) {
    $response['success'] = true;
  }

  $obBD_con_get->echoJson($response);
  exit();
}

if (isset($delAbono)) {
  $response['success'] = false;
  $obBD_con_get->validaCierrePeriodo('det_ccpp_c', 'Cpc_Fec', 'Dcc_Cod', $Com_Fec, $Dcc_Cod, $obBD_conexion_set);

  //Revertir el anticipo ( det_ant_cccc, anticipo_cliente, pag_ant_cli)
  //Obtengo los codigos de los anticipos vinculados al cobro anulado
  $codigos_anticipos = $obBD_con_get->getArrayConsulta(55, array('Dcc_Cod' => $fila['Dcc_Cod']), $obBD_conexion_get);
  if (count($codigos_anticipos) > 0) {
    foreach ($codigos_anticipos as $cod) {
      //Actualizo el estado del anticipo 
      /*Busco en los detalles de anticipos si devuelve mas de un registro con un Dcc_Cod distinto 
        el estado es 'U' caso contrario 'A' */
      $anticipos_diferentes = $obBD_con_get->getArrayConsulta(56, array('Ant_Cod' => $cod['Ant_Cod'], 'Dcc_Cod' => $fila['Dcc_Cod']), $obBD_conexion_get);
      if (count($anticipos_diferentes) > 0) {
        //Estado U en anticipo_clientes y pag_ant_cli
        $obBD_con_set->operacionobBD(27, array('Ant_Cod' => $cod['Ant_Cod'], 'Ant_Est' => "U"), $obBD_conexion_set);
        $obBD_con_set->operacionobBD(57, array('Ant_Cod' => $cod['Ant_Cod'], 'Pac_Est' => "U"), $obBD_conexion_set);
      } else {
        //Estado A en anticipo_clientes y pag_ant_cli
        $obBD_con_set->operacionobBD(27, array('Ant_Cod' => $cod['Ant_Cod'], 'Ant_Est' => "A"), $obBD_conexion_set);
        $obBD_con_set->operacionobBD(57, array('Ant_Cod' => $cod['Ant_Cod'], 'Pac_Est' => "A"), $obBD_conexion_set);
      }
      //Elimino el detalle de det_ant_cliente
      $obBD_con_set->operacionobBD(58, array('Ant_Cod' => $cod['Ant_Cod'], 'Dcc_Cod' => $fila['Dcc_Cod']), $obBD_conexion_set);
    }
  }

  try {
    //cambiamos a estado 'A' los cheques del pago, estos quedan disponibles para liberar o reutilizar
    $obBD_con_set->operacionobBD(41, array('Com_Cod' => $Com_Cod), $obBD_conexion_set);
    //cambiamos a estado 'I' el comprobante del pago
    $obBD_con_set->operacionobBD(42, array('Com_Cod' => $Com_Cod), $obBD_conexion_set);

    if ($obBD_con_set->Error == 0) {
      $response['success'] = true;
    }
  } catch (Exception $e) {
    $obBD_con_set->rollBack_nomsn($obBD_conexion_set);
    $response['success'] = false;
    $response['message'] = '<span class="red">ERROR:</span> ' . $e->getMessage();
  }

  $obBD_con_set->echoJson($response);
  exit();
}

?>
<!DOCTYPE html>
<html>

<head>
  <!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
  <TITLE><?php echo "Ccxcc Modificar [EXA]"; ?></TITLE>
  <meta charset="UTF-8">
  <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
  <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
  <style>
    .txt-green {
      color: #29a827;
    }

    .txt-red {
      color: #ff0000;
    }

    .txt-blue {
      color: #467de8;
    }

    .obs-mayus {
      text-transform: uppercase;
    }

    .btn-sg-pg {
      padding-right: 2;
    }

    #searchGrid_mod .no_padding {
      padding: 0 !important;
    }

    #searchGrid_mod .no_padding input[type="text"] {
      height: 23px;
      font-size: 14px;
      font-weight: bold;
      -moz-appearance: textfield !important;
    }

    #searchGrid_mod .no_padding input[type="text"]::-webkit-outer-spin-button,
    #searchGrid_mod .no_padding input[type="text"]::-webkit-inner-spin-button {
      -webkit-appearance: none !important;
      margin: 0 !important;
    }

    #searchGrid_mod input[type="text"]:read-only {
      background-color: #a2a2a2;
      border: none;
    }
  </style>
</head>

<body>
  <div class="panel panel-main">
    <div class="panel-heading exa-header">
      <h3 class="panel-title">&raquo; Modificar cobros por lotes a clientes</h3>
    </div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
      <div id="listar_cccc">
        <div class="row">
          <form name="searchCccc" id="searchCccc" class="form-horizontal normal" action="javascript:$('#searchGrid_mod').Search('#searchCccc','ajaxComprobante');">
            <div class="col-sm-6">
              <fieldset class="exa-fieldset">
                <legend class="Titulos2">Seleccionar Cliente</legend>
                <div class="form-group">
                  <label class="col-sm-4 control-label label-sm">C&eacute;dula/RUC:</label>
                  <div class="col-sm-6">
                    <input name="Cli_Cod" id="Cli_Cod" type="text" style="display:none;" />
                    <input name="tip_trans" id="tip_trans" value="mod" type="text" style="display:none;" />

                    <div class="input-group input-group-xs">
                      <input name="Prs_Ced" id="Prs_Ced" type="text" placeholder="Seleccione o cree un cliente..." class="form-control input-xs" tabindex="1" readonly />
                      <span class="input-group-btn">
                        <button type="button" onclick="$('#clientesDialog').dialog('open');" class="btn btn-success btn-xs" title="Seleccionar Proveedor" tabindex="2"><span class="glyphicon glyphicon-list-alt"></span></button>
                      </span>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-4 control-label label-xs">Cliente:</label>
                  <div class="col-sm-6"><input name="nombre" id="nombre" class="form-control input-xs databind datatitle" readonly /></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-4 control-label label-xs">Direcci&oacute;n:</label>
                  <div class="col-sm-6"><input name="Prs_Dir" id="Prs_Dir" type="text" class="form-control input-xs databind datatitle" readonly /></div>
                </div>
              </fieldset>
            </div>
            <div class="col-sm-6">
              <fieldset class="exa-fieldset">
                <legend class="Titulos2">Filtros</legend>
                <div class="form-group">
                  <label class="col-xs-2 control-label label-xs">Por Periodo:</label>
                  <input type="text" name="por_peri" id="por_peri" value="n" style="display:none">
                  <div class="col-xs-2">
                    <div class="input-group">
                      <span class="input-group-addon" style="padding:2px 5px 0px 5px;margin:0;line-height:0;">
                        <input type="checkbox" id="f_periodo" name="f_periodo" onchange="cambiarFiltro()">
                      </span>
                      <select class="form-control input-xs" name="sel_per" id="sel_per" onchange="cambioPreiodoSearch('peri')" disabled>
                        <?php $periodos_rows = $obBD_con_get->getArrayConsulta(4, "", $obBD_conexion_get);
                        if (count($periodos_rows) > 0) {
                          foreach ($periodos_rows as $row) {
                            echo "<option value='$row[Pec_Cod]' data-inicio='$row[Pec_Fei]' data-fin='$row[Pec_Fef]'>$row[anio]</option>";
                          }
                        }
                        ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-xs-6">
                    <div class="input-group input-group-xs">
                      <span class="input-group-addon bold alert-info">Desde:</span>
                      <input disabled onchange="cambioPreiodoSearch('txt')" name="txt_fec_ini" type="text" id="txt_fec_ini" size="10" class="form-control input-xs datepicker databind" style="text-align: center;" />
                      <span class="input-group-addon bold alert-info">Hasta:</span>
                      <input disabled name="txt_fec_fin" type="text" id="txt_fec_fin" size="10" class="form-control input-xs datepicker databind" style="text-align: center;" />
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-xs-2 control-label label-xs">Filtrar Por:</label>
                  <div class="col-xs-8 radioset opt_search">
                    <input id="radsc1" name="op_opciones" type="radio" value="p" checked="" onclick="setfocus(this.form.search)" onchange="setSelVen('T')" alt="" /><label for="radsc1">Todos&nbsp;</label>
                    <input id="radsc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" onchange="setSelVen('V')" alt="" /><label for="radsc2">Vencidos</label>
                    <input id="radsc3" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)" onchange="setSelVen('P')" alt="" /><label for="radsc3">Por Vencer</label>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-xs-2 control-label">B&uacute;squeda:</label>
                  <div class="col-xs-5">
                    <div class="input-group">
                      <select class="form-control input-xs" name="sel_ven" id="sel_ven">
                        <option value="1">Todos</option>
                      </select>
                      <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Documento" tabindex="-1"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
                    </div>
                  </div>
                </div>
              </fieldset>
            </div>
          </form>
        </div>
        <div class="row">
          <div class="col-sm-12">
            <table id="searchGrid_mod" name="searchGrid_mod"></table>
            <div id="sgPager"></div>
            <div class="Titulos2">
              <span id="plan-footer"><strong>Leyenda:</strong>
                <span class="glyphicon glyphicon-stop" style="color:#ff8a8a;"></span> Vencidos
                <span class="glyphicon glyphicon-stop" style="color:#80ff00;"></span> Pagados
              </span>
            </div>
            <br>
          </div>
        </div>
      </div>
      <div id="agregar_cccc" hidden>
        <div class="row">
          <div class="col-sm-12">
            <div class="row">
              <form class="form-horizontal normal" name="formPagos" id="formPagos" method="post" action="javascript:preModificarPago()">
                <input name="Com_Cod" id="Com_Cod" value="add" type="text" style="display:none;" />
                <div class="col-sm-12">
                  <div class="row">
                    <div class="col-sm-6">
                      <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos del asiento y cliente</legend>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-sm required" for="Pec_Cod">Periodo contable:</label>
                            <div class="col-sm-2">
                              <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" required="" onchange="setFecPeriodoCom()">
                                <?php $rows_periodos = $obBD_con_get->getArrayConsulta(5, "", $obBD_conexion_get);
                                if (count($rows_periodos) > 0) {
                                  foreach ($rows_periodos as $row) {
                                ?>
                                <?php echo "<option value='$row[Pec_Cod]' data-pla-cod='$row[Pla_Cod]' data-pec-fei='$row[Pec_Fei]' data-pec-fef='$row[Pec_Fef]'  data-periodo='$row[priodo_m]'>$row[priodo_m]</option>"; ?>

                                <?php }
                                } ?>
                              </select>
                            </div>
                            <div id="btn_view_facts" class="col-sm-6" style="text-align:right;">
                              <a onclick="$('#verFactsDialog').dialog('open');" title="Facturas incluidas en este abono" class="btn btn-xs btn-success"><span class="glyphicon glyphicon-eye-open"></span> Ver Facturas</a>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-sm required" for="Tia_Cod">Tipo Comprobante:</label>
                            <div class="col-sm-4">
                              <input name="Tia_Cod_temp" id="Tia_Cod_temp" type="text" style="display:none;" />
                              <input name="Com_Num" id="Com_Num" type="text" style="display:none;" />
                              <select id="Tia_Cod" name="Tia_Cod" class="form-control input-xs" required="" onchange="">
                                <?php $row_rs_tipo_asien2 = $obBD_con_get->getArrayConsulta(6, "ALL", $obBD_conexion_get);
                                foreach ($row_rs_tipo_asien2 as $row) { ?>
                                  <option value="<?php echo $row['Tia_Cod']; ?>" data-abr="<?php echo $row['Tia_Abr']; ?>"><?php echo utf8_encode($row['Tia_Des']) ?> </option>
                                <?php } ?>
                              </select>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-sm">C&eacute;dula/RUC:</label>
                            <div class="col-sm-4">
                              <input name="agg_Cli_Cod" id="agg_Cli_Cod" type="text" style="display:none;" />
                              <input name="agg_Prs_Ced" id="agg_Prs_Ced" type="text" class="form-control input-xs" tabindex="1" readonly />
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-xs">Proveedor:</label>
                            <div class="col-sm-6"><input name="agg_nombre" id="agg_nombre" class="form-control input-xs databind datatitle" readonly /></div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-xs required" for="Com_Con">Concepto:</label>
                            <div class="col-sm-6">
                              <textarea id="Com_Con" name="Com_Con" class="form-control input-xs obs-mayus" style="resize: none;" required=""></textarea>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-xs required" for="Com_Obs">Observaci&oacute;n:</label>
                            <div class="col-sm-6">
                              <textarea id="Com_Obs" name="Com_Obs" class="form-control input-xs obs-mayus" style="resize: none;" required=""></textarea>
                            </div>
                          </div>
                        </div>
                      </fieldset>
                    </div>
                    <div class="col-sm-6">
                      <fieldset class="exa-fieldset">
                        <legend class="Titulos2">Datos del pago</legend>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-xs required" for="Com_Fec">Fecha del comprobante:</label>
                            <div class="col-sm-2">
                              <input id="Com_Fec" name="Com_Fec" class="form-control input-xs datepicker" placeholder="yy-mm-dd" type="text" />
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-sm required">Tipo de pago:</label>
                            <div class="col-sm-3">
                              <select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs" onchange="enableDisableCampos();">
                                <?php $row_rs_tipo_asien2 = $obBD_con_get->getArrayConsulta(7, "", $obBD_conexion_get);
                                foreach ($row_rs_tipo_asien2 as $row) {
                                  if ($row['Pag_Abr'] != 'ANT') { ?>
                                    <option value="<?php echo utf8_encode($row['Pag_Cod']); ?>" data-abr="<?php echo utf8_encode($row['Pag_Abr']); ?>"><?php echo utf8_encode($row['Pag_Des']) ?> </option>
                                <?php }
                                } ?>
                              </select>
                            </div>
                            <div id="cont_anticipo_info" class="col-sm-6 txt-blue" style="padding-left:0;" hidden>
                              <span class="glyphicon glyphicon-info-sign" style="font-size:10px;" title="Cant. disponible en anticipos para este proveedor"></span>
                              <label class="control-label label-xs">Disponible $ <span id="anticipo_info">0.00</span></label>
                            </div>
                            <div id="cont_ccc_info" class="col-sm-6 txt-blue" style="padding-left:0;" hidden>
                              <a class="btn btn-info btn-xs" onclick="$('#comprasDialog').dialog('open');"><span class="glyphicon glyphicon-random"></span>&nbsp; Compras</a>
                              <span class="glyphicon glyphicon-info-sign" style="font-size:10px;" title="Cant. disponible para el cruce de cuentas con este proveedor"></span>
                              <label class="control-label label-xs">Deuda del proveedor $ <span id="ccc_info">0.00</span></label>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-sm required" for="Ban_Cod">Acreditar a:</label>
                            <div class="col-sm-4">
                              <select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs ed_element ed_CHE ed_TRF ed_TDC ed_DEP ed_EFE ed_NDC ed_OTR ed_ANT ed_CDC" required="" onchange="" disabled>
                                <?php $row_rs_tipo_asien2 = $obBD_con_get->getArrayConsulta(10, array('Ban_Tip' => 'B'), $obBD_conexion_get);
                                foreach ($row_rs_tipo_asien2 as $row) { ?>
                                  <option value="<?php echo $row['Ban_Cod']; ?>" data-des="<?php echo $row['Pld_Des']; ?>" data-cue="<?php echo $row['Ban_Cue']; ?>" data-cdc="<?php echo $row['Pld_Cdc']; ?>" data-pla="<?php echo $row['Pld_Cod']; ?>"><?php echo $row['Pld_Des'] ?> - <?php echo $row['Ban_Cue'] ?></option>
                                <?php } ?>
                              </select>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-sm required" for="Bak_Cod">Banco:</label>
                            <div class="col-sm-4">
                              <select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs ed_element ed_CHE ed_TRF" required="" onchange="$('#Che_Num').trigger('onkeyup');" disabled>
                                <?php $row_rs_tipo_asien2 = $obBD_con_get->getArrayConsulta(9, "", $obBD_conexion_get);
                                foreach ($row_rs_tipo_asien2 as $row) { ?>
                                  <option value="<?php echo $row['Bak_Cod']; ?>" data-des="<?php echo $row['Pld_Des']; ?>" data-cdc="<?php echo $row['Pld_Cdc']; ?>" data-pla="<?php echo $row['Pld_Cod']; ?>"><?php echo $row['Bak_Des'] ?></option>
                                <?php } ?>
                              </select>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-xs required">No. Cuenta:</label>
                            <div class="col-sm-4">
                              <input type="text" id="Che_Cta" name="Che_Cta" class="form-control input-xs ed_element ed_CHE" autocomplete="off" disabled>
                            </div>
                          </div>
                        </div>

                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-xs" for="Num_Doc">Nro Documento:</label>
                            <div class="col-sm-2">
                              <input id="Num_Doc" name="Num_Doc" class="form-control input-xs ed_elemento ed_TRF" placeholder="002554" type="text" disabled />
                            </div>
                          </div>
                        </div>

                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-xs required" for="Che_Fec">Fecha del cheque:</label>
                            <div class="col-sm-2">
                              <input id="Che_Fec" name="Che_Fec" class="form-control input-xs datepicker ed_element ed_CHE" placeholder="yy-mm-dd" type="text" disabled />
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-xs required">No. cheque:</label>
                            <div class="col-sm-2">
                              <div class="input-group input-group-xs">
                                <input type="text" id="Che_Num" name="Che_Num" onchange="" class="form-control input-xs ed_element ed_CHE" onkeyup="verificarNoCheque(this.value)" onkeypress="return soloNumeros(event)" autocomplete="off" disabled>
                                <span class="input-group-addon"><i id="indicadorChe" class=""></i></span>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-xs required">Beneficiario:</label>
                            <div class="col-sm-4">
                              <input type="text" id="Che_Ben_N" name="Che_Ben_N" class="form-control input-xs ed_element ed_CHE" autocomplete="off" disabled>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="form-group">
                            <label class="col-sm-3 control-label label-xs required" for="Com_Val_pago">Valor:</label>
                            <div class="col-sm-2">
                              <input id="Cpp_Cod" name="Cpp_Cod" type="text" hidden />
                              <input id="PgabrEx" name="PgabrEx" type="text" hidden />
                              <input id="Com_Val" name="Com_Val" type="text" hidden />
                              <input id="Com_Val_temp" name="Com_Val_temp" type="text" hidden />
                              <input id="Com_Val_dism" name="Com_Val_dism" type="text" value="none" hidden />
                              <input id="lim_val_pago" name="lim_val_pago" type="text" value="none" hidden />
                              <input id="lim_val_pago_cc" name="lim_val_pago_cc" type="text" value="none" hidden />
                              <input id="Com_Val_pago" name="Com_Val_pago" class="form-control input-xs" type="text" onchange="cambioValPago($(this));" onkeypress="return  validar_decimal(event)" autocomplete="off" />
                            </div>
                            <div class="col-sm-6 txt-blue" style="padding-left:0;">
                              <span class="glyphicon glyphicon-info-sign" style="font-size:10px;" title="Debe completar esta cantidad para realizar el pago"></span>
                              <label class="control-label label-xs" title="Debe completar esta cantidad para realizar el pago">Valor a pagar $ <span id="saldo_info1">0.00</span> - </label>
                              <label id="saldo_info" class="control-label label-xs txt-red">(Total agregado: $ <span id="saldo_info2">0.00</span>)</label>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-sm-6">
                          </div>
                          <div class="col-sm-6">
                            <a class="btn btn-success btn-xs" onclick="preAddPago()"><span class="glyphicon glyphicon-arrow-down"></span> Agregar pago</a>
                          </div>
                        </div>
                      </fieldset>
                    </div>
                  </div>
                </div>
              </form>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <table id="pagosGrid" name="pagosGrid"></table>
                <table id="pagosGridPager"></table>
                <div class="Titulos2">
                  <span id="plan-footer"><strong>Leyenda:</strong>
                    <span class="glyphicon glyphicon-stop" style="color:#ff8a8a;"></span>Cheques Protestados
                  </span>
                </div>
                <br>
                <div class="">
                  <a class="btn btn-inverse btn-xs" onclick="limpiarPagos();moveToList();"><span class="glyphicon glyphicon-arrow-left"></span> Atras</a>
                  <a class="btn btn-success btn-xs" onclick="$('#formPagos').formSubmit();"><span class="glyphicon glyphicon-book"></span> Guardar</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="verPagosDialogMod" title="Pagos">
    <div class="row">
      <div class="col-sm-12">
        <fieldset class="exa-fieldset">
          <legend class="Titulos2">Datos del Abono</legend>
          <form id="verPagosForm" class="form-horizontal normal">
            <div class="row">
              <div class="col-sm-7">
                <div class="form-group">
                  <label class="col-xs-4 control-label label-xs">Proveedor:</label>
                  <div class="col-xs-8">
                    <input type="text" id="cli_show" class="form-control input-xs" readonly>
                    <input type="text" id="Com_Cod_view" style="display:none">
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-xs-4 control-label label-xs">No. Compr.:</label>
                  <div class="col-xs-8">
                    <input type="text" id="compr_show" class="form-control input-xs" readonly>
                  </div>
                </div>
                <br>
                <div class="form-group">
                  <div class="col-sm-4"></div>
                  <div class="col-xs-8">
                    <a id="impCanc" target="_blank" href="" style="display: inline;" title="Imprimir Comprobante de Cancelaci&oacute;n"><span class="btn btn-primary btn-xs start"> <i class="glyphicon glyphicon-print"></i> <span>Impr. Compr. de cancelaci&oacute;n</span></span> </a>
                  </div>

                </div>
              </div>
              <div class="col-sm-5">
                <div class="form-group">
                  <label class="col-xs-4 control-label label-xs">C&eacute;dula/R.U.C.:</label>
                  <div class="col-xs-8">
                    <input type="text" id="ruc_show" class="form-control input-xs" readonly>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-xs-4 control-label label-xs">Fecha:</label>
                  <div class="col-xs-8">
                    <input type="text" id="fec_show" class="form-control input-xs" readonly>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </fieldset>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-12">
        <fieldset class="exa-fieldset">
          <legend class="Titulos2">Observaci&oacute;n</legend>
          <div class="form-group">
            <div class="col-xs-12">
              <textarea id="obs_show" class="form-control input-xs" readonly></textarea>
            </div>
          </div>
        </fieldset>
      </div>
    </div>
    <br>
    <div class="row">
      <div class="col-sm-12">
        <div id="tabs_abo_det" class="ui-tab-fix">
          <ul style="font-size: 12px;" role="tablist">
            <li id="ant_detasi"><a href="#ant_det_asi">Asientos</a></li>
            <li id="ant_detche"><a href="#ant_det_che">Cheques</a></li>
          </ul>
          <div id="ant_det_asi">
            <div class="row">
              <div class="col-sm-12" style="padding-top: 10px;">
                <table id="showPagosAsi" name="showPagosAsi"></table>
              </div>
            </div>
          </div>
          <div id="ant_det_che">
            <div class="row">
              <div class="col-sm-12" style="padding-top: 10px;">
                <table id="showPagosChe" name="showPagosChe"></table>
                <div class="Titulos2">
                  <span id="plan-footer"><strong>Leyenda:</strong>
                    <span class="glyphicon glyphicon-stop" style="color:#ff8a8a;"></span> Protestados
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="verFactsDialog" title="Facturas incluidas en este abono">
    <div class="row">
      <div class="col-sm-12">
        <center>
          <h4>Desmarque las facturas que desea excluir de este abono</h4>
        </center>
      </div>
    </div>
    <br>
    <div class="row">
      <div class="col-sm-12">
        <fieldset class="exa-fieldset">
          <legend class="Titulos2">Datos de las facturas</legend>
          <table id="factsGrid" name="factsGrid"></table>
        </fieldset>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-12" style="text-align:right">
        <a class="btn btn-sm btn-success" onclick="$('#verFactsDialog').dialog('close');recTotAPagar();">Aceptar</a>
      </div>
    </div>
  </div>


  <div id="comprasDialog" title="Cuentas por Pagar">
    <div class="row">
      <div class="col-sm-12">
        <table id="crucesGrid" name="crucesGrid"></table>
      </div>
    </div>
    <br>
    <div class="form-group center">
      <a id="btnGuardar" class="btn btn-sm btn-success" onclick="$('#comprasDialog').dialog('close')"> <i class="glyphicon glyphicon-ok"></i> Aceptar</a>
    </div>
  </div>

  <div id="clientesDialog" title="B&uacute;squeda de Clientes">
    <form class="form-horizontal normal"> </form>
  </div>
  <div id="cuentasDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
  <div id="successDialog" title="Mensaje del Sistema">
    <center>
      <h2>El Comprobante se ha registrado con Exito!</h2>
    </center>
    <center>
      <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-danger fileinput-button" style="display: inline;">
        <i class="icon-ban-circle icon-white"></i>
        <span>Cerrar</span>
      </button>
      <a id="impCompr" target="_blank" href="" style="display: inline;" title="Imprimir Comprobante"><span class="btn btn-success start"> <i class="icon-print icon-white"></i> <span>Comprobante</span></span> </a>
      <a id="impComprCanc" target="_blank" href="" style="display: inline;" title="Imprimir Comprobante"><span class="btn btn-primary start"> <i class="icon-print icon-white"></i> <span>Comprobante de cancelaci&oacute;n</span></span> </a>
    </center>
  </div>

  <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
  <script src="../VALIDACIONES/tes_val_cccc_lotes.js?a=63"></script>
  <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
  <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
  <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
</body>

</html>