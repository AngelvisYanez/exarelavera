<?php

/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * Fecha de creación  2015-07-22
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_kardex.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * Creacion del Objeto de conexion
 */
$obBD_conexion = new Class_Log_Conexion_Kar($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Kar;

$hoy = date("Y-m-d");
$mes = date("m");

ini_set("memory_limit", "4096M");
ini_set('max_execution_time', 600);

if (isset($proAjax)) {
  $contar = $obBD_con1->getRowConsulta(1052, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*', $obBD_conexion);
  $pagination = pages($contar['total'], $page, $rows);
  $responce = $pagination['data'];
  $responce['rows'] = $obBD_con1->getArrayConsulta(1052, $search . '*' . $Ses_Emp_Cod . '*' . $op_opciones . '*' . $pagination['limits'], $obBD_conexion);
  utf8_encode_deep($responce['rows']);
  echo json_encode($responce);
  exit();
}

if (isset($ajaxProd)) {
  try {
    $Ite_Cod = $Pro_Cod;
    //Carga el estado actual y actualiza stock y precio promedio de stock y producto
    $responce['stocks'] = $obBD_con1->getPromedio($Ite_Cod, $Ses_Suc_Cod, $obBD_conexion);
    $responce['prod'] = $obBD_con1->getRowConsulta(1051, $Ite_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
    $responce['success'] = true;
  } catch (Exception $e) {
    $responce = array('success' => false, 'message' => 'No se logro obtener información del Producto!', 'error' => $e);
  }
  utf8_encode_deep($responce);
  echo json_encode($responce);
  exit();
}

if (isset($ajaxKardex)) {
  try {
    $Ite_Cod = $Pro_Cod;
    $kardex1 = $obBD_con1->getArrayConsulta(1048, $ini . '*' . $Ite_Cod, $obBD_conexion);
    $saldoInicial = $obBD_con1->getArrayConsulta(10488, $ini . '*' . $Ite_Cod, $obBD_conexion);

    if (count($saldoInicial) > 0) {
      $x = COUNT($saldoInicial);
      for ($i = 1; $i < $x; $i++) {
        //venta de producto salida
        if ($saldoInicial[$i]['Kar_Sal'] * 1 != 0) { //Realiza venta
          if ($saldoInicial[$i - 1]['Promedio'] != null) {
            $saldoInicial[$i]['Kar_Pre'] = $saldoInicial[$i - 1]['Promedio']; //El precio de salida es igual al precio promedio anterior
            $saldoInicial[$i]['Kar_Ime'] = floatval($saldoInicial[$i]['Kar_Pre']) * floatval($saldoInicial[$i]['Kar_Sal']); //calcula el valor de Salida
          } else {
            $saldoInicial[$i]['Kar_Ime'] = floatval($saldoInicial[$i]['Kar_Pre']) * floatval($saldoInicial[$i]['Kar_Sal']);
          }
        }
        $saldoInicial[$i]['Stock'] = $saldoInicial[($i - 1)]['Stock'] * 1 + $saldoInicial[$i]['Kar_Can'] * 1 - $saldoInicial[$i]['Kar_Sal'];
        $saldoInicial[$i]['Saldo'] = ($saldoInicial[($i - 1)]['Saldo'] * 1) + ($saldoInicial[$i]['Kar_Ims'] * 1) - ($saldoInicial[$i]['Kar_Ime'] * 1);
        $saldoInicial[$i]['Promedio'] = ($saldoInicial[$i]['Stock'] != 0 ?  $saldoInicial[$i]['Saldo'] / $saldoInicial[$i]['Stock'] : $saldoInicial[($i - 1)]['Promedio']);
      }

      $kardex1[0]['Precio_ent'] = null;
      $kardex1[0]['Precio_sal'] = null;
      $kardex1[0]['Saldo'] =  $saldoInicial[$x - 1]['Saldo'];
      $kardex1[0]['Stock'] = $saldoInicial[$x - 1]['Stock'];
      $kardex1[0]['Promedio'] =  floatval($kardex1[0]['Saldo']) /  floatval($kardex1[0]['Stock']);
    
    } else {
      $kardex1[0]['Promedio'] = 0;
      $kardex1[0]['Saldo'] = 0;
      $kardex1[0]['Stock'] = 0;
    }

    $date = date_create($ini);
    date_sub($date, date_interval_create_from_date_string("1 days"));
    $result = $date->format('Y-m-d');
    list($ann, $mes, $dia) = explode('-', $result);
    $kardex1[0]['Kar_Det'] = '<b>Saldo al ' . $dia . ', de ' . mes($mes, 1) . ', ' . $ann . '</b>';

    $kardex2 = $obBD_con1->getArrayConsulta(1050, $ini . '*' . $fin . '*' . $Ite_Cod, $obBD_conexion);

    if (count($kardex2) > 0) {
      $kardex = array_merge($kardex1, $kardex2);
    } else {
      $kardex = $kardex1;
    }

    $x = COUNT($kardex); //original

    for ($i = 1; $i < $x; $i++) {
      if ($kardex[$i]['Kar_Sal'] * 1 != 0) {
        $kardex[$i]['Kar_Pre'] = $kardex[$i - 1]['Promedio'];
        $kardex[$i]['Kar_Ime'] = $kardex[$i]['Kar_Pre'] * $kardex[$i]['Kar_Sal'];
      }
      $kardex[$i]['Stock'] = $kardex[($i - 1)]['Stock'] * 1 + $kardex[$i]['Kar_Can'] * 1 - $kardex[$i]['Kar_Sal'];
      $kardex[$i]['Saldo'] = $kardex[$i - 1]['Saldo'] * 1 + $kardex[$i]['Kar_Ims'] * 1 - $kardex[$i]['Kar_Ime'];
      //promedio
      $kardex[$i]['Promedio'] = ($kardex[$i]['Stock'] != 0 ?   $kardex[$i]['Saldo'] / $kardex[$i]['Stock'] : $kardex[$i - 1]['Promedio']);
    }

    // Implement pagination
    $page = isset($_POST['page']) ? $_POST['page'] : 1;
    $limit = isset($_POST['rows']) ? $_POST['rows'] : 7000;
    $totalRecords = count($kardex);
    $totalPages = ceil($totalRecords / $limit);
    $start = ($page - 1) * $limit;
    $kardexPage = array_slice($kardex, $start, $limit);

    $responce['rows'] = $kardexPage;
    $responce['records'] = $totalRecords;
    $responce['page'] = $page;
    $responce['total'] = $totalPages;
    $responce['userdata'] = $kardex[$totalRecords - 1];
    $responce['success'] = true;

  } catch (Exception $e) {
    $responce = array('success' => false, 'message' => 'No se logro obtener información del Kardex!', 'error' => $e->getMessage());
  }
  utf8_encode_deep($responce);
  echo json_encode($responce);
  exit();
}


if (isset($obtenerPromedio)) {
  $valorPromedio = $obBD_con1->getPromedio($producto, $obBD_conexion);
  echo json_encode($valorPromedio);
  exit();
}

?>
<!DOCTYPE html>
<HTML>

<HEAD>
  <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
  <TITLE><?Php echo "Inventario Kardex [EXA]"; ?></TITLE>
  <meta charset="UTF-8">
  <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
  <style>
  </style>
</HEAD>

<BODY>

  <div class="panel panel-main">
    <div class="panel-heading exa-header">
      <h3 class="panel-title">&raquo; Consultar Kardex</h3>
    </div>

    <div class="panel-body ui-widget-content ui-corner-bottom exa-body">

      <div class="row">

        <div class="col-sm-12">
          <form id="formKardex" class="form-horizontal normal" action="javascript:$('#kardex').Search('#formKardex','ajaxKardex');">

            <fieldset class="exa-fieldset">
              <legend class="Titulos2">Descripción Producto:</legend> <!-- Form Name -->
              <div class="row">
                <div class="col-xs-4">
                  <!-- static input-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-xs ">Descripción:</label>
                    <div class="col-sm-8">
                      <div class="input-group input-group-xs">
                        <input type="text" name="Pro_Cod" id="Pro_Cod" value="" style="display: none" />
                        <input id="producto" type="text" class="form-control" placeholder="Seleccione un Producto ..." required readonly />
                        <span class="input-group-btn">
                          <button class="btn btn-success" onclick="$('#proDialog').dialog('open');" type="button"><span class="glyphicon glyphicon-check" title="Buscar Proveedor"></span></button>
                        </span>
                      </div><!-- /input-group -->
                    </div>
                  </div>
                  <!-- static input-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-xs ">Marca:</label>
                    <div class="col-sm-8">
                      <span class="form-control input-xs" data-producto="Mar_Des"></span>
                    </div>
                  </div>
                  <!-- static input-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-xs ">Adquisición:</label>
                    <div class="col-sm-8">
                      <span class="form-control input-xs" data-producto="Adq_Des"></span>
                    </div>
                  </div>
                </div>
                <div class="col-xs-4">
                  <!-- static input-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-xs ">Categoria:</label>
                    <div class="col-sm-8">
                      <span class="form-control input-xs" data-producto="Cat_Des"></span>
                    </div>
                  </div>
                  <!-- static input-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-xs ">Cod. Cat.:</label>
                    <div class="col-sm-8">
                      <span class="form-control input-xs" data-producto="Pro_Cdc"></span>
                    </div>
                  </div>
                  <!-- static input-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-xs ">Observación:</label>
                    <div class="col-sm-8">
                      <span class="form-control input-xs" data-producto="Pro_Obs"></span>
                    </div>
                  </div>
                </div>
                <div class="col-xs-4">
                  <!-- static input-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-xs ">IVA:</label>
                    <div class="col-sm-8">
                      <span class="form-control input-xs" data-producto="Iva_Por"></span>
                    </div>
                  </div>
                  <!-- static input-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-xs ">Cod.Barras:</label>
                    <div class="col-sm-8">
                      <span class="form-control input-xs" data-producto="Pro_Bar"></span>
                    </div>
                  </div>
                  <!-- static input-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-xs ">Ubicacion:</label>
                    <div class="col-sm-8">
                      <span class="form-control input-xs" data-producto="Ubi_Des"></span>
                    </div>
                  </div>
                </div>
              </div>



            </fieldset>

            <div class="row">
              <div class="col-xs-4">
                <fieldset class="exa-fieldset">
                  <legend class="Titulos2">Estado Actual:</legend> <!-- Form Name -->

                  <!-- static input-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-xs ">Stock:</label>
                    <div class="col-sm-8">
                      <span class="form-control input-xs" data-stock="Stock"></span>
                    </div>
                  </div>
                  <!-- static input-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-xs ">Prec.Prom.:</label>
                    <div class="col-sm-8">
                      <span class="form-control input-xs" data-stock="Promedio"></span>
                    </div>
                  </div>
                  <!-- static input-->
                  <div class="form-group">
                    <label class="col-sm-3 control-label label-xs ">Sal.Actual:</label>
                    <div class="col-sm-8">
                      <span class="form-control input-xs" data-stock="Saldo"></span>
                    </div>
                  </div>

                </fieldset>
              </div>
              <div class="col-xs-8">
                <fieldset class="exa-fieldset">
                  <legend class="Titulos2">Filtros:</legend> <!-- Form Name -->
                  <div class="row">
                    <div class="col-xs-12">
                      <div class="form-group">
                        <label class="col-sm-2 control-label label-xs ">Desde:</label>
                        <div class="col-sm-3">
                          <input name="ini" type="text" id="ini" class="form-control input-sm">
                        </div>
                        <label class="col-sm-2 control-label label-xs ">Hasta:</label>
                        <div class="col-sm-3">
                          <input name="fin" type="text" id="fin" class="form-control input-sm">
                        </div>
                        <div class="col-xs-2">
                          <div class=""><button type="button" onclick="if($('#Pro_Cod').val()!==''){this.form.submit();$('#kardex').jqGrid('setCaption', $('#producto').val()+' - '+'Desde '+ $('#ini').val()+' Hasta '+$('#fin').val());}else{$.alert('Seleccione el Producto');}" class="btn btn-sm btn-success" title="Ejecutar Búsqueda"><span class="glyphicon glyphicon-search"></span> &nbsp;Filtrar</button></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </fieldset>
              </div>
            </div>
          </form>
        </div>
        <div class="col-sm-12" style="min-height: 400px;">
          <table id="kardex"></table>
          <div id="kardexPager"></div>

          <script>
            $(document).ready(function() {
              var kardexGrid = $("#kardex");
              kardexGrid.createGrid({

                colModel: [
                { label: 'Cod.Int.', name: 'Kar_Key', key: true, hidden: true, viewable: true },
                { label: 'Fecha', name: 'Kar_Fec', align: "center", width: 40 },
                { label: 'Detalle', name: 'Kar_Det', width: 75 },
                { label: 'Doc.', name: 'Doc', width: 15, classes: 'columnHighlight2' },
                { label: 'Num. Doc.', name: 'Doc_Num', width: 55, classes: 'columnHighlight2' },
                { label: 'Cliente/Proveedor', name: 'Cli_Prv', width: 55, classes: 'columnHighlight2' },
                { label: 'Entrada', name: 'Precio_sal', width: 35, align: "right", formatter: 'currency',
                  formatoptions: {
                  prefix: '$',
                  thousandsSeparator: ',',
                  decimalSeparator: '.',
                  decimalPlaces: 4,
                  defaultValue: ''
                  }, classes: 'columnHighlight5'
                },
                { label: 'Salida', name: 'Precio_ent', width: 35, align: "right", formatter: 'currency',
                  formatoptions: {
                  prefix: '$',
                  thousandsSeparator: ',',
                  decimalSeparator: '.',
                  decimalPlaces: 4,
                  defaultValue: ''
                  }, classes: 'columnHighlight5'
                },
                // Entradas
                { label: 'Cant.', name: 'Kar_Can', width: 25, align: "right", formatter: 'intornumber',
                  formatoptions: {
                  defaultValue: ''
                  }, classes: 'columnHighlight1'
                },
                { label: 'V.Uni.', name: 'Kar_Prs', width: 35, align: "right", formatter: 'number',
                  formatoptions: {
                  defaultValue: '',
                  decimalPlaces: 6
                  }, classes: 'columnHighlight1'
                },
                { label: 'V.Tot.', name: 'Kar_Ims', width: 40, align: "right", formatter: 'currency',
                  formatoptions: {
                  prefix: '$',
                  thousandsSeparator: ',',
                  decimalSeparator: '.',
                  decimalPlaces: 4,
                  defaultValue: ''
                  }, classes: 'columnHighlight1'
                },
                // Salidas
                { label: 'Cant.', name: 'Kar_Sal', width: 25, align: "right", formatter: 'intornumber',
                  formatoptions: {
                  defaultValue: '',
                  decimalPlaces: 4
                  }, classes: 'columnHighlight3'
                },
                { label: 'V.Uni.', name: 'Kar_Pre', width: 35, align: "right", formatter: 'number',
                  formatoptions: {
                  defaultValue: '',
                  decimalPlaces: 6
                  }, classes: 'columnHighlight3'
                },
                { label: 'V.Tot.', name: 'Kar_Ime', width: 40, align: "right", formatter: 'currency',
                  formatoptions: {
                  prefix: '$',
                  thousandsSeparator: ',',
                  decimalSeparator: '.',
                  decimalPlaces: 4,
                  defaultValue: ''
                  }, classes: 'columnHighlight3'
                },
                // Existencias
                { label: 'Cant.', name: 'Stock', width: 25, align: "right", formatter: 'intornumber',
                  formatoptions: {
                    defaultValue: ''
                  }, classes: 'columnHighlight5'
                },
                { label: 'V.Uni.', name: 'Promedio', width: 35, align: "right", formatter: 'number',
                  formatoptions: {
                  defaultValue: '',
                  decimalPlaces: 6
                  }, classes: 'columnHighlight5'
                },
                { label: 'V.Tot.', name: 'Saldo', width: 45, align: "right", formatter: 'number',
                  formatoptions: {
                  prefix: '$',
                  thousandsSeparator: ',',
                  decimalSeparator: '.',
                  decimalPlaces: 4,
                  defaultValue: ''
                  }, classes: 'columnHighlight5'
                }

              ],

              height: 270,
              caption: ' ',
              footerrow: true,
              userDataOnFooter: true,
              rowNum: 7000,
              // rowList: [7000, 14000, 21000, 28000],
              pager: "#kardexPager",
              pgbuttons: true, // Activar botones del paginador
              pgtext: 'Página {0} de {1}', // Texto del paginador
              datatype: "json",
              mtype: "POST",
              url: "<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>",
              postData: {
                  ajaxKardex: true,
                Pro_Cod: function() { return $('#Pro_Cod').val(); },
                ini: function() { return $('#ini').val(); },
                fin: function() { return $('#fin').val(); }
              },
              beforeRequest: function() {
                if ($('#Pro_Cod').val() === '') {
                  return false; // Cancel the request if no product is selected
                }
              },
              
              // loadComplete: function() {
              //   $(this).setGridSummary(['Precio_sal', 'Precio_ent', 'Kar_Can', 'Kar_Ims', 'Kar_Sal', 'Kar_Ime'], {
              //     Cli_Prv: '<div style="text-align:right">TOTAL PAGINA</div>',
              //     Kar_Pre: '',
              //     Kar_Prs: '',
              //     Kar_Fec: '',
              //     Kar_Det: '',
              //     Doc: '',
              //     Doc_Num: ''
              //   });
              // }

              loadComplete: function() {
                var grid = $(this);
                var totalRecords = grid.jqGrid('getGridParam', 'records');
                var postData = grid.jqGrid('getGridParam', 'postData');
                postData.rows = totalRecords;
                postData.page = 1;
                setTimeout(() => {
                  $.ajax({
                    url: grid.jqGrid('getGridParam', 'url'),
                    type: 'POST',
                    data: postData,
                    dataType: 'json',
                    success: function(response) {
                    var totalSummary = {
                      Precio_sal: 0,
                      Precio_ent: 0,
                      Kar_Can: 0,
                      Kar_Ims: 0,
                      Kar_Sal: 0,
                      Kar_Ime: 0,
                      Stock: 0,
                      Promedio: 0,
                      Saldo: 0
                    };
                    $.each(response.rows, function(index, row) {
                      totalSummary.Precio_sal += parseFloat(row.Precio_sal) || 0;
                      totalSummary.Precio_ent += parseFloat(row.Precio_ent) || 0;
                      totalSummary.Kar_Can += parseFloat(row.Kar_Can) || 0;
                      totalSummary.Kar_Ims += parseFloat(row.Kar_Ims) || 0;
                      totalSummary.Kar_Sal += parseFloat(row.Kar_Sal) || 0;
                      totalSummary.Kar_Ime += parseFloat((row.Kar_Pre * row.Kar_Sal).toFixed(4)) || 0;
                      totalSummary.Stock = parseFloat(row.Stock) || 0;
                      totalSummary.Promedio = row.Stock != 0 ? parseFloat((row.Saldo / row.Stock).toFixed(6)) : 0;
                      totalSummary.Saldo = parseFloat(row.Saldo) || 0;
                    });
                    grid.jqGrid('footerData', 'set', {
                      Precio_sal: totalSummary.Precio_sal.toFixed(4),
                      Precio_ent: totalSummary.Precio_ent.toFixed(4),
                      Kar_Can: totalSummary.Kar_Can.toFixed(4),
                      Kar_Ims: totalSummary.Kar_Ims.toFixed(4),
                      Kar_Sal: totalSummary.Kar_Sal.toFixed(4),
                      Kar_Ime: totalSummary.Kar_Ime.toFixed(4),
                      Stock: totalSummary.Stock.toFixed(4),
                      Promedio: totalSummary.Promedio.toFixed(4),
                      Saldo: totalSummary.Saldo.toFixed(4),
                      Cli_Prv: '<div style="text-align:right">TOTAL GENERAL:</div>',
                      Kar_Pre: '',
                      Kar_Prs: '',
                      Kar_Fec: '',
                      Kar_Det: '',
                      Doc: '',
                      Doc_Num: ''
                    });
                    }
                  });
                }, 600);
                }

              }, true, "#kardexPager", {
              view: false
              }).setGroupHeaders({
                groupHeaders: [
                  { "numberOfColumns": 3, "titleText": "Respaldo", "startColumnName": "Doc" },
                  { "numberOfColumns": 2, "titleText": "Valores", "startColumnName": "Precio_sal" },
                  { "numberOfColumns": 3, "titleText": "Entradas", "startColumnName": "Kar_Can" },
                  { "numberOfColumns": 3, "titleText": "Salidas", "startColumnName": "Kar_Sal" },
                  { "numberOfColumns": 3, "titleText": "Existencias", "startColumnName": "Stock" }
                ], useColSpanStyle: true
              });

              kardexGrid.gridButtonsAdd([
                // { caption: 'Imprimir', buttonicon: "glyphicon glyphicon-print",
                //   onClickButton: function() {
                //     $("#kardex").jqGrid('printGrid');
                //   }
                // },

                // { caption: 'Exportar', buttonicon: "glyphicon glyphicon-download",
                //   onClickButton: function() {
                //     $("#kardex").jqGrid('exportGridExcel');
                //   }
                // }


                { caption: 'Imprimir', buttonicon: "glyphicon glyphicon-print",
                  onClickButton: function() {
                  var $grid = $("#kardex");
                  var postData = $grid.jqGrid('getGridParam', 'postData');
                  postData.rows = 1000000;
                  postData.page = 1;

                  $.post(
                  "<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_FULL_SPECIAL_CHARS); ?>",
                  postData,
                  function(response) {
                  var colModel = $grid.jqGrid('getGridParam', 'colModel');
                  var data = response.rows;

                  var printContent = `
                  <html>
                    <head>
                        <title>${$('#producto').val()}  Desde: ${$('#ini').val()} - Hasta: ${$('#fin').val()}</title>
                        <style>
                          @media print {
                            body { margin: 0; }
                            table { width: auto; max-width: 100%; border-collapse: collapse; table-layout: auto; }
                            th, td { padding: 2px; border: 1px solid #ddd; word-wrap: break-word; }
                            .no-print { display: none !important; }
                          }
                          @page {
                            size: auto;
                            margin: 10mm;
                          }
                        </style>
                      </head>
                      <body>
                        <table>
                          <thead>
                            <tr>
                              <th colspan="${colModel.filter(c => !c.hidden).length}" style="text-align:center; font-weight:bold;">
                              ${$('#producto').val()}  Desde: ${$('#ini').val()} - Hasta: ${$('#fin').val()}
                              </th>
                            </tr>

                            <tr>
                              <th colspan="3" style="border: 1px solid black;"></th>
                              <th colspan="3" style="border: 1px solid black;">Respaldo</th>
                              <th colspan="2" style="border: 1px solid black;">Valores</th>
                              <th colspan="3" style="border: 1px solid black;">Entradas</th>
                              <th colspan="3" style="border: 1px solid black;">Salidas</th>
                              <th colspan="3" style="border: 1px solid black;">Existencias</th>
                            </tr>

                            <tr>
                            <th style="border: 1px solid black; background-color: #f2f2f2;">#</th>
                            ${colModel.map(col => !col.hidden && col.name !== 'Kar_Key' && col.label ? `<th style='border: 1px solid black; background-color: #f2f2f2;'>${col.label}</th>` : '').join('')}
                            </tr>

                          </thead>
                          <tbody>
                            ${data.map((row, index) => `
                            <tr>
                              <td style='border: 1px solid black;'>${index + 1}</td>
                              ${colModel.map(col => {
                                if (!col.hidden && col.name !== 'Kar_Key') {
                                  var cellValue = row[col.name];
                                  var formattedValue = cellValue !== null && cellValue !== undefined ? cellValue : '';
                                  var decimalPlaces = ['Kar_Prs', 'Kar_Pre', 'Promedio'].includes(col.name) ? 6 : 4;
                                  if (col.formatter === 'currency' || col.formatter === 'number') {
                                    var numberValue = parseFloat(cellValue) || 0;
                                    formattedValue = numberValue.toLocaleString('en-US', {
                                      minimumFractionDigits: decimalPlaces,
                                      maximumFractionDigits: decimalPlaces
                                    });
                                    if (col.formatter === 'currency') {
                                      formattedValue = "$" + formattedValue;
                                    }
                                  }
                                  // Check if the entire column is empty
                                  var isColumnEmpty = true;
                                  for (var i = 0; i < data.length; i++) {
                                    if (data[i][col.name] !== null && data[i][col.name] !== undefined && data[i][col.name] !== '') {
                                    isColumnEmpty = false;
                                    break;
                                    }
                                  }
                                  if (!isColumnEmpty) {
                                    return `<td style='border: 1px solid black;'>${formattedValue}</td>`;
                                  }
                                  return '';
                                }
                              return '';
                              }).join('')}
                            </tr>
                            `).join('')}
                          </tbody>
                            <tfoot>
                              <tr>
                                <td colspan="${colModel.filter(c => !c.hidden).length}" style="text-align:right; border: 1px solid black;">
                                Generado el ${new Date().toISOString().slice(0, 10)} por EXA [Software Contable]
                                </td>
                              </tr>
                            </tfoot>
                        </table>
                      </body>
                    </html>`;

                    var iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    document.body.appendChild(iframe);
                    iframe.contentDocument.write(printContent);
                    iframe.contentDocument.close();
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                    document.body.removeChild(iframe);

                    }, 'json');
                  }
                },
                
                { caption: 'Exportar', buttonicon: "glyphicon glyphicon-download",
                  onClickButton: function() {
                    var $grid = $("#kardex");
                    var postData = $grid.jqGrid('getGridParam', 'postData');
                    postData.rows = 1000000;
                    postData.page = 1;

                    $.post(
                    "<?php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_FULL_SPECIAL_CHARS); ?>",
                    postData,
                    function(response) {
                      var colModel = $grid.jqGrid('getGridParam', 'colModel');
                      var data = response.rows;

                      var xlsContent = "<table border='1'><thead>";

                      // Fila con información del producto y fechas
                      xlsContent += "<tr><th colspan='" + (colModel.filter(c => !c.hidden).length) + "' style='text-align:center; font-weight:bold;'>" + 
                        $('#producto').val() + " - Desde: " + $('#ini').val() + " - Hasta: " + $('#fin').val() + "</th></tr>";

                      // 1. Grupos de columnas
                      xlsContent += "<tr>";
                      var groups = [
                        { colspan: 3, title: '' },
                        { colspan: 3, title: 'Respaldo' },
                        { colspan: 2, title: 'Valores' },
                        { colspan: 3, title: 'Entradas' },
                        { colspan: 3, title: 'Salidas' },
                        { colspan: 3, title: 'Existencias' }
                      ];
                      groups.forEach(group => {
                        xlsContent += "<th colspan='" + group.colspan + "' style='border: 1px solid black;'>" + group.title + "</th>";
                      });
                      xlsContent += "</tr>";

                      // 2. Encabezados normales
                      xlsContent += "<tr><th style='border: 1px solid black; background-color: #f2f2f2;'>#</th>";
                      colModel.forEach(col => {
                        if (!col.hidden && col.name !== 'Kar_Key' && col.label) {
                        xlsContent += "<th style='border: 1px solid black; background-color: #f2f2f2;'>" + col.label + "</th>";
                        }
                      });
                      xlsContent += "</tr></thead><tbody>";

                      // 3. Datos formateados
                      data.forEach((row, index) => {
                        xlsContent += "<tr><td style='border: 1px solid black;'>" + (index + 1) + "</td>";
                        colModel.forEach(col => {
                          if (!col.hidden && col.name !== 'Kar_Key') {
                            var cellValue = row[col.name];
                            var formattedValue = cellValue !== null && cellValue !== undefined ? cellValue : '';
                            var decimalPlaces = ['Kar_Prs', 'Kar_Pre', 'Promedio'].includes(col.name) ? 6 : 4;
                            if (col.formatter === 'currency' || col.formatter === 'number') {
                              var numberValue = parseFloat(cellValue) || 0;
                              formattedValue = numberValue.toLocaleString('en-US', {
                                minimumFractionDigits: decimalPlaces,
                                maximumFractionDigits: decimalPlaces
                              });
                              if (col.formatter === 'currency') {
                                formattedValue = "$" + formattedValue;
                              }
                            }
                              // Check if the entire column is empty
                              var isColumnEmpty = true;
                              for (var i = 0; i < data.length; i++) {
                                if (data[i][col.name] !== null && data[i][col.name] !== undefined && data[i][col.name] !== '') {
                                  isColumnEmpty = false;
                                  break;
                                }
                              }
                              if (!isColumnEmpty) {
                              xlsContent += "<td style='border: 1px solid black;'>" + formattedValue + "</td>";
                              }
                          }
                        });
                        xlsContent += "</tr>";
                      });

                      xlsContent += "</tbody></table>";
                      
                      // Pie de página
                      var currentDate = new Date().toISOString().slice(0, 10);
                      xlsContent += "<tfoot><tr><td colspan='" + (colModel.filter(c => !c.hidden).length) + "' style='text-align:right; border: 1px solid black;'>" +
                      "Generado el " + currentDate + " por EXA [Software Contable]" + "</td></tr></tfoot>";

                      // Descargar archivo
                      var blob = new Blob([xlsContent], { type: "application/vnd.ms-excel;charset=utf-8" });
                      var link = document.createElement("a");
                      link.href = URL.createObjectURL(blob);
                      link.download = "kardex_" + new Date().toISOString().slice(0, 10) + ".xls";
                      document.body.appendChild(link);
                      link.click();
                      document.body.removeChild(link);

                    }, 'json' );
                  }
                }

              ]);
            });

            $.createDateRange('#ini', '#fin');
            $.fn.fmatter.intornumber = function(cellval, opts) {
              var op = (cellval % 1 === 0 ? $.extend({}, opts.integer) : $.extend({}, opts.number));
              if (opts.colModel !== undefined && opts.colModel.formatoptions !== undefined) {
                op = $.extend({}, op, opts.colModel.formatoptions);
              }
              if ($.fmatter.isEmpty(cellval) || (!isNaN(cellval) && cellval * 1 === 0)) {
                return op.defaultValue;
              }
              return $.fmatter.util.NumberFormat(cellval, op);
            };
            $.fn.fmatter.intornumber.unformat = function(cellval, options, element) {
              var opts = $.jgrid.getRegional(this, 'formatter') || {},
              op = $.extend({}, opts.number, options.colModel.formatoptions || {});
              return cellval.replace(new RegExp(op.thousandsSeparator.replace(/([\.\*\_\'\(\)\{\}\+\?\\])/g, "\\$1"), "g"), "").replace(op.decimalSeparator, '.');
            };
            
            </script>
        </div>

      </div>
    </div>
  </div>
  <!--INICIO DEL DIALOGO BUSCAR CUENTA-->
  <div id="proDialog" title="B&uacute;squeda de Productos">
    <form class="form-horizontal normal">
      <fieldset>
        <legend>Filtros</legend>
        <div class="form-group">
          <label class="col-md-2 control-label label-xs">Filtrar Por:</label>
          <div class="col-md-5 radioset">
            <input id="radc1" name="op_opciones" type="radio" value="d" checked="" onclick="setfocus(this.form.search)" alt="" /><label for="radc1">&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;</label>
            <input id="radc2" name="op_opciones" type="radio" value="c" onclick="setfocus(this.form.search)" alt="" /><label for="radc2">&nbsp;&nbsp;C&oacute;digo&nbsp;&nbsp;</label>
          </div>
        </div>
        <div class="form-group">
          <label class="col-md-2 control-label">B&uacute;squeda:</label>
          <div class="col-md-7">
            <div class="input-group">
              <input name="search" onkeydown="if (event.keyCode === 13) this.form.submit()" type="text" size="50" maxlength="50" placeholder="Ingrese búsqueda..." autofocus class="form-control input-sm " />
              <span class="input-group-btn"><button type="button" onclick="this.form.submit()" class="btn btn-success btn-sm" title="Buscar Producto"><span class="glyphicon glyphicon-search"></span> <span>Buscar</span></button></span>
            </div><!-- /input-group -->
          </div>
        </div>
      </fieldset>
    </form>
  </div>
  <!-- FIN DEL DIALOGO CUENTAS-->
  <script>
    // DIALOG BUSCAR CUENTAS            
    $.createSearchDialog('proDialog', [
      { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 15, align: "center", hidden: true },
      { label: 'Producto', name: 'Ite_Lar', width: 110, classes: "highlightSearch" },
      { label: 'Descripción', name: 'Pro_Obs', width: 110, classes: "highlightSearch" },
      { label: 'Marca', name: 'Mar_Des', width: 40 },
      { label: 'Tipo', name: 'Cat_Des', width: 40, align: "center" },
      { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false,
        formatter: function(cellvalue, options, rowObject) {
          return $.getGridButton(SelectProd, {
            Pro_Cod: rowObject.Pro_Cod,
            Ite_Lar: rowObject.Ite_Lar
          });
        }
      }
    ]);

    function SelectProd(data) {
      $('#proDialog').dialog('close');
      $('#Pro_Cod').val(data['Pro_Cod']);
      $('#producto').val(data['Ite_Lar']);
      if ($('#ini').val() === '') {
      $('#ini').val('2000-01-01');
      }
      if ($('#fin').val() === '') {
      $('#fin').datepicker("setDate", new Date());
      }
      $('#kardex').jqGrid('setCaption', data['Ite_Lar'] + ' - ' + 'Desde ' + $('#ini').val() + ' Hasta ' + $('#fin').val());
      $.getDataJson('<?Php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>', {
      Pro_Cod: data['Pro_Cod'],
      ajaxProd: true
      }, function(response) {
      $('#formKardex').setData(response['prod'], 'producto').setData(response['stocks'], 'stock');
      });
      // Vacia el gridview y los datos del pie de pagina
      $('#kardex').jqGrid('clearGridData');
      // Vacia la última fila de totales
      $('#kardex').jqGrid('footerData', 'set', { Precio_sal: '', Precio_ent: '', Kar_Can: '', Kar_Ims: '', Kar_Sal: '', Kar_Ime: '', Stock: '', Promedio: '', Saldo: '' });
      $('#actualizar').toggle(true);
    }
  </script>
  <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>

</HTML>