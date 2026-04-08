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
      enableDateOne();
      enableDateTwo();
      enableDateF2();
      enableR1F2();
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
function enableDateF2() {
      $('#radB3').on("click", function () {
            $('#frm_cli_frec').find('#divFechaForm2').show();
            $('#frm_cli_frec').find('input:text[name=Fec_Ini]').removeAttr('disabled');
            $('#frm_cli_frec').find('input:text[name=Fec_Fin]').removeAttr('disabled');
            $('#frm_cli_frec').find('#search').attr('disabled', 'disabled');
            validaDesdeF2();
            validaHastaF2();

            
      });
}
// habilar fechas form1
function enableDateOne() {
      $('#radsc2').on("click", function () {
            $('#divFecha').show();
            $('#Fec_Ini').removeAttr('disabled');
            $('#Fec_Fin').removeAttr('disabled');
            $('#search').attr('disabled', 'disabled');
            validaDesde();
            validaHasta();

      });
}
function enableDateTwo() {
      $('#radsc1').on("click", function () {
            $('#divFecha').hide();
            $('#Fec_Ini').attr('disabled', 'disabled');
            $('#Fec_Fin').attr('disabled', 'disabled');
            $('#search').removeAttr('disabled');
      });
}
function enableR1F2() {
      $('#radB1').on("click", function () {
            $('#frm_cli_frec').find('#divFechaForm2').hide();
            $('#frm_cli_frec').find('#Fec_Ini').attr('disabled', 'disabled');
            $('#frm_cli_frec').find('#Fec_Fin').attr('disabled', 'disabled');
            $('#frm_cli_frec').find('#search').removeAttr('disabled');
      });
      $('#radB2').on("click", function () {
            $('#frm_cli_frec').find('#divFechaForm2').hide();
            $('#frm_cli_frec').find('#Fec_Ini').attr('disabled', 'disabled');
            $('#frm_cli_frec').find('#Fec_Fin').attr('disabled', 'disabled');
            $('#frm_cli_frec').find('#search').removeAttr('disabled');
      });
}

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
// armar gridPrincipal
function armarGridPrincipal() {
      container.createGrid({
            caption: 'Productos más vendidos <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="total DESC">Nro. Venta</option><option value="Cantidad DESC">Valor</option><select>&nbsp;</div>',
            height: 430,
            colModel: [
                  { label: 'Cod. Int', name: 'Pro_Cod', width: 10, key: true, hidden: false, viewable: true },
                  //{ label: 'AN', name: 'Cha_Cod', width: 40, align: 'center', sorttype: 'string' },
                  //{ label: 'Categoria', name: 'Cat_Des', width: 65 },
                  { label: 'Desc. Larga', name: 'Ite_Lar', width: 100 },
                  //{ label: 'Desc. Corta', name: 'Ite_Cor', width: 40 },
                  //{ label: 'M.', name: 'Pro_Uni', width: 20 },
                  { label: 'Observaciones', name: 'Pro_Obs', width: 60 },
                  { label: 'Marca', name: 'Mar_Des', width: 20, align: 'center' },
                  { label: 'Num. Ventas', name: 'total', width: 20, align: 'center' },
                  { label: 'Unidades', name: 'Veces', width: 20, align: 'center' },
                  { 
                        label: 'Valor', 
                        name: 'Cantidad', 
                        width: 20, 
                        align: 'right', 
                        formatter: 'currency', 
                        formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' },
                        summaryTpl: "Total: {0}",
                        summaryType: "sum"
                  },

            ],

            footerrow: true,

            loadComplete: function() {
                  $(this).jqGrid('footerData', 'set', {
                      Veces: "<div style='text-align:right;'>TOTAL: </div>",
                      Cantidad: $(this).jqGrid('getCol', 'Cantidad', true, 'sum')
                  }, true);
              }

              

            }, true, '#containerPager', { refresh: false, view: true })
            .gridButtonsAdd([
                  { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () { container.jqGrid('exportGridExcel', { nombre: 'Productos', hoja: 'HOJA 1' }); } }
            ]);

      tablaClientesFrec.createGrid({
            caption: 'Clientes con más ventas <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy2"><option value="">No ordenar</option><option value="total DESC">Nro. Venta</option><option value="Cantidad DESC ">Valor</option><select>&nbsp;</div>',
            height: 430,
            colModel: [
                  { label: 'Cod. Int', name: 'Cli_Cod', width: 10, key: true, hidden: false, viewable: true },
                  //{ label: 'AN', name: 'Cha_Cod', width: 40, align: 'center', sorttype: 'string' },
                  //{ label: 'Categoria', name: 'Cat_Des', width: 65 },
                  { label: 'Cédula', name: 'Prs_Ced', width: 40 },
                  { label: 'Cliente', name: 'Cliente', width: 100 },
                  //{ label: 'M.', name: 'Pro_Uni', width: 20 },
                  { label: 'Num. Ventas', name: 'total', width: 20, align: 'center' },
                  { label: 'Valor', name: 'Cantidad', width: 20, align: 'right', formatter: 'currency', formatoptions: { prefix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: '' } },

            ],

            footerrow: true,

            loadComplete: function() {

                  $(this).jqGrid('footerData', 'set', {
                      total: "<div style='text-align:right;'>TOTAL:</div>",
                      Cantidad: $(this).jqGrid('getCol', 'Cantidad', true, 'sum')
                  }, true);
              }

      }, true, '#clienFrecuentesTablaPager', { refresh: false, view: true })
            .gridButtonsAdd([
                  { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () { tablaClientesFrec.jqGrid('exportGridExcel', { nombre: 'Clientes', hoja: 'HOJA 1' }); } }
            ]);
      tablaProdLow.createGrid({
            caption: 'Productos sin stock',
            height: 430,
            colModel: [
                  { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 20, align: "center", hidden: false },
                  { label: 'C&oacute;d.Barra', name: 'Pro_Bar', width: 25 },
                  { label: 'Descripción', name: 'Ite_Lar', width: 110 },
                  { label: 'Categoria', name: 'Cat_Des', width: 90, align: "center" },
                  { label: 'IVA', name: 'Iva_Por', width: 20, align: "center", formatter: 'truefalse', formatoptions: { yesMsg: 'Grava IVA', noMsg: 'No Grava IVA' }, title: false },
                  { label: 'Adq.', name: 'Adq_Des', width: 30, align: "center", formatter: 'title', formatoptions: { title: function (o) { return o['Adq_Des']; } } },
                  { label: 'STOCK', name: 'Stock', width: 40, align: "center" }

            ]

      }, true, '#prodLowTablaPager', { refresh: false, view: true })
            .gridButtonsAdd([
                  { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () { tablaProdLow.jqGrid('exportGridExcel', { nombre: 'Productos Stock Low', hoja: 'HOJA 1' }); } }
            ]);


      tablaProdPrice.createGrid({
            caption: 'Productos sin precio',
            height: 430,
            colModel: [
                  { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 20, align: "center", hidden: false },
                  { label: 'C&oacute;d.Barra', name: 'Pro_Bar', width: 25 },
                  { label: 'Descripción', name: 'Ite_Lar', width: 110 },
                  { label: 'Categoria', name: 'Cat_Des', width: 90, align: "center" },
                  { label: 'IVA', name: 'Iva_Por', width: 20, align: "center", formatter: 'truefalse', formatoptions: { yesMsg: 'Grava IVA', noMsg: 'No Grava IVA' }, title: false },
                  { label: 'Adq.', name: 'Adq_Des', width: 30, align: "center", formatter: 'title', formatoptions: { title: function (o) { return o['Adq_Des']; } } },
                  { label: 'PVP', name: 'Pre_Pvp', width: 40, align: "right", formatter: 'currency' }
            ]

      }, true, '#prodPriceTablaPager', { refresh: false, view: true })
            .gridButtonsAdd([
                  { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download', onClickButton: function () { tablaProdPrice.jqGrid('exportGridExcel', { nombre: 'Productos Price Cero', hoja: 'HOJA 1' }); } }
            ]);
}
