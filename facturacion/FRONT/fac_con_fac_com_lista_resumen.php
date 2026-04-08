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
  $obBD_con1->echoLog('** PHP PLAN DE CUENTAS AJAX ***');
  //ChromePhp::log('Compras');

  $fec = " AND com.Cop_Fec BETWEEN '$Fec_Ini' AND '$Fec_Fin' ";

  if ($order != '') {
    $order = " ORDER BY " . $order;
  }

  $cod_plan = "";
  if ($op_opciones == 'h') {
    $cod_plan = "AND det_plan.Pld_Cod LIKE '%$search%' ";
  }

  $cuenta = "";
  if ($op_opciones == 'c') {
    $cuenta = " AND det_plan.Pld_Des LIKE '%$search%' ";
  }

  $nombre_proveedor = "";
  if ($op_opciones == 'nom_prov') {
    $nombre_proveedor = " AND CONCAT(persona.Prs_Nom,' ',persona.Prs_Ape) LIKE '%$search%' ";
  }

  $ced_ruc = "";
  if ($op_opciones == 'ced_ruc') {
    $ced_ruc = " AND persona.Prs_Ced LIKE '%$search%' ";
  }

  $sucursal = "";
  if ($Suc_Cod != 'T') {
      $sucursal = "AND puntos_imp.Suc_Cod = '$Suc_Cod' ";
  }

  $resultado =  $obBD_con1->getArrayConsulta(46, $Ses_Emp_Cod . '*' . $order . '*'  . $fec . '*'  . $cuenta . '*' . $nombre_proveedor . '*' . $ced_ruc."*".$cod_plan."*".$Fec_Ini . '*' . $sucursal, $obBD_conexion);
  $obBD_con1->echoJson($resultado);
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
                  <form id="frm_prod_ven" name="frm_prod_ven" class="form-horizontal normal" action="javascript:$('#container').Search('#frm_prod_ven','prodAjax');">
                    <fieldset class="exa-fieldset" id="prodFormTemp">
                      <div class="col-xs-12 col-sm-6">
                        <legend class="Titulos2">B&uacute;squeda</legend>
                        <input name="order" type="hidden" value="" />
                        <div class="form-group">
                          <label class="col-sm-2 control-label label-xs">Filtrar Por:</label>
                          <div class="col-sm-7 radioset opt_search">
                            <input id="radsc1" name="op_opciones" type="radio" value="h" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radsc1">Cod.Plan</label>
                            <input id="radsc3" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radsc3">Cuenta</label>
                            <input id="radsc4" name="op_opciones" type="radio" value="nom_prov" onclick="setfocus(this.form.search)" alt="" /><label for="radsc4">Proveedor</label>
                            <input id="radsc5" name="op_opciones" type="radio" value="ced_ruc" onclick="setfocus(this.form.search)" alt="" /><label for="radsc5">Ced/Ruc</label>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="col-sm-2 control-label">B&uacute;squeda:</label>
                          <div class="col-sm-7">
                            <div class="input-group">
                              <input id="search" name="search" onkeydown="if (event.keyCode === 13)
                                                this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese b&uacute;squeda..." autofocus class="form-control input-xs clearable submit" />
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
                      <div class="col-xs-6">
                        <div class="form-group">
                          <div id="divFecha" class="form-group" style="display:block;">
                            <div class="form-group">
                              <label class="col-sm-3 control-label label-xs " for="Tip_Pago">Ingrese el rango de fechas:</label>
                              <div class="col-xs-6">
                                <div class="input-group input-group-xs por_fecha">
                                  <span class="input-group-addon"><span class="">Rango:</span></span>
                                  <span class="input-group-addon alert-info">Desde</span>
                                  <input type="text" id="Fec_Ini" name="Fec_Ini" class="form-control" />
                                  <span class="input-group-addon alert-info">Hasta</span>
                                  <input type="text" id="Fec_Fin" name="Fec_Fin" class="form-control" />
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                          <label class="col-sm-1 control-label label-xs">Sucursal:</label>
                          <div class="col-sm-3">
                              <?php $sucursal = $obBD_con2->getArray('sucursal.selectWhere', array('clean' => true, 'unsetCols' => true, 'addCols' => array('sucursal' => array('Suc_Cod', 'Suc_Des')), 'where' => array('Emp_Cod' => $Ses_Emp_Cod))); ?>
                              <select name="Suc_Cod" class="form-control input-xs">
                                  <option value="T" selected=""><< TODAS >></option>
                                  <?php foreach ($sucursal as $s) { ?>
                                      <option value="<?php echo $s['Suc_Cod']; ?>"><?php echo $s['Suc_Des']; ?></option>
                                  <?php } ?>
                              </select>
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
      <table id="tablaReporte" cellspacing="0" cellpadding="0" style="width: 700px; border-collapse: collapse;table-layout:fixed  ;font-size:12px;"></table>
      <?php echo $obBD_con1->getReportFooter($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?>
    </div>
  </div>

  <script src="../VALIDACIONES/fact_val_fac_com_lista_resumen.js?k=321"></script>
  <script type="text/javascript" src="../../framework//jquery/jquery.plugins/MaskedInput//jquery.maskedinput.1.4.1.min.js"></script>
  <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
  <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
  <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>
</BODY>