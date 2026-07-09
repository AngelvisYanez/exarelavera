<?php
/**
* @descripcion Permite registrar socios de la empresa
* @version 1.0
* @fecha de creacion 2018-02-08
* @author edison.moya
* @version 1.0
* @fecha de actualizaci�n 2018-02-08
*
* @package contabilidad.FRONT
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_socio_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Soc($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas
*/
$obBD_con1 =  new Class_Log_Datos_Soc;

//para obtener planes de cuenta para agregar aportaciones
if (isset($cuen2Ajax)||isset($cuenAjax)) {
  $obBD_con1->getPageGridJson(16,$_GET, $obBD_conexion,false);
}

//para obtener todos los planes de centa para modificar aportaciones
if (isset($cuenmod2Ajax)||isset($cuenmodAjax)) {
  $obBD_con1->getPageGridJson(16,$_GET, $obBD_conexion,false);
}

//permite saber si una persona ya esta registrada como persona, socio, provvedor y cliente
if (isset($existePersona)) {
  //Se obtiene el Ide_Cod basandonos en la longitud de la cadena ingresada (cedula,ruc,pasaporte,etc)
  $longitud = strlen($Prs_Ced);
  $identificacion = $obBD_con1->getRowConsulta(2, $longitud, $obBD_conexion);
  $persona = $obBD_con1->getRowConsulta(3, $Prs_Ced, $obBD_conexion);
  $socio = $obBD_con1->getRowConsulta(4, $Prs_Ced, $obBD_conexion);
  $cliente = $obBD_con1->getRowConsulta(22, $Prs_Ced, $obBD_conexion);
  $proveedore = $obBD_con1->getRowConsulta(23, $Prs_Ced, $obBD_conexion);
  if (isset($persona["Prs_Cod"])) {
    $response = array_merge($identificacion, $persona);
    $response["existe"] = true;
  } else {
    $response["existe"] = false;
    $response["Ide_Cod"] = $identificacion["Ide_Cod"];
    $response["Ide_Des"] = $identificacion["Ide_Des"];
    $response["Prs_Ced"] = $Prs_Ced;
  }
  //Se verifica si la persona ya se encuentra registrada en la tabla socio
  if (isset($socio["Prs_Cod"])) {
    $response["Soc_Fec"] = $socio["Prs_Fec"];
    $response["socio"] = true;
  } else {
    $response["socio"] = false;
  }
  //Se verifica si la persona ya se encuentra registrada en la tabla cliente
  if (isset($cliente["Prs_Cod"])) {
    $response["cliente"] = true;
  } else {
    $response["cliente"] = false;
  }
  //Se verifica si la persona ya se encuentra registrada en la tabla proveedore
  if (isset($proveedore["Prs_Cod"])) {
    $response["proveedore"] = true;
  } else {
    $response["proveedore"] = false;
  }

  $obBD_con1->echoJson($response);
  exit();
}

//se busca un socio y sus datos de persona en la base de datos
if (isset($buscarSocio)) {
  //Se obtiene el socio seleccionado
  $sociosel = $obBD_con1->getRowConsulta(9, $Soc_Cod, $obBD_conexion);
  $apoinimod = $obBD_con1->getRowConsulta(39, $Soc_Cod, $obBD_conexion);
  $responce['sociomod']=$sociosel;
  if(count($responce) > 0){
    $responce['apoinimod']=$apoinimod;
  }else{
    $responce['apoinimod']=0;
  }

  $obBD_con1->echoJson($responce);
  exit();
}

//verificamos si el numero de un cheque ya esta registrado dentro de la tabla cheques_ext
if (isset($verificarCheNum)) {
  //Se obtiene el socio seleccionado
  $response['numero_che']=false;
  $Cli_Cod= $obBD_con1->getRowConsulta(27, 'Cli_Cod' . '*' . 'cliente'. '*' .$Prs_Cod_apo,$obBD_conexion);
  $num_Ches = $obBD_con1->getArrayConsulta(29, $Bak_Cod.'*'.$Cli_Cod['Cli_Cod'], $obBD_conexion);
  foreach ($num_Ches as $nch) {
    if($nch['Che_num']==$Che_Num){
      $response['numero_che']=true;
    }
  }

  $obBD_con1->echoJson($response);
  exit();
}

//verificamos si el numero de un cheque ya esta registrado excepto el numero que fue cargado para modificar
if (isset($verificarCheNum2)) {
  //Se obtiene el socio seleccionado
  $response['numero_che']=false;
  $Cli_Cod= $obBD_con1->getRowConsulta(27, 'Cli_Cod' . '*' . 'cliente'. '*' .$Prs_Cod_mod,$obBD_conexion);
  $num_Ches = $obBD_con1->getArrayConsulta(29, $Bak_Cod_mod.'*'.$Cli_Cod['Cli_Cod'], $obBD_conexion);
  //
  if ($Che_Cod_mod=="no") {
    foreach ($num_Ches as $nch) {
      if($nch['Che_num']==$Che_Num_mod){
        $response['numero_che']=true;
      }
    }
  } else {
    if ($Che_Num_mod_ori == $Che_Num_mod) {
      $response['numero_che']=false;
    }else {
      foreach ($num_Ches as $nch) {
        if($nch['Che_num']==$Che_Num_mod ){
          $response['numero_che']=true;
        }
      }
    }
  }

  $obBD_con1->echoJson($response);
  exit();
}

//Secci�n ajax para guardar un nuevo socio en la base de datos
if (isset($saveSocio)) {
  $response['success'] = false;
  $response['message'] = "No se ha logrado realizar la Transaccion";
  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

  if ($Prs_Cod == 0) {
    $obBD_con1->operacionobBD(5, $Ciu_Cod . '*' . $Ide_Cod . '*' . $Prs_Ced . '*' . $Prs_Nom . '*' . $Prs_Ape . '*' . $Prs_Sex . '*' . $Prs_Tel . '*' . $Prs_Cor . '*' . $Prs_Dir, $obBD_conexion);
    //id de la ultima persona agregagada
    $Prs_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
  }else{
    //Ejecutamos un update en la tabla persona
    $obBD_con1->operacionobBD(6, $Ciu_Cod . '*' . $Ide_Cod . '*' . $Prs_Cod . '*' . $Prs_Nom . '*' . $Prs_Ape . '*' . $Prs_Sex . '*' . $Prs_Tel . '*' . $Prs_Cor . '*' . $Prs_Dir, $obBD_conexion);
    $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
  }

  //verificamos si existe el como cliente, si no existe se crea un nuevo cliente
  if ($Prs_Cod_cli == 0) {
    $obBD_con1->operacionobBD(20, $Prs_Cod, $obBD_conexion);
  }
  //verificamos si existe el como cliente, si no existe se crea un nuevo cliente
  if ($Prs_Cod_prv == 0) {
    $obBD_con1->operacionobBD(21, $Prs_Cod, $obBD_conexion);
  }

  //insert en la tabla socio saveApoIni
  $obBD_con1->operacionobBD(7, $Prs_Cod . '*' . $Ses_Suc_Cod . '*'.$Soc_Fec, $obBD_conexion);
  $Soc_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);

  if ($saveApoIni=="si") {
    $obBD_con1->operacionobBD(40, $Soc_Cod . '*' . $Apo_ini_fec. '*' . $Apo_ini_val . '*'.$Apo_ini_con, $obBD_conexion);
  }

  $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
  if ($obBD_con1->Error == 0) {
    $response['success'] = true;
  }
  $obBD_con1->echoJson($response);
  exit();
}

//modificar un socio seleccionado
if (isset($modSocio)) {
  $responsemod['success'] = false;
  $responsemod['message'] = "No se ha logrado realizar la Transaccion";
  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

  //actualizamos el socio y la persona
  $obBD_con1->operacionobBD(10, $Soc_Cod . '*' . $Prs_Nom_m . '*' . $Prs_Ape_m . '*' . $Soc_Fec_m . '*' . $Prs_Sex_m .'*' . $Prs_Tel_m .'*' . $Prs_Cor_m . '*' . $Prs_Dir_m . '*' . $Ciu_Cod_m, $obBD_conexion);

    if($ind_cr_apoini == "nocreado"){
    if(floatval($Apo_Val_m) >0){
      $obBD_con1->operacionobBD(40, $Soc_Cod . '*' . $Apo_Fec_m. '*' . $Apo_Val_m . '*'."Aporte inicial", $obBD_conexion);
    }
  }else{
    if(floatval($Apo_Val_m) >0){
      $obBD_con1->operacionobBD(49, $Apo_Val_m . '*' . $Apo_Fec_m . '*' . $apoini_codmod, $obBD_conexion);
    }
  }

  $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);

  if ($obBD_con1->Error == 0) {
    $responsemod['success'] = true;
  }
  $obBD_con1->echoJson($responsemod);
  exit();
}

//Guardar la aportacion, comprobante y asientos de un aporte del socio
if (isset($saveAportacion)) {
  //generamos el numero del comprobante en base al tipo de pago, periodo contable y fecha del comprobante
  $var_mes = explode('-', $Com_Fec);
  $Com_Num = $obBD_con1->codigoComprAuto($Tia_Cod, $Pec_Cod, $var_mes[1], $obBD_conexion);

  $responseaport['success'] = false;
  $responseaport['message'] = "No se ha logrado realizar la Transaccion";
  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
  $clie='null';
  $prove='null';
  $Com_Tip='I';
  $tabla="";
  $campo="";
  if ($Tipo_comp == "I") {
    $tabla="cliente";
    $campo="Cli_Cod";
    $Cli_Cod= $obBD_con1->getRowConsulta(27, 'Cli_Cod' . '*' . 'cliente'. '*' .$Prs_Cod_apo,$obBD_conexion);
    $clie=$Cli_Cod['Cli_Cod'];
  }
  if ($Tipo_comp == "D") {
    $tabla="proveedore";
    $campo="Prv_Cod";
    $Prv_Cod= $obBD_con1->getRowConsulta(27, 'Prv_Cod' . '*' . 'proveedore'. '*' .$Prs_Cod_apo,$obBD_conexion);
    $prove=$Prv_Cod['Prv_Cod'];
  }
  //insertamos un comprobante
  $obBD_con1->operacionobBD(24, $Pec_Cod .'*' . $prove . '*' . $clie . '*' . $Com_Num. '*' . $Com_Fec. '*' . $Com_Con
  . '*' . $Com_Tip. '*' . $Apo_Val. '*' . 'sin observaciones'. '*' . $Pag_Cod. '*' . $Tia_Cod, $obBD_conexion);

  $ultimo = $obBD_con1->insercionid ($obBD_conexion);
  $ultimo_che='null';

  $tipoPagoAbr= $obBD_con1->getRowConsulta(47, $Pag_Cod,$obBD_conexion);
  if ($tipoPagoAbr['Pag_Abr']=="CHE") {
    // guardamos el cheque
    $obBD_con1->operacionobBD(28, $Bak_Cod .'*' . $Cli_Cod['Cli_Cod'] . '*' . $Che_Cta . '*' . $Che_Num. '*' . $Che_Fec. '*' . $Apo_Val
    . '*' . 'sin observaciones', $obBD_conexion);
    $ultimo_che = $obBD_con1->insercionid ($obBD_conexion);
  }
  //insertamos un aporte Soc_Cod,Com_Cod,Apo_Fec,Apo_Val,Apo_Con
  $obBD_con1->operacionobBD(26, $Soc_Cod_apo .'*' . $ultimo . '*' . $Com_Fec . '*' . $Apo_Val. '*' . $Com_Con.'*' . $ultimo_che, $obBD_conexion);

  foreach ($save as $row)
  {
    if($row['Det_Tip']=='D') {$valor=$row['Debe'];}
    else {$valor=$row['Haber'];}
    $obBD_con1->operacionobBD(25, $ultimo .'*' . $row['Det_Tip'] . '*' . $valor . '*' . $row['Pld_Des']. '*' . $row['Glosa']. '*' . $row['Pld_Cod'], $obBD_conexion);
  }

  $responseaport['link']="../../contabilidad/FRONT/con_pri_compr_1.2.php?codigo=$ultimo&tabla=$tabla&campo=$campo&tipo=$Tia_Cod&Pec_Cod=$Pec_Cod";

  $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);

  if ($obBD_con1->Error == 0) {
    $responseaport['success'] = true;
  }
  $obBD_con1->echoJson($responseaport);
  exit();
}

// obtenemos los socios registrados en la base de datos
if (isset($sociosAjax)) {
  $obBD_con1->getPageGridJson(8,$_GET, $obBD_conexion);
}

//obtenemos todas las aportaciones de un socio
if(isset($ajaxSubgrid)){
  $responce['rows'] = $obBD_con1->getArrayConsulta(30, $ajaxSubgrid, $obBD_conexion);

  $responce['records']=count($responce['rows']);

  $responce['records']=count($responce['rows']);
  $obBD_con1->echoJson($responce);
  exit();
}

//para visualizar los datos del asiento de una aportacion
if(isset($detApoAjax)){
  $responce['success']=false;
  $responce['asi']['rows'] = $obBD_con1->getArrayConsulta(31, $Com, $obBD_conexion);$responce['asi']['records']=count($responce['asi']['rows']);
  $responce['success']=true;
  $obBD_con1->echoJson($responce);
}

//para visualizar los datos del cheque de una aportacion
if(isset($detApoCheAjax)){
  $responce['success']=false;
  $responce['Cheban'] = $obBD_con1->getRowConsulta(45, $Com, $obBD_conexion);
  $responce['success']=true;
  $obBD_con1->echoJson($responce);
}

// obtenemos los socios registrados en la base de datos
if (isset($anularComApoChe)) {
  $responce['success']=false;
  $responce['message'] = "No se ha logrado realizar la Transaccion";
  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

  $obBD_con1->operacionobBD(32, $Com_Cod, $obBD_conexion);
  $obBD_con1->operacionobBD(33, $Com_Cod, $obBD_conexion);

  $tipoPagoAbr= $obBD_con1->getRowConsulta(47, $Pag_Cod,$obBD_conexion);
  if ($tipoPagoAbr['Pag_Abr']=='CHE') {
    $obBD_con1->operacionobBD(34, $Che_Cod, $obBD_conexion);
  }

  $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
  if ($obBD_con1->Error == 0) {
    $responce['success'] = true;
  }
  $obBD_con1->echoJson($responce);
  exit();
}

//carga solo la aportacion inical del socio en caso de que la tuviere
if (isset($aportIni)) {
  $responce['success']=false;
  $responce['message'] = "No se ha logrado realizar la Transaccion";

  $responce['rowsini']=$obBD_con1->getRowConsulta(39, $socio, $obBD_conexion);

  if ($obBD_con1->Error == 0) {
    $responce['success'] = true;
  }
  $obBD_con1->echoJson($responce);
  exit();
}

//obtiene tanto los datos del cheuque como los del banco
if (isset($extCheBan)) {
  $responce['success']=false;
  $responce['message'] = "No se ha logrado realizar la Transaccion";

  $responce['rowCheban']= $obBD_con1->getRowConsulta(36, $Che_Cod .$Prs_Cod_apo,$obBD_conexion);

  if ($obBD_con1->Error == 0) {
    $responce['success'] = true;
  }
  $obBD_con1->echoJson($responce);
  exit();
}

//guarda la modificacion de los datos de la aportacion de un socio
if (isset($modificarAportacionSoc)) {
  $responce['success']=false;
  $responce['message'] = "No se ha logrado realizar la Transaccion";

  //generamos el numero del comprobante en base al tipo de pago, periodo contable y fecha del comprobante
  $var_mes = explode('-', $Com_Fec_mod);
  $Com_Num = $obBD_con1->codigoComprAuto($Tia_Cod_mod, $Pec_Cod_mod, $var_mes[1], $obBD_conexion);

  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

  $clie='null';
  $prove='null';
  $Com_Tip='I';
  $tabla="";
  $campo="";
  if ($Tipo_comp_mod == "I") {
    $tabla="cliente";
    $campo="Cli_Cod";
    $Cli_Cod= $obBD_con1->getRowConsulta(27, $campo . '*' . $tabla. '*' .$Prs_Cod_mod,$obBD_conexion);
    $clie=$Cli_Cod['Cli_Cod'];
  }
  if ($Tipo_comp_mod == "D") {
    $tabla="proveedore";
    $campo="Prv_Cod";
    $Prv_Cod= $obBD_con1->getRowConsulta(27, $campo . '*' . $tabla. '*' .$Prs_Cod_mod,$obBD_conexion);
    $prove=$Prv_Cod['Prv_Cod'];
  }

  //modificamos el comprobante
  $obBD_con1->operacionobBD(42, $Pec_Cod_mod .'*'. $clie .'*' . $prove . '*' . $Com_Num. '*' . $Com_Fec_mod. '*' . $Com_Con_mod
  . '*' . $Com_Tip. '*' . $Apo_Val_mod. '*' . $Pag_Cod_mod. '*' . $Tia_Cod_mod. '*' . $Com_Cod_mod, $obBD_conexion);

  $ultimo_che='null';
  $tipoPagoAbr= $obBD_con1->getRowConsulta(47, $Pag_Cod_mod,$obBD_conexion);
  if ($tipoPagoAbr['Pag_Abr']=="CHE") {
    if ($Che_Cod_mod=="no") {
      // insertamos un nuevo cheque
      $obBD_con1->operacionobBD(28, $Bak_Cod_mod .'*' . $clie . '*' . $Che_Cta_mod . '*' . $Che_Num_mod. '*' . $Che_Fec_mod. '*' . $Apo_Val_mod
      . '*' . 'Aporte', $obBD_conexion);
      $ultimo_che = $obBD_con1->insercionid ($obBD_conexion);
    }else {
      // modificamos el chuque
      $obBD_con1->operacionobBD(43, $Bak_Cod_mod . '*' . $Che_Cta_mod . '*' . $Che_Num_mod. '*' . $Che_Fec_mod. '*' . $Apo_Val_mod
      . '*' . $Che_Cod_mod, $obBD_conexion);
      $ultimo_che=$Che_Cod_mod;
    }
  } else {
    if ($Che_Cod_mod!="no") {
      //damos de baja el scheque en caso de que fuese el modo de pago anterior y se cambie por otro mkodo de pago
      $obBD_con1->operacionobBD(34, $Che_Cod_mod, $obBD_conexion);
    }
  }

  //modificamos la aportacion
  $obBD_con1->operacionobBD(41, $ultimo_che .'*' . $Com_Fec_mod . '*' . $Apo_Val_mod . '*' . $Com_Con_mod. '*' . $Apo_Cod_mod, $obBD_conexion);

  //eliminamos los asientos de este comprobante
  $obBD_con1->operacionobBD(44, $Com_Cod_mod, $obBD_conexion);

  //insertamos nuevos asientos
  foreach ($savemod as $row)
  {
    if($row['Det_Tip']=='D') {$valor=$row['DebeMod'];}
    else {$valor=$row['HaberMod'];}
    $obBD_con1->operacionobBD(25, $Com_Cod_mod .'*' . $row['Det_Tip'] . '*' . $valor . '*' . $row['Pld_Des']. '*' . $row['GlosaMod']. '*' . $row['Pld_Cod'], $obBD_conexion);
  }

  $responce['link']="../../contabilidad/FRONT/con_pri_compr_1.2.php?codigo=$Com_Cod_mod&tabla=$tabla&campo=$campo&tipo=$Tia_Cod_mod&Pec_Cod=$Pec_Cod_mod";

  $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);

  if ($obBD_con1->Error == 0) {
    $responce['success'] = true;
    $responce['message'] = "Los datos se han modificado correctamente!";
  }
  $obBD_con1->echoJson($responce);
  exit();
}

//devuelve la estructura html de una tabla con registros detallados de las aportaciones de los socios
if (isset($reporteDetalladoAporte)) {
  $estructuraTable="".
  "<thead>".
        "<th style='border: black 1px solid;'>Cod. Socio</th>".
        "<th style='border: black 1px solid;'>C&eacute;dula</th>".
        "<th style='border: black 1px solid;'>Nombre</th>".
        "<th style='border: black 1px solid;'>Socio desde</th>".
        "<th style='border: black 1px solid;'>Total de aportaciones</th>".
        "<th style='border: black 1px solid;'>Tel&eacute;fono</th>".
        "<th style='border: black 1px solid;'>Direci&oacute;n</th>".
        "<th style='border: black 1px solid;'>Correo</th>".
        "</thead>".
        "<tbody>";

        $contadorR=0;
        $totalSum=0;
        $exportApoRows = $obBD_con1->getArrayConsulta(35, "", $obBD_conexion);
        foreach ($exportApoRows as $exportApo) {
          $totalSum+=$exportApo['totapo'];
          $contadorR++;
          $estructuraTable.= "<tr><td style='background-color: #e4e4e4;border: black 1px solid;'>".$exportApo['Soc_Cod']."</td><td style='background-color: #e4e4e4;border: black 1px solid;mso-number-format:\"@\";'>".$exportApo['Prs_Ced']."</td>".
          "<td style='background-color: #e4e4e4;border: black 1px solid;'>".$exportApo['nombre']."</td><td style='background-color: #e4e4e4;border: black 1px solid;'>". $exportApo['Soc_Fec']."</td><td style='background-color: #e4e4e4;border: black 1px solid;mso-number-format:\"$\#\,\#\#0\.00\";'>". str_replace(".", ",", $exportApo['totapo'])."</td>".
          "<td style='background-color: #e4e4e4;border: black 1px solid;'>". $exportApo['Prs_Tel']."</td><td style='background-color: #e4e4e4;border: black 1px solid;'>". $exportApo['Prs_Dir']."</td><td style='background-color: #e4e4e4;border: black 1px solid;mso-number-format:\"@\";'>".$exportApo['Prs_Cor']."</td></tr>".
          "".
          "<tr><td></td><td style='background-color: #f0f0f0;border: black 0.1pt solid;text-align:center;font-weight: bold;' colspan=\"7\">Datos de aportaciones</td></tr>".
          "<thead>".
          "<th ></th>".
          "<th style='background-color: #f0f0f0;border: black 0.1pt solid;'>No. Compr</th>".
          "<th style='background-color: #f0f0f0;border: black 0.1pt solid;'>Fecha de Aportaci&oacute;n</th>".
          "<th style='background-color: #f0f0f0;border: black 0.1pt solid;'>observaci&oacute;n</th>".
          "<th style='background-color: #f0f0f0;border: black 0.1pt solid;'>Valor</th>".
          "<th style='background-color: #f0f0f0;border: black 0.1pt solid;'>Tipo de pago</th>".
          "<th style='background-color: #f0f0f0;border: black 0.1pt solid;'>N&uacute;mero de Cheque</th>".
          "<th style='background-color: #f0f0f0;border: black 0.1pt solid;'>Banco</th>".
          "</thead>";
          $aporteinicial=$obBD_con1->getRowConsulta(39, $exportApo['Soc_Cod'], $obBD_conexion);
          if ($aporteinicial!=0) {
            $estructuraTable.="<tr>".
            "<td></td>".
            "<td style='background-color: #d9efff;text-align:center;border: black 0.1pt solid;'>-</td>".
            "<td style='background-color: #d9efff;border: black 0.1pt solid;'>". $aporteinicial['Apo_Fec']."</td>".
            "<td style='background-color: #d9efff;border: black 0.1pt solid;'>". $aporteinicial['Apo_Con']."</td>".
            "<td style='background-color: #d9efff;border: black 0.1pt solid;mso-number-format:\"$\#\,\#\#0\.00\";'>". str_replace(".", ",", $aporteinicial['Apo_Val'])."</td>".
            "<td style='background-color: #d9efff;text-align:center;border: black 0.1pt solid;'>-</td>".
            "<td style='background-color: #d9efff;text-align:center;border: black 0.1pt solid;'>-</td>".
            "<td style='background-color: #d9efff;text-align:center;border: black 0.1pt solid;'>-</td>".
            "</tr>";
            }
            $exportApoSubRows = $obBD_con1->getArrayConsulta(30, $exportApo['Soc_Cod'], $obBD_conexion);
            foreach ($exportApoSubRows as $exportApoSub) {
              $estructuraTable.="<tr>".
              "<td></td>".
              "<td style='border: black 0.1pt solid;'>". $exportApoSub['codigo_compro']."</td>".
              "<td style='border: black 0.1pt solid;'>". $exportApoSub['Apo_Fec']."</td>".
              "<td style='border: black 0.1pt solid;'>". $exportApoSub['Apo_Con']."</td>".
              "<td style='border: black 0.1pt solid;mso-number-format:\"$\#\,\#\#0\.00\";'>". str_replace(".", ",", $exportApoSub['Apo_Val'])."</td>".
              "<td style='border: black 0.1pt solid;'>". $exportApoSub['Pag_Des']."</td>";

              $tipoPagoAbr= $obBD_con1->getRowConsulta(47, $exportApoSub['Pag_Cod'],$obBD_conexion);
              if ($tipoPagoAbr['Pag_Abr']=='CHE') {
                $chequeBan= $obBD_con1->getRowConsulta(36, $exportApoSub['Che_Cod'] .$Prs_Cod_apo,$obBD_conexion);
                $estructuraTable.= "<td style='border: black 0.1pt solid;'>". $chequeBan['Che_Num'].+"</td>".
                "<td style='border: black 0.1pt solid;'>". $chequeBan['Bak_Des']."</td>";
                }else{
                  $estructuraTable.= "<td style='border: black 0.1pt solid;'></td><td style='border: black 0.1pt solid;'></td>";
                  }
                  $estructuraTable.="</tr>";
                  }
                  $estructuraTable.="<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
                  }
                  $estructuraTable.="<br>".
                  "<tr><td></td><td></td><td></td><td style='border: black 1px solid;'>Total</td><td style='border: black 1px solid;mso-number-format:\"$\#\,\#\#0\.00\";'>$". number_format($totalSum, 2, ',', '.')."</td></tr>".
            "</tbody>";
    $responce['tablereport']=$estructuraTable;
    if ($obBD_con1->Error == 0) {
      $responce['success'] = true;
      $responce['message'] = "Reporte generado!";
    }
    $obBD_con1->echoJson($responce);
    exit();
}

//obtiene el tipo de pago en base a su Codigo
if (isset($obtenerTipoPago)) {
  $responce['success']=false;
  $responce['message'] = "No se ha logrado realizar la Transaccion";

  $responce['tipoPago']= $obBD_con1->getRowConsulta(47, $Pag_Cod,$obBD_conexion);

  if ($obBD_con1->Error == 0) {
    $responce['success'] = true;
  }
  $obBD_con1->echoJson($responce);
  exit();
}

//verifica si la cuenta de las aportaciones d elos socios ya fue parametrizada
if (isset($verificarParametrizada)) {
  $responce['success']=false;
  $responce['message'] = "No se ha logrado realizar la Transaccion";

  $ctaParametrizada=$obBD_con1->getRowConsulta(48, "", $obBD_conexion);
  if (count($ctaParametrizada) > 0) {
    $responce['verifica'] = "si";
    $responce['message'] = "Cuenta parametrizada";
  } else {
    $responce['verifica'] = "no";
    $responce['message'] = "No se ha encontrado una cuenta parametrizada para las aportaciones de los socios";
  }

  if ($obBD_con1->Error == 0) {
    $responce['success'] = true;
  }
  $obBD_con1->echoJson($responce);
  exit();
}
?>
<!DOCTYPE HTML>
<HTML>
  <HEAD>
    <TITLE> <?php echo "APORT. SOCIOS GESTIONAR [EXA]"; ?></TITLE>
    <?Php require_once("../../mascaras/model1/estilos/basic.php"); ?>
    <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
    <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
    <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
    <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
    <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script language="javascript" src="../VALIDACIONES/con_val_aportacion.js?a=2"></script>
    <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
    <style media="screen">
    .esp_fields{
      margin-bottom: 5px;
    }
    </style>
  </HEAD>
  <BODY>
    <div class="panel panel-main">
      <div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Registrar Socio</h3></div>
      <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
        <div class="row">
          <div class="col-sm-12">
            <div id="tabs" class="ui-tab-fix">
              <ul style="font-size: 12px;">
                <li><a href="#nue_soc" onclick="limpiarFormComp()">Ingresar nuevo socio</a></li>
                <li><a href="#mod_soc" onclick="cargarSocios()">Consultar socios</a></li>
                <li><a href="#mod_soc_apo" onclick="cargarSociosApo()">Modificar Aportaciones</a></li>
              </ul>
              <div id="nue_soc">
                <FORM id="formSocio" name="formSocio" class="form-horizontal normal" enctype="multipart/form-data" action="javascript:saveForm();">
                  <input type="hidden" id="Prs_Cod" name="Prs_Cod" value="0">
                  <input type="hidden" id="Prs_Cod_cli" name="Prs_Cod_cli" value="0">
                  <input type="hidden" id="Prs_Cod_prv" name="Prs_Cod_prv" value="0">
                  <div class="row">

                    <div class="col-sm-12">
                      <FIELDSET class="exa-fieldset">
                        <legend class="Titulos2">Datos Personales</legend>
                        <div class="form-group Titulos2">
                          <div class="col-sm-12">
                            <b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.
                            <hr/>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-4 control-label label-sm required" for="Prs_Ced">C&eacute;dula/R.U.C.:</label>
                          <div class="col-sm-4">
                            <div class="input-group input-group-xs">
                              <input id="Prs_Ced" name="Prs_Ced" type="text" class="form-control input-sm" required="" placeholder="Ingresar Informaci&oacute;n" onkeypress="return validar_numeric(event);" />
                              <span class="input-group-btn">
                                <button class="btn btn-success" type="button" onclick="comprobarForm()"><span class="glyphicon glyphicon-refresh" title="Buscar persona"></span> Comprobar</button>
                              </span>
                            </div>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-4 control-label label-sm required" for="Ciu_Cod">Tipo de Documento:</label>
                          <div class="col-sm-4">
                            <select id="Ide_Cod" name="Ide_Cod" class="form-control input-xs" required="">
                              <option value="2" data-provincia="" data-pais="">CEDULA DE IDENTIDAD</option>
                              <option value="1" data-provincia="" data-pais="">REGISTRO UNICO CONTRIBUYENTE</option>
                              <option value="3" data-provincia="" data-pais="">PASAPORTE</option>
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-4 control-label label-sm required" for="Prs_Nom">Nombres:</label>
                          <div class="col-sm-4">
                            <input id="Prs_Nom" name="Prs_Nom" class="form-control input-xs" placeholder="" type="text" required=""/>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-4 control-label label-sm required" for="Prs_Ape">Apellidos:</label>
                          <div class="col-sm-4">
                            <input id="Prs_Ape" name="Prs_Ape" class="form-control input-xs" placeholder="" type="text" required=""/>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-4 control-label label-sm required" for="Prs_Sex">Genero:</label>
                          <div class="col-sm-2">
                            <select id="Prs_Sex" name="Prs_Sex" class="form-control input-xs">
                              <option value="M">MASCULINO</option>
                              <option value="F">FEMENINO</option>
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-4 control-label label-sm required" for="Ciu_Cod">Ciudad:</label>
                          <div class="col-sm-3">
                            <?php $rows_ciudad = $obBD_con1->getArrayConsulta(1, "", $obBD_conexion); ?>
                            <select name="Ciu_Cod" id="Ciu_Cod" data-placeholder="Seleccione una ciudad" class="chzn-select-template-example">
                              <option value="" data-provincia="" data-pais=""></option>
                              <?Php foreach ($rows_ciudad as $row) {?>
                                <option value="<?Php echo $row['Ciu_Cod']; ?>" data-provincia="<?Php echo $row['Pro_Nom']; ?>" data-pais="<?Php echo $row['Pas_Nom']; ?>"><?Php echo $row['Ciu_Des']; ?></option>
                                <?Php } ?>
                              </select>
                            </div>
                          </div>
                          <div class="form-group">
                            <label class="col-sm-4 control-label label-sm" for="Prs_Tel">Tel&eacute;fono:</label>
                            <div class="col-sm-4">
                              <input id="Prs_Tel" name="Prs_Tel" class="form-control input-xs" placeholder="" type="text" onkeypress="return validar_numeric(event);"/>
                            </div>
                          </div>
                          <div class="form-group">
                            <label class="col-sm-4 control-label label-sm" for="Prs_Cor">Email:</label>
                            <div class="col-sm-4">
                              <input id="Prs_Cor" name="Prs_Cor" class="form-control input-xs" placeholder="" type="text"/>
                            </div>
                          </div>
                          <div class="form-group">
                            <label class="col-sm-4 control-label label-sm required" for="Prs_Dir">Direcci&oacute;n:</label>
                            <div class="col-sm-4">
                              <textarea id="Prs_Dir" name="Prs_Dir" class="form-control input-xs" style="resize: none;" required=""></textarea>
                            </div>
                          </div>
                        </FIELDSET>
                        <FIELDSET class="exa-fieldset">
                          <legend class="Titulos2">Datos de socio</legend>
                          <div class="form-group">
                            <label class="col-sm-4 control-label label-sm required" for="Soc_Fec">Fecha desde la que es socio:</label>
                            <div class="col-sm-2">
                              <div class="input-group input-group-xs">
                                <span class="input-group-addon" style="padding:0px 5px 0px 5px;margin:0;line-height:0;">
                                  <i class="glyphicon glyphicon-calendar"></i>
                                </span>
                                <input id="Soc_Fec" name="Soc_Fec" class="form-control input-xs" placeholder="yy-mm-dd" type="text" readonly="" required/>
                              </div>
                            </div>
                          </div>
                          <div class="form-group">
                            <input type="text" name="Apo_ini_con" id="Apo_ini_con" value="Aporte inicial" style="display:none;">
                            <div class="">
                              <label class="col-sm-4 control-label label-sm" for="Apo_ini_val">Aportaci&oacute;n Inicial:</label>
                              <div class="col-sm-2">
                                <div class="input-group input-group-xs">
                                  <span class="input-group-addon" style="padding:2px 5px 0px 5px;margin:0;line-height:0;">
                                    <input id="checkApoIni" type="checkbox" onchange="ActivarApoIni()">
                                  </span>
                                  <input id="Apo_ini_val" name="Apo_ini_val" type="text" class="form-control" placeholder="Valor de la aportaci&oacute;n" disabled onkeypress="return  validar_decimal(event)" required>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="form-group">
                            <label class="col-sm-4 control-label label-sm" for="Apo_ini_fec">Fecha de la aportaci&oacute;n:</label>
                            <div class="col-sm-2">
                              <div class="input-group input-group-xs">
                                <span class="input-group-addon" style="padding:0px 5px 0px 5px;margin:0;line-height:0;">
                                  <i class="glyphicon glyphicon-calendar"></i>
                                </span>
                                <input id="Apo_ini_fec" name="Apo_ini_fec" class="form-control input-xs" placeholder="yy-mm-dd" type="text" disabled readonly required/>
                              </div>
                            </div>
                          </div>
                        </FIELDSET>
                        <button id="btn_guardar_form" type="submit" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                      </div>
                    </div>
                  </FORM>
                </div>
                <div id="mod_soc">
                  <div class="row" id="tot_soc">
                    <form id="frm_bus" name="frm_bus" class="form-horizontal normal" action="javascript:$('#tableResult').Search('#frm_bus','sociosAjax');">
                      <fieldset class="exa-fieldset">
                        <legend class="Titulos2">B&uacute;squeda de Socios</legend>
                        <div class="form-group">
                          <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                          <div class="col-sm-5 radioset">
                            <input id="rad_ba1" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)"/><label for="rad_ba1">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                            <input id="rad_ba2" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)"/><label for="rad_ba2">&nbsp;&nbsp;Nombre&nbsp;&nbsp;</label>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                          <div class="col-sm-5">
                            <div class="input-group">
                              <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                              <span class="input-group-btn">
                                <button id="btsearch" class="btn btn-success btn-xs" type="button" title="Buscar Socio" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                              </span>
                            </div>
                          </div>
                        </div>
                      </fieldset>
                    </form>
                    <div>
                      <table id="tableResult"></table>
                      <div id="tableResultPager"></div>
                      <br>
                      <div class="row">

                        <div class="col-sm-12">
                          <a title="Imprimir reporte general de aportaciones" class="btn btn-primary btn-sm" onClick="imprimirAportacion()"><span class="glyphicon glyphicon-print"></span>Imprimir general</a>
                          <a title="Imprimir reporte detallado de aportaciones" class="btn btn-primary btn-sm" onClick="imprimirAportacionDet()"><span class="glyphicon glyphicon-print"></span>Imprimir detallado</a>
                          <a title="Exportar reporte general de aportaciones" class="btn btn-primary btn-sm" onClick="exportarAportacion()"><span class="glyphicon glyphicon-save"></span>Exportar general</a>
                          <a title="Exportar reporte detallado de aportaciones" class="btn btn-primary btn-sm" onClick="exportarAportacionDet()"><span class="glyphicon glyphicon-save"></span>Exportar detallado</a>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- secion para agregar aportaciones de los socios -->
                  <div class="row hidden" id="aport_soc">
                    <div class"col-sm-12">
                    </div>
                    <form name="formAport" id="formAport" method="post" action="javascript:$.createDialogConfirm('�Est&aacute; seguro que desea guardar los datos?',null,guardarAportacion)">
                      <input type="text" id="Soc_Cod_apo" name="Soc_Cod_apo" hidden="true">
                      <input type="text" id="Prs_Cod_apo" name="Prs_Cod_apo" hidden="true">
                      <div class="row">
                        <div class="col-sm-4">
                          <div class="row">
                            <div class="col-sm-12">
                              <fieldset class="exa-fieldset" style="padding-bottom: 1%;">
                                <legend>
                                  <label class="Titulos2">Datos de la aportaci&oacute;n</label>
                                </legend>
                                <div class="col-sm-10">
                                  <div class="row esp_fields">
                                    <label class="col-sm-4 control-label label-sm required" for="Pec_Cod">Periodo contable:</label>
                                    <div class="col-sm-5">
                                      <select id="Pec_Cod" name="Pec_Cod" class="form-control input-xs" required="" onchange="setPeriodo()">
                                        <?php $rows_periodos = $obBD_con1->getArrayConsulta(17, "", $obBD_conexion);
                                        if (count($rows_periodos) > 0)
                                        {
                                          foreach($rows_periodos as $row){
                                            ?>
                                            <?echo "<option value='$row[Pec_Cod]' data--pec_-cod='$row[Pec_Cod]' data--pla_-cod='$row[Pla_Cod]' data--pec_-fei='$row[Pec_Fei]' data--pec_-fef='$row[Pec_Fef]'  data-periodo='$row[priodo_m]'>$row[priodo_m]</option>";?>

                                          <?php }
                                        }?>
                                      </select>
                                    </div>
                                  </div>
                                  <div class="row esp_fields">
                                    <label class="col-sm-4 control-label label-sm" for="Prs_Nom_ap">Socio:</label>
                                    <div class="col-sm-8">
                                      <input id="Prs_Nom_ap" name="Prs_Nom_ap" class="form-control input-xs" placeholder="" type="text" required="" readonly/>
                                    </div>
                                  </div>
                                  <div class="row esp_fields">
                                    <label class="col-sm-4 control-label label-sm" for="Prs_Ced_ap">C&eacute;dula:</label>
                                    <div class="col-sm-5">
                                      <input id="Prs_Ced_ap" name="Prs_Ced_ap" class="form-control input-xs" placeholder="" type="text" readonly/>
                                    </div>
                                  </div>
                                  <div class="row esp_fields">
                                    <label class="col-sm-4 control-label label-sm required" for="Apo_Val">Valor:</label>
                                    <div class="col-sm-5">
                                      <div class="input-group input-group-xs">
                                        <span class="input-group-addon"> <i class="glyphicon glyphicon-usd"></i>
                                        </span>
                                        <input class="form-control input-xs" name="Apo_Val" id="Apo_Val"  onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required placeholder="0.00" />
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </fieldset>
                            </div>
                          </div>
                        </div>
                        <div class="col-sm-8">
                          <fieldset class="exa-fieldset">
                            <legend>
                              <label class="Titulos2">Comprobante</label>
                            </legend>
                            <div class="col-sm-6">
                              <div class="row esp_fields">
                                <label class="col-sm-5 control-label label-sm required" for="Com_Fec">Fecha de aportaci&oacute;n:</label>
                                <div class="col-sm-4">
                                  <input id="Com_Fec" name="Com_Fec" class="form-control input-xs" placeholder="yy-mm-dd" type="text"/>
                                </div>
                              </div>
                              <div class="row esp_fields">
                                <label class="col-sm-5 control-label label-sm required" for="Pag_Cod">Tipo de pago:</label>
                                <div class="col-sm-4">
                                  <select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs" required="" onchange="load_plan_to_document($('#Pag_Cod option:selected').attr('pagabr'))">
                                    <option value="nada" pagabr="nada">Seleccione...</option>
                                    <?$tipos_pago_apo = $obBD_con1->getArrayConsulta(46, "", $obBD_conexion);
                                    foreach($tipos_pago_apo as $row){?>

                                      <option value="<?Php echo $row['Pag_Cod']; ?>" pagabr="<?Php echo $row['Pag_Abr']; ?>"><?Php echo $row['Pag_Des']; ?></option>
                                    <?php } ?>
                                  </select>
                                </div>
                              </div>
                              <div class="row esp_fields">
                                <label class="col-sm-5 control-label label-sm required" for="Tipo_comp">Tipo de comprobante:</label>
                                <div class="col-sm-4">
                                  <select id="Tipo_comp" name="Tipo_comp" class="form-control input-xs" required="" onchange="cambiarAsiento(this.value)" disabled>
                                    <option value="nada">Seleccione...</option>
                                    <option value="D">Diario</option>
                                    <option value="I">Ingreso</option>
                                    <option value="E">Egreso</option>
                                  </select>
                                </div>
                              </div>
                              <div class="row esp_fields">
                                <label class="col-sm-5 control-label label-sm required" for="Tia_Cod">Tipo de asiento:</label>
                                <div class="col-sm-7">
                                  <select id="Tia_Cod" name="Tia_Cod" class="form-control input-xs" required="" disabled>
                                    <option value="">Seleccione tipo de comprobante</option>
                                  </select>
                                </select>
                              </div>
                            </div>
                            <div class="row esp_fields">
                              <label class="col-sm-5 control-label label-sm required" for="Com_Con">Observaci&oacute;n:</label>
                              <div class="col-sm-7">
                                <textarea id="Com_Con" name="Com_Con" class="form-control input-xs" style="resize: none;" required=""></textarea>
                              </div>
                            </div>
                          </div>
                          <div class="col-sm-6">
                            <div class="row esp_fields">
                              <label class="col-sm-4 control-label label-sm required" for="Bak_Cod">Banco:</label>
                              <div class="col-sm-8">
                                <select id="Bak_Cod" name="Bak_Cod" class="form-control input-xs" onchange="verificarNoCheque($('#Che_Num').val());" required disabled>
                                  <?php $rows_banks = $obBD_con1->getArrayConsulta(19, "", $obBD_conexion);
                                  if (count($rows_banks) > 0)
                                  {
                                    foreach($rows_banks as $row){
                                      ?>
                                      <option value="<?Php echo $row['Bak_Cod']; ?>"><?Php echo $row['Bak_Des']; ?></option>
                                    <?php }
                                  }?>
                                </select>
                              </div>
                            </div>
                            <div class="row esp_fields">
                              <label class="col-sm-4 control-label label-sm required" for="Che_Fec">Fecha de cheque:</label>
                              <div class="col-sm-4">
                                <input id="Che_Fec" name="Che_Fec" class="form-control input-xs" placeholder="yy-mm-dd" type="text" disabled/>
                              </div>
                            </div>
                            <div class="row esp_fields">
                              <label class="col-sm-4 control-label label-sm required" for="Che_Num">No. Cheque:</label>
                              <div class="col-sm-3">
                                <div class="input-group input-group-xs">
                                  <input class="form-control input-xs" name="Che_Num" id="Che_Num" type="text" size="10" maxlength="12" style="text-align:right" onkeyup="verificarNoCheque(this.value)" onkeypress="return soloNumeros(event)" required placeholder="No. Cheque..." disabled/>
                                  <span class="input-group-addon"> <i id="indicadorChe" class=""></i></span>
                                </div>
                              </div>
                              <label style="display: inline-block;" class=" control-label label-sm" for="Che_Cta">No. Cuenta:</label>
                              <div style="display: inline-block;">
                                <input style="width: 125px;" id="Che_Cta" name="Che_Cta" class="form-control input-xs" placeholder="No. Cuenta..." type="text" required="" disabled/>
                              </div>
                            </div>
                          </div>
                        </fieldset>
                      </div>
                    </div>
                  </form>
                  <div class="row">
                    <div class="col-sm-12">
                      <fieldset class="exa-fieldset">
                        <legend>
                          <label class="Titulos2">Datos del Asiento Contable</label>
                        </legend>
                        <div class="col-sm-12">
                          <div class="row">
                            <div id="compGrilla" style="width: 100%;padding-top: 10px;">
                              <table id="comp"></table>
                              <div id="compPager"></div>
                            </div>
                            <a title="Escoger otro socio" class="btn btn-inverse btn-sm" onClick="limpiarFormComp();totalSocios();"><span class="glyphicon glyphicon-arrow-left"></span> Atras</a>
                            <a id="add_cnt" class="btn btn-primary btn-sm hidden" title="Agregar cuenta" onclick="$('#cuenDialog').dialog('open');"><i class="glyphicon glyphicon-plus"></i> Agregar</a>
                            <button type="button" name="agregarAporte" class="btn btn-success btn-sm" title="Guardar aportes" onclick="$('#formAport').formSubmit();"><i class="glyphicon glyphicon-ok"></i> Guardar</button>
                          </div>
                        </div>
                      </fieldset>
                    </div>
                  </div>
                </div>
                <!-- final de secion para agregar aportaciones de los socios -->
              </div>
              <div id="mod_soc_apo">
                <div class="row" id="tot_soc_apo">
                  <form id="frm_bus_apo" name="frm_bus_apo" class="form-horizontal normal" action="javascript:$('#tableResultApo').Search('#frm_bus_apo','sociosAjax');">
                    <fieldset class="exa-fieldset">
                      <legend class="Titulos2">B&uacute;squeda de Socios</legend>
                      <div class="form-group">
                        <label class="col-sm-2 control-label label-xs">Filtrar por:</label>
                        <div class="col-sm-5 radioset">
                          <input id="rad_ba3" name="op_opciones" type="radio" value="c" checked="" onclick="setfocus(this.form.search)"/><label for="rad_ba3">&nbsp;&nbsp;C&eacute;dula/R.U.C.&nbsp;&nbsp;</label>
                          <input id="rad_ba4" name="op_opciones" type="radio" value="d" onclick="setfocus(this.form.search)"/><label for="rad_ba4">&nbsp;&nbsp;Nombre&nbsp;&nbsp;</label>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-2 control-label label-xs">B&uacute;squeda:</label>
                        <div class="col-sm-5">
                          <div class="input-group">
                            <input type="text" id="search" name="search" class="form-control input-xs" placeholder="Ingrese &iacute;ndice de b&uacute;squeda" autofocus="">
                            <span class="input-group-btn">
                              <button id="btsearch" class="btn btn-success btn-xs" type="button" title="Buscar Socio" onclick="this.form.submit()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                            </span>
                          </div>
                        </div>
                      </div>
                    </fieldset>
                  </form>
                  <div>
                    <table id="tableResultApo"></table>
                    <div id="tableResultApoPager"></div>
                    <br>
                  </div>
                </div>
                <!-- secion para modificar aportaciones de los socios -->
                <div class="row hidden" id="mod_aport_soc">
                  <div class"col-sm-12">
                  </div>
                  <form name="formModAport" id="formModAport" method="post" action="javascript:$.createDialogConfirm('�Est&aacute; seguro que desea guardar los datos?',null,guardarModAportacion)">
                    <?$ctaParametrizada=$obBD_con1->getRowConsulta(48, "", $obBD_conexion);?>
                    <input type="text" name="Pld_Cdc_mod" id="Pld_Cdc_mod" value="<?echo $ctaParametrizada['Pld_Cdc'];?>" style="display:none;">
                    <input type="text" name="tsg_id" id="tsg_id" value="" style="display:none;">
                    <input type="text" name="Che_Num_mod_ori" id="Che_Num_mod_ori" value="" style="display:none;">
                    <input type="text" name="Prs_Cod_mod" id="Prs_Cod_mod" value="" style="display:none;">
                    <input type="text" name="Che_Cod_mod" id="Che_Cod_mod" value="" style="display:none;">
                    <input type="text" name="Com_Cod_mod" id="Com_Cod_mod" value="" style="display:none;">
                    <input type="text" name="Apo_Cod_mod" id="Apo_Cod_mod" value="" style="display:none;">
                    <div class="row">
                      <div class="col-sm-4">
                        <div class="row">
                          <div class="col-sm-12">
                            <fieldset class="exa-fieldset" style="padding-bottom: 1%;">
                              <legend>
                                <label class="Titulos2">Datos de la aportaci&oacute;n</label>
                              </legend>
                              <div class="col-sm-10">
                                <div class="row esp_fields">
                                  <label class="col-sm-4 control-label label-sm required" for="Pec_Cod_mod">Periodo contable:</label>
                                  <div class="col-sm-5">
                                    <select id="Pec_Cod_mod" name="Pec_Cod_mod" class="form-control input-xs" required="" onchange="setPeriodo()">
                                      <?php $rows_periodos = $obBD_con1->getArrayConsulta(17, "", $obBD_conexion);
                                      if (count($rows_periodos) > 0)
                                      {
                                        foreach($rows_periodos as $row){
                                          ?>
                                          <?echo "<option value='$row[Pec_Cod]' data--pec_-cod='$row[Pec_Cod]' data--pla_-cod='$row[Pla_Cod]' data--pec_-fei='$row[Pec_Fei]' data--pec_-fef='$row[Pec_Fef]'  data-periodo='$row[priodo_m]'>$row[priodo_m]</option>";?>

                                        <?php }
                                      }?>
                                    </select>
                                  </div>
                                </div>
                                <div class="row esp_fields">
                                  <label class="col-sm-4 control-label label-sm" for="Prs_Nom_mod">Socio:</label>
                                  <div class="col-sm-8">
                                    <input id="Prs_Nom_mod" name="Prs_Nom_mod" class="form-control input-xs" placeholder="" type="text" required="" readonly/>
                                  </div>
                                </div>
                                <div class="row esp_fields">
                                  <label class="col-sm-4 control-label label-sm" for="Prs_Ced_ap_mod">C&eacute;dula:</label>
                                  <div class="col-sm-5">
                                    <input id="Prs_Ced_ap_mod" name="Prs_Ced_ap_mod" class="form-control input-xs" placeholder="" type="text" readonly/>
                                  </div>
                                </div>
                                <div class="row esp_fields">
                                  <label class="col-sm-4 control-label label-sm required" for="Apo_Val_mod">Valor:</label>
                                  <div class="col-sm-5">
                                    <div class="input-group input-group-xs">
                                      <span class="input-group-addon"> <i class="glyphicon glyphicon-usd"></i>
                                      </span>
                                      <input class="form-control input-xs" name="Apo_Val_mod" id="Apo_Val_mod"  onchange=" updateValores()" type="text" size="10" maxlength="12" style="text-align:right" onkeypress="return  validar_decimal(event)" required placeholder="0.00" />
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </fieldset>
                          </div>
                        </div>
                      </div>
                      <div class="col-sm-8">
                        <fieldset class="exa-fieldset">
                          <legend>
                            <label class="Titulos2">Comprobante</label>
                          </legend>
                          <div class="col-sm-6">
                            <div class="row esp_fields">
                              <label class="col-sm-5 control-label label-sm required" for="Com_Fec_mod">Fecha de aportaci&oacute;n:</label>
                              <div class="col-sm-4">
                                <input id="Com_Fec_mod" name="Com_Fec_mod" class="form-control input-xs" placeholder="yy-mm-dd" type="text"/>
                              </div>
                            </div>
                            <div class="row esp_fields">
                              <label class="col-sm-5 control-label label-sm required" for="Pag_Cod_mod">Tipo de pago:</label>
                              <div class="col-sm-4">
                                <select id="Pag_Cod_mod" name="Pag_Cod_mod" class="form-control input-xs" required="" onchange="load_plan_to_document_mod($('#Pag_Cod_mod option:selected').attr('pagabr'))">
                                  <option value="nada">Seleccione...</option>
                                  <?$tipos_pago_apo = $obBD_con1->getArrayConsulta(46, "", $obBD_conexion);
                                  foreach($tipos_pago_apo as $row){?>

                                    <option value="<?Php echo $row['Pag_Cod']; ?>" pagabr="<?Php echo $row['Pag_Abr']; ?>"><?Php echo $row['Pag_Des']; ?></option>
                                  <?php } ?>
                                </select>
                              </div>
                            </div>
                            <div class="row esp_fields">
                              <label class="col-sm-5 control-label label-sm required" for="Tipo_comp_mod">Tipo de comprobante:</label>
                              <div class="col-sm-4">
                                <select id="Tipo_comp_mod" name="Tipo_comp_mod" class="form-control input-xs" required="" onchange="cargartipoAsientoMod(this.value)">
                                  <option value="nada">Seleccione...</option>
                                  <option value="D">Diario</option>
                                  <option value="I">Ingreso</option>
                                  <option value="E">Egreso</option>
                                </select>
                              </div>
                            </div>
                            <div class="row esp_fields">
                              <label class="col-sm-5 control-label label-sm required" for="Tia_Cod_mod">Tipo de asiento:</label>
                              <div class="col-sm-7">
                                <select id="Tia_Cod_mod" name="Tia_Cod_mod" class="form-control input-xs" required="">
                                  <option value="">Seleccione tipo de comprobante</option>
                                </select>
                              </select>
                            </div>
                          </div>
                          <div class="row esp_fields">
                            <label class="col-sm-5 control-label label-sm required" for="Com_Con_mod">Observaci&oacute;n:</label>
                            <div class="col-sm-7">
                              <textarea id="Com_Con_mod" name="Com_Con_mod" class="form-control input-xs" style="resize: none;" required=""></textarea>
                            </div>
                          </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="row esp_fields">
                            <label class="col-sm-4 control-label label-sm required" for="Bak_Cod_mod">Banco:</label>
                            <div class="col-sm-8">
                              <select id="Bak_Cod_mod" name="Bak_Cod_mod" class="form-control input-xs" onchange="verificarNoCheque2($('#Che_Num_mod').val());" required disabled>
                                <?php $rows_banks = $obBD_con1->getArrayConsulta(19, "", $obBD_conexion);
                                if (count($rows_banks) > 0)
                                {
                                  foreach($rows_banks as $row){
                                    ?>
                                    <option value="<?Php echo $row['Bak_Cod']; ?>"><?Php echo $row['Bak_Des']; ?></option>
                                  <?php }
                                }?>
                              </select>
                            </div>
                          </div>
                          <div class="row esp_fields">
                            <label class="col-sm-4 control-label label-sm required" for="Che_Fec_mod">Fecha de cheque:</label>
                            <div class="col-sm-4">
                              <input id="Che_Fec_mod" name="Che_Fec_mod" class="form-control input-xs" placeholder="yy-mm-dd" type="text" disabled/>
                            </div>
                          </div>
                          <div class="row esp_fields">
                            <label class="col-sm-4 control-label label-sm required" for="Che_Num_mod">No. Cheque:</label>
                            <div class="col-sm-3">
                              <div class="input-group input-group-xs">
                                <input class="form-control input-xs" name="Che_Num_mod" id="Che_Num_mod" type="text" size="10" maxlength="12" style="text-align:right" onkeyup="verificarNoCheque2(this.value)" onkeypress="return soloNumeros(event)" required placeholder="No. Cheque..." disabled/>
                                <span class="input-group-addon"> <i id="indicadorChe2" class=""></i></span>
                              </div>
                            </div>
                            <label style="display: inline-block;" class=" control-label label-sm" for="Che_Cta_mod">No. Cuenta:</label>
                            <div style="display: inline-block;">
                              <input style="width: 125px;" id="Che_Cta_mod" name="Che_Cta_mod" class="form-control input-xs" placeholder="No. Cuenta..." type="text" required="" disabled/>
                            </div>
                          </div>
                        </div>
                      </fieldset>
                    </div>
                  </div>
                </form>
                <div class="row">
                  <div class="col-sm-12">
                    <fieldset class="exa-fieldset">
                      <legend>
                        <label class="Titulos2">Datos del Asiento Contable</label>
                      </legend>
                      <div class="col-sm-12">
                        <div class="row">
                          <div id="det_mod_apo_asien" style="width: 100%;padding-top: 10px;">
                            <table id="mod_apo_asien"></table>
                            <div id="mod_apo_asienPager"></div>
                          </div>
                          <a title="Modificar otra aportaci&oacute;n" class="btn btn-inverse btn-sm" onClick="cargarSociosApo();"><span class="glyphicon glyphicon-arrow-left"></span> Atras</a>
                          <a id="add_cnt_mod" class="btn btn-primary btn-sm hidden" title="Agregar cuenta" onclick="$('#cuenmodDialog').dialog('open');"><i class="glyphicon glyphicon-plus"></i> Agregar cuenta</a>
                          <button type="button" name="agregarAporte" class="btn btn-success btn-sm" title="Modificar datos" onclick="$('#formModAport').formSubmit();"><i class="glyphicon glyphicon-ok"></i> Guardar</button>
                        </div>
                      </div>
                    </fieldset>
                  </div>
                </div>
              </div>
              <!-- final de secion para modificar aportaciones de los socios -->
              </div>
            </div>
            <!-- MODAL MODIFICAR-->
            <div id="editDialog" title="Editar Usuario">
              <form id ="formDialog" name="formDialog" class="form-horizontal" autocomplete="off">
                <div class="form-group Titulos2">
                  <div class="col-sm-12"><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco (  <span class="required"></span> ) son campos obligatorios.<hr/></div>
                </div>
                <div class="col-sm-10 col-sm-offset-1">
                  <fieldset>
                    <!-- Soc_Cod-->
                    <div>
                      <input type="text" id="Soc_Cod" name="Soc_Cod" hidden="true">
                    </div>
                    <!-- Cedula -->
                    <div class="form-group">
                      <label class="col-xs-4 control-label label-xs required">C&eacute;dula:</label>
                      <div class="col-xs-8" >
                        <input id="Prs_Ced_m" name="Prs_Ced_m" class="form-control input-xs readOnly" readOnly=""> </input>
                      </div>
                    </div>

                    <!-- Usuario -->
                    <div class="form-group">
                      <label class="col-sm-4 control-label label-sm required" for="Prs_Nom_m">Nombres:</label>
                      <div class="col-sm-8">
                        <input id="Prs_Nom_m" name="Prs_Nom_m" class="form-control input-xs" placeholder="" type="text" required=""/>
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="col-sm-4 control-label label-sm required" for="Prs_Ape_m">Apellidos:</label>
                      <div class="col-sm-8">
                        <input id="Prs_Ape_m" name="Prs_Ape_m" class="form-control input-xs" placeholder="" type="text" required=""/>
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="col-sm-4 control-label label-sm required" for="Prs_Sex_m">Genero:</label>
                      <div class="col-sm-8">
                        <select id="Prs_Sex_m" name="Prs_Sex_m" class="form-control input-xs" required="">
                          <option value="M">MASCULINO</option>
                          <option value="F">FEMENINO</option>
                        </select>
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="col-sm-4 control-label label-sm required" for="Ciu_Cod_m">Ciudad:</label>
                      <div class="col-sm-8">
                        <select name="Ciu_Cod_m" id="Ciu_Cod_m" data-placeholder="Seleccione una ciudad" class="chzn-select-template-example">
                          <option value="" data-provincia="" data-pais=""></option>
                          <?Php foreach ($rows_ciudad as $row) {?>
                            <option value="<?Php echo $row['Ciu_Cod']; ?>" data-provincia="<?Php echo $row['Pro_Nom']; ?>" data-pais="<?Php echo $row['Pas_Nom']; ?>"><?Php echo $row['Ciu_Des']; ?></option>
                            <?Php } ?>
                          </select>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Prs_Tel_m">Tel&eacute;fono:</label>
                        <div class="col-sm-8">
                          <input id="Prs_Tel_m" name="Prs_Tel_m" class="form-control input-xs" placeholder="" type="text" onkeypress="return validar_numeric(event);"/>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-4 control-label label-sm" for="Prs_Cor_m">Email:</label>
                        <div class="col-sm-8">
                          <input id="Prs_Cor_m" name="Prs_Cor_m" class="form-control input-xs" placeholder="" type="text"/>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required" for="Prs_Dir_m">Direcci&oacute;n:</label>
                        <div class="col-sm-8">
                          <textarea id="Prs_Dir_m" name="Prs_Dir_m" class="form-control input-xs" style="resize: none;" required=""></textarea>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-4 control-label label-sm required" for="Soc_Fec_m">Socio desde:</label>
                        <div class="col-sm-8">
                          <div class="input-group input-group-xs">
                            <input id="Soc_Fec_m" name="Soc_Fec_m" class="form-control input-xs" placeholder="yy-mm-dd" type="text" readonly=""/>
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-sm-12">
                          <fieldset class="exa-fieldset">
                            <legend>
                              <label class="Titulos2">Aportaci&oacute;n inicial</label>
                            </legend>
                            <div class="form-group">
                              <label class="col-sm-4 control-label label-sm required" for="Apo_Val_m">Valor:</label>
                              <div class="col-sm-8">
                                <input type="text" name="apoini_codmod" id="apoini_codmod" value="" style="display:none;">
                                <input type="text" name="ind_cr_apoini" id="ind_cr_apoini" value="" style="display:none;">
                                <input id="Apo_Val_m" name="Apo_Val_m" class="form-control input-xs" placeholder="" type="text" onkeypress="return  validar_decimal(event)"/>
                              </div>
                            </div>
                            <div class="form-group">
                              <label class="col-sm-4 control-label label-sm required" for="Apo_Fec_m">Fecha:</label>
                              <div class="col-sm-8">
                                <div class="input-group input-group-xs">
                                  <input id="Apo_Fec_m" name="Apo_Fec_m" class="form-control input-xs" placeholder="yy-mm-dd" type="text" readonly=""/>
                                </div>
                              </div>
                            </div>
                          </fieldset>
                        </div>
                      </div>
                      <br>
                      <!-- Buttons -->
                      <div class="form-group">
                        <label class="col-md-4 control-label" for="btnModificar"></label>
                        <div class="col-md-8">
                          <button type="button" id="btnModificar" name="btnModificar" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i> Modificar</button>
                        </div>
                      </div>
                    </fieldset>
                  </div>
                </form>
              </div>
              <!-- END MODAL MODIFICAR-->
              <!-- modal detalles de la aportacion -->
              <div id="detAportacion" title="Detalles del comprobante y asientos contables" style="display: none">
                <div class="row">
                  <div class="col-sm-12">
                    <div class="row">
                      <div class="col-sm-6">
                        <fieldset class="exa-fieldset">
                          <legend>
                            <label class="Titulos2">Datos del Comprobante</label>
                          </legend>
                          <div class="col-sm-12" style="text-align:right;">
                            <div class="row esp_fields">
                              <label class="col-sm-6 control-label label-sm" for="det_apo_no_comp">No. comprobante:</label>
                              <div class="col-sm-6">
                                <input id="det_apo_no_comp" name="det_apo_no_comp" class="form-control input-xs" type="text" readonly/>
                              </div>
                            </div>
                            <div class="row esp_fields">
                              <label class="col-sm-6 control-label label-sm" for="det_apo_fec_comp">Fecha:</label>
                              <div class="col-sm-6">
                                <input id="det_apo_fec_comp" name="det_apo_fec_comp" class="form-control input-xs" type="text" readonly/>
                              </div>
                            </div>
                            <div class="row esp_fields">
                              <label class="col-sm-6 control-label label-sm" for="det_apo_val_comp">Valor:</label>
                              <div class="col-sm-6">
                                <input id="det_apo_val_comp" name="det_apo_val_comp" class="form-control input-xs" type="text" readonly/>
                              </div>
                            </div>
                          </div>
                        </fieldset>
                      </div>
                      <div class="col-sm-6">
                        <fieldset class="exa-fieldset">
                          <legend>
                            <label class="Titulos2">Datos del Socio</label>
                          </legend>
                          <div class="col-sm-12" style="text-align:right;">
                            <div class="row esp_fields">
                              <label class="col-sm-4 control-label label-sm" for="det_apo_ced_soc">C&eacute;dula:</label>
                              <div class="col-sm-8">
                                <input id="det_apo_ced_soc" name="det_apo_ced_soc" class="form-control input-xs" type="text" readonly/>
                              </div>
                            </div>
                            <div class="row esp_fields">
                              <label class="col-sm-4 control-label label-sm" for="det_apo_nom_soc">Nombre:</label>
                              <div class="col-sm-8">
                                <input id="det_apo_nom_soc" name="det_apo_nom_soc" class="form-control input-xs" type="text" readonly/>
                              </div>
                            </div>
                            <div class="row esp_fields">
                              <label class="col-sm-4 control-label label-sm" for="det_apo_dir_soc">Direcci&oacute;n:</label>
                              <div class="col-sm-8">
                                <input id="det_apo_dir_soc" name="det_apo_dir_soc" class="form-control input-xs" type="text" readonly/>
                              </div>
                            </div>
                          </div>
                        </fieldset>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                          <legend>
                            <label class="Titulos2">Observaci&oacute;n</label>
                          </legend>
                          <div class="col-sm-12">
                            <div class="row">
                              <textarea name="det_apo_obs" id="det_apo_obs" class="form-control input-xs" readonly></textarea>
                            </div>
                          </div>
                        </fieldset>
                      </div>
                    </div>
                    <br>
                    <div class="row">
                      <div class="col-sm-12">
                        <div id="tabs_apo" class="ui-tab-fix">
                          <ul style="font-size: 12px;" role="tablist">
                            <li><a id="verAsidat" href="#asieapos">Asientos</a></li>
                            <li id="verChedat"><a href="#cheapos">Cheque</a></li>
                          </ul>
                          <div id="asieapos">
                            <div class="row">
                              <div class="col-sm-12">
                                <fieldset class="exa-fieldset">
                                  <legend>
                                    <label class="Titulos2">Datos del Asiento Contable</label>
                                  </legend>
                                  <div class="col-sm-12">
                                    <div class="row">
                                      <div id="det_apo_asien" style="width: 100%;padding-top: 10px;">
                                        <table id="apo_asien"></table>
                                        <div id="apo_asienPager"></div>
                                      </div>
                                    </div>
                                  </div>
                                </fieldset>
                              </div>
                            </div>
                          </div>
                          <div id="cheapos">
                            <div class="row">
                              <div class="col-sm-12">
                                <fieldset class="exa-fieldset">
                                  <legend>
                                    <label class="Titulos2">Datos del Cheque</label>
                                  </legend>
                                  <div class="col-sm-6" style="text-align:right;">
                                    <div class="row esp_fields">
                                      <label class="col-sm-6 control-label label-sm" for="det_apo_no_che">No. Cheque:</label>
                                      <div class="col-sm-6">
                                        <input id="det_apo_no_che" name="det_apo_no_che" class="form-control input-xs" type="text" readonly/>
                                      </div>
                                    </div>
                                    <div class="row esp_fields">
                                      <label class="col-sm-6 control-label label-sm" for="det_apo_cheval">Valor:</label>
                                      <div class="col-sm-6">
                                        <input id="det_apo_cheval" name="det_apo_cheval" class="form-control input-xs" type="text" readonly/>
                                      </div>
                                    </div>
                                  </div>
                                  <div class="col-sm-6" style="text-align:right;">
                                    <div class="row esp_fields">
                                      <label class="col-sm-4 control-label label-sm" for="det_apo_no_cta">No. Cuenta:</label>
                                      <div class="col-sm-8">
                                        <input id="det_apo_no_cta" name="det_apo_no_cta" class="form-control input-xs" type="text" readonly/>
                                      </div>
                                    </div>
                                    <div class="row esp_fields">
                                      <label class="col-sm-4 control-label label-sm" for="det_apo_ban">Banco:</label>
                                      <div class="col-sm-8">
                                        <input id="det_apo_ban" name="det_apo_ban" class="form-control input-xs" type="text" readonly/>
                                      </div>
                                    </div>
                                  </div>
                                </fieldset>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- fin modal detalles de la aportacion -->

              <!-- inicio modal imprimir comprobante guardado -->
              <div id="successDialog"  title="Mensaje del Sistema">
                <center><h2>El Comprobante se ha registrado con Exito!</h2></center>
                <center>
                  <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-danger fileinput-button" style="display: inline;" >
                    <i class="icon-ban-circle icon-white"></i>
                    <span>Cerrar</span>
                  </button>
                  <a id="impCompr" target="_blank" href=""  style="display: inline;" title="Imprimir Comprobante"><span  class="btn btn-success start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></span> </a>
                </center>
              </div>
              <!-- fin del modal imrpimir comprobante despues de guiardar -->

              <div id="cuenDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
              <div id="cuen2Dialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>

              <div id="exportar" style="display: none">
                <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, '<p style="margin-left:10%;">REPORTE DETALLADO DE APORTACIONES DE LOS SOCIOS</p>', '<span style="margin-left:10%;" class="subtitle">Total de registros</span>', $obBD_conexion,false,8) ?>
                <table id="tablaExportaApo" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>
              </div>
              <div id="imprimir" style="display: none">
                <div style="width: 1030px;">
                  <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, '<p style="margin-left:10%;">REPORTE DETALLADO DE APORTACIONES DE LOS SOCIOS</p>', '<span style="margin-left:10%;" class="subtitle">Total de registros</span>', $obBD_conexion,false,9) ?>
                  <table id="tablaimpaApo" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>
                  <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
                </div>
              </div>
              <div id="exportarGen" style="display: none">
                <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, '<p style="margin-left:10%;">REPORTE GENERAL DE APORTACIONES DE LOS SOCIOS</p>', '<span style="margin-left:10%;" class="subtitle">Total de registros</span>', $obBD_conexion,false,9) ?>
                <table id="tablaExportaApoGen" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>
              </div>
              <div id="imprimirGen" style="display: none">
                <div style="width: 1030px;">
                  <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, '<p style="margin-left:10%;">REPORTE GENERAL DE APORTACIONES DE LOS SOCIOS</p>', '<span style="margin-left:10%;" class="subtitle">Total de registros</span>', $obBD_conexion,false,9) ?>
                  <table id="tablaimpaApoGen" cellspacing="0" cellpadding="0" style="width: 1030px; border-collapse: collapse;table-layout: fixed;"></table>
                  <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod,$Ses_Usu_Cod,$obBD_conexion); ?>
                </div>
              </div>

              <div id="cuenmodDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>
              <div id="cuenmod2Dialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>

            </div>
          </div>
        </div>
      </div>
      <script>
      var numeroCheque=false;

      //verifica si el numero un cheque esta repetido excepto e numero que se cargo para la modificacion
      function verificarNoCheque2(valor){
        datach ={"verificarCheNum2":true,"Che_Num_mod":valor, "Che_Num_mod_ori":$("#Che_Num_mod_ori").val(), "Bak_Cod_mod":$("#Bak_Cod_mod").val(),"Prs_Cod_mod":$("#Prs_Cod_mod").val(),"Che_Cod_mod":$("#Che_Cod_mod").val()};
        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",datach, function( response ) {
          if (response['numero_che'] === true) {
            $("#indicadorChe2").removeClass("green glyphicon glyphicon-ok");
            $("#indicadorChe2").removeClass("red glyphicon glyphicon-remove");
            $("#indicadorChe2").addClass("red glyphicon glyphicon-remove");
            numeroCheque=true;
          } else {
            numeroCheque=false;
            if(valor===""){
              $("#indicadorChe2").removeClass("green glyphicon glyphicon-ok");
              $("#indicadorChe2").removeClass("red glyphicon glyphicon-remove");
              $("#indicadorChe2").addClass("red glyphicon glyphicon-remove");
            }else{
              $("#indicadorChe2").removeClass("red glyphicon glyphicon-remove");
              $("#indicadorChe2").removeClass("green glyphicon glyphicon-ok");
              $("#indicadorChe2").addClass("green glyphicon glyphicon-ok");
            }
          }
        },'json');
      }

      //funcion para verificfar datos del formuklario de modificar aportacion y guardar
      function guardarModAportacion(){
        var batch = gridCompAsienMod.getGridBatch();
        var tot=parseFloat($("#Apo_Val_mod").val()),
        deb = gridCompAsienMod.jqGrid("getCol", "DebeMod", false, "sum"),
        hab = gridCompAsienMod.jqGrid("getCol", "HaberMod", false, "sum");
        var idsgapo= gridCompAsienMod.jqGrid('getDataIDs');
        if (idsgapo.length>1) {
          if(numeroCheque===false){
            if (deb===hab) {
              if (deb===tot && hab===tot) {
                if (deb===tot) {
                  if (hab===tot) {
                    var data=$('#formModAport').serializeObject();
                    data["modificarAportacionSoc"]=true;
                    data["savemod"]=batch;
                    $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function( responce ) {
                      if(responce['success']===true){
                        $("#mod_aport_soc").addClass("hidden");
                        $("#tot_soc_apo").removeClass("hidden");
                        $("#mod_aport_soc").moveComp("#tot_soc_apo").updateGridsSizes();

                        limpiarFormCompMod();

                        $('#impCompr').attr('href',responce['link']);
                        $('#successDialog').dialog('open');
                        $("#"+$("#tsg_id").val()).trigger('reloadGrid',[]);
                      }else{
                        $.alert(responce['message']);
                        gridCompAsienMod.startGridEdit();
                      }
                    },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");gridCompAsienMod.startGridEdit();});
                  }else{
                    gridCompAsienMod.startGridEdit();
                    $.alert("El total del haber no coincide con el valor de la aportaci&oacute;n");
                  }
                }else{
                  gridCompAsienMod.startGridEdit();
                  $.alert("El total del debe no coincide con el valor de la aportaci&oacute;n");
                }
              }else{
                gridCompAsienMod.startGridEdit();
                $.alert("Los totales no coinciden con el valor de la aportaci&oacute;n");
              }
            }else{
              gridCompAsienMod.startGridEdit();
              $.alert("Los totales no coinciden");
            }
          }else{
            gridCompAsienMod.startGridEdit();
            $.alert("El n&uacute;mero de este cheque( "+$("#Che_Num_mod").val()+" ) ya esta registrdo");
          }
        }else{
          gridCompAsienMod.startGridEdit();
          $.alert("Hace falta agregar una cuenta");
        }
      }

      //carga los tipos de asiento en base al tipo de comprobante
      function cargartipoAsientoMod(valor){
        if(valor==="nada"){
          //removemos todos los elementos del select para tipo de asiento
          $('#Tia_Cod_mod option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de asiento
          $("#Tia_Cod_mod").append('<option value="" selected="selected">Seleccione un tipo de comprobante</option>');
          $("#Tia_Cod_mod").attr("disabled","disabled");
          $("#add_cnt").attr("disabled","disabled");
        }else{
          //habilitamos el select para tipos de asientos
          $("#Tia_Cod_mod").removeAttr("disabled");
          //removemos todos los elementos del select para tipo de asiento
          $('#Tia_Cod_mod option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de asiento
          $("#Tia_Cod_mod").append('<option value="" selected="selected">Seleccione...</option>');
        }

        if(valor === 'D'){
          <?Php
          $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(12, "D", $obBD_conexion);
          foreach ($row_rs_tipo_asien2 as $row)
          { ?>
            $("#Tia_Cod_mod").append('<option value="<?php echo $row['Tia_Cod']; ?>" ><?php echo $row['Tia_Abr'] ?> - <?php echo $row['Tia_Des'] ?></option>');
            <?php } ?>
          }
          if(valor === 'I'){
            <?Php
            $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(12, "I", $obBD_conexion);
            foreach ($row_rs_tipo_asien2 as $row)
            { ?>
              $("#Tia_Cod_mod").append('<option value="<?php echo $row['Tia_Cod']; ?>" ><?php echo $row['Tia_Abr'] ?> - <?php echo $row['Tia_Des'] ?></option>');
              <?php } ?>
            }
          }

      //genera un reporte detallado de las aportaciones de los socios
      function exportarAportacionDet(){
        $('#tablaExportaApo').html("");
        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{reporteDetalladoAporte:true}, function( response ) {
          if(response['success']===true){
            $('#tablaExportaApo').html(""+response['tablereport']);
            $.downloadFile($.exportarExcelBlob($('#exportar').html(), 'Aportaciones'), 'Aportaciones_' + $.getDate() + '.xls');
          }else{$.alert(response['message']);}
        },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
      }

      //genera unja vista de del reporte detallado de las aportaciones de los socios
      function imprimirAportacionDet(){
        $('#tablaimpaApo').html("");
        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{reporteDetalladoAporte:true}, function( response ) {
          if(response['success']===true){
            $('#tablaimpaApo').html(""+response['tablereport']);
            $('#imprimir').printElement();
          }else{$.alert(response['message']);}
        },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
      }

      //verifica si el numero de un cheque ya se encuentra registrado
      function verificarNoCheque(valor){
        datach ={"verificarCheNum":true,"Che_Num":valor, "Bak_Cod":$("#Bak_Cod").val(),"Prs_Cod_apo":$("#Prs_Cod_apo").val()};
        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",datach, function( response ) {
          if (response['numero_che'] === true) {
            $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
            $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
            $("#indicadorChe").addClass("red glyphicon glyphicon-remove");
            numeroCheque=true;
          } else {
            numeroCheque=false;
            if(valor===""){
              $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
              $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
              $("#indicadorChe").addClass("red glyphicon glyphicon-remove");
            }else{
              $("#indicadorChe").removeClass("red glyphicon glyphicon-remove");
              $("#indicadorChe").removeClass("green glyphicon glyphicon-ok");
              $("#indicadorChe").addClass("green glyphicon glyphicon-ok");
            }
          }
        },'json');
      }

      //carga los datos al modal "detAportacion" y abre el mismo
      function selectDetalle(par){
        $('#detAportacion').dialog('open');
        var sociodata = jQuery('#tableResultApo').jqGrid('getRowData', par[0]);
        var aportedata = jQuery('#'+par[1]).jqGrid('getRowData', par[2]);

        $("#det_apo_no_comp").val(aportedata.codigo_compro);
        $("#det_apo_obs").val(aportedata.Apo_Con);
        $("#det_apo_fec_comp").val(aportedata.Apo_Fec);
        $("#det_apo_val_comp").val("$ "+aportedata.Apo_Val);
        $("#det_apo_ced_soc").val(sociodata.Prs_Ced);
        $("#det_apo_nom_soc").val(sociodata.nombre);
        $("#det_apo_nom_soc").attr("title",""+sociodata.nombre);
        $("#det_apo_dir_soc").val(sociodata.Prs_Dir);
        $("#det_apo_dir_soc").attr("title",""+sociodata.Prs_Dir);

        $("#verAsidat").trigger("click");

        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{obtenerTipoPago:true, Pag_Cod:""+aportedata.Pag_Cod}, function( responce ) {
          if(responce['success']===true){

            if(responce['tipoPago']['Pag_Abr']==="CHE"){

              $("#verChedat").removeClass("hidden");
              $("#det_apo_cheval").val("$ "+aportedata.Apo_Val);
              $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{detApoCheAjax:true,Com:aportedata.Che_Cod}, function( response ) {
                if(response['success']===true){
                  $("#det_apo_no_che").val(""+response['Cheban']['Che_Num']);
                  $("#det_apo_no_cta").val(""+response['Cheban']['Che_Cta']);
                  $("#det_apo_ban").val(""+response['Cheban']['Bak_Des']);
                }else{$.alert(response['message']);}
              },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
            } else {
              $("#verChedat").removeClass("hidden");
              $("#verChedat").addClass("hidden");
            }
          }else{
            $.alert(responce['message']);
          }
        },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});

        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{detApoAjax:true,Com:aportedata.Com_Cod}, function( response ) {
          if(response['success']===true){

            $('#pagoDialog').dialog('open');

            $("#apo_asien").jqGrid("clearGridData");
            $("#apo_asien").jqGrid('setGridParam',{rowNum:response['asi']['records']});
            $("#apo_asien").jqGrid('setGridParam', {data:response['asi']['rows'],page:1,records:response['asi']['records'] }).trigger('reloadGrid');

            $("#apo_asien").trigger("reloadGrid");

          }else{$.alert(response['message']);}
        },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });
      }

      //verifica si se encuentra parametrizada la cuenta de aportaciones de los socios
      function aportaciones(row){
        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{verificarParametrizada:true}, function( responce ) {
          if(responce['success']===true){
            if (responce['verifica']==="si"){
              agg_aportaciones(row);
            }else{
              $.alert(responce['message']);
            }
          }else{
            $.alert(responce['message']);
          }
        },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
      }

      function preAnularCheComp(compro){
        $.createDialogConfirm('�Est&aacute; seguro que desea anular esta aportaci&oacute;n?',compro,anularComApoChe);
      }
      function anularComApoChe(compro){
        var dataanu={};
        dataanu["anularComApoChe"]=true;

        dataanu["Com_Cod"]=compro.Com_Cod;
        dataanu["Che_Cod"]=compro.Che_Cod;
        dataanu["Pag_Cod"]=compro.Pag_Cod;

        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",dataanu, function( responce ) {
          if(responce['success']===true){
            $.alert("Aportaci&oacute;n anulada!");
            $("#tableResultApo").trigger('reloadGrid',[]);
          }else{
            $.alert(responce['message']);
          }
        },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
      }

      //METODO PARA CARGAR DATOS DEL REGIUSTRO AL FORMULARIO DE MODIFICACION tipoPago obtenerTipoPago
      function modificarAportacion(aporte_comp){
        var bancosel="";
        var sociodata = jQuery('#tableResultApo').jqGrid('getRowData', aporte_comp[0]);

        tipo_de_pago_abr="";

        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{obtenerTipoPago:true, Pag_Cod:""+aporte_comp[1].Pag_Cod}, function( responce ) {
          if(responce['success']===true){
            tipo_de_pago_abr=""+responce['tipoPago']['Pag_Abr'];
            if(tipo_de_pago_abr==="EFE"){
              $("#add_cnt_mod").removeClass("hidden");
              $("#add_cnt_mod").addClass("hidden");

              $("#Bak_Cod_mod").attr("disabled","disabled");
              $("#Che_Fec_mod").attr("disabled","disabled");
              $("#Che_Num_mod").attr("disabled","disabled");
              $("#Che_Cta_mod").attr("disabled","disabled");

              //removemos todos los elementos del select para tipo de comprobantes
              $('#Tipo_comp_mod option').remove();
              //agregamos una opcion sin valor por defecto para el select de tipos de comprobantes
              $("#Tipo_comp_mod").append('<option value="nada">Seleccione...</option>');
              $("#Tipo_comp_mod").append('<option value="D">Diario</option>');
              $("#Tipo_comp_mod").append('<option value="I">Ingreso</option>');
            }
            if(tipo_de_pago_abr==="OTR"){
              anulable="sirm";
              $("#add_cnt_mod").removeClass("hidden");
              $("#Bak_Cod_mod").attr("disabled","disabled");
              $("#Che_Fec_mod").attr("disabled","disabled");
              $("#Che_Num_mod").attr("disabled","disabled");
              $("#Che_Cta_mod").attr("disabled","disabled");

              //removemos todos los elementos del select para tipo de comprobantes
              $('#Tipo_comp_mod option').remove();
              //agregamos una opcion sin valor por defecto para el select de tipos de comprobantes
              $("#Tipo_comp_mod").append('<option value="nada">Seleccione...</option>');
              $("#Tipo_comp_mod").append('<option value="D">Diario</option>');
              $("#Tipo_comp_mod").append('<option value="I">Ingreso</option>');
            }
            if(tipo_de_pago_abr==="CHE"){
              $("#add_cnt_mod").addClass("hidden");
              $("#add_cnt_mod").addClass("hidden");

              $("#Che_Cod_mod").val(""+aporte_comp[1].Che_Cod);
              $('#Tipo_comp_mod option').remove();
              //agregamos una opcion sin valor por defecto para el select de tipos de comprobantes
              $("#Tipo_comp_mod").append('<option value="nada">Seleccione...</option>');
              $("#Tipo_comp_mod").append('<option value="I">Ingreso</option>');

              $("#Bak_Cod_mod").removeAttr("disabled");
              $("#Che_Fec_mod").removeAttr("disabled");
              $("#Che_Num_mod").removeAttr("disabled");
              $("#Che_Cta_mod").removeAttr("disabled");

              $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{extCheBan:true,Che_Cod:aporte_comp[1].Che_Cod}, function( responce ) {
                if(responce['success']===true){
                  //accion aqui
                  bancosel=responce['rowCheban']['Bak_Cod'];
                  $("#Bak_Cod_mod option[value="+ bancosel +"]").prop("selected",true);
                  $("#Che_Num_mod").val(""+responce['rowCheban']['Che_Num']);
                  $("#Che_Num_mod_ori").val(""+responce['rowCheban']['Che_Num']);
                  $("#Che_Cta_mod").val(""+responce['rowCheban']['Che_Cta']);
                  $("#Che_Fec_mod").val(""+responce['rowCheban']['Che_Fec']);
                }else{
                  $.alert(responce['message']);
                }
              },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
            }
            //------------------------------------------
            $("#Pec_Cod_mod option[value="+ aporte_comp[1].Pec_Cod +"]").prop("selected",true);
            if(aporte_comp[1].Cli_Cod!=null){
              $("#Tipo_comp_mod option[value=I]").prop("selected",true);

              $('#Tia_Cod_mod option').remove();

              <?Php
              $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(12, "I", $obBD_conexion);
              foreach ($row_rs_tipo_asien2 as $row)
              { ?>
                $("#Tia_Cod_mod").append('<option value="<?php echo $row['Tia_Cod']; ?>" ><?php echo $row['Tia_Abr'] ?> - <?php echo $row['Tia_Des'] ?></option>');
                <?php } ?>
            }
            if(aporte_comp[1].Prv_Cod!=null){
              $("#Tipo_comp_mod option[value=D]").prop("selected",true);

              $('#Tia_Cod_mod option').remove();

              <?Php
              $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(12, "D", $obBD_conexion);
              foreach ($row_rs_tipo_asien2 as $row)
              { ?>
                $("#Tia_Cod_mod").append('<option value="<?php echo $row['Tia_Cod']; ?>" ><?php echo $row['Tia_Abr'] ?> - <?php echo $row['Tia_Des'] ?></option>');
                <?php } ?>
            }

            $("#Pag_Cod_mod option[value="+ aporte_comp[1].Pag_Cod +"]").prop("selected",true);

            $("#Tia_Cod_mod option[value="+ aporte_comp[1].Tia_Cod +"]").prop("selected",true);
          }else{
            $.alert(responce['message']);
          }
        },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});

        $("#tot_soc_apo").addClass("hidden");$("#mod_aport_soc").removeClass("hidden");$("#tot_soc_apo").moveComp("#mod_aport_soc").updateGridsSizes();
        var anulable="norm";

        setPeriodo();

        $("#Tipo_comp_mod").removeAttr("disabled");
        $("#Tia_Cod_mod").removeAttr("disabled");

        $("#tsg_id").val(""+aporte_comp[2]);
        $("#Apo_Cod_mod").val(""+aporte_comp[1].Apo_Cod);
        $("#Com_Cod_mod").val(""+aporte_comp[1].Com_Cod);

        $("#Com_Con_mod").val(""+aporte_comp[1].Apo_Con);
        $("#Apo_Val_mod").val(""+parseFloat(aporte_comp[1].Apo_Val).toFixed(2));
        $("#Com_Fec_mod").val(""+aporte_comp[1].Apo_Fec);

        $("#Prs_Cod_mod").val(sociodata.persona_cod);
        $("#Prs_Nom_mod").val(sociodata.nombre);
        $("#Prs_Ced_ap_mod").val(sociodata.Prs_Ced);
        $("#Che_Cod_mod").val("no");

        $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{detApoAjax:true,Com:aporte_comp[1].Com_Cod}, function( response ) {
          if(response['success']===true){
            for (var i = 0; i < response['asi']['rows'].length; i++) {
              if (response['asi']['rows'][i]['Pld_Cdc']===$("#Pld_Cdc_mod").val()) {
                if(!gridCompAsienMod.existsId(response['asi']['rows'][i]['Pld_Cod'])){
                  gridCompAsienMod.jqGrid('addRowData', response['asi']['rows'][i]['Pld_Cod'],
                  {Pld_Cod:response['asi']['rows'][i]['Pld_Cod'],grid_tipp:"acs",Det_Tip:"H",
                  Pld_Cdc:response['asi']['rows'][i]['Pld_Cdc'],Pld_Des:response['asi']['rows'][i]['Pld_Des'],
                  GlosaMod:response['asi']['rows'][i]['Asi_Glo'],DebeMod:"",HaberMod:response['asi']['rows'][i]['Haber']},"last");
                  gridCompAsienMod.startGridEdit();
                  gridCompAsienMod.updateGridDiario2();
                }
              }else{
                if (response['asi']['rows'][i]['Asi_Deh']==="D") {
                  if(!gridCompAsienMod.existsId(response['asi']['rows'][i]['Pld_Cod'])){
                    gridCompAsienMod.jqGrid('addRowData', response['asi']['rows'][i]['Pld_Cod'],
                    {Pld_Cod:response['asi']['rows'][i]['Pld_Cod'],grid_tipp:anulable,Det_Tip:"D",
                    Pld_Cdc:response['asi']['rows'][i]['Pld_Cdc'],Pld_Des:response['asi']['rows'][i]['Pld_Des'],
                    GlosaMod:response['asi']['rows'][i]['Asi_Glo'],DebeMod:response['asi']['rows'][i]['Debe'],HaberMod:""},"last");
                    gridCompAsienMod.startGridEdit();
                    gridCompAsienMod.updateGridDiario2();
                  }
                }
                if (response['asi']['rows'][i]['Asi_Deh']==="H") {
                  if(!gridCompAsienMod.existsId(response['asi']['rows'][i]['Pld_Cod'])){
                  gridCompAsienMod.jqGrid('addRowData', response['asi']['rows'][i]['Pld_Cod'],
                    {Pld_Cod:response['asi']['rows'][i]['Pld_Cod'],grid_tipp:anulable,Det_Tip:"H",
                    Pld_Cdc:response['asi']['rows'][i]['Pld_Cdc'],Pld_Des:response['asi']['rows'][i]['Pld_Des'],
                    GlosaMod:response['asi']['rows'][i]['Asi_Glo'],DebeMod:"",HaberMod:response['asi']['rows'][i]['Haber']},"last");
                    gridCompAsienMod.startGridEdit();
                    gridCompAsienMod.updateGridDiario2();
                  }
                }
              }
            }
          }else{$.alert(response['message']);}
        },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!"); });

        gridCompAsienMod.updateGridDiario2();
        // $("#tituloModifiAport").text("Socio: "+sociodata.nombre+" | No. Compr.: "+aporte_comp[1].codigo_compro);
        setPeriodo();
      }

      // cargar subgrilla con aprtaciones de los socios
      function cargarSociosApo(){
        limpiarFormCompMod();
        gridCompAsienMod.jqGrid("clearGridData", true).trigger("reloadGrid");
        $("#tot_soc_apo").removeClass("hidden");$("#mod_aport_soc").addClass("hidden");$("#mod_aport_soc").moveComp("#tot_soc_apo").updateGridsSizes();
        //funcion para cargar la grid con los socios
        $("#tableResultApo").createGrid({
          postData: $("#frm_bus_apo").getData("sociosAjax"), height: 250,
          colModel: [
            { label: '&nbsp;', name: 'persona_cod', width: 15,align:"center", hidden:true },
            {label: 'Cod. Int.', name: 'Soc_Cod', key:true, width: 30, align: "left"},
            {label: 'C&eacute;dula', name: 'Prs_Ced', width: 50, align: "left",cellattr:function(){return 'style="'+excelFormats.text+'"';}},
            {label: 'Nombre', name: 'nombre', width: 100, align: "left"},
            {label: 'Socio desde', name: 'Soc_Fec', width: 50, align: "left"},
            { label: 'Total de aportaciones', name: 'totapo', width: 80, align: 'right',
            formatter:'currency', formatoptions: {
              prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.',defaultValue:''
            },summaryType: "sum"},
            {label: 'Genero', name: 'Prs_Sex', width: 50, align: "left"},
            {label: 'Tel&eacute;fono', name: 'Prs_Tel', width: 50, align: "left"},
            {label: 'Correo', name: 'Prs_Cor', width: 75, align: "left"},
            {label: 'Direci&oacute;n', name: 'Prs_Dir', width: 140, align: "left"},
            {label: 'Estado', name: 'Soc_Est', width: 25, align: "left"}
        ],
        rowNum:1000,gridview: true, viewrecords: true,footerrow: true, userDataOnFooter: false,
        onSelectRow: function(rowid, e) { $("#tableResultApo").resetSelection();},
        subGridOptions: {
          "plusicon"  : "ui-icon-triangle-1-e","minusicon" : "ui-icon-triangle-1-s","openicon"  : "ui-icon-arrowreturn-1-e","reloadOnExpand" : false,"selectOnExpand" : true
        },subGrid: true,multiselect: false,
        subGridRowExpanded: function(subgrid_id, row_id) {
          var subgrid_table_id = subgrid_id+"_t";
          var socioid = jQuery('#tableResultApo').jqGrid('getRowData', row_id);
          $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
          $("#"+subgrid_table_id).jqGrid({
            url:"<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>?ajaxSubgrid="+socioid.Soc_Cod,datatype: "json",regional : 'es',
            autowidth : true, shrinkToFit: true,cmTemplate: {sortable:false},//colNames: ['No','Item','Qty','Unit','Line Total'],
            colModel: [
                  {label:'Cod.Int.',name:"Com_Cod",width:50,key:true,align:"center"},
                  {label:'.',name:"Che_Cod",width:80,align:"center",hidden:true},
                  {label:'.',name:"Cli_Cod",width:80,align:"center",hidden:true},
                  {label:'.',name:"Prv_Cod",width:80,align:"center",hidden:true},
                  {label:'..',name:"Pag_Cod",width:80,align:"center",hidden:true},
                  {label:'No. Compr.',name:"codigo_compro",width:45,align:"center"},
                  {label:'Fecha de aportaci&oacute;n',name:"Apo_Fec",width:45,align:"center"},
                  {label:'Valor',name:"Apo_Val",width:45, align: 'right', formatter:'currency', decimalPlaces: '2', formatoptions: {prefix:'$ ', thousandsSeparator:',',decimalSeparator:'.'}},
                  {label:'Observaci�n',name:"Apo_Con",width:100},
                  {label:'Tipo de pago',name:"Pag_Des",width:50,align:"center"},
                  {label:'<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'act1', width: 25, align: 'center',viewable: false,
                  formatter:function (cellvalue, options, rowObject) {
                    if (rowObject.Com_Cod==='-') {
                      return "-";
                    }else {
                      var sdparms = [row_id, ""+subgrid_table_id , rowObject.Com_Cod];
                      var amparms = [row_id, rowObject, ""+subgrid_table_id];
                      return  $.getGridButton(selectDetalle, sdparms, 'Ver datos del comprobante y asiento contable', 'info-sign','','info')+"&nbsp;"+
                      $.getGridButton(modificarAportacion, amparms, 'Modificar Aportaci&oacute;n', 'pencil','','success')+"&nbsp;"+
                      $.getGridButton(preAnularCheComp, rowObject, 'Anular Aportaci&oacute;n', 'remove','','danger');
                    }
                  }
                }
              ],beforeSelectRow: function(rowid, e) {return false;},
              rowNum:10000000, pager: "",height: '100%',
              loadComplete: function(){
                $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",{aportIni:true,socio:row_id}, function( responce ) {
                  if(responce['success']===true){
                    if (responce['rowsini']===null) {
                      // alert("nulo");
                    }else{
                      $("#"+subgrid_table_id).jqGrid("addRowData","-", {Com_Cod:"-",Che_Cod:"-",Pag_Cod:"-",codigo_compro:"-",Apo_Fec:responce['rowsini']['Apo_Fec'],Apo_Val:responce['rowsini']['Apo_Val'],Apo_Con:responce['rowsini']['Apo_Con'],Pag_Des:"-"}, "first");
                    }
                  }
                },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");});
              }
            });
          },
          loadComplete: function(sociosAjax){
            $('#tableResultApo tr').each(function () {
              if($(this).find("td").eq(9).text()==="Inactivo"){
                $(this).addClass("cellRed2");
                $(this).addClass("myAltRowClass");
              }
            });
            $('#tableResultApo').jqGrid('footerData', 'set', {
              Soc_Fec:"<div style='text-align:right;'>TOTAL:</div>",
              totapo: $(this).jqGrid('getCol', 'totapo', true, 'sum')
            },true);
          }
        }, false, "#tableResultApoPager");
        $("#tableResultApo").trigger('reloadGrid',[]);
      }

      //permite guardar la aportacion y los asientos que tenga
      function guardarAportacion(){
        var batch = gridCompAsien.getGridBatch();
        var     tot=parseFloat($("#Apo_Val").val()),
        deb = gridCompAsien.jqGrid("getCol", "Debe", false, "sum"),
        hab = gridCompAsien.jqGrid("getCol", "Haber", false, "sum");
        var idsgr= gridCompAsien.jqGrid('getDataIDs');
        if (idsgr.length>1) {
          if(numeroCheque===false){
            if (deb===hab) {
              if (deb===tot && hab===tot) {
                if (deb===tot) {
                  if (hab===tot) {
                    var data=$('#formAport').serializeObject();
                    data["saveAportacion"]=true;
                    data["save"]=batch;
                    $.post( "<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>",data, function( responseaport ) {
                      if(responseaport['success']===true){
                        totalSocios();
                        limpiarFormComp();
                        $('#impCompr').attr('href',responseaport['link']);
                        $('#successDialog').dialog('open');
                        $("#tableResult").trigger('reloadGrid',[]);
                      }else{
                        $.alert(responseaport['message']);
                        gridCompAsien.startGridEdit();
                      }
                    },'json').fail(function(error) { $.alert("El Servidor ha fallado en responder!");gridCompAsien.startGridEdit();});
                  }else{
                    gridCompAsien.startGridEdit();
                    $.alert("El total del haber no coincide con el valor de la aportaci&oacute;n");
                  }
                }else{
                  gridCompAsien.startGridEdit();
                  $.alert("El total del debe no coincide con el valor de la aportaci&oacute;n");
                }
              }else{
                gridCompAsien.startGridEdit();
                $.alert("Los totales no coinciden con el valor de la aportaci&oacute;n");
              }
            }else{
              gridCompAsien.startGridEdit();
              $.alert("Los totales no coinciden");
            }
          }else{
            gridCompAsien.startGridEdit();
            $.alert("El n&uacute;mero de este cheque( "+$("#Che_Num").val()+" ) ya esta registrdo");
          }
        } else {
          gridCompAsien.startGridEdit();
          $.alert("Hace falta agregar una cuenta");
        }
      }
      //funcion que permite agregar los valores del tipo de asiento de acuerdo al tipo de comprobante
      function cambiarAsiento(valor){
        if(valor==="nada"){
          //removemos todos los elementos del select para tipo de asiento
          $('#Tia_Cod option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de asiento
          $("#Tia_Cod").append('<option value="" selected="selected">Seleccione un tipo de comprobante</option>');
          $("#Tia_Cod").attr("disabled","disabled");
          $("#add_cnt").attr("disabled","disabled");
        }else{
          //habilitamos el select para tipos de asientos
          $("#Tia_Cod").removeAttr("disabled");
          //removemos todos los elementos del select para tipo de asiento
          $('#Tia_Cod option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de asiento
          $("#Tia_Cod").append('<option value="" selected="selected">Seleccione...</option>');
        }

        //Consultamos los tipos de asiento que en el campo "Tia_Ini" sean igual al "valor" enviado, pudiendo ser D, I, E  para
        // posteriormente asignar los valores consultados como elementos al select de tipos de asientos
        if(valor === 'D'){
          <?Php
          $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(12, "D", $obBD_conexion);
          foreach ($row_rs_tipo_asien2 as $row)
          { ?>
            $("#Tia_Cod").append('<option value="<?php echo $row['Tia_Cod']; ?>" ><?php echo $row['Tia_Abr'] ?> - <?php echo $row['Tia_Des'] ?></option>');
            <?php } ?>
          }
        if(valor === 'I'){
          <?Php
          $row_rs_tipo_asien2 = $obBD_con1->getArrayConsulta(12, "I", $obBD_conexion);
          foreach ($row_rs_tipo_asien2 as $row)
          { ?>
            $("#Tia_Cod").append('<option value="<?php echo $row['Tia_Cod']; ?>" ><?php echo $row['Tia_Abr'] ?> - <?php echo $row['Tia_Des'] ?></option>');
          <?php } ?>
        }
      }

      //permite agregar un tipo de cuenta de acuerdo al modo de pago seleccionado
      function load_plan_to_document(valor){
        if(valor==="nada"){
          //removemos todos los elementos del select para tipo de asiento
          $('#Tia_Cod option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de asiento
          $("#Tia_Cod").append('<option value="" selected="selected">Seleccione un tipo de comprobante</option>');
          $("#Tia_Cod").attr("disabled","disabled");
          $("#add_cnt").removeClass("hidden");
          $("#add_cnt").addClass("hidden");

          //removemos todos los elementos del select para tipo de asiento
          $('#Tipo_comp option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de asiento
          $("#Tipo_comp").append('<option value="nada" selected="selected">Seleccione un modo de pago</option>');
          $("#Tipo_comp").attr("disabled","disabled");
        }

        if (valor === "EFE"){
          $("#add_cnt").removeClass("hidden");
          $("#add_cnt").addClass("hidden");
          //habilitamos el select para tipos de comprobantes
          $("#Tipo_comp").removeAttr("disabled");
          //removemos todos los elementos del select para tipo de comprobantes
          $('#Tipo_comp option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de comprobantes
          $("#Tipo_comp").append('<option value="nada" selected="selected">Seleccione...</option>');
          $("#Tipo_comp").append('<option value="D">Diario</option>');
          $("#Tipo_comp").append('<option value="I">Ingreso</option>');

          gridCompAsien.jqGrid("clearGridData", true).trigger("reloadGrid");
          $("#Bak_Cod").attr("disabled","disabled");
          $("#Che_Fec").attr("disabled","disabled");
          $("#pos_fec").attr("disabled","disabled");
          $("#Che_Num").attr("disabled","disabled");
          $("#Che_Cta").attr("disabled","disabled");
          <?php
          $row_plan_ban = $obBD_con1->getRowConsulta(13, $Ses_Emp_Cod,$obBD_conexion);
          ?>
          if(!gridCompAsien.existsId("<?php echo $row_plan_ban['Pld_Cod']?>")){
            //Index,Pld_Cod,Det_Tip,Pld_Cdc,Pld_Des,Glosa, Debe,Haber
            gridCompAsien.jqGrid("addRowData","<?php echo $row_plan_ban['Pld_Cod']?>", {Pld_Cod:"<?php echo $row_plan_ban['Pld_Cod']?>",grid_tipp:"norm",Det_Tip:"D",Pld_Cdc:"<?php echo $row_plan_ban['Pld_Cdc']?>",Pld_Des:"<?php echo $row_plan_ban['Pld_Des']?>",Glosa:"",Debe:parseFloat(""+$("#Apo_Val").val()),Haber:""}, "last");
            gridCompAsien.startGridEdit();
            gridCompAsien.updateGridDiario();
          }
          <?php
          $row_plan_soc = $obBD_con1->getRowConsulta(14, $Ses_Emp_Cod,$obBD_conexion);
          ?>
          if(!gridCompAsien.existsId("<?php echo $row_plan_soc['Pld_Cod']?>")){
            gridCompAsien.jqGrid("addRowData","<?php echo $row_plan_soc['Pld_Cod']?>", {Pld_Cod:"<?php echo $row_plan_soc['Pld_Cod']?>",grid_tipp:"acs",Det_Tip:"H",Pld_Cdc:"<?php echo $row_plan_soc['Pld_Cdc']?>",Pld_Des:"<?php echo $row_plan_soc['Pld_Des']?>",Glosa:"",Debe:"",Haber:parseFloat(""+$("#Apo_Val").val())}, "last");
            gridCompAsien.startGridEdit();
            gridCompAsien.updateGridDiario();
          }
        }
        if(valor==="CHE"){
          $("#add_cnt").removeClass("hidden");
          $("#add_cnt").addClass("hidden");
          //removemos todos los elementos del select para tipo de asiento
          $('#Tia_Cod option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de asiento
          $("#Tia_Cod").append('<option value="" selected="selected">Seleccione un tipo de comprobante</option>');
          $("#Tia_Cod").attr("disabled","disabled");

          //habilitamos el select para tipos de comprobantes
          $("#Tipo_comp").removeAttr("disabled");
          //removemos todos los elementos del select para tipo de comprobantes
          $('#Tipo_comp option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de comprobantes
          $("#Tipo_comp").append('<option value="nada" selected="selected">Seleccione...</option>');
          $("#Tipo_comp").append('<option value="I">Ingreso</option>');

          gridCompAsien.jqGrid("clearGridData", true).trigger("reloadGrid");
          $("#Bak_Cod").removeAttr("disabled");
          $("#Che_Fec").removeAttr("disabled");
          $("#pos_fec").removeAttr("disabled");
          $("#Che_Num").removeAttr("disabled");
          $("#Che_Cta").removeAttr("disabled");
          <?php
          $row_plan_ban2 = $obBD_con1->getRowConsulta(15, $Ses_Emp_Cod,$obBD_conexion);
          ?>
          if(!gridCompAsien.existsId("<?php echo $row_plan_ban2['Pld_Cod']?>")){
            gridCompAsien.jqGrid("addRowData","<?php echo $row_plan_ban2['Pld_Cod']?>", {Pld_Cod:"<?php echo $row_plan_ban2['Pld_Cod']?>",grid_tipp:"norm",Det_Tip:"D",Pld_Cdc:"<?php echo $row_plan_ban2['Pld_Cdc']?>",Pld_Des:"<?php echo $row_plan_ban2['Pld_Des']?>",Glosa:"",Debe:parseFloat(""+$("#Apo_Val").val()),Haber:""}, "last");
            gridCompAsien.startGridEdit();
            gridCompAsien.updateGridDiario();
          }
          <?php
          $row_plan_soc2 = $obBD_con1->getRowConsulta(14, $Ses_Emp_Cod,$obBD_conexion);
          ?>
          if(!gridCompAsien.existsId("<?php echo $row_plan_soc2['Pld_Cod']?>")){
            gridCompAsien.jqGrid("addRowData","<?php echo $row_plan_soc2['Pld_Cod']?>", {Pld_Cod:"<?php echo $row_plan_soc2['Pld_Cod']?>",grid_tipp:"acs",Det_Tip:"H",Pld_Cdc:"<?php echo $row_plan_soc2['Pld_Cdc']?>",Pld_Des:"<?php echo $row_plan_soc2['Pld_Des']?>",Glosa:"",Debe:"",Haber:parseFloat(""+$("#Apo_Val").val())}, "last");
            gridCompAsien.startGridEdit();
            gridCompAsien.updateGridDiario();
          }
        }
        if(valor==="OTR"){
          $("#add_cnt").removeClass("hidden");
          //en caso de aportar un bien
          $("#Com_Con").focus();
          $('#Tipo_comp option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de comprobantes
          $("#Tipo_comp").append('<option value="nada" selected="selected">Seleccione...</option>');
          $("#Tipo_comp").append('<option value="D">Diario</option>');
          $("#Tipo_comp").append('<option value="I">Ingreso</option>');

          $("#Bak_Cod").attr("disabled","disabled");
          $("#Che_Fec").attr("disabled","disabled");
          $("#pos_fec").attr("disabled","disabled");
          $("#Che_Num").attr("disabled","disabled");
          $("#Che_Cta").attr("disabled","disabled");

          //habilitamos el select para tipos de comprobantes
          $("#Tipo_comp").removeAttr("disabled");
          gridCompAsien.jqGrid("clearGridData", true).trigger("reloadGrid");
          <?php
          $row_plan_ban = $obBD_con1->getRowConsulta(13, $Ses_Emp_Cod,$obBD_conexion);
          ?>
          if(!gridCompAsien.existsId("<?php echo $row_plan_ban['Pld_Cod']?>")){
            gridCompAsien.jqGrid("addRowData","<?php echo $row_plan_ban['Pld_Cod']?>", {Pld_Cod:"<?php echo $row_plan_ban['Pld_Cod']?>",grid_tipp:"sirm",Det_Tip:"D",Pld_Cdc:"<?php echo $row_plan_ban['Pld_Cdc']?>",Pld_Des:"<?php echo $row_plan_ban['Pld_Des']?>",Glosa:"",Debe:parseFloat(""+$("#Apo_Val").val()),Haber:""}, "last");
            gridCompAsien.startGridEdit();
            gridCompAsien.updateGridDiario();
          }
          <?php
          $row_plan_soc = $obBD_con1->getRowConsulta(14, $Ses_Emp_Cod,$obBD_conexion);
          ?>
          if(!gridCompAsien.existsId("<?php echo $row_plan_soc['Pld_Cod']?>")){
            gridCompAsien.jqGrid("addRowData","<?php echo $row_plan_soc['Pld_Cod']?>", {Pld_Cod:"<?php echo $row_plan_soc['Pld_Cod']?>",grid_tipp:"acs",Det_Tip:"H",Pld_Cdc:"<?php echo $row_plan_soc['Pld_Cdc']?>",Pld_Des:"<?php echo $row_plan_soc['Pld_Des']?>",Glosa:"",Debe:"",Haber:parseFloat(""+$("#Apo_Val").val())}, "last");
            gridCompAsien.startGridEdit();
            gridCompAsien.updateGridDiario();
          }
        }
      }

      //permite agregar un tipo de cuenta de acuerdo al modo de pago seleccionado para la modificacion
      function load_plan_to_document_mod(valor){
        if(valor==="nada"){
          //removemos todos los elementos del select para tipo de asiento
          $('#Tia_Cod_mod option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de asiento
          $("#Tia_Cod_mod").append('<option value="" selected="selected">Seleccione un tipo de comprobante</option>');
          $("#Tia_Cod_mod").attr("disabled","disabled");
          $("#add_cnt_mod").attr("disabled","disabled");

          //removemos todos los elementos del select para tipo de asiento
          $('#Tipo_comp_mod option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de asiento
          $("#Tipo_comp_mod").append('<option value="nada" selected="selected">Seleccione un modo de pago</option>');
          $("#Tipo_comp_mod").attr("disabled","disabled");

          $("#Bak_Cod_mod").attr("disabled","disabled");
          $("#Che_Fec_mod").attr("disabled","disabled");
          $("#Che_Num_mod").attr("disabled","disabled");
          $("#Che_Cta_mod").attr("disabled","disabled");
        }

        if (valor === "EFE"){
          $("#add_cnt_mod").removeClass("hidden");
          $("#add_cnt_mod").addClass("hidden");
          //habilitamos el select para tipos de comprobantes
          $("#Tipo_comp_mod").removeAttr("disabled");
          //removemos todos los elementos del select para tipo de comprobantes
          $('#Tipo_comp_mod option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de comprobantes
          $("#Tipo_comp_mod").append('<option value="nada" selected="selected">Seleccione...</option>');
          $("#Tipo_comp_mod").append('<option value="D">Diario</option>');
          $("#Tipo_comp_mod").append('<option value="I">Ingreso</option>');

          gridCompAsienMod.jqGrid("clearGridData", true).trigger("reloadGrid");
          $("#Bak_Cod_mod").attr("disabled","disabled");
          $("#Che_Fec_mod").attr("disabled","disabled");
          $("#Che_Num_mod").attr("disabled","disabled");
          $("#Che_Cta_mod").attr("disabled","disabled");
          <?php
          $row_plan_ban = $obBD_con1->getRowConsulta(13, $Ses_Emp_Cod,$obBD_conexion);
          ?>
          if(!gridCompAsienMod.existsId("<?php echo $row_plan_ban['Pld_Cod']?>")){
            //Index,Pld_Cod,Det_Tip,Pld_Cdc,Pld_Des,Glosa, Debe,Haber
            gridCompAsienMod.jqGrid("addRowData","<?php echo $row_plan_ban['Pld_Cod']?>", {Pld_Cod:"<?php echo $row_plan_ban['Pld_Cod']?>",grid_tipp:"norm",Det_Tip:"D",Pld_Cdc:"<?php echo $row_plan_ban['Pld_Cdc']?>",Pld_Des:"<?php echo $row_plan_ban['Pld_Des']?>",GlosaMod:"",DebeMod:parseFloat(""+$("#Apo_Val_mod").val()),HaberMod:""}, "last");
            gridCompAsienMod.startGridEdit();
            gridCompAsienMod.updateGridDiario2();
          }
          <?php
          $row_plan_soc = $obBD_con1->getRowConsulta(14, $Ses_Emp_Cod,$obBD_conexion);
          ?>
          if(!gridCompAsienMod.existsId("<?php echo $row_plan_soc['Pld_Cod']?>")){
            gridCompAsienMod.jqGrid("addRowData","<?php echo $row_plan_soc['Pld_Cod']?>", {Pld_Cod:"<?php echo $row_plan_soc['Pld_Cod']?>",grid_tipp:"acs",Det_Tip:"H",Pld_Cdc:"<?php echo $row_plan_soc['Pld_Cdc']?>",Pld_Des:"<?php echo $row_plan_soc['Pld_Des']?>",GlosaMod:"",DebeMod:"",HaberMod:parseFloat(""+$("#Apo_Val_mod").val())}, "last");
            gridCompAsienMod.startGridEdit();
            gridCompAsienMod.updateGridDiario2();
          }
        }
        if(valor==="CHE"){
          $("#add_cnt_mod").removeClass("hidden");
          $("#add_cnt_mod").addClass("hidden");
          //removemos todos los elementos del select para tipo de asiento
          $('#Tia_Cod_mod option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de asiento
          $("#Tia_Cod_mod").append('<option value="" selected="selected">Seleccione un tipo de comprobante</option>');
          $("#Tia_Cod_mod").attr("disabled","disabled");

          //habilitamos el select para tipos de comprobantes
          $("#Tipo_comp_mod").removeAttr("disabled");
          //removemos todos los elementos del select para tipo de comprobantes
          $('#Tipo_comp_mod option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de comprobantes
          $("#Tipo_comp_mod").append('<option value="nada" selected="selected">Seleccione...</option>');
          $("#Tipo_comp_mod").append('<option value="I">Ingreso</option>');

          gridCompAsienMod.jqGrid("clearGridData", true).trigger("reloadGrid");
          $("#Bak_Cod_mod").removeAttr("disabled");
          $("#Che_Fec_mod").removeAttr("disabled");
          $("#Che_Num_mod").removeAttr("disabled");
          $("#Che_Cta_mod").removeAttr("disabled");
          <?php
          $row_plan_ban2 = $obBD_con1->getRowConsulta(15, $Ses_Emp_Cod,$obBD_conexion);
          ?>
          if(!gridCompAsienMod.existsId("<?php echo $row_plan_ban2['Pld_Cod']?>")){
            gridCompAsienMod.jqGrid("addRowData","<?php echo $row_plan_ban2['Pld_Cod']?>", {Pld_Cod:"<?php echo $row_plan_ban2['Pld_Cod']?>",grid_tipp:"norm",Det_Tip:"D",Pld_Cdc:"<?php echo $row_plan_ban2['Pld_Cdc']?>",Pld_Des:"<?php echo $row_plan_ban2['Pld_Des']?>",GlosaMod:"",DebeMod:parseFloat(""+$("#Apo_Val_mod").val()),HaberMod:""}, "last");
            gridCompAsienMod.startGridEdit();
            gridCompAsienMod.updateGridDiario2();
          }
          <?php
          $row_plan_soc2 = $obBD_con1->getRowConsulta(14, $Ses_Emp_Cod,$obBD_conexion);
          ?>
          if(!gridCompAsienMod.existsId("<?php echo $row_plan_soc2['Pld_Cod']?>")){
            gridCompAsienMod.jqGrid("addRowData","<?php echo $row_plan_soc2['Pld_Cod']?>", {Pld_Cod:"<?php echo $row_plan_soc2['Pld_Cod']?>",grid_tipp:"acs",Det_Tip:"H",Pld_Cdc:"<?php echo $row_plan_soc2['Pld_Cdc']?>",Pld_Des:"<?php echo $row_plan_soc2['Pld_Des']?>",GlosaMod:"",DebeMod:"",HaberMod:parseFloat(""+$("#Apo_Val_mod").val())}, "last");
            gridCompAsienMod.startGridEdit();
            gridCompAsienMod.updateGridDiario2();
          }
        }
        if(valor==="OTR"){
          $("#add_cnt_mod").removeClass("hidden");
          //en caso de aportar un bien
          $("#Com_Con_mod").focus();
          $('#Tipo_comp_mod option').remove();
          //agregamos una opcion sin valor por defecto para el select de tipos de comprobantes
          $("#Tipo_comp_mod").append('<option value="nada" selected="selected">Seleccione...</option>');
          $("#Tipo_comp_mod").append('<option value="D">Diario</option>');
          $("#Tipo_comp_mod").append('<option value="I">Ingreso</option>');

          $("#Bak_Cod_mod").attr("disabled","disabled");
          $("#Che_Fec_mod").attr("disabled","disabled");
          $("#Che_Num_mod").attr("disabled","disabled");
          $("#Che_Cta_mod").attr("disabled","disabled");

          //habilitamos el select para tipos de comprobantes
          $("#Tipo_comp_mod").removeAttr("disabled");
          gridCompAsienMod.jqGrid("clearGridData", true).trigger("reloadGrid");
          <?php
          $row_plan_ban = $obBD_con1->getRowConsulta(13, $Ses_Emp_Cod,$obBD_conexion);
          ?>
          if(!gridCompAsienMod.existsId("<?php echo $row_plan_ban['Pld_Cod']?>")){
            gridCompAsienMod.jqGrid("addRowData","<?php echo $row_plan_ban['Pld_Cod']?>", {Pld_Cod:"<?php echo $row_plan_ban['Pld_Cod']?>",grid_tipp:"sirm",Det_Tip:"D",Pld_Cdc:"<?php echo $row_plan_ban['Pld_Cdc']?>",Pld_Des:"<?php echo $row_plan_ban['Pld_Des']?>",GlosaMod:"",DebeMod:parseFloat(""+$("#Apo_Val_mod").val()),HaberMod:""}, "last");
            gridCompAsienMod.startGridEdit();
            gridCompAsienMod.updateGridDiario2();
          }
          <?php
          $row_plan_soc = $obBD_con1->getRowConsulta(14, $Ses_Emp_Cod,$obBD_conexion);
          ?>
          if(!gridCompAsienMod.existsId("<?php echo $row_plan_soc['Pld_Cod']?>")){
            gridCompAsienMod.jqGrid("addRowData","<?php echo $row_plan_soc['Pld_Cod']?>", {Pld_Cod:"<?php echo $row_plan_soc['Pld_Cod']?>",grid_tipp:"acs",Det_Tip:"H",Pld_Cdc:"<?php echo $row_plan_soc['Pld_Cdc']?>",Pld_Des:"<?php echo $row_plan_soc['Pld_Des']?>",GlosaMod:"",DebeMod:"",HaberMod:parseFloat(""+$("#Apo_Val_mod").val())}, "last");
            gridCompAsienMod.startGridEdit();
            gridCompAsienMod.updateGridDiario2();
          }
        }
      }

      //Funci�n para modificar el socio
      function saveFormMod() {
        var formData = new FormData(document.getElementById("formDialog"));
        formData.append("modSocio", true);
        $.ajax({
          url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
          type: "post",dataType: "json",data: formData,cache: false,contentType: false,processData: false
        })
        .done(function (responsemod) {
          if (responsemod.success === true) {
            $.alert("Transaccion Realizada con &Eacute;xito!");
            $("#tableResult").trigger('reloadGrid',[]);
            $('#editDialog').dialog('close');
          } else {
            $.alert(responsemod.message);
          }
        });
      }

      // metodo para cargar los datoas del socio en el formulario de edicion de socios
      function modificarSocio(row){
        $('#Soc_Cod').val(row.Soc_Cod);
        var data = {Soc_Cod: $('#Soc_Cod').val(), buscarSocio: true};
        $.post("<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", data, function (sociosel) {
          $('#Prs_Ced_m').val(sociosel['sociomod']['Prs_Ced']);
          $('#Prs_Nom_m').val(sociosel['sociomod']['Prs_Nom']);
          $('#Prs_Ape_m').val(sociosel['sociomod']['Prs_Ape']);
          if(sociosel['sociomod']['Prs_Sex'] === 'M'){
            $('#Prs_Sex_m').prop('selectedIndex', 0);
          }else{
            $('#Prs_Sex_m').prop('selectedIndex', 1);
          }
          $('#Ciu_Cod_m').val(sociosel['sociomod']['Ciu_Cod']).trigger("chosen:updated");
          $('#Prs_Tel_m').val(sociosel['sociomod']['Prs_Tel']);
          $('#Prs_Cor_m').val(sociosel['sociomod']['Prs_Cor']);
          $('#Prs_Dir_m').val(sociosel['sociomod']['Prs_Dir']);
          $('#Soc_Fec_m').val(sociosel['sociomod']['Soc_Fec']);

          if(sociosel['apoinimod'] === null){
            $('#Apo_Val_m').val(parseFloat(0).toFixed(2));
            $('#ind_cr_apoini').val("nocreado");
          }else{
			$('#ind_cr_apoini').val("si");  
            $('#Apo_Fec_m').val(sociosel['apoinimod']['Apo_Fec']);
            $('#apoini_codmod').val(sociosel['apoinimod']['Apo_Cod']);
            $('#Apo_Val_m').val(parseFloat(sociosel['apoinimod']['Apo_Val']).toFixed(2));
          }

          }, 'json').fail(function () {alert();});
        $('#editDialog').dialog('open');
      }

      //Funci�n para registrar el socio
      function saveForm() {
        var formData = new FormData(document.getElementById("formSocio"));
        if ($("#checkApoIni").prop('checked')) {
          formData.append("saveApoIni", "si");
        }else{
          formData.append("saveApoIni", "no");
        }
        formData.append("saveSocio", true);
        $.ajax({
          url: '<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>',
          type: "post",dataType: "json",data: formData,cache: false,contentType: false,processData: false
        })
        .done(function (response) {
          if (response.success === true) {
            $.alert("Transaccion Realizada con &Eacute;xito!");
            limpiar();
          } else {
            $.alert(response.message);
          }
        });
      }

      //Funci�n para comprobar de que un socio existe o no
      function comprobarForm() {
        var cedula = $('#Prs_Ced').val();
        var campo = $('#Prs_Ced').attr('id');
        var respuesta = validar_cedula(cedula, campo);
        if (respuesta === true){
          var data = {Prs_Ced: cedula, existePersona: true};
          $.post("<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>", data, function (response) {
            if (response['cliente'] === true) {
              $("#Prs_Cod_cli").val("1");
            }else{
              $("#Prs_Cod_cli").val("0");
            }
            if (response['proveedore'] === true) {
              $("#Prs_Cod_prv").val("1");
            }else{
              $("#Prs_Cod_prv").val("0");
            }

            if (response['socio'] === true) {
              $('#formSocio').setData(response, false);
              $.alert('La persona ya se encuentra registrada como socio..!!');
              $( "#btn_guardar_form" ).attr( "disabled","");
            }
            else {
              $( "#btn_guardar_form" ).removeAttr("disabled");
              if (response['existe'] === true) {
                $('#formSocio').setData(response, false);
                $('#Ide_Cod').val(response['Ide_Cod']);
                $('#Ciu_Cod').val(response['Ciu_Cod']).trigger("chosen:updated");
                $('#Soc_Fec').focus();
              } else {
                $('#Prs_Nom').focus();
                limpiar();
                $('#Ide_Cod').val(response['Ide_Cod']);
                $('#Ide_Des').val(response['Ide_Des']);
                $('#Prs_Ced').val(response['Prs_Ced']);
              }
            }
          }, 'json').fail(function () {alert();});
        }
      }
    </script>
  </BODY>
</HTML>
