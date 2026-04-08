<?php

/**
 * @abstract Permite realizar consultas de las compras realizadas
 * @author Wilson Belduma
 * @version 1.0
 * Fecha de cración: 21-06-2024
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_ven_lista.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);

/* Creacion del objeto mysql para las consultas */
$obBD_con1 = new Class_Log_Datos_Vent_Lista;
$obBD_con2 = new MysqlDatos(true);
$obBD_con1->debugLogs(false);

$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/* Compras */

/* Productos más vendidos */

if (isset($prodAjax)) {
  $obBD_con1->echoLog('** PHP PRODUCTOS AJAX ***');
  //ChromePhp::log('Compras');


  if ($Tip_Pago != '') {
    $tipopago = " HAVING  Pago='$Tip_Pago' ";
  }

  if ($order != '') {
    $order = " ORDER BY " . $order;
  }

  $cod_compra = "";
  if ($op_opciones == 'h') {
    $cod_compra = "AND com.Cop_Cod LIKE '%$search%' ";
  }

  $fec = ""; //  AND com.Cop_Fec BETWEEN DATE_FORMAT('$mes-01', '%Y-%m-%d') AND   LAST_DAY('$mes-01') ";
  $fec = " AND com.Cop_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin' ";
  /* if ($op_opciones == 'f') {
    $fec = " AND com.Cop_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin' ";
  }*/

  $cuenta = "";
  if ($op_opciones == 'c') {
    $cuenta = " AND det_plan.Pld_Des LIKE '%$search%' ";
    if ($cod_plan_cntas != '') {
      $cuenta = " AND det_plan.Pld_Cod = '$cod_plan_cntas' ";
    }
  }

  $nombre_proveedor = "";
  if ($op_opciones == 'nom_prov') {
    $nombre_proveedor = " AND CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) LIKE '%$search%' ";
  }

  $ced_ruc = "";
  if ($op_opciones == 'ced_ruc') {
    $ced_ruc = " AND persona.Prs_Ced LIKE '%$search%' ";
  }

  if ($Tip_Consumo != '') {
    $consumo = " AND com.Con_Cod ='$Tip_Consumo'";
  }

  if ($Tip_documento != '') {
    $consumo = " AND com.Tic_Cod ='$Tip_documento'";
  }

  if ($codigos_compras != '') {
    $sql_cod_compras = $codigos_compras;
  }

  $sucursal = "";
  if ($Suc_Cod != 'T') {
      $sucursal = " AND puntos_imp.Suc_Cod = '$Suc_Cod' ";
  }

  $resultado =  $obBD_con1->getArrayConsulta(42, $Ses_Emp_Cod . '*' . $cat . '*' . $tipopago . '*' . $order . '*' . $cod_compra . '*' . $fec . '*' . $consumo . '*' . $cuenta . '*' . $nombre_proveedor . '*' . $ced_ruc . '*' . $sql_cod_compras . '*' . $sucursal, $obBD_conexion);
  $obBD_con1->echoJson($resultado);
}

if (isset($comprasDetAjax)) {
  $responce['rows'] = $obBD_con1->getArrayConsulta(43, $comprasDetAjax, $obBD_conexion);
  $responce['records'] = count($responce['rows']);
  $responce['records'] = count($responce['rows']);
  $obBD_con1->echoJson($responce);
  exit();
}

if (isset($cuenAjax)) {

  if ($des_plan != '') {
    $data = " AND det_plan.Pld_Des LIKE '%$des_plan%'";
  }

  $resultado['rows'] = $obBD_con1->getArrayConsulta(47, $Ses_Emp_Cod . "*" . $data, $obBD_conexion);
  $obBD_con1->echoJson($resultado);
  exit();
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
  <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
  <TITLE><?Php echo "Compras Reportes [EXA]"; ?></TITLE>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
  <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
  <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
  <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
  <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
  <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
  <script> </script>
  <style>
    .ui-jqgrid .jqgrow td {
      white-space: normal !important;
      word-wrap: break-word;
    }

    @media print {
      #tablaReporte {
        width: 100%;
        font-size: 10pt;
      }

      #tablaReporte td,
      #tablaReporte th {
        word-wrap: break-word;
        white-space: normal;
      }
    }
  </style>
</HEAD>

<BODY>
  <div class="panel panel-main" id="formFinal">
    <div class="panel-heading exa-header">
      <h3 class="panel-title">&raquo; Datos Compras</h3>
    </div>
    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
      <div class="row">
        <div class="col-xs-12 ">
          <div id="tabsDatos" class="ui-tab-fix">
            <div class="panels-area form-horizontal normal ">
              <!-- CREAR TAB !-->
              <div id="tabs-1">
                <div class="row">
                  <form id="frm_prod_ven" name="frm_prod_ven" class="form-horizontal normal" action="javascript:$('#container').Search('#frm_prod_ven','prodAjax'); $('#codigos_compras').val(''); $('#cantidad_aproximada').val('')">
                  <fieldset class="exa-fieldset" id="prodFormTemp" style="margin-left: 15px; width: 97%;">
                      <div class="col-xs-12 col-sm-7">
                        <legend class="Titulos2">B&uacute;squeda</legend>
                        <input name="order" type="hidden" value="" />
                        <div class="form-group">
                          <label class="col-sm-2 control-label label-xs">Filtrar Por:</label>
                          <div class="col-sm-7 radioset opt_search">
                            <input id="radsc1" name="op_opciones" type="radio" value="h" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">Cod.Com</label>
                            <!--input id="radsc2" name="op_opciones" type="radio" value="f" onclick="setfocus(this.form.search)" alt="" /><label for="radsc2">Fecha</label-->
                            <!--input id="radsc3" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radsc3">Cuenta</label-->
                            <input id="radsc3" name="op_opciones" type="radio" value="c" onclick="open_plan_cuentas()" alt="" /><label for="radsc3">Cuenta</label>
                            <!--button id="Cli_Btn" type="button" onclick="$('#clieDialog').dialog('open');" class="btn btn-success btn-xs" title="Buscar Cliente" tabindex="2"><span class="glyphicon glyphicon-search"></span></button-->
                            <input id="radsc4" name="op_opciones" type="radio" value="nom_prov" onclick="setfocus(this.form.search)" alt="" /><label for="radsc4">Proveedor</label>
                            <input id="radsc5" name="op_opciones" type="radio" value="ced_ruc" onclick="setfocus(this.form.search)" alt="" /><label for="radsc5">Ced/Ruc</label>
                          </div>
                        </div>
                        <div id="divFecha" class="form-group" style="display:block;">
                          <div class="col-xs-6" style="margin-top: 5px;">
                            <div class="input-group input-group-xs por_fecha">
                                <label class="col-sm-2 control-label label-xs" style="margin-left: 40px; margin-right: 14px;">Rango:</label>
                                <span class="input-group-addon alert-info">Desde</span>
                              <input type="text" id="Fec_Ini" name="Fec_Ini" class="form-control" style="width: 110px; margin-right: 10px;"/>
                              <span class="input-group-addon alert-info">Hasta</span>
                              <input type="text" id="Fec_Fin" name="Fec_Fin" class="form-control" style="width: 110px;"/>
                            </div>
                          </div>
                        </div>
                        <div class="form-group" style="margin-top: 10px;">
                          <label class="col-sm-2 control-label">B&uacute;squeda:</label>
                          <div class="col-sm-7">
                            <div class="input-group">
                              <input id="search" name="search" onkeydown="if (event.keyCode === 13)
                                  this.form.submit()" type="text" size="50" maxlength="50" style="width: 320px;" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
                              <input type="text" id="cod_plan_cntas" name="cod_plan_cntas" style="display: none;">
                              <span class="input-group-btn">
                                <button type="button" onclick="this.form.submit()" class="btn btn-success btn-xs" title="Buscar Producto" tabindex="-1">
                                  <span class="glyphicon glyphicon-search"></span> <span>Buscar</span>
                                </button>
                              </span>
                            </div>
                          </div><input type="text" tabindex="-1" style="display:none;" />
                        </div>
                      </div>
                      <!-- FILTROS TIPO DE PAGO--->
                      <div class="col-xs-5" style="margin-top: 10px;">
                        <div class="form-group">
                          <label class="col-sm-3 control-label label-xs " for="Tip_Pago" >Tipo de pago:</label>
                          <div class="col-sm-6">
                            <select name="Tip_Pago" id="Tip_Pago" class="form-control input-xs">
                              <option value=""><< Todas >></option>
                              <option value="Contado">Contado</option>
                              <option value="Credito">Crédito</option>
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-3 control-label label-xs " for="Tip_Consumo">Tipo de Consumo:</label>
                          <div class="col-sm-6">
                            <?php
                            //Consultar el plan de cuentas de la empresa
                            $row_rs_consumo = $obBD_con1->getArrayConsulta(44, $Ses_Emp_Cod, $obBD_conexion); ?>
                            <select name="Tip_Consumo" id="Tip_Consumo" class="form-control input-xs" style="margin-top: 7px;">
                              <option value=""><< Todos >></option>
                              <?Php foreach ($row_rs_consumo as $row) { ?><option value="<?php echo $row['Con_Cod']; ?>"><?php echo $row['Con_Des']; ?></option><?Php } ?>
                            </select>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="col-sm-3 control-label label-xs " for="Tip_documento">Documento:</label>
                          <div class="col-sm-6">
                            <?php
                            //Consultar por tipo de documento
                            $row_rs_consumo = $obBD_con1->getArrayConsulta(45, $Ses_Emp_Cod, $obBD_conexion); ?>
                            <select name="Tip_documento" id="Tip_documento" class="form-control input-xs">
                              <option value=""><< Todas >></option>
                              <?Php foreach ($row_rs_consumo as $row) { ?><option value="<?php echo $row['Tic_Cod']; ?>"><?php echo   utf8($row['Tic_Des']); ?></option><?Php } ?>
                            </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-3 control-label label-xs">Sucursal:</label>
                          <div class="col-sm-5">
                              <?php $sucursal = $obBD_con2->getArray('sucursal.selectWhere', array('clean' => true, 'unsetCols' => true, 'addCols' => array('sucursal' => array('Suc_Cod', 'Suc_Des')), 'where' => array('Emp_Cod' => $Ses_Emp_Cod))); ?>
                              <select name="Suc_Cod" class="form-control input-xs">
                                  <option value="T" selected=""><< TODAS >></option>
                                  <?php foreach ($sucursal as $s) { ?>
                                      <option value="<?php echo $s['Suc_Cod']; ?>"><?php echo $s['Suc_Des']; ?></option>
                                  <?php } ?>
                              </select>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="col-sm-3 control-label label-xs " for="Tip_documento">Aproximación:</label>
                          <div class="col-sm-3">
                            <input class="form-control input-xs" type="number" name="cantidad_aproximada" id="cantidad_aproximada" value="0.00" onchange="valores_aproximados()">
                            <input type="text" name="codigos_compras" id="codigos_compras" style="display: none;">
                          </div>
                          <div class="col-sm-3">
                            <div id="procesando" style="display: none;">Procesando...</div>
                          </div>
                        </div>
                      </div>
                    </fieldset>
                  </form>
                </div>
                <div id="tablasProd" class="" style="min-height: 550px;">
                  <table id="container"></table>
                  <div id="containerPager"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="imprimir" style="display: none;">
    <div style="width: 1030px;">
      <?php echo $obBD_con1->getReportHeader($Ses_Suc_Cod, 'REPORTE DE COMPRAS', '<span class="subtitle">Total de registros</span>', $obBD_conexion) ?>
      <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 700px; border-collapse: collapse;table-layout:auto  ;font-size:12px;"></table>
      <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
    </div>
  </div>
  <div id="agregar_plan_cuentas" style="display: none;">
    <div class="col-sm-12">
      <form class="form-horizontal normal" name="formCuentas" id="formCuentas" action="javascript:$('#Lista_plan').Search('#formCuentas','cuenAjax');">
        <fieldset class="exa-fieldset">
          <legend class="Titulos2">Plan de Cuentas</legend>
          <div class="col-sm-4">
          </div>
          <div class="col-sm-12">
            <div class="form-group">
              <label class="col-sm-4 control-label" style="margin-bottom: 7px;">Descripcion:</label>
              <div class=" col-sm-6">
                <input class=" form-control input-xs clearable submit" name="des_plan" id="des_plan" type="text" />
                <input class=" form-control input-xs clearable submit" name="cod_plan" id="cod_plan" type="text" style="display: none;" />
              </div>
              <button class=" col-sm-2  btn btn-success btn-xs">Buscar</button>
            </div>
          </div>
        </fieldset>
        <div class="row">
          <style>
            #Lista_plan,
            #gbox_Lista_plan,
            #gview_Lista_plan,
            .ui-jqgrid-hdiv,
            .ui-jqgrid-bdiv,
            .ui-jqgrid-htable {
              width: 100% !important;
            }
          </style>
          <div class="col-sm-12">
            <table id="Lista_plan" style="width: 100%!important;"></table>
          </div>
        </div>
      </form>
    </div>
  </div>
  <script src="../VALIDACIONES/fact_val_fac_com_lista.js?k=321"></script>
  <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
  <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
  <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
  <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</BODY>