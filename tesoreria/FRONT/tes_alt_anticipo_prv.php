<?php

/**
 *
 * @abstract Premite registrar anticipos a Proveedores
 * @author Edison Moya
 * @version 1.0
 * Fecha de creacion  2018-03-09
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_anticipo_prv.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/**
 * Creacion del objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Ant_Prv($Ses_Dat_Dis);
/*
* Creacion del objeto para las consultas
*/
$obBD_con1 =  new Class_Log_Datos_Ant_Prv;

/* Configuraciones de la Empresa */
$configs = $obBD_con1->getRowConsulta(34, $Ses_Emp_Cod, $obBD_conexion);
if ($configs["Cof_NegCam"] == 'S') {
  $grupo_empresas = $obBD_con1->getRowConsulta(37, $Ses_Emp_Cod, $obBD_conexion); //Solo si tiene grupo de ecomar
  if (isset($negociacionesAjax)) {
    $Emp_Cod = $Ses_Emp_Cod;
    if (!empty($grupo_empresas["Emp_Cod"])) {
      $empresas = array_merge((array)$Emp_Cod, (array)$grupo_empresas["Emp_Cod"]);
      $Emp_Cod = implode(",", $empresas);
    }
    $data_negociaciones = $obBD_con1->getArrayConsulta(35,  $Emp_Cod . '*' . $search, $obBD_conexion);
    $obBD_con1->echoJson($data_negociaciones);
  }
}
//Seccion para obtener los proveedores registrados en la empresa
if (isset($proveedoresAjax)) {
  $obBD_con1->getPageGridJson(1, $_GET, $obBD_conexion, false);
}

/**Cargar cuentas para los anticipos */
if (isset($cuentasAjax)) {
  $obBD_con1->getPageGridJson(33, $_GET, $obBD_conexion);
}
/**Fin de los anticipos */

if (isset($cargar_cuentas_pagos)) {
  $resp['bandera'] = true;
  $resp['message'] = "No se ha logrado realizar la Transaccion";
  if ($tipo === 'INICIAL') {
    $data = $obBD_con1->getRowConsulta(3, "", $obBD_conexion);
    $resp['message'] = "ANTICIPOS A PROVEEDORES";
  }

  //  Datos de proveedores en opcion otros
  if ($tipo == 'EFE' || $tipo == 'OTR') {
    $data = $obBD_con1->getArrayConsulta(4, array('Ban_Tip' => 'C'), $obBD_conexion);
    $resp['message'] = "PAGOS EN ";
  }
  // Find de datos proveedores opcion otros


  if ($tipo === 'EFE' || $tipo === 'DEP') {
    $data = $obBD_con1->getArrayConsulta(4, array('Ban_Tip' => 'C'), $obBD_conexion);
    $resp['message'] = "PAGOS EN ";
  }
  if ($tipo === 'CHE' || $tipo === 'TRF') {
    $data = $obBD_con1->getArrayConsulta(4, array('Ban_Tip' => 'B'), $obBD_conexion);
    $resp['message'] = "PAGOS EN ";
  }

  if (count($data) < 1) {
    $resp['bandera'] = false;
  }

  $resp['data'] = $data;
  $resp['success'] = true;
  $obBD_con1->echoJson($resp);
}

//Secci�n ajax para guardar un nuevo socio en la base de datos
if (isset($saveAnticipo)) {
  //Bloquea el periodo contable
  //$obBD_con1->validaCierrePeriodo('anticipos_proveedores', 'Atp_Fec', 'Atp_Cod', $Atp_Fec, null, $obBD_conexion);

  $response['success'] = false;
  $response['arrayche'] = array();
  $response['message'] = "No se ha logrado realizar la Transaccion";
  $response['bnd_che'] = false;
  $imagenes_pagos = array();
  $archivos_guardados = array();

  // Los comprobantes son opcionales y solo se aceptan para pagos por transferencia.
  if (!empty($_FILES['Pap_img_archivos']['name']) && is_array($_FILES['Pap_img_archivos']['name'])) {
    $tipos_permitidos = array(
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/webp' => 'webp',
      'image/gif' => 'gif'
    );
    $directorio_abs = dirname(__FILE__) . '/../../facturacion/FRONT/' . intval($Ses_Emp_Cod) . '/compr_prov';
    $directorio_rel = 'facturacion/FRONT/' . intval($Ses_Emp_Cod) . '/compr_prov';

    foreach ($_FILES['Pap_img_archivos']['name'] as $pago_idx => $nombre_original) {
      $error_archivo = isset($_FILES['Pap_img_archivos']['error'][$pago_idx])
        ? intval($_FILES['Pap_img_archivos']['error'][$pago_idx])
        : UPLOAD_ERR_NO_FILE;
      if ($error_archivo === UPLOAD_ERR_NO_FILE || trim((string)$nombre_original) === '') {
        continue;
      }
      if ($error_archivo !== UPLOAD_ERR_OK) {
        $response['message'] = 'No se pudo cargar el comprobante de transferencia.';
        $obBD_con1->echoJson($response);
        exit();
      }

      $pago_post = isset($pago_anticipo_proveedores[$pago_idx])
        ? $pago_anticipo_proveedores[$pago_idx]
        : array();
      if (empty($pago_post['Pag_Abr']) || $pago_post['Pag_Abr'] !== 'TRF') {
        continue;
      }

      $tmp_archivo = $_FILES['Pap_img_archivos']['tmp_name'][$pago_idx];
      $tam_archivo = intval($_FILES['Pap_img_archivos']['size'][$pago_idx]);
      $info_imagen = @getimagesize($tmp_archivo);
      $mime_imagen = ($info_imagen && !empty($info_imagen['mime'])) ? $info_imagen['mime'] : '';
      if (!is_uploaded_file($tmp_archivo) || $tam_archivo <= 0 || $tam_archivo > 5242880 || !isset($tipos_permitidos[$mime_imagen])) {
        $response['message'] = 'El comprobante debe ser una imagen JPG, PNG, WEBP o GIF de máximo 5 MB.';
        $obBD_con1->echoJson($response);
        exit();
      }
      if (!is_dir($directorio_abs) && !@mkdir($directorio_abs, 0777, true) && !is_dir($directorio_abs)) {
        $response['message'] = 'No se pudo crear la carpeta para guardar los comprobantes.';
        $obBD_con1->echoJson($response);
        exit();
      }

      $nombre_archivo = 'transferencia_' . date('Ymd_His') . '_' . uniqid() . '.' . $tipos_permitidos[$mime_imagen];
      $destino_abs = $directorio_abs . '/' . $nombre_archivo;
      if (!move_uploaded_file($tmp_archivo, $destino_abs)) {
        $response['message'] = 'No se pudo guardar el comprobante de transferencia.';
        $obBD_con1->echoJson($response);
        exit();
      }
      $imagenes_pagos[$pago_idx] = $directorio_rel . '/' . $nombre_archivo;
      $archivos_guardados[] = $destino_abs;
    }
  }

  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

  $Pec_Cod = $obBD_con1->getRowConsulta(5, $Atp_Fec, $obBD_conexion);

  $var_mes = explode('-', $Atp_Fec);
  $Com_Num = $obBD_con1->codigoComprAuto($Tia_Cod, $Pec_Cod['Pec_Cod'], $var_mes[1], $obBD_conexion);

  //insertamos un comprobante y extraemos el id ingresado
  $obBD_con1->operacionobBD(6, array('Pec_Cod' => $Pec_Cod['Pec_Cod'], 'Prv_Cod' => $Prv_Cod, 'Com_Num' => $Com_Num, 'Com_Fec' => $Atp_Fec, 'Com_Con' => $Atp_Obs, 'Com_Val' => $Atp_Val, 'Tia_Cod' => $Tia_Cod), $obBD_conexion);
  $ultimo_comprobate = $obBD_con1->insercionid($obBD_conexion);

  //insertamos un anticipo a proveedores
  $obBD_con1->operacionobBD(7, array('Atp_Fec' => $Atp_Fec, 'Atp_Val' => $Atp_Val, 'Atp_Obs' => $Atp_Obs, 'Com_Cod' => $ultimo_comprobate, 'Prv_Cod' => $Prv_Cod), $obBD_conexion);
  $ultimo_anticipo = $obBD_con1->insercionid($obBD_conexion);

  //REGISTRAR LA NEGOCIACION DE CAMARON
  if (isset($Cod_Neg) && !empty($Cod_Neg) && $Cod_Neg != 0) {
    $obBD_con1->operacionobBD(36, $Cod_Neg . '*' . $ultimo_anticipo . '*' . 'ANTP', $obBD_conexion);
  }
  // insertamos los pagos y sus respectivos asientos
  $contador_cheque = 0;
  foreach ($pago_anticipo_proveedores as $pago_idx => $pago) {
    if ($pago['grid_tipp'] == 'pago') {
      // insertamos un asiento por cada pago
      $obBD_con1->operacionobBD(9, array('Com_Cod' => $ultimo_comprobate, 'Asi_Deh' => 'H', 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => $pago['Haber'], 'Pld_Cod' => $pago['Pld_Cod']), $obBD_conexion);
      $ultimo_asiento = $obBD_con1->insercionid($obBD_conexion);
      $pap_img = isset($imagenes_pagos[$pago_idx]) ? $imagenes_pagos[$pago_idx] : '';

      if ($pago['Pag_Abr'] == 'EFE' || $pago['Pag_Abr'] == 'DEP') {
        // insertamos un pago de anticipo a proveedores
        $obBD_con1->operacionobBD(8, array('Pap_Cto' => '', 'Pap_Ctd' => $pago['Pap_Ctd'], 'Pap_Val' => $pago['Haber'], 'Atp_Cod' => $ultimo_anticipo, 'Pag_Cod' => $pago['Pag_Cod'], 'Asi_Cod' => $ultimo_asiento, 'Pap_img' => $pap_img), $obBD_conexion);
      } else {
        // insertamos un pago de anticipo a proveedores
        $obBD_con1->operacionobBD(8, array('Pap_Cto' => $pago['Pap_Cto'], 'Pap_Ctd' => $pago['Pap_Ctd'], 'Pap_Val' => $pago['Haber'], 'Atp_Cod' => $ultimo_anticipo, 'Pag_Cod' => $pago['Pag_Cod'], 'Asi_Cod' => $ultimo_asiento, 'Pap_img' => $pap_img), $obBD_conexion);
      }

      if ($pago['Pag_Abr'] == 'CHE') {
        $response['bnd_che'] = true;
        $contador_cheque++;
        array_push($response['arrayche'], array('link' => "?codigo2=$contador_cheque&asi=" . $ultimo_asiento . "&ban=" . $pago['Ban_Cod'] . "&pro=" . $Prv_Cod, 'che' => "No.:" . $pago['Che_Num'] . " - Valor:$ " . $pago['Haber']));
        // insertamos un registro en la tabla cheque
        $obBD_con1->operacionobBD(12, array('Che_Cod' => $contador_cheque, 'Prv_Cod' => $Prv_Cod, 'Ban_Cod' => $pago['Ban_Cod'], 'Asi_Cod' => $ultimo_asiento, 'Che_Num' => $pago['Che_Num'], 'Che_Fec' => $pago['Che_Fec'], 'Che_Val' => $pago['Haber'], 'Che_Obs' => $Atp_Obs, 'Che_Ben' => $nombre), $obBD_conexion);
      }
    } else {
      $Pld_Cod_ini = $obBD_con1->getRowConsulta(3, "", $obBD_conexion);

      // insertamos un asiento por cada pago
      $obBD_con1->operacionobBD(9, array('Com_Cod' => $ultimo_comprobate, 'Asi_Deh' => 'D', 'Asi_Glo' => $pago['Glosa'], 'Asi_Val' => $pago['Debe'], 'Pld_Cod' => $Pld_Cod_ini['Pld_Cod']), $obBD_conexion);
    }
  }
  $Pec_Cod_val = $Pec_Cod['Pec_Cod'];
  $response['link'] = "../../contabilidad/FRONT/con_pri_compr_2.1.php?codigo=$ultimo_comprobate&tabla=proveedore&campo=Prv_Cod&tipo=$Tia_Cod&Pec_Cod=$Pec_Cod_val";

  $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
  if ($obBD_con1->Error == 0) {
    $response['success'] = true;
  } else {
    foreach ($archivos_guardados as $archivo_guardado) {
      if (is_file($archivo_guardado)) {
        @unlink($archivo_guardado);
      }
    }
  }
  $obBD_con1->echoJson($response);
  exit();
}

if (isset($obtenerPeriodoMinMax)) {
  $resp['success'] = false;
  $resp['message'] = "No se ha logrado realizar la Transaccion";

  $resp['data'] = $obBD_con1->getRowConsulta(11, "", $obBD_conexion);

  $resp['success'] = true;
  $obBD_con1->echoJson($resp);
}

//verificamos si el numero de un cheque ya esta registrado dentro de la tabla cheques
if (isset($verificarCheNum)) {
  //Se obtiene el socio seleccionado
  $response['numero_che'] = false;
  $num_Ches = $obBD_con1->getArrayConsulta(13, $Ban_Cod, $obBD_conexion);
  foreach ($num_Ches as $nch) {
    if ($nch['Che_Num'] == $Che_Num) {
      $response['numero_che'] = true;
    }
  }

  $obBD_con1->echoJson($response);
  exit();
}
?>

<!DOCTYPE html>
<HTML>

<HEAD>
  <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
  <TITLE><?Php echo "Ant.Proveedor Registrar [EXA]"; ?></TITLE>
  <meta charset="UTF-8">
  <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
  <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
  <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
  <script src="../VALIDACIONES/tes_val_anticipo_prv.js?a=22"></script>
  <style>
    .pagination>li>a,
    .pagination>li>span {
      padding: 4px 2px;
    }

    .pagination {
      /*display: block;*/
      margin: 0;
      padding: 0;
    }

    .chosen-default span,
    .chosen-single span {
      color: #555;
    }

    .chosen-single span {
      padding-left: 5px;
    }

    .tes-comprobante-control {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 8px;
    }

    .tes-comprobante-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      margin: 0;
      padding: 4px 8px;
      border: 1px solid #b8c0c7;
      border-radius: 4px;
      background: linear-gradient(180deg, #fff 0%, #eef1f3 100%);
      color: #4f5b64;
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 1px 2px rgba(45, 55, 65, .12);
    }

    .tes-comprobante-btn:hover,
    .tes-comprobante-btn:focus {
      border-color: #929da6;
      color: #303940;
      background: #e4e8eb;
    }

    .tes-comprobante-nombre {
      overflow: hidden;
      max-width: 105px;
      color: #5f6b76;
      font-size: 11px;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .tes-comprobante-preview {
      display: none;
      width: 58px;
      height: 58px;
      border: 1px solid #cbd5df;
      border-radius: 4px;
      object-fit: cover;
      background: #fff;
    }

    .tes-comprobante-accion {
      display: none;
      padding: 2px 5px;
      border: 0;
      background: transparent;
      color: #2878b8;
    }

    .tes-comprobante-quitar {
      color: #c0392b;
    }

    .tes-comprobante-ayuda {
      display: block;
      margin-top: 5px;
      color: #7b8792;
      font-size: 10px;
    }
  </style>
</HEAD>

<BODY>
  <div class="panel panel-main">
    <div class="panel-heading exa-header">
      <h3 class="panel-title">&raquo;Anticipos de Proveedores</h3>
    </div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
      <div class="row">
        <form class="form-horizontal normal" id="AnticipoPrvForm" method="post" action="javascript:$.createDialogConfirm('�Est&aacute; seguro que desea guardar los datos?',null,guardar_anticipo)">
          <div class="col-sm-12">
            <div class="form-group Titulos2">
              <div class="col-sm-12">
                <b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.
                <hr />
              </div>
            </div>
          </div>

          <div class="col-sm-12">
            <div class="row">
              <div class="col-sm-6">
                <fieldset class="exa-fieldset">
                  <legend class="Titulos2">Datos del Proveedor</legend>
                  <div class="form-group">
                    <label class="col-sm-4 control-label label-sm required">C&eacute;dula/RUC:</label>
                    <div class="col-sm-6">
                      <input name="bandera_prov" id="bandera_prov" type="text" value="nosel" style="display:none;" />
                      <input name="Prs_Cod" id="Prs_Cod" type="text" style="display:none;" />
                      <input name="Prv_Cod" id="Prv_Cod" type="text" style="display:none;" />
                      <input name="save_bnd" id="save_bnd" type="text" value="n" style="display:none;" />
                      <input name="op_opciones" type="text" value="c" style="display: none;" />
                      <input name="Atp_Val" id="Atp_Val" type="text" value="0.00" style="display: none;" />
                      <div class="input-group input-group-sm">
                        <input name="Prs_Ced" id="Prs_Ced" type="text" placeholder="Seleccione o cree un proveedor..." class="form-control input-sm" tabindex="1" required="" readonly />
                        <span class="input-group-btn">
                          <button type="button" onclick="$('#proveedoresDialog').dialog('open');" class="btn btn-success btn-sm" title="Buscar Proveedor" tabindex="2"><span class="glyphicon glyphicon-list-alt"></span></button>
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-sm-4 control-label label-sm">Proveedor:</label>
                    <div class="col-sm-6"><input name="nombre" id="nombre" class="form-control input-sm databind datatitle" readonly /></div>
                  </div>
                  <div class="form-group">
                    <label class="col-sm-4 control-label label-sm">Direcci&oacute;n:</label>
                    <div class="col-sm-6"><input name="Prs_Dir" id="Prs_Dir" type="text" class="form-control input-sm databind datatitle" readonly /></div>
                  </div>

                  <!-- Negociacion -->
                  <?php if ($configs["Cof_NegCam"] == 'S') { ?>
                    <div class="form-group">
                      <label class="col-sm-4 control-label label-sm">Neg. camarón:</label>
                      <div class="col-sm-6">
                        <div class="input-group input-group-sm">
                          <input type="text" name="Num_Neg" id="Num_Neg" placeholder="Ingrese cod.Negociación..." class="form-control input-sm clearable dialogSearch" tabindex="1" readonly />
                          <input type="text" name="Cod_Neg" id="Cod_Neg" style="display:none;" />
                          <span class="input-group-btn">
                            <button id="Prv_Btn_" type="button" onclick="$('#negDialog').dialog('open');" class="btn btn-success btn-sm" title="Buscar Negociación" tabindex="2"><span class="glyphicon glyphicon-search"></span></button>
                          </span>
                        </div>
                      </div>
                    </div>
                  <?php } ?>

                </fieldset>
              </div>
              <div class="col-sm-6">
                <fieldset class="exa-fieldset">
                  <legend class="Titulos2">Datos del Anticipo</legend>
                  <div class="form-group">
                    <label class="col-sm-4 control-label label-sm required">Fecha:</label>
                    <div class="col-sm-6">
                      <div class="input-group">
                        <input name="Atp_Fec" type="text" id="Atp_Fec" size="10" class="form-control input-sm datepicker" required="" />
                        <span class="input-group-addon">
                          <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-sm-4 control-label label-sm required">Tipo de Asiento:</label>
                    <div class="col-sm-6">
                      <select id="Tia_Cod" name="Tia_Cod" class="form-control input-sm readOnly" required="">
                        <?php $rows_tipo_asiento = $obBD_con1->getArrayConsulta(10, "", $obBD_conexion);
                        if (count($rows_tipo_asiento) > 0) {
                          foreach ($rows_tipo_asiento as $row) {

                            if ($row["Tia_Cod"] != 32) {
                              echo "<option value='$row[Tia_Cod]'>$row[Tia_Abr] - $row[Tia_Des]</option>";
                            }

                            if ($row["Tia_Cod"] == 32 &&  $Ses_Emp_Cod == 20) {
                              echo "<option value='$row[Tia_Cod]'>$row[Tia_Abr] - $row[Tia_Des]</option>";

                              /*  if (
                                $Ses_Emp_Cod == 211 || $Ses_Emp_Cod == 241 || $Ses_Emp_Cod == 185 || $Ses_Emp_Cod == 69 || $Ses_Emp_Cod == 25
                                || $Ses_Emp_Cod == 356 || $Ses_Emp_Cod == 20 || $Ses_Emp_Cod == 44 || $Ses_Emp_Cod == 213
                              ) {
                              }*/
                            }
                          }
                        } ?>
                      </select>

                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-4 control-label label-sm ">Observaci&oacuten:</label>
                    <div class="col-sm-6">
                      <!-- <div class="input-group input-group-sm"> -->
                      <textarea class="form-control" id="Atp_Obs" val="" name="Atp_Obs" rows="2"></textarea>
                      <!-- </div> -->
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
          <div class="row">
            <div class="col-sm-12">
              <div id="contenedor_pagos" style="width: 100%;padding-top: 10px;">
                <table id="pagos"></table>
                <div id="pagosPager"></div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="center">
                <div class="center">
                  <br>
                  <button class="btn btn-sm btn-success no" onclick="preguardadopagos();"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Inicio del diálogo para buscar Proveedores -->
      <div id="proveedoresDialog" title="B&uacute;squeda de Proveedores">
        <form class="form-horizontal normal"> </form>
      </div>

      <!-- dialogo de registro de pagos de anticipo -->
      <div id="pagosDialog" title="Agregar Pagos">
        <form id="pagosForm" class="form-horizontal normal">
          <div class="form-group">
            <label class="col-xs-4 control-label label-xs required">Tipo:</label>
            <div class="col-xs-6">
              <select id="Pag_Cod" name="Pag_Cod" class="form-control input-xs readOnly" onchange="cambiarCamposPagos($(this).find(':selected').data().class, $('#Pag_Cod option:selected').attr('data-abr'))" required="">
                <?php $rows_tipo_pago = $obBD_con1->getArrayConsulta(2, "", $obBD_conexion);
                if (count($rows_tipo_pago) > 0) {
                  foreach ($rows_tipo_pago as $row) {
                    echo "<option value='$row[Pag_Cod]' data-abr='$row[Pag_Abr]' data-class='$row[Pag_Des]' >$row[Pag_Des]</option>";
                  }
                } ?>
              </select>
            </div>
          </div>

          <!-- Bancos de DataBase (Agrego la opcion otros)-->
          <div class="form-group Cheque Transferencia Efectivo Deposito Otros">
            <label class="col-xs-4 control-label label-xs required">Cuenta:</label>
            <div class="col-xs-6">
              <select id="Ban_Cod" name="Ban_Cod" class="form-control input-xs readOnly" required="">
              </select>
            </div>
          </div>

          <div class="form-group  Deposito Transferencia">
            <label class="col-xs-4 control-label label-xs required">Cta. Destino:</label>
            <div class="col-xs-6">
              <input type="text" id="Pap_Ctd" name="Pap_Ctd" onchange="" onkeypress="return soloNumeros(event)" class="form-control input-xs">
            </div>
          </div>

          <div class="form-group  Deposito Transferencia">
            <label class="col-xs-4 control-label label-xs required">No. Documento:</label>
            <div class="col-xs-6">
              <input type="text" id="Num_Doc" name="Num_Doc" onchange="" onkeypress="return soloNumeros(event)" class="form-control input-xs">
            </div>
          </div>

          <div class="form-group Transferencia" id="grupoPapImg">
            <label class="col-xs-4 control-label label-xs">Comprobante:</label>
            <div class="col-xs-7">
              <div class="tes-comprobante-control">
                <label for="Pap_img_archivo" class="tes-comprobante-btn" title="Seleccionar comprobante de pago">
                  <i class="glyphicon glyphicon-picture"></i>
                  <span>Cargar imagen</span>
                </label>
                <input type="file" id="Pap_img_archivo" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;" onchange="seleccionarComprobanteTransferencia(this)">
                <span id="Pap_img_nombre" class="tes-comprobante-nombre">Ningún archivo</span>
                <img id="Pap_img_preview" class="tes-comprobante-preview" alt="Vista previa del comprobante">
                <button type="button" id="Pap_img_ver" class="tes-comprobante-accion" onclick="verComprobanteTransferenciaActual()" title="Ampliar imagen">
                  <i class="glyphicon glyphicon-eye-open"></i>
                </button>
                <button type="button" id="Pap_img_quitar" class="tes-comprobante-accion tes-comprobante-quitar" onclick="quitarComprobanteTransferencia()" title="Quitar imagen">
                  <i class="glyphicon glyphicon-remove"></i>
                </button>
              </div>
              <small class="tes-comprobante-ayuda">Opcional · JPG, PNG, WEBP o GIF · máximo 5 MB</small>
            </div>
          </div>

          <div class="form-group Cheque">
            <label class="col-xs-4 control-label label-xs required">Fecha:</label>
            <div class="col-xs-6">
              <input name="Che_Fec" type="text" id="Che_Fec" size="10" class="form-control input-xs datepicker" required="" />
            </div>
          </div>

          <div class="form-group Cheque">
            <label class="col-xs-4 control-label label-xs required">No. cheque:</label>
            <div class="col-xs-6">
              <div class="input-group input-group-xs">
                <span class="input-group-addon"><i id="indicadorChe" class=""></i></span>
                <input type="text" id="Che_Num" name="Che_Num" onchange="" class="form-control input-xs" onkeyup="verificarNoCheque(this.value)" onkeypress="return soloNumeros(event)">
              </div>
            </div>
          </div>


          <div class="form-group Transferencia Deposito Efectivo Cheque Otros">
            <label class="col-xs-4 control-label label-sm required">Valor:</label>
            <div class="col-xs-6 ">
              <div class="input-group input-group-xs">
                <span class="input-group-addon"><i id="indicadorChe" class="glyphicon glyphicon-usd"></i></span>
                <input name="Pap_Val" type="text" id="Pap_Val" size="10" class="form-control input-xs" required="" autocomplete="off" onkeypress="return  validar_decimal(event)" />
              </div>
            </div>
          </div>

          <div class="form-group center">
            </br>
            <a class="btn btn-sm btn-primary" onclick="AgregarPago()"><i class="glyphicon glyphicon-floppy-disk"></i> Agregar</a>
          </div>
        </form>
      </div>

      <div id="comprobantePreviewDialog" title="Comprobante de transferencia">
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:280px;background:#f4f6f8;border-radius:6px;padding:10px;">
          <img id="comprobantePreviewGrande" alt="Comprobante de transferencia" style="display:block;max-width:100%;max-height:70vh;border-radius:5px;box-shadow:0 5px 18px rgba(0,0,0,.18);">
          <a id="comprobantePreviewDescargar" class="btn btn-success btn-sm" href="#" download style="margin-top:12px;">
            <i class="glyphicon glyphicon-download-alt"></i> Descargar imagen
          </a>
        </div>
      </div>


      <div id="successDialog" title="Mensaje del Sistema">
        <center>
          <h2>El Comprobante se ha registrado con Exito!</h2>
        </center>
        <center>
          <button type="button" onclick="$('#successDialog').dialog('close');" class="btn btn-danger fileinput-button" style="display: inline;">
            <i class="icon-ban-circle icon-white"></i>
            <span>Cerrar</span>
          </button>
          <a id="impCompr" target="_blank" href="" style="display: inline;" title="Imprimir Comprobante"><span class="btn btn-success start"> <i class="icon-print icon-white"></i> <span>Imprimir</span></span> </a>
          <br><br>
          <fieldset class="exa-fieldset" id="siche" hidden>
            <legend class="Titulos2">Impresi&oacute;n de Cheques</legend>
            <div>
              <center>
                <h5>Eliga el cheque que desea imprimir!</h5>
              </center>
              <div class="row">
                <div class="form-group">
                  <div class="col-sm-3"></div>
                  <div class="col-sm-6">
                    <div class="input-group">
                      <select id="Che_imp" name="Che_imp" class="form-control input-xs" onchange="cambiarChe()">
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <br>
              <div class="row">
                <?php $ruta = './' . (file_exists('cheques/' . $Ses_Emp_Cod) ? "cheques/$Ses_Emp_Cod/" : ''); ?>
                <div id="conten_bancos_imp">
                  <table style="margin-bottom:10px;" cellpadding="1" border="1">
                    <tr>
                      <td align="center" class="ui-widget-content" colspan="6"><b>&nbsp; plantillas &nbsp;</b></td>
                    </tr>
                    <tr id="impchetd">
                      <td align="center"><a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_mac_1.0.php" href="" target="_blank" title="Banco de Machala"><img src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" width="22" height="35" /></a></td>
                      <td align="center"><a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_pac_1.0.php" href="" target="_blank" title="Banco del Pacifico"><img src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" width="24" height="23" /></a></td>
                      <td align="center"><a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_rum_1.0.php" href="" target="_blank" title="Banco del Rumiñahui"><img src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" width="30" height="15" /></a></td>
                      <td align="center"><a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_gua_1.0.php" href="" target="_blank" title="Banco del Guayaquil"><img src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" width="36" height="18" /></a></td>
                      <td align="center"><a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_pch_1.0.php" href="" target="_blank" title="Banco del Pichincha"><img src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" width="36" height="30" /></a></td>
                      <td align="center"><a data-ruta="<?php echo $ruta; ?>tes_pri_cheque_int_1.0.php" href="" target="_blank" title="Banco Internacional"><img src="../../mascaras/model1/imagenes/32x32/ban_int.jpg" width="32" height="32" /></a></td>
                      <td align="center"><a data-ruta="<?php echo $ruta; ?>cheques/1/tes_pri_cheque_loj_1.0.php" href="" target="_blank" title="Banco de Loja"><img src="../../mascaras/model1/imagenes/32x32/banco_loja.jpg" width="32" height="32" /></a></td>
                    </tr>
                  </table>
                </div>
              </div>
            </div>
          </fieldset>
        </center>
      </div>


      <div id="verPagosDialog" title="Pago">
        <form id="verPagosForm" class="form-horizontal normal">
          <div class="form-group">
            <label class="col-xs-4 control-label label-xs">Tipo de pago:</label>
            <div class="col-xs-6">
              <input type="text" id="pago_ver" class="form-control input-xs" readonly>
            </div>
          </div>

          <div class="form-group Cheque">
            <label class="col-xs-4 control-label label-xs">No. cheque:</label>
            <div class="col-xs-6">
              <input type="text" id="numero_ver" class="form-control input-xs" readonly>
            </div>
          </div>

          <!-- Bancos de DataBase -->
          <div class="form-group Cheque Transferencia">
            <label class="col-xs-4 control-label label-xs">Cuenta:</label>
            <div class="col-xs-6">
              <input type="text" id="cuenta_ver" class="form-control input-xs" readonly>
            </div>
          </div>

          <div class="form-group  Deposito Transferencia">
            <label class="col-xs-4 control-label label-xs">Cta. Destino:</label>
            <div class="col-xs-6">
              <input type="text" id="destino_ver" class="form-control input-xs" readonly>
            </div>
          </div>

          <div class="form-group  Deposito Transferencia">
            <label class="col-xs-4 control-label label-xs">No. Documento:</label>
            <div class="col-xs-6">
              <input type="text" id="Num_DocPv" class="form-control input-xs" readonly>
            </div>
          </div>

          <div class="form-group Cheque">
            <label class="col-xs-4 control-label label-xs">Fecha:</label>
            <div class="col-xs-6">
              <input type="text" id="fecha_ver" class="form-control input-xs" readonly>
            </div>
          </div>

          <div class="form-group Transferencia Deposito Efectivo Cheque">
            <label class="col-xs-4 control-label label-sm">Valor:</label>
            <div class="col-xs-6 ">
              <input type="text" id="valor_ver" class="form-control input-xs" readonly>
            </div>
          </div>

          <div class="form-group center">
            </br>
            <a class="btn btn-sm btn-primary" onclick="$('#verPagosDialog').dialog('close');"><i class="glyphicon glyphicon-remove"></i> Cerrar</a>
          </div>
        </form>
      </div>

    </div>
  </div>
  <div id="cuentasDialog" title="B&uacute;squeda de Cuentas" style="display: none"></div>

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

  <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
  <script>
    //Ver negociaciones
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
            width: 30
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

</BODY>

</html>