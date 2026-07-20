<?php

/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creaci�n  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_ccpp.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');
/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Che;
/**
 * Evita el reenvio 
 */
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$mes = date("m");

if (isset($provAjax)) {
  $data = filter_input_array(INPUT_GET);
  $data["Emp_Cod"] = $Ses_Emp_Cod;
  $contar = $obBD_con1->getRowConsulta(3, $data, $obBD_conexion);
  $pagination = pages($contar['total'], $page, $rows);
  $responce = $pagination['data'];
  $data["limits"] = $pagination['limits'];
  if ($contar['total'] > 0)
    $responce['rows'] =  $obBD_con1->getArrayConsulta(3, $data, $obBD_conexion);
  utf8_encode_deep($responce['rows']);
  echo json_encode($responce);
  exit();
}
if (isset($save)) {
  $data = filter_input_array(INPUT_POST);
  $obBD_con1->validaCierrePeriodo('compras', 'Cop_Fec', 'Cop_Cod', $Cop_Fec, null, $obBD_conexion);
  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);

  $obBD_con1->operacionobBD(41, $data, $obBD_conexion);
  $data['Cop_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
  $obBD_con1->operacionobBD(42, $data, $obBD_conexion);
  $data['Com_Cod'] = $obBD_con1->insercionid($obBD_conexion->conexion);
  $obBD_con1->operacionobBD(43, $data, $obBD_conexion);
  $obBD_con1->operacionobBD(44, $data, $obBD_conexion);

  $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
  $responce['Cop_Cod'] = $data['Cop_Cod'];
  $responce['Com_Cod'] = $data['Com_Cod'];
  if ($obBD_con1->Error == 0) $responce['success'] = true;
  else {
    $responce['success'] = false;
    $responce['message'] = $obBD_con1->MsgError;
  }

  echo json_encode($responce);
  exit();
}
$row_rs_periodo = $obBD_con1->getRowConsulta(39, $Ses_Emp_Cod, $obBD_conexion);
$PecIni = explode('-', $row_rs_periodo['Pec_Fei']);
$maximo = ($PecIni[0] * 1 - 1) . '-12-' . ultimoDia(12, ($PecIni[0] * 1 - 1));

?>
<!DOCTYPE html>
<HTML>

<HEAD>
  <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
  <TITLE><?Php echo "Ccxpp Deudas Iniciales [EXA] "; ?></TITLE>
  <meta charset="UTF-8">
  <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
  <style>

  </style>
</HEAD>

<BODY>

  <div class="panel panel-main">
    <div class="panel-heading exa-header">
      <h3 class="panel-title">&raquo; Registro de Cuentas Por Pagar Iniciales</h3>
    </div>

    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
      <form id="formDeudas" class="form-horizontal normal" action="javascript:if($('#PrvCodBus').val()!==''){$.createDialogConfirm(null,null,saveForm);}else{$.alert('Selecione un Proveedor');} ">
        <div class="row">
          <div class="col-sm-6">
            <fieldset class="exa-fieldset">
              <legend class="Titulos2">Documento</legend> <!-- Form Name -->
              <input type="text" value="<?php echo $row_rs_periodo['Pec_Cod']; ?>" style="display: none;" name='Pec_Cod' />
              <?php
              $row_rs_vendedor = $obBD_con1->getRowConsulta(53, $Ses_Prs_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
              ?>
              <input type="text" value="<?php echo $row_rs_vendedor['Vnd_Cod']; ?>" style="display: none;" name='Vnd_Cod' />


              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-sm required" for="Tic_Cod">Tipo de Doc.:</label>
                <div class="col-sm-9">
                  <select name="Tic_Cod" id="Tic_Cod" class="form-control input-sm" required>
                    <option value="">Seleccione...</option>
                    <?Php
                    $rs_tip_compr = $obBD_con1->getArrayConsulta(37, '', $obBD_conexion);
                    foreach ($rs_tip_compr as $row_rs_tip_compr) { ?>
                      <option value="<?php echo $row_rs_tip_compr['Tic_Cod'] ?>"><?php echo $row_rs_tip_compr['Tic_Des']; ?></option>
                    <?php
                    } ?>
                  </select>
                </div>
              </div>
              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-sm required" for="Ciu_Cod">Ciudad:</label>
                <div class="col-sm-4">
                  <select name="Ciu_Cod" id="Ciu_Cod" class="form-control input-sm" required>
                    <option value="">Seleccione...</option>
                    <?Php
                    $rs_ciudad = $obBD_con1->getArrayConsulta(40, '', $obBD_conexion);
                    foreach ($rs_ciudad as $row_rs_ciudad) { ?>
                      <option value="<?php echo $row_rs_ciudad['Ciu_Cod'] ?>"><?php echo $row_rs_ciudad['Ciu_Des'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-sm required" for="Cop_Num">Num. Doc.:</label>
                <div class="col-sm-4">
                  <input id="Cop_Num" name="Cop_Num" class="form-control input-sm" placeholder="999-999-999999999" type="text" required="">

                </div>
              </div>


              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-sm required" for="Cop_Fec">Fec. Emisión:</label>
                <div class="col-sm-4">
                  <input id="Cop_Fec" name="Cop_Fec" class="form-control input-sm dateType" placeholder="0000-00-00">

                </div>
              </div>

              <!-- Textarea -->
              <div class="form-group">
                <label class="col-sm-3 control-label" for="des_cuenta">Observación:</label>
                <div class="col-sm-9">
                  <textarea class="form-control" id="des_cuenta" name="Cop_Obs"></textarea>
                </div>
              </div>


            </fieldset>

          </div>
          <div class="col-sm-6">

            <fieldset class="exa-fieldset">
              <legend class="Titulos2">Deudas Anteriores:</legend> <!-- Form Name -->

              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-sm required" for="cod_cuenta">Proveedor:</label>
                <div class="col-sm-8">
                  <div class="input-group input-group-sm">
                    <input type="text" name="Prv_Cod" id="PrvCodBus" value="" style="display: none" />
                    <input id="docu" name="Provee" type="text" class="form-control" placeholder="Seleccione un Proveedor ..." required readonly />
                    <span class="input-group-btn">
                      <button class="btn btn-success" onclick="$('#provDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                    </span>
                  </div><!-- /input-group -->
                </div>
              </div>

              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-sm required" for="des_padre">Cuenta Deudora:</label>
                <div class="col-sm-7">
                  <select name="Pld_Cod" id="ccpp_prove" class="form-control input-sm" required>
                    <?Php
                    $row_rs_ccpp_prove = $obBD_con1->getArrayConsulta(38, $row_rs_periodo['Pla_Cod'], $obBD_conexion);
                    foreach ($row_rs_ccpp_prove as $row) { ?>
                      <option <?Php if ($row['Ccp_Def'] == 'D') {
                                echo "selected";
                              } ?> value="<?Php echo $row['Pld_Cod']; ?>"><?Php echo $row['Pld_Des']; ?></option>
                    <?Php
                    } ?>
                  </select>
                </div>
              </div>

              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-sm required" for="cod_cuenta">Deuda:</label>
                <div class="col-sm-4">
                  <div class="input-group input-group-sm">
                    <span class="input-group-addon bold"> $ </span>
                    <input id="Asi_Val" name="Asi_Val" class="form-control" placeholder="" type="text" required onkeypress="return validar_decimal(event);" style="text-align: right">

                  </div>
                </div>
              </div>

              <!-- Text input-->
              <div class="form-group">
                <label class="col-sm-3 control-label label-sm required" for="Cpp_Ven">Fec. Venc.:</label>
                <div class="col-sm-4">
                  <input id="Cpp_Ven" name="Cpp_Ven" class="form-control input-sm dateType" placeholder="0000-00-00" type="text" required />

                </div>
              </div>

              <!-- Textarea -->
              <div class="form-group">
                <label class="col-sm-3 control-label" for="des_cuenta">Observación:</label>
                <div class="col-sm-9">
                  <textarea class="form-control" id="des_cuenta" name="Com_Obs"></textarea>
                </div>
              </div>

            </fieldset>
          </div>
          <div class="col-sm-12">
            <div class="form-group">
              <div class="col-sm-12 center">
                <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-floppy-disk"></span> Guardar</button>
                <button type="button" onclick="resetForm();updateCodigo();resetGrid();" class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span> Cancelar</button>
              </div>
            </div>
            <div class="form-group Titulos2">
              <div class="col-sm-12">
                <hr /><b>NOTA:</b> Los campos que se encuentran marcados con un asterisco ( <span class="required"></span> ) son campos obligatorios.
              </div>
            </div>
          </div>
        </div>

      </form>
    </div>
  </div>
  <!--INICIO DEL DIALOGO BUSCAR PROVEEDOR-->
  <div id="provDialog" title="Búsqueda de Proveedores">
    <form class="form-horizontal normal">
      <fieldset>
        <legend>Filtros</legend>
        <div class="form-group">
          <label class="col-md-2 control-label label-xs">Filtrar Por:</label>
          <div class="col-md-8 radioset">
            <input id="rad1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="rad1">&nbsp;&nbsp;Apellido&nbsp;&nbsp;</label>
            <input id="rad2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="rad2">&nbsp;&nbsp;Cédula/R.U.C.&nbsp;&nbsp;</label>
          </div>
        </div>
        <div class="form-group">
          <label class="col-md-2 control-label">B&uacute;squeda:</label>
          <div class="col-md-7">
            <div class="input-group">
              <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese proveedor a buscar..." autofocus class="form-control input-sm " /><input type="text" style="display:none" />
              <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar cuenta"><span class="glyphicon glyphicon-search"></span> <span> Buscar</span></button></span>
            </div><!-- /input-group -->
          </div>
        </div>
      </fieldset>
    </form>
  </div>
  <script type="text/javascript">
    $(document).ready(function() {
      $.createSearchDialog('#provDialog', [{
          label: 'Cód.Int.',
          name: 'Prv_Cod',
          key: true,
          hidden: true,
          viewable: true
        },
        {
          label: 'Cédula/R.U.C.',
          name: 'Prs_Ced',
          width: 50
        },
        {
          label: 'Proveedor',
          name: 'proveedor',
          width: 190,
          cellattr: function(rowId, tv, rawObject, cm, rdata) {
            return 'style="white-space: normal;"';
          }
        },
        {
          label: 'Dirección',
          name: 'Prs_Dir',
          hidden: true,
          viewable: true
        },
        {
          label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
          name: 'act1',
          width: 18,
          align: 'center',
          viewable: false,
          formatter: function(cellvalue, options, rowObject) {
            var clic = 'selectProvee($("#provGrid").jqGrid("getRowData",' + rowObject.Prv_Cod + '))';
            return '<span class="btn btn-success btn-xs" title="Seleccionar" onclick=\'' + clic + '\'><i class="glyphicon glyphicon-arrow-right"></span>';
          }
        }
      ]);

    });
  </script>
  <!-- FIN DEL DIALOGO PROVEEDOR-->
  <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
  <script type="text/javascript">
    function saveForm() {
      if ($('#Asi_Val').val() * 1 > 0) {
        $.post("<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>", $('#formDeudas').getData('save'), function(response) {
          if (response['success'] === true) {
            $('#formDeudas')[0].reset();
            $.alert("El Registro se ha Guardado con Exito!");
          } else {
            $.alert("No se logro guardar el Registro!");
          }
        }, 'json').fail(function(error) {
          $.alert("El Servidor ha fallado en responder!");
        });
      } else {
        $.alert('El valor de la deuda debe ser mayor que cero!');
      }
    }

    function selectProvee(data) {
      if (typeof data === 'undefined') {
        $("input[name='Prv_Cod']").val('');
        $("#docu").val('');
        return false;
      } else {
        $("#docu").val(data['proveedor']);
        $("input[name='Prv_Cod']").val(data['Prv_Cod']);
        $("#provDialog").dialog("close");
      }
    }
    $(document).ready(function() {
      $("#Cop_Num").mask("999-999-999999999", {
        placeholder: "_"
      });
      $.createDatePickers('.dateType');
      //$('#Cop_Fec').datepicker( "option", "maxDate", '<?php echo $maximo; ?>');
    });
  </script>
</BODY>

</HTML>