var container;
var tablaClientesFrec;

$(function () {
      container = $("#container");
      ffi2 = $('#frm_cli_frec').find('#Fec_Ini');
      fff2 = $('#frm_cli_frec').find('#Fec_Fin');
      tablaClientesFrec = $("#clienFrecuentesTabla");
      tablaProdLow = $("#prodLowTabla");
      tablaProdPrice = $("#prodPriceTabla");
      $("#tabsDatos").createTabs();
      $.createDateRange('#Fec_Ini', '#Fec_Fin');
      $.createDateRange($('#frm_cli_frec').find('input:text[name=Fec_Ini]'), $('#frm_cli_frec').find('input:text[name=Fec_Fin]'));
     
      armarGridPrincipal();

      $('#OrderBy').on('change', function () {
            $('input[name=order]').val($(this).val());
            $('#frm_prod_ven').formSubmit();
      });

      $('#OrderBy2').on('change', function () {
            $('input[name=order]').val($(this).val());
            $('#frm_cli_frec').formSubmit();
      });

});
// habilitar fechas form2

// Verificar que las fechas no sean erroneass.
function validaDesde() {
      $("#Fec_Ini").on("change", function () {
            if ($('#Fec_Ini').val() > $('#Fec_Fin').val()) {
                  $.alert('El valor de Desde es superior al Hasta');
                  $('#Fec_Ini').val('');
            }
      });
}
function validaDesdeF2() {
      $('#frm_cli_frec').find('input:text[name=Fec_Ini]').on("change", function () {
            if ($('#frm_cli_frec').find('input:text[name=Fec_Ini]').val() > $('#frm_cli_frec').find('input:text[name=Fec_Fin]').val()) {
                  $.alert('El valor de Desde es superior al Hasta');
                  $('#frm_cli_frec').find('input:text[name=Fec_Ini]').val('');
            }
      });
}
function validaHasta() {
      $("#Fec_Fin").on("change", function () {
            if ($('#Fec_Fin').val() < $('#Fec_Ini').val()) {
                  $.alert('El valor de Hasta debe ser superior o igual al Desde');
                  $('#Fec_Fin').val('');
            }
      });
}
function validaHastaF2() {
      $('#frm_cli_frec').find("#Fec_Fin").on("change", function () {
            if ($('#frm_cli_frec').find('#Fec_Fin').val() < $('#frm_cli_frec').find('#Fec_Ini').val()) {
                  $.alert('El valor de Hasta debe ser superior o igual al Desde');
                  $('#frm_cli_frec').find('#Fec_Fin').val('');
            }
      });
}

function armarGridPrincipal() {
      var gridConfig = {
            caption: 'Detalles de Compras <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="total DESC">Nro. Venta</option><option value="Cantidad DESC">Valor</option></select>&nbsp;</div>',
            height: 430,
            colModel: [
                  { label: 'Pld. Cod', name: 'Pld_Cod', width: 10 },
                  { label: 'Rubro', name: 'Pld_Des', width: 30, align: 'left' },
                  { label: 'Suc.', name: 'Suc_Des', width: 15, align: 'left', hidden: true },
            ],
            footerrow: true,
            loadComplete: function () {

                  var $grid = $(this);
                  // Calcular el total general para cada fila
                  var rowIds = $grid.jqGrid('getDataIDs');
                  rowIds.forEach(function(rowId) {
                        var totalGeneral = 0;
                        for (var i = 0; i < meses.length - 1; i++) { // Excluyendo "Gran Total"
                              var colName = 'total_' + meses[i];
                              totalGeneral += parseFloat($grid.jqGrid('getCell', rowId, colName)) || 0;
                        }
                        $grid.jqGrid('setCell', rowId, 'total_general', totalGeneral);
                  });

                  $(this).jqGrid('footerData', 'set', {
                        Pld_Des: "<div style='text-align:center;'>TOTAL: </div>",
                        total_enero: $(this).jqGrid('getCol', 'total_enero', true, 'sum'),
                        total_febrero: $(this).jqGrid('getCol', 'total_febrero', true, 'sum'),
                        total_marzo: $(this).jqGrid('getCol', 'total_marzo', true, 'sum'),
                        total_abril: $(this).jqGrid('getCol', 'total_abril', true, 'sum'),
                        total_mayo: $(this).jqGrid('getCol', 'total_mayo', true, 'sum'),
                        total_junio: $(this).jqGrid('getCol', 'total_junio', true, 'sum'),
                        total_julio: $(this).jqGrid('getCol', 'total_julio', true, 'sum'),
                        total_agosto: $(this).jqGrid('getCol', 'total_agosto', true, 'sum'),
                        total_septiembre: $(this).jqGrid('getCol', 'total_septiembre', true, 'sum'),
                        total_octubre: $(this).jqGrid('getCol', 'total_octubre', true, 'sum'),
                        total_noviembre: $(this).jqGrid('getCol', 'total_noviembre', true, 'sum'),
                        total_diciembre: $(this).jqGrid('getCol', 'total_diciembre', true, 'sum'),
                        total_general: $(this).jqGrid('getCol', 'total_general', true, 'sum')
                  }, true);
            }
      };
      var meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre','Gran Total'];
      meses.forEach(function (mes) {
            gridConfig.colModel.push({
                  label: mes.charAt(0).toUpperCase() + mes.slice(1), // Primera letra mayúscula
                  name: 'total_' + mes.toLowerCase(), // Nombre de la columna en minúsculas
                  width: 10,
                  align: 'right',
                  formatter: 'currency',
                  hidden: false // Por defecto, ocultar la columna
            });
      });

      gridConfig.colModel.push({
            label: 'Gran Total',
            name: 'total_general',
            width: 15,
            align: 'right',
            formatter: 'currency',
            hidden: false // Mostrar la columna
      });

      container.createGrid(gridConfig, true, '#containerPager', { refresh: false, view: true })
            .gridButtonsAdd([
                  { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download',
                        onClickButton: function () {
                              container.jqGrid('exportGridExcel', { nombre: 'Productos', hoja: 'HOJA 1' });
                        }
                  },
                  { caption: 'Exportar PDF', buttonicon: 'glyphicon glyphicon-download',
                        onClickButton: function () {
                              imprimir();
                        }
                  }
            ]);
      // Función para imprimir
      function imprimir() {
            $('#tablaReporte').html(container.jqGrid('exportGridInnerHTML', {
                  footer: true,
                  generated: false,
                  removeHiddens: true,
                  removeCols: [1]
            }));
            $('#imprimir').printElement();
      }
}
