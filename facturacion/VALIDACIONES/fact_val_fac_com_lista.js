var container;
var tablaClientesFrec;
var Lista_plan_cuentas;
$(function () {
      Lista_plan_cuentas = $("#Lista_plan");
      load_plan_cuentas();
      container = $("#container");
      ffi2 = $('#frm_cli_frec').find('#Fec_Ini');
      fff2 = $('#frm_cli_frec').find('#Fec_Fin');
      tablaClientesFrec = $("#clienFrecuentesTabla");
      tablaProdLow = $("#prodLowTabla");
      tablaProdPrice = $("#prodPriceTabla");
      $("#tabsDatos").createTabs();
      $.createDateRange('#Fec_Ini', '#Fec_Fin');
      $.createDateRange($('#frm_cli_frec').find('input:text[name=Fec_Ini]'), $('#frm_cli_frec').find('input:text[name=Fec_Fin]'));
      /*  enableDateOne();
        enableDateTwo();
        enableDateF2();
        enableR1F2();*/
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

      $('#radsc3').on("click", function () {
            $('#divFecha').hide();
            $('#Fec_Ini').attr('disabled', 'disabled');
            $('#Fec_Fin').attr('disabled', 'disabled');
            $('#search').removeAttr('disabled');
      });
      $('#radsc4').on("click", function () {
            $('#divFecha').hide();
            $('#Fec_Ini').attr('disabled', 'disabled');
            $('#Fec_Fin').attr('disabled', 'disabled');
            $('#search').removeAttr('disabled');
      });
      $('#radsc5').on("click", function () {
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
            caption: 'Detalles de Compras <div class="pull-right"><b>ORDENAR POR:</b>&nbsp;<select id="OrderBy"><option value="">No ordenar</option><option value="com.Cop_Cod ASC">Nro. Compra</option><option value="total DESC">Total Desc.</option> <option value="Cop_Fec DESC">Fecha DESC.</option> <option value="Cop_Fec ASC">Fecha ASC.</option> </select>&nbsp;</div>',
            height: 430,
            autowidth: true, // Cambiar a false para controlar el ancho manualmente
            shrinkToFit: false, // Desactivar el ajuste automático de columnas
            colModel: [
                  { label: 'Num. Com.', name: 'Cop_Cod', width: 90, aling: 'center', key: true, hidden: false, viewable: true },
                  { label: 'Fecha', name: 'Cop_Fec', width: 90 },
                  { label: 'Documento', name: 'Tic_Des', width: 160, align: 'center' },
                  { label: 'Suc.', name: 'Suc_Des', width: 160, align: 'center', hidden: true },
                  { label: 'No. Doc', name: 'Cop_Num', width: 165, align: 'center' },
                  { label: 'Cédula/Ruc', name: 'Prs_Ced', width: 110, align: 'center',},
                  { label: 'Proveedor', name: 'proveedor', width: 220 , align: 'center',},
                  { label: 'Detalle de Compra', name: 'Cop_Obs', width: 250, align: 'center' },
                  { label: 'Consumo', name: 'Con_Des', width: 90, align: 'center' },  
                  { label: 'Forma Pago', name: 'Pago', width: 90, align: 'center' },
                  { label: 'Cuenta', name: 'Pld_Cdc', width: 90, align: 'center' },
                  { label: 'Rubro', name: 'Pld_Des', width: 120, align: 'center',
                        formatter: function(cellValue, options, rowObject) {
                              // Elimina espacios y opcionalmente convierte a mayúsculas
                              if(cellValue){
                              // return $.trim(cellValue); // o 
                              return $.trim(cellValue).toUpperCase();
                              }
                        return cellValue;
                        }
                  },
                  { label: 'Base. 0%', name: 'Sub_0', width: 75, align: 'center',
                        formatoptions: { 
                              refix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: ''
                        }
                  },
                  { label: 'Base. 5%', name: 'Sub_5', width: 75, align: 'center',
                        formatoptions: { 
                              refix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: ''
                        }
                  },
                  { label: 'Base. 8%', name: 'Sub_8', width: 75, align: 'center',
                        formatoptions: { 
                              refix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: ''
                        }
                  },
                  { label: 'Base. 12%', name: 'Sub_12', width: 75, align: 'center',
                        formatoptions: { 
                              refix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: ''
                        }
                  },
                  { label: 'Base. 15%', name: 'Sub_15', width: 75, align: 'center',
                        formatoptions: { 
                              refix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: ''
                        }
                  },
                  { label: 'Subtotal.', name: 'subtotal', formatter: 'currency', align: 'right', width: 90 },
                  { label: 'IVA(%)', name: 'Iva_Por', width: 50, align: 'center' },
                  { label: 'Tot_IVA', name: 'total_iva', formatter: 'currency', align: 'right', width: 90 },
                  { label: 'Total', name: 'total', width: 90, align: 'right', formatter: 'currency',
                        formatoptions: { 
                              refix: '$ ', thousandsSeparator: ',', decimalSeparator: '.', defaultValue: ''
                        }, summaryTpl: "Total: {0}", summaryType: "sum"
                  },

            ], sortname: 'Pld_Des', sortorder: 'asc',grouping:true,       
            groupingView:{
            groupField : ['Pld_Des'],
            groupOrder:[ 'asc'],
            groupDataSorted: true,
            groupColumnShow:[true],
            groupText:['<div class="txtLeft">Cuenta: {0}</div>']
            },
            subGrid: true,
            multiselect: false,
            footerrow: true,
            subGridRowExpanded: function (subgrid_id, row_id) {
                  let subgrid_table_id = subgrid_id + "_t";
                  // let compras_data = jQuery('#Lista_Anticipos').jqGrid('getRowData', row_id);
                  //#Lista_Anticipos'
                  $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
                  $("#" + subgrid_table_id).createGrid({
                        url: "?comprasDetAjax=" + row_id,
                        datatype: "json",
                        regional: 'es',
                        height: 'auto',
                        colModel: [
                              { label: 'Cod. Pro', name: 'Pro_Cod', width: 10, align: "left" },
                              { label: 'Cod. Pla', name: 'Pld_Cod', width: 10, align: "left" },
                              { label: 'No. Compr.', name: 'Cop_Cod', width: 20, align: "left" },
                              { label: 'Num. Plan', name: 'Pld_Cdc', width: 30, align: "left" },
                              { label: 'Des. Plan', name: 'Pld_Des', width: 50, align: "left" },
                              { label: 'Iva.', name: 'iva', formatter: 'currency', width: 20, align: "left" },
                              { label: 'Importe.', name: 'Cop_Imp', formatter: 'currency', width: 20, align: "left" },

                        ],
                        loadComplete: function () {
                        },
                        beforeSelectRow: function (rowid, e) { return false; },
                        rowNum: 10000,
                        pager: ""
                  });
            },

            loadComplete: function () {
                  $(this).jqGrid('footerData', 'set', {
                        Pld_Des: "<div style='text-align:center;'>TOTAES: </div>",
                        Sub_0: $(this).jqGrid('getCol', 'Sub_0', true, 'sum'),
                        Sub_5: $(this).jqGrid('getCol', 'Sub_5', true, 'sum'),
                        Sub_8: $(this).jqGrid('getCol', 'Sub_8', true, 'sum'),
                        Sub_12: $(this).jqGrid('getCol', 'Sub_12', true, 'sum'),
                        Sub_15: $(this).jqGrid('getCol', 'Sub_15', true, 'sum'),
                        subtotal: $(this).jqGrid('getCol', 'subtotal', true, 'sum'),
                        total_iva: $(this).jqGrid('getCol', 'total_iva', true, 'sum'),
                        total: $(this).jqGrid('getCol', 'total', true, 'sum')
                  }, true);
            }
      }, true, '#containerPager', { refresh: false, view: true })
            .gridButtonsAdd([
                  { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download',
                        onClickButton: function () {
                              exportarGruposAExcel();
                        }
                  },
                  { caption: 'Imprimir', buttonicon: 'glyphicon glyphicon-print',
                        onClickButton: function () {
                              imprimirGrupos();
                        }
                  }

            ]);

      // function imprimir() {
      //       $('#tablaReporte').html(container.jqGrid('exportGridInnerHTML', {
      //             footer: true,
      //             generated: false,
      //             removeHiddens: true,
      //             removeCols: [1]
      //       }));
      //       $('#imprimir').printElement();
      // }
}

// Ayuda para generar las tablas por grupo 
function generarTablasPorGrupo() {
      // Obtenemos el grid principal (ajusta el id si es necesario)
      var grid = $("#container");
      // Obtenemos la configuración de agrupamiento del grid
      var groupingView = grid.jqGrid('getGridParam', 'groupingView');
      var groups = groupingView.groups;
      var dataIDs = grid.jqGrid('getDataIDs');

      // Variable que almacenará el HTML final
      var htmlFinal = "<!DOCTYPE html>" + "<html>" + "<head>" + "<meta charset='UTF-8'>" +  "<title>Reporte</title>" + "</head>" + "<body>";

      // Encabezado principal centrado
      htmlFinal += "<h2 style='text-align:center; margin-bottom:20px;'>Detalles del Reporte</h2>"+"<br><br>";

      // Definimos la cabecera con todas las columnas del grid principal
      var cabeceraHTML = 
            "<tr>" +
                  "<th>Num. Com.</th>" +
                  "<th>Fecha</th>" +
                  "<th>Documento</th>" +
                  "<th>No. Doc</th>" +
                  "<th>Ced/Ruc</th>" +
                  "<th>Proveedor</th>" +
                  "<th>Detalle Comp.</th>" +
                  "<th>Consumo</th>" +
                  "<th>Forma Pago.</th>" +
                  "<th>Cuenta</th>" +
                  "<th>Rubro</th>" +
                  "<th>Base 0%</th>" +
                  "<th>Base 5%</th>" +
                  "<th>Base 8%</th>" +
                  "<th>Base 12%</th>" +
                  "<th>Base 15%</th>" +
                  "<th>Subtotal.</th>" +
                  "<th>IVA(%)</th>" +
                  "<th>Total IVA</th>" +
                  "<th>Total</th>" +
            "</tr>";

      // Recorremos cada grupo definido en el grid
      for (var i = 0; i < groups.length; i++) {
      var grupo = groups[i];
      var valorGrupo = grupo.value;  // Ejemplo: "COMBUSTIBLE"
      var inicio = grupo.startRow;
      var fin = inicio + grupo.cnt - 1;
      
      // Inicio de la tabla para este grupo
      htmlFinal += "<table border='1' cellspacing='0' cellpadding='3' style='width:100%; margin-bottom:10px;'>";
      
      // Encabezado del grupo: se muestra en una fila que abarca todas las columnas (16)
      htmlFinal += "<thead>";
      htmlFinal += "<tr><th colspan='20' style='text-align:left; background:#ccc; padding:5px;'>" +
                  valorGrupo + "</th></tr>";
      htmlFinal += cabeceraHTML;
      htmlFinal += "</thead>";
      
      htmlFinal += "<tbody>";
      
      // Inicializamos acumuladores para los totales del grupo
      var sumB0 = 0, sumB5 = 0, sumB8 = 0, sumB12 = 0, sumB15 = 0, sumSubtotal = 0, sumTotalIVA = 0, sumTotal = 0;
      
      // Recorremos las filas del grupo
      for (var r = inicio; r <= fin; r++) {
            var rowId = dataIDs[r];
            var rowData = grid.jqGrid('getRowData', rowId);
            
            // Se asume que los valores vienen formateados como moneda (por ejemplo, "$ 1,234.56").
            // Se utiliza esta función inline para extraer el valor numérico:
            var parseCurrency = function(val) {
                  return parseFloat((val || "0").replace(/[^0-9\.-]+/g, "")) || 0;
            };
            var base_0 = parseCurrency(rowData.Sub_0);
            var base_5 = parseCurrency(rowData.Sub_5);
            var base_8 = parseCurrency(rowData.Sub_8);
            var base_12 = parseCurrency(rowData.Sub_12);
            var base_15 = parseCurrency(rowData.Sub_15);
            var subtotal = parseCurrency(rowData.subtotal);
            var totalIVA = parseCurrency(rowData.total_iva);
            var total = parseCurrency(rowData.total);
            
            // Acumulamos los totales del grupo
            sumB0 += base_0;
            sumB5 += base_5;
            sumB8 += base_8;
            sumB12 += base_12;
            sumB15 += base_15;
            sumSubtotal += subtotal;
            sumTotalIVA += totalIVA;
            sumTotal += total;
            
            htmlFinal += "<tr>" +
                        "<td>" + rowData.Cop_Cod + "</td>" +
                        "<td>" + rowData.Cop_Fec + "</td>" +
                        "<td>" + rowData.Tic_Des + "</td>" +
                        "<td>" + rowData.Cop_Num + "</td>" +
                        "<td style='mso-number-format:\"\\@\";'>" + rowData.Prs_Ced + "</td>" +
                        "<td>" + rowData.proveedor + "</td>" +
                        "<td>" + rowData.Cop_Obs + "</td>" +
                        "<td>" + rowData.Con_Des + "</td>" +
                        "<td>" + rowData.Pago + "</td>" +
                        "<td>" + rowData.Pld_Cdc + "</td>" +
                        "<td>" + rowData.Pld_Des + "</td>" +
                        "<td>" + rowData.Sub_0 + "</td>" +
                        "<td>" + rowData.Sub_5 + "</td>" +
                        "<td>" + rowData.Sub_8 + "</td>" +
                        "<td>" + rowData.Sub_12 + "</td>" +
                        "<td>" + rowData.Sub_15 + "</td>" +
                        "<td style='text-align:right;'>" + rowData.subtotal + "</td>" +
                        "<td style='text-align:center;'>" + rowData.Iva_Por + "</td>" +
                        "<td style='text-align:right;'>" + rowData.total_iva + "</td>" +
                        "<td style='text-align:right;'>" + rowData.total + "</td>" +
                        "</tr>";
      }
      
      // Fila de totales para el grupo
      htmlFinal += 
            "<tr style='font-weight:bold;'>" +
            "<td colspan='11' style='text-align:right;'>Totales:</td>" +
            "<td style='text-align:right;'>" + sumB0.toFixed(2) + "</td>" +
            "<td style='text-align:right;'>" + sumB5.toFixed(2) + "</td>" +
            "<td style='text-align:right;'>" + sumB8.toFixed(2) + "</td>" +
            "<td style='text-align:right;'>" + sumB12.toFixed(2) + "</td>" +
            "<td style='text-align:right;'>" + sumB15.toFixed(2) + "</td>" +
            "<td style='text-align:right;'>" + sumSubtotal.toFixed(2) + "</td>" +
            "<td></td>" +
            "<td style='text-align:right;'>" + sumTotalIVA.toFixed(2) + "</td>" +
            "<td style='text-align:right;'>" + sumTotal.toFixed(2) + "</td>" +
            "</tr>";
      
      htmlFinal += "</tbody></table>";
      
      // Se agregan dos saltos de línea entre cada grupo
      htmlFinal += "<br><br>";
      }
      return htmlFinal;
}

// Función para exportar las tablas por grupo a Excel
function exportarGruposAExcel() {
      var htmlGrupos = generarTablasPorGrupo();
      var bom = "\uFEFF"; // Carácter BOM para UTF-8
      var uri = 'data:application/vnd.ms-excel;base64,';
      var template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" ' +
            'xmlns:x="urn:schemas-microsoft-com:office:excel" ' +
            'xmlns="http://www.w3.org/TR/REC-html40">' +
            '<head>' +
            '<meta charset="UTF-8">' + '<!--[if gte mso 9]>' +
            '<xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>' +
            '<x:Name>{worksheet}</x:Name>' +
            '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>' +
            '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook>' +
            '</xml><![endif]--></head>' +
            '<body>{table}</body></html>';

            var base64 = function(s) {
                  var utf8Bytes = new TextEncoder().encode(bom + s); // Incluir BOM
                  var binary = '';
                  utf8Bytes.forEach(b => binary += String.fromCharCode(b));
                  return window.btoa(binary);
            };

      var ctx = { worksheet: 'Hoja1', table: htmlGrupos };
      var htmlTemplate = template.replace(/{(\w+)}/g, function(m, p) { return ctx[p]; });

      // Crear el nombre del archivo con la fecha actual
      var today = new Date();
      var dd = today.getDate();
      var mm = today.getMonth() + 1;
      var yyyy = today.getFullYear();
      if (dd < 10) { dd = '0' + dd; }
      if (mm < 10) { mm = '0' + mm; }
      var dateStr = dd+ "-" +mm+ "-" +yyyy;
      var fileName = "Reporte_Compras-" + dateStr + ".xls";

      // Verificar si el navegador soporta el atributo download
      var link = document.createElement("a");
      if (typeof link.download !== "undefined") {
            link.href = uri + base64(htmlTemplate);
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
      } else {
            // Fallback: abre la descarga usando window.location.href (sin opción de nombre)
            window.location.href = uri + base64(htmlTemplate);
      }
}

// Función para imprimir las tablas por grupo reusando el HTML generado de excel
function imprimirGrupos() {
      // Genera el HTML con las tablas por grupo
      var htmlGrupos = generarTablasPorGrupo();
      // Abre una nueva ventana para la impresión
      var printWindow = window.open("", "PrintWindow", "width=1200,height=800");

      // Construye el documento HTML.
      printWindow.document.write("<html><head><title>Reporte Compras</title>");
      printWindow.document.write("<style>");
      printWindow.document.write("body { font-family: Arial, sans-serif; margin:0; padding:0; }");
      printWindow.document.write("h2 { text-align: center; margin: 20px 0; }");
      printWindow.document.write("table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }");
      printWindow.document.write("th, td { border: 1px solid #000; padding: 5px; font-size: 12px; }");
      printWindow.document.write("thead { background: #ccc; }");
      printWindow.document.write("tfoot { font-weight: bold; background: #f9f9f9; }");
      printWindow.document.write("tr { page-break-inside: avoid; }");
      printWindow.document.write("</style>");
      printWindow.document.write("</head><body>");
      printWindow.document.write(htmlGrupos);
      printWindow.document.write("</body></html>");
      printWindow.document.close();
      printWindow.focus();
      
      // Espera un breve retardo para asegurarse de que se cargue todo y luego llama al diálogo nativo
      setTimeout(function(){
            printWindow.print();
            printWindow.close();
      }, 500);
}

//Nuevos metodos
function open_plan_cuentas() {
      $("#search").val("");
      $("#cod_plan_cntas").val("");
      load_plan_cuentas();
      $('#Lista_plan').Search('#formCuentas', 'cuenAjax');
      $('#agregar_plan_cuentas').dialog({
            autoOpen: false,
            modal: true,
            width: '50%',
            height: 360,
            title: 'Plan de cuentas',
            open: function (event, ui) {
                  $(this).parent().find('.btn-siguiente').addClass('btn-primary'); // Ejemplo: btn-primary de Bootstrap
            }
      });
      $('#agregar_plan_cuentas').dialog('open');
}


function load_plan_cuentas() {
      //Dialog buscar clientes
      Lista_plan_cuentas.createGrid({
            mtype: "GET",
            height: 200,
            datatype: 'json',
            // responsive: true,
            regional: 'es',
            autowidth: true,
            shrinkToFit: true,
            cmTemplate: {
                  sortable: false
            },
            colModel: [{
                  label: 'Cód.Int.',
                  name: 'Pld_Cod',
                  key: true,
                  width: 10,
                  align: "center",
                  hidden: false
            },
            {
                  label: 'C&oacute;digo',
                  name: 'Pld_Cdc',
                  width: 20
            },
            {
                  label: 'Cuenta',
                  name: 'Pld_Des',
                  width: 50,
                  cellattr: function (rowId, tv, rawObject, cm, rdata) {
                        return 'style="white-space: normal;"';
                  }
            },
            {
                  label: '<center><i class="ui-icon ui-icon-gear"></i></center>',
                  name: 'act1',
                  width: 10,
                  align: 'center',
                  viewable: false,
                  formatter: 'gridButton',
                  formatoptions: {
                        action: 'SelectCta',
                        title: 'Seleccione Cuentas',
                        data: ['Pld_Cod', 'Pld_Cdc', 'Pld_Des']
                  }
            }]
      })
}

function SelectCta(cta) {
      console.log("Datos de la fila seleccionada:", cta.Pld_Des);
      $("#search").val(cta.Pld_Des);
      $("#cod_plan_cntas").val(cta.Pld_Cod);
      $('#agregar_plan_cuentas').dialog('close');
}

function valores_aproximados() {
     // $('#procesando').show();
      var cantidad_aproximacion = $("#cantidad_aproximada").val();//obtener el valor para aproximar
      if (cantidad_aproximacion.trim() === '' || cantidad_aproximacion.trim() <= 0) {
            $('#codigos_compras').val("");
            
      } else {
            let allData = container.jqGrid('getRowData');//datos de la tabla
            totalArray = allData.map(row => parseFloat(row.total));//obtengo los datos solo de la columna de totales
            cod_compra_Array = allData.map(row => parseFloat(row.Cop_Cod));//codigo de la compra
            let result = findBestCombination(cod_compra_Array, totalArray, cantidad_aproximacion); //llama al metodo para aproximar
            let sum = result.combination.reduce((acc, value) => acc + value, 0); //cantidades qeu se han utilizado
            $('#codigos_compras').val(" AND  com.Cop_Cod IN (" + result.usedValues.join(", ") + ")");
      }
}


function findBestCombination(cod_compra_Array, array, target) {
      let bestSum = 0;
      let n = array.length;
      let closestSum = Array(target + 1).fill(null);
      closestSum[0] = 0;
      array.forEach((value, index) => {
            for (let sum = target; sum >= value; sum--) {
                  if (closestSum[sum - value] !== null) {
                        if (closestSum[sum] === null || Math.abs(sum - target) < Math.abs(closestSum[sum] - target)) {
                              closestSum[sum] = closestSum[sum - value] + value;
                        }
                  }
            }
      });
      if (closestSum[target] !== null) {
            bestSum = closestSum[target];
      } else {
            bestSum = 0;
      }
      let combination = [];
      let usedValues = [];
      let sum = target;
      array.slice().reverse().forEach((value, index) => {
            if (sum >= value && closestSum[sum - value] !== null) {
                  combination.push(value);
                  usedValues.push(cod_compra_Array[array.length - 1 - index]);
                  sum -= value;
            }
      });
      if (combination.length === 0) {
            return { combination: [], usedValues: [], sum: 0 };
      }
      return { combination: combination.reverse(), usedValues: usedValues.reverse(), sum: bestSum };
}


