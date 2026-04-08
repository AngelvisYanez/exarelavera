<?php

/**
 * @abstract Permite realizar la cancelacion de comprobantes por abonos
 * @author Erik Niebla
 * @version 1.0
 * @update Santiago Ruiz
 * Fecha de actualizacion 2020-01-10
 * Fecha de creaci�n  2015-07-22
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
    $ini = $hoy;
    $kardex1 = $obBD_con1->getArrayConsulta(1048, $ini . '*' . $Ite_Cod, $obBD_conexion);
    if (count($kardex1) == 1 && $kardex1[0]['Saldo'] !== 0 && $kardex1[0]['Stock'] != 0) {
      $kardex1[0]['Promedio'] = round(($kardex1[0]['Saldo'] / $kardex1[0]['Stock']), 6);
    } else {
      $kardex1[0]['Promedio'] = 0;
      $kardex1[0]['Saldo'] = 0;
      $kardex1[0]['Stock'] = 0;
    }
    list($ann, $mes, $dia) = explode('-', $ini);
    $kardex1[0]['Kar_Det'] = '<b>Saldo al ' . $dia . ', de ' . mes($mes, 1) . ', ' . $ann . '</b>';
    $responce['stocks']=$kardex1[0];
    $responce['prod'] = $obBD_con1->getRowConsulta(1051, $Ite_Cod . '*' . $Ses_Suc_Cod, $obBD_conexion);
    $responce['success'] = true;
    
    //$responce['stocks'] = $obBD_con1->getPromedio($Ite_Cod, $Ses_Suc_Cod, $obBD_conexion);

    //ChromePhp::log($kardex1);
  } catch (Exception $e) {
    $responce = array(success => false, message => 'No se logro obtener información del Producto!', error => $e);
  }
  utf8_encode_deep($responce);
  echo json_encode($responce);
  exit();
}

if (isset($ajaxKardex)) {
  try {
    $Ite_Cod = $Pro_Cod;
    $borras;
    if ($tipos == 'P') {
      $borras = 'AND (kardex_ie.bod_cod is null or kardex_ie.bod_cod=' . $bodega . ')';
    }
    if ($tipos == 'S') {
      $borras = 'AND kardex_ie.bod_cod=' . $bodega;
    }
    if ($tipos == 'T') {
      $borras = '';
    }

    $kardex1 = $obBD_con1->getArrayConsulta(1048, $ini . '*' . $Ite_Cod, $obBD_conexion);
    if (count($kardex1) == 1 && $kardex1[0]['Saldo'] !== 0 && $kardex1[0]['Stock'] != 0) {
      $kardex1[0]['Promedio'] = round(($kardex1[0]['Saldo'] / $kardex1[0]['Stock']), 6);
    } else {
      $kardex1[0]['Promedio'] = 0;
      $kardex1[0]['Saldo'] = 0;
      $kardex1[0]['Stock'] = 0;
    }
    list($ann, $mes, $dia) = explode('-', $ini);
    $kardex1[0]['Kar_Det'] = '<b>Saldo al ' . $dia . ', de ' . mes($mes, 1) . ', ' . $ann . '</b>';



    
    $kardex2 = $obBD_con1->getArrayConsulta(5009, $ini . '*' . $fin . '*' . $Ite_Cod . '*' . $borras, $obBD_conexion);
   // $kardex2 = $obBD_con1->getArrayConsulta(1050, $ini . '*' . $fin . '*' . $Ite_Cod . '*' . $borras, $obBD_conexion);




    if (count($kardex2) > 0) $kardex = array_merge($kardex1, $kardex2);
    else $kardex = $kardex1;
    $x = COUNT($kardex);

    for ($i = 1; $i < $x; $i++) {
      if ($kardex[$i]['Kar_Sal'] * 1 != 0) {
        $kardex[$i]['Kar_Pre'] = $kardex[$i - 1]['Promedio'];
        $kardex[$i]['Kar_Ime'] = $kardex[$i]['Kar_Pre'] * $kardex[$i]['Kar_Sal'];
      }

      $kardex[$i]['Stock'] = $kardex[($i - 1)]['Stock'] * 1 + $kardex[$i]['Kar_Can'] * 1 - $kardex[$i]['Kar_Sal'];
      $kardex[$i]['Saldo'] = $kardex[$i - 1]['Saldo'] * 1 + $kardex[$i]['Kar_Ims'] * 1 - $kardex[$i]['Kar_Ime'];
      $kardex[$i]['Promedio'] = ($kardex[$i]['Stock'] != 0 ? $kardex[$i]['Saldo'] / $kardex[$i]['Stock'] : $kardex[$i - 1]['Promedio']);
      // $kardex[$i]['Bodega']='pp';
      // //ChromePhp::log($kardex[$i]['Bodega']);
    }



    $responce['rows'] = $kardex;
    $responce['records'] = count($kardex);
    $responce['userdata'] = $kardex[$responce['records'] - 1];
    $responce['success'] = true;
  } catch (Exception $e) {
    $responce = array(success => false, message => 'No se logro obtener información del Kardex!', error => $e);
  }
  utf8_encode_deep($responce);
  echo json_encode($responce);
  exit();
}


$bodegas = $obBD_con1->getArrayConsulta(5008, array('usu_cod' => $Ses_Usu_Cod, 'Emp_Cod' => $Ses_Suc_Cod), $obBD_conexion);



?>
<!DOCTYPE html>
<HTML>

<HEAD>
  <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
  <TITLE><?Php echo "Inv. Kardex Bodega [EXA]"; ?></TITLE>
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
                        <br>
                        <br>
                        <label class="col-sm-4 control-label label-xs ">Bodega:</label>
                        <input name="tipos" type="hidden" id="tipos" class="form-control input-sm">
                        <div class="col-sm-4">

                          <select class="form-control input-sm" name="bodega" id="bodega">
                            <option data-tipo='T'>Todas las bodegas</option>
                            <?php
                            foreach ($bodegas as $bod) {
                              echo "<option value='{$bod['Bod_Cod']}' data-tipo='{$bod['Bod_Tip']}'>{$bod['Bod_Nom']}</option>";
                            }
                            ?>
                          </select>
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
                colModel: [{
                    label: 'Cod.Int.',
                    name: 'Kar_Key',
                    key: true,
                    hidden: true,
                    viewable: true
                  },
                  {
                    label: 'Fecha',
                    name: 'Kar_Fec',
                    align: "center",
                    width: 40
                  },
                  {
                    label: 'Detalle',
                    name: 'Kar_Det',
                    width: 75
                  },
                  {
                    label: 'Doc.',
                    name: 'Doc',
                    width: 15,
                    classes: 'columnHighlight2'
                  },
                  {
                    label: 'Num. Doc.',
                    name: 'Doc_Num',
                    width: 55,
                    classes: 'columnHighlight2'
                  },

                  {
                    label: 'Entrada',
                    name: 'Precio_sal',
                    width: 35,
                    align: "right",
                    formatter: 'currency',
                    formatoptions: {
                      prefix: '$',
                      thousandsSeparator: ',',
                      decimalSeparator: '.',
                      decimalPlaces: 4,
                      defaultValue: ''
                    },
                    classes: 'columnHighlight5'
                  },
                  {
                    label: 'Salida',
                    name: 'Precio_ent',
                    width: 35,
                    align: "right",
                    formatter: 'currency',
                    formatoptions: {
                      prefix: '$',
                      thousandsSeparator: ',',
                      decimalSeparator: '.',
                      decimalPlaces: 4,
                      defaultValue: ''
                    },
                    classes: 'columnHighlight5'
                  },

                  {
                    label: 'Cant.',
                    name: 'Kar_Can',
                    width: 25,
                    align: "right",
                    formatter: 'intornumber',
                    formatoptions: {
                      defaultValue: ''
                    },
                    classes: 'columnHighlight1'
                  },
                  {
                    label: 'V.Uni.',
                    name: 'Kar_Prs',
                    width: 35,
                    align: "right",
                    formatter: 'number',
                    formatoptions: {
                      defaultValue: '',
                      decimalPlaces: 6
                    },
                    classes: 'columnHighlight1'
                  },
                  {
                    label: 'V.Tot.',
                    name: 'Kar_Ims',
                    width: 40,
                    align: "right",
                    formatter: 'currency',
                    formatoptions: {
                      prefix: '$',
                      thousandsSeparator: ',',
                      decimalSeparator: '.',
                      decimalPlaces: 4,
                      defaultValue: ''
                    },
                    classes: 'columnHighlight1'
                    
                  },

                  {
                    label: 'Cant.',
                    name: 'Kar_Sal',
                    width: 25,
                    align: "right",
                    formatter: 'intornumber',
                    formatoptions: {
                      defaultValue: ''
                    },
                    classes: 'columnHighlight3'
                  },
                  {
                    label: 'V.Uni.',
                    name: 'Kar_Pre',
                    width: 35,
                    align: "right",
                    formatter: 'number',
                    formatoptions: {
                      defaultValue: '',
                      decimalPlaces: 6
                    },
                    classes: 'columnHighlight3'
                  },
                  {
                    label: 'V.Tot.',
                    name: 'Kar_Ime',
                    width: 40,
                    align: "right",
                    formatter: 'currency',
                    formatoptions: {
                      prefix: '$',
                      thousandsSeparator: ',',
                      decimalSeparator: '.',
                      decimalPlaces: 4,
                      defaultValue: ''
                    },
                    classes: 'columnHighlight3'
                  },

                  {
                    label: 'Cant.',
                    name: 'Stock',
                    width: 25,
                    align: "right",
                    formatter: 'intornumber',
                    formatoptions: {
                      defaultValue: ''
                    },
                    classes: 'columnHighlight5'
                  },
                  {
                    label: 'V.Uni.',
                    name: 'Promedio',
                    width: 35,
                    align: "right",
                    formatter: 'number',
                    formatoptions: {
                      defaultValue: '',
                      decimalPlaces: 6
                    },
                    classes: 'columnHighlight5'
                  },
                  {
                    label: 'V.Tot.',
                    name: 'Saldo',
                    width: 45,
                    align: "right",
                    formatter: 'number',
                    formatoptions: {
                      prefix: '$',
                      thousandsSeparator: ',',
                      decimalSeparator: '.',
                      decimalPlaces: 4,
                      defaultValue: ''
                    },
                    classes: 'columnHighlight5'
                  },
                  {
                    label: 'Ubi. Bodega',
                    name: 'Bodega',
                    width: 45,
                    align: "right",
                    formatoptions: {
                      defaultValue: ''
                    },
                    classes: 'columnHighlight5'
                  }

                ],
                height: 270,
                caption: ' ',
                footerrow: true,
                userDataOnFooter: true,
                rowNum: 10000000,
                pgbuttons: false,
                pgtext: null,
                loadComplete: function() {
                  $(this).setGridSummary(['Precio_sal', 'Precio_ent', 'Kar_Can', 'Kar_Ims', 'Kar_Sal', 'Kar_Ime'], {
                    Doc_Num: '<div style="text-align:right">TOTALES:</div>',
                    Kar_Pre: '',
                    Kar_Prs: '',
                    Kar_Fec: '',
                    Kar_Det: '',
                    Doc: ''
                  });
                }




              }, true, "#kardexPager", {
                view: false
              }).setGroupHeaders({
                groupHeaders: [{
                    "numberOfColumns": 2,
                    "titleText": "Respaldo",
                    "startColumnName": "Doc"
                  },
                  {
                    "numberOfColumns": 2,
                    "titleText": "Valores",
                    "startColumnName": "Precio_sal"
                  },
                  {
                    "numberOfColumns": 3,
                    "titleText": "Entradas",
                    "startColumnName": "Kar_Can"
                  },
                  {
                    "numberOfColumns": 3,
                    "titleText": "Salidas",
                    "startColumnName": "Kar_Sal"
                  },
                  {
                    "numberOfColumns": 3,
                    "titleText": "Existencias",
                    "startColumnName": "Stock"
                  }
                ],
                useColSpanStyle: true
              });
              kardexGrid.gridButtonsAdd([{
                  caption: 'Imprimir',
                  buttonicon: "glyphicon glyphicon-print",
                  onClickButton: function() {
                    $("#kardex").jqGrid('printGrid');
                  }
                },
                {
                  caption: 'Exportar',
                  buttonicon: "glyphicon glyphicon-download",
                  onClickButton: function() {
                    $("#kardex").jqGrid('exportGridExcel');
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
    $.createSearchDialog('proDialog', [{
        label: 'C&oacute;d.Int.',
        name: 'Pro_Cod',
        key: true,
        width: 15,
        align: "center",
        hidden: true
      },
      {
        label: 'Producto',
        name: 'Ite_Lar',
        width: 110,
        classes: "highlightSearch"
      },
      {
        label: 'Descripción',
        name: 'Pro_Obs',
        width: 110,
        classes: "highlightSearch"
      },
      {
        label: 'Marca',
        name: 'Mar_Des',
        width: 40
      },
      {
        label: 'Tipo',
        name: 'Cat_Des',
        width: 40,
        align: "center"
      },
      {
        label: '&nbsp;',
        name: 'act1',
        width: 20,
        align: 'center',
        viewable: false,
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
      $('#ini').val('2015-01-01'); //$('#ini').datepicker("setDate", new Date(today.getTime() - (30 * 24 * 3600 * 1000)));
      $('#fin').datepicker("setDate", new Date());
      $('#kardex').jqGrid('setCaption', data['Ite_Lar'] + ' - ' + 'Desde ' + $('#ini').val() + ' Hasta ' + $('#fin').val());
      $.getDataJson('<?Php echo filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_STRING); ?>', {
        Pro_Cod: data['Pro_Cod'],
        ajaxProd: true
      }, function(response) {
        $('#formKardex').setData(response['prod'], 'producto').setData(response['stocks'], 'stock');
      });
      // $('#kardex').Search('#formKardex', 'ajaxKardex');
    }
    $('#bodega').change(function() {
      $('#tipos').val($('#bodega').find(':selected').data('tipo'));
    });
    $('#tipos').val($('#bodega').find(':selected').data('tipo'));
  </script>
  <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js"></script>
</BODY>

</HTML>