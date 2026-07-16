// ==================== GLOBAL VARIABLES ====================
var gridAlimentacion;
var comidasRegistradas = [];

// ==================== INITIALIZATION ====================
$(function () {
  // Inicializar datepicker para modal
  if (typeof $.createDatePickers === "function") {
    $.createDatePickers("#txtFechaModal");
  } else if ($.fn.datepicker) {
    $("#txtFechaModal").datepicker({
      dateFormat: "dd/mm/yy",
      changeMonth: true,
      changeYear: true,
    });
  }

  // Inicializar Chosen para selects
  $("#cboPersonalModal").chosen({
    no_results_text: "No hay resultados",
    width: "100%",
  });
  
  $("#cboPersonalReporte").chosen({
    no_results_text: "No hay resultados",
    width: "250px",
  });

  // Hacer el modal arrastrable (draggable) con la cabecera como manejador
  if (typeof $.fn.draggable === "function") {
    $("#modalRegistroAlimentacion .modal-dialog").draggable({
      handle: ".modal-header",
    });
  }

  // Cargar selects iniciales
  loadPersonalModal();
  loadPersonalReporte();

  // Inicializar grid al cargar la página
  initializeGrid();

  // Event listeners para modal
  $("#cboPersonalModal").on("change", function () {
    cargarComidasRegistradas();
  });

  // Detectar cambios en la fecha (datepicker nativo o input)
  $("#txtFechaModal").on("change changeDate input", function () {
    cargarComidasRegistradas();
  });

  $("#btnNuevoAlimentacion").on("click", function () {
    nuevoRegistroModal();
    cargarComidasRegistradas();
    $("#modalRegistroAlimentacion").modal("show");
  });

  $("#btnGuardarModal").on("click", guardarAlimentacionModal);

  $("#btnBuscar").on("click", reloadGridAlimentacion);
  $("#btnGenerarReporte").on("click", generarReporte);

  // Bind buttons for PDF and Excel export on Report Tab
  $("#btnExportarReportePDF").on("click", exportarPDFFormalRRHH);
  $("#btnExportarReporteExcel").on("click", exportarExcelReporteWeb);
});

// ==================== LOAD SELECTS ====================
function loadPersonalModal() {
  $.ajax({
    url: "man_alt_alimentacion.php",
    data: { listPersonalAjax: 1 },
    type: "GET",
    dataType: "json",
    success: function (data) {
      var $select = $("#cboPersonalModal");
      $select.empty();
      $select.append('<option value="">Seleccione Personal</option>');
      if (data) {
        $.each(data, function (i, item) {
          $select.append(
            '<option value="' + item.Per_Cod + '">' + item.nombre + "</option>",
          );
        });
      }
      $select.trigger("chosen:updated");
    },
  });
}

function loadPersonalReporte() {
  $.ajax({
    url: "man_alt_alimentacion.php",
    data: { listPersonalAjax: 1 },
    type: "GET",
    dataType: "json",
    success: function (data) {
      var $select = $("#cboPersonalReporte");
      $select.empty();
      $select.append('<option value="">Todos</option>');
      if (data) {
        $.each(data, function (i, item) {
          $select.append(
            '<option value="' + item.Per_Cod + '">' + item.nombre + "</option>",
          );
        });
      }
      $select.trigger("chosen:updated");
    },
  });
}

// ==================== MODAL REGISTRO ====================
// ==================== VALIDACIÓN DE SECUENCIA ====================
// Función para validar que no se salten comidas (D -> A -> M -> C)
function validarSecuenciaComidas(
  todosComidas,
  comidasExistentes,
  comidAsSeleccionadas,
) {
  var orden = ["D", "A", "M", "C"];
  var nombres = { D: "Desayuno", A: "Almuerzo", M: "Merienda", C: "Cena" };

  // Si no hay comidas a guardar, no hay que validar secuencia
  if (comidAsSeleccionadas.length === 0) {
    return null;
  }

  // Crear conjunto de todas las comidas después de guardar
  var comidasTotales = {};
  orden.forEach(function (c) {
    comidasTotales[c] = false;
  });

  comidasExistentes.forEach(function (c) {
    comidasTotales[c] = true;
  });

  comidAsSeleccionadas.forEach(function (c) {
    comidasTotales[c] = true;
  });

  // Validar que no haya huecos en la secuencia (todas las anteriores a la mayor seleccionada deben existir)
  var mayorSeleccionadoIndex = -1;
  for (var i = 0; i < orden.length; i++) {
    if (comidasTotales[orden[i]]) {
      mayorSeleccionadoIndex = i;
    }
  }

  var comidasFaltantes = [];
  for (var i = 0; i < mayorSeleccionadoIndex; i++) {
    if (!comidasTotales[orden[i]]) {
      comidasFaltantes.push(nombres[orden[i]]);
    }
  }

  if (comidasFaltantes.length > 0) {
    return (
      "No puede saltar comidas. Le falta registrar: " +
      comidasFaltantes.join(", ") +
      "."
    );
  }

  return null; // Secuencia válida
}

function cargarComidasRegistradas() {
  var fecha = $("#txtFechaModal").val();
  var personal = $("#cboPersonalModal").val();

  // Resetear checkboxes (quitar selección y habilitar todos)
  $('input[name="chkAlimentacionModal"]')
    .prop("checked", false)
    .prop("disabled", false);
  comidasRegistradas = [];

  if (!fecha || !personal) {
    return;
  }

  $.ajax({
    url: "man_alt_alimentacion.php",
    data: {
      getAlimentacionRegistradaAjax: 1,
      Per_Cod: personal,
      Mal_Fec: fecha,
    },
    type: "GET",
    dataType: "json",
    success: function (data) {
      if (data && Array.isArray(data)) {
        comidasRegistradas = data;
        $.each(data, function (i, val) {
          $('input[name="chkAlimentacionModal"][value="' + val + '"]')
            .prop("checked", true)
            .prop("disabled", true);
        });
      }
    },
  });
}

function guardarAlimentacionModal() {
  var fecha = $("#txtFechaModal").val();
  var personal = $("#cboPersonalModal").val();

  var tiposSeleccionados = [];
  var nuevosTipos = [];

  $('input[name="chkAlimentacionModal"]:checked').each(function () {
    var val = $(this).val();
    tiposSeleccionados.push(val);
    if (comidasRegistradas.indexOf(val) === -1) {
      nuevosTipos.push(val);
    }
  });

  // Validar y bloquear el proceso si existen errores
  if (!fecha || fecha.trim() === "") {
    mostrarAlertaError("Debe ingresar o seleccionar la fecha.");
    return;
  }
  if (!personal || personal.trim() === "") {
    mostrarAlertaError("Debe seleccionar al personal.");
    return;
  }
  if (tiposSeleccionados.length === 0) {
    mostrarAlertaError("Debe seleccionar al menos un tipo de alimentación.");
    return;
  }

  // Bloquear si todas las raciones seleccionadas ya existían previamente
  if (nuevosTipos.length === 0 && tiposSeleccionados.length > 0) {
    mostrarAlertaError(
      "No ha seleccionado ninguna ración de alimentación nueva para guardar (las seleccionadas ya fueron registradas).",
    );
    return;
  }

  // Validar que no salte comidas (secuencia: D -> A -> M -> C)
  var comidas_registradas_conjunto = comidasRegistradas.slice(); // copiar array
  var comidas_a_guardar_conjunto = nuevosTipos.slice(); // copiar array
  var todas_comidas = comidas_registradas_conjunto.concat(
    comidas_a_guardar_conjunto,
  );

  // Orden de secuencia válida
  var orden = ["D", "A", "M", "C"];
  var errorsecuencia = validarSecuenciaComidas(
    todas_comidas,
    comidasRegistradas,
    nuevosTipos,
  );
  if (errorsecuencia) {
    mostrarAlertaError(errorsecuencia);
    return;
  }

  $.ajax({
    url: "man_alt_alimentacion.php",
    data: {
      saveAlimentacionAjax: 1,
      txtFecha: fecha,
      cboPersonal: personal,
      cboTipos: tiposSeleccionados,
    },
    type: "POST",
    dataType: "json",
    beforeSend: function () {
      $("#btnGuardarModal").prop("disabled", true);
      $("#loaderAlimentacion").show();
    },
    success: function (resp) {
      if (resp.success) {
        mostrarAlertaExito(resp.message);
        $("#modalRegistroAlimentacion").modal("hide");
        nuevoRegistroModal();
        reloadGridAlimentacion();
      } else {
        mostrarAlertaError(resp.message);
      }
    },
    error: function (xhr) {
      mostrarAlertaError("Error al guardar: " + xhr.responseText);
    },
    complete: function () {
      $("#btnGuardarModal").prop("disabled", false);
      $("#loaderAlimentacion").hide();
    },
  });
}

// Funciones auxiliares para mostrar alertas propias del proyecto
function mostrarAlertaError(mensaje) {
  if (typeof $.alert === "function") {
    $.alert(mensaje);
  } else if (typeof alertError === "function") {
    alertError(mensaje);
  } else {
    alert(
      mensaje.replace(/<br>/g, "\n").replace(/<b>/g, "").replace(/<\/b>/g, ""),
    );
  }
}

function mostrarAlertaExito(mensaje) {
  if (typeof $.alert === "function") {
    $.alert(mensaje);
  } else if (typeof alertExito === "function") {
    alertExito(mensaje);
  } else {
    alert(
      mensaje.replace(/<br>/g, "\n").replace(/<b>/g, "").replace(/<\/b>/g, ""),
    );
  }
}

function nuevoRegistroModal() {
  $("#txtFechaModal").val(
    $.datepicker ? $.datepicker.formatDate("dd/mm/yy", new Date()) : "",
  );
  $("#cboPersonalModal").val("").trigger("chosen:updated");
  $('input[name="chkAlimentacionModal"]')
    .prop("checked", false)
    .prop("disabled", false);
  comidasRegistradas = [];
}

// ==================== FILTRO PERIODO ====================
function ajustarFiltroFecha(tipo) {
  $("#filtroFechaDia").hide();
  $("#filtroFechaSemana").hide();
  $("#filtroFechaMes").hide();
  $("#filtroQuincena").hide();

  if (tipo === "D") {
    $("#filtroFechaDia").show();
  } else if (tipo === "S") {
    $("#filtroFechaSemana").show();
  } else if (tipo === "Q") {
    $("#filtroFechaMes").show();
    $("#filtroQuincena").show();
  } else if (tipo === "M") {
    $("#filtroFechaMes").show();
  }
}

// Limita el año a 4 dígitos en inputs nativos type=date/week/month
function limitarAnioInput($input) {
  var val = $input.val();
  if (!val) return;

  // El valor de estos inputs siempre tiene el formato YYYY-... o YYYY-W...
  var partes = val.split("-");
  if (partes[0] && partes[0].length > 4) {
    partes[0] = partes[0].substring(0, 4);
    $input.val(partes.join("-"));
  }
}

$(function () {
  // Restringir año a 4 dígitos en los filtros de fecha nativos
  $("#filtroFechaDia, #filtroFechaSemana, #filtroFechaMes").on(
    "input change",
    function () {
      limitarAnioInput($(this));
    },
  );
});

// ==================== GRID ====================
function initializeGrid() {
  gridAlimentacion = $("#gridAlimentacion").createGrid(
    {
      caption: "",
      url: "man_alt_alimentacion.php",
      postData: { listAlimentacionGridAjax: 1 },
      height: 350,
      rowNum: 20,
      rowList: [10, 20, 50, 100],
      colModel: [
        {
          label: "Código",
          name: "Mal_Cod",
          key: true,
          hidden: true,
          width: 50,
          align: "center",
        },
        { label: "Active_Ids", name: "Active_Ids", hidden: true },
        {
          label: "Fecha",
          name: "Mal_Fec",
          width: 90,
          formatter: "date",
          formatoptions: { newformat: "d/m/Y" },
        },
        { label: "Cédula/RUC", name: "Per_Ced", width: 110 },
        { label: "Personal", name: "Per_Nom", width: 190 },
        {
          label: "D",
          name: "Tip_D",
          width: 45,
          align: "center",
          formatter: function (cell) {
            return cell == 1
              ? '<span style="color: #10b981; font-weight: bold; font-size: 14px;">✔</span>'
              : '<span style="color: #cbd5e1;">-</span>';
          },
        },
        {
          label: "A",
          name: "Tip_A",
          width: 45,
          align: "center",
          formatter: function (cell) {
            return cell == 1
              ? '<span style="color: #10b981; font-weight: bold; font-size: 14px;">✔</span>'
              : '<span style="color: #cbd5e1;">-</span>';
          },
        },
        {
          label: "M",
          name: "Tip_M",
          width: 45,
          align: "center",
          formatter: function (cell) {
            return cell == 1
              ? '<span style="color: #10b981; font-weight: bold; font-size: 14px;">✔</span>'
              : '<span style="color: #cbd5e1;">-</span>';
          },
        },
        {
          label: "C",
          name: "Tip_C",
          width: 45,
          align: "center",
          formatter: function (cell) {
            return cell == 1
              ? '<span style="color: #10b981; font-weight: bold; font-size: 14px;">✔</span>'
              : '<span style="color: #cbd5e1;">-</span>';
          },
        },
        { label: "Usuario", name: "Usu_Nom", width: 160 },
        {
          label: "Estado",
          name: "Mal_Est",
          width: 80,
          formatter: function (cell) {
            return cell == "A" ? "Activo" : "Inactivo";
          },
        },
        {
          label: "Acciones",
          name: "acciones",
          width: 90,
          align: "center",
          sortable: false,
          formatter: function (cell, opts, rowData) {
            if (rowData.Mal_Est == "A" && rowData.Active_Ids) {
              var previewBtn =
                '<button type="button" class="btn btn-xs btn-info" title="Ver detalle" onclick="previewRegistroByIds(\'' +
                rowData.Active_Ids +
                '\');"><i class="glyphicon glyphicon-eye-open"></i></button>';
              return previewBtn;
            }
            return "";
          },
        },
      ],
      viewrecords: true,
      jsonReader: {
        root: "rows",
        page: "page",
        total: "total",
        records: "records",
        repeatitems: false,
      },
    },
    false,
    "#pagerAlimentacion",
    { refresh: true, view: false },
  );

  // Agregar botón de Excel al pager
  $("#gridAlimentacion").jqGrid("navButtonAdd", "#pagerAlimentacion", {
    caption: "Excel",
    title: "Exportar a Excel",
    buttonicon: "glyphicon glyphicon-file",
    onClickButton: function () {
      exportarExcelAlimentacion();
    },
    position: "last",
  });
}

// Función para exportar a Excel
function exportarExcelAlimentacion() {
  if (typeof $.printExport === "function") {
    $("#gridAlimentacion").printExport({
      type: "excel",
      filename:
        "Alimentacion_" +
        new Date().toISOString().slice(0, 10).replace(/-/g, ""),
    });
  } else {
    alert(
      "La función de exportación a Excel no está disponible en este momento.",
    );
  }
}

function reloadGridAlimentacion() {
  if (!gridAlimentacion) {
    initializeGrid();
  }

  gridAlimentacion
    .setGridParam({
      url: "man_alt_alimentacion.php",
      datatype: "json",
      postData: {
        listAlimentacionGridAjax: 1,
        f_tipo: $("#tipoFiltroFecha").val(),
        f_val_dia: $("#filtroFechaDia").val(),
        f_val_semana: $("#filtroFechaSemana").val(),
        f_val_mes: $("#filtroFechaMes").val(),
        f_quincena: $("#filtroQuincena").val(),
        f_buscar: $("#txtBuscar").val(),
        f_tipo_busqueda: $("#tipoBusqueda").val(),
        f_estado: $("#cboEstado").val(),
      },
      page: 1,
    })
    .trigger("reloadGrid");
}

function anularRegistro(mal_cod) {
  if (confirm("¿Está seguro de anular este registro?")) {
    $.ajax({
      url: "man_alt_alimentacion.php",
      data: { anularAlimentacionAjax: 1, Mal_Cod: mal_cod },
      type: "POST",
      dataType: "json",
      success: function (resp) {
        if (resp.success) {
          if (typeof alertExito === "function") {
            alertExito(resp.message);
          } else {
            alert(resp.message);
          }
          reloadGridAlimentacion();
        } else {
          if (typeof alertError === "function") {
            alertError(resp.message);
          } else {
            alert(resp.message);
          }
        }
      },
    });
  }
}

// Mostrar preview de las comidas de un registro usando Active_Ids
function previewRegistroByIds(activeIds) {
  if (!activeIds) {
    alert("No se recibió el identificador del registro.");
    return;
  }

  $.ajax({
    url: "man_alt_alimentacion.php",
    data: { getPreviewAlimentacionAjax: 1, Active_Ids: activeIds },
    type: "GET",
    dataType: "json",
    success: function (resp) {
      if (!resp || !resp.success) {
        mostrarAlertaError(
          resp && resp.message
            ? resp.message
            : "No se pudo obtener el detalle de alimentación.",
        );
        return;
      }

      $("#pv_fecha").text(resp.data.fecha || "");
      $("#pv_personal").text(resp.data.personal || "");

      renderPreviewTimeline(resp.data.comidas || []);
      $("#modalPreviewAlimentacion").modal("show");
    },
    error: function (xhr) {
      mostrarAlertaError("Error al obtener el detalle: " + xhr.responseText);
    },
  });
}

function renderPreviewTimeline(comidas) {
  var orden = [
    { clave: "D", label: "Desayuno", icon: "glyphicon-fire" },
    { clave: "A", label: "Almuerzo", icon: "glyphicon-cutlery" },
    { clave: "M", label: "Merienda", icon: "glyphicon-time" },
    { clave: "C", label: "Cena", icon: "glyphicon-star" },
  ];

  var comidasSet = {};
  comidas.forEach(function (tipo) {
    comidasSet[tipo] = true;
  });

  var html = '<div class="preview-timeline">';
  orden.forEach(function (item, index) {
    var active = comidasSet[item.clave];
    html += '<div class="preview-step' + (active ? " active" : "") + '">';
    html +=
      '<div class="preview-icon"><i class="glyphicon ' +
      item.icon +
      '"></i></div>';
    html += '<div class="preview-label">' + item.label + "</div>";
    html += "</div>";
  });
  html += "</div>";

  $("#pv_comidas_list").html(html);
}

// ==================== EXPORTACIONES REPORTE ====================
function exportarPDFFormalRRHH() {
  if ($("#divReportePrint").length === 0) {
    mostrarAlertaError(
      "Primero debe generar el reporte para poder imprimirlo/exportarlo a PDF.",
    );
    return;
  }
  window.print();
}

function exportarExcelReporteWeb() {
  if ($("#tablaExcelRRHH").length === 0) {
    mostrarAlertaError(
      "Primero debe generar el reporte para poder exportarlo a Excel.",
    );
    return;
  }

  if (typeof $.printExport === "function") {
    $("#tablaExcelRRHH").printExport({
      type: "excel",
      filename:
        "Reporte_Alimentacion_" +
        new Date().toISOString().slice(0, 10).replace(/-/g, ""),
    });
  } else {
    mostrarAlertaError("La función de exportación a Excel no está disponible.");
  }
}

// ==================== REPORTE ====================
function generarReporte() {
  $.ajax({
    url: "man_alt_alimentacion.php",
    data: {
      getReporteAlimentacionAjax: 1,
      cboMes: $("#cboMes").val(),
      cboAnio: $("#cboAnio").val(),
      cboPersonalReporte: $("#cboPersonalReporte").val(),
    },
    type: "GET",
    dataType: "json",
    success: function (reporte) {
      var html = "";
      var mes_nombre = $("#cboMes option:selected").text();
      var anio = $("#cboAnio").val();

      var totDes = 0,
        totAlm = 0,
        totMer = 0,
        totCen = 0;
      var perCount = 0; // Estructuras para el PDF y Excel Oculto
      var pdfDetallePersonal = "";
      var pdfResumenPersonal = "";
      var excelFilas = "";
      var vehiculosHtml = "";

      $.each(reporte, function (key, per_data) {
        perCount++;
        var p_des = 0,
          p_alm = 0,
          p_mer = 0;
        var q1_total = 0,
          q2_total = 0;

        var detalleHtml =
          '<table class="table table-bordered table-condensed table-striped" style="margin-top:10px; font-size:12px;">';
        detalleHtml +=
          '<thead style="background:#f1f5f9; color:#334a5f;"><tr><th>Fecha</th><th style="text-align:center;">D</th><th style="text-align:center;">A</th><th style="text-align:center;">M</th></tr></thead><tbody>';

        var pdfTablaDiario =
          '<div style="margin-top:20px;"><strong>Personal: ' +
          per_data.Per_Nom +
          '</strong></div><table border="1" cellpadding="3" cellspacing="0" style="width:100%; font-size:11px; margin-top:5px; margin-bottom:15px; border-collapse:collapse; border-color:#cbd5e1;">';
        pdfTablaDiario +=
          '<tr style="background-color:#f1f5f9; font-weight:bold;"><td>Fecha</td><td align="center">D</td><td align="center">A</td><td align="center">M</td><td align="center">Total Diario</td></tr>';

        var ultimo_dia = new Date(
          anio,
          parseInt($("#cboMes").val()),
          0,
        ).getDate();
        for (var dia = 1; dia <= ultimo_dia; dia++) {
          var mes_str = String($("#cboMes").val()).padStart(2, "0");
          var dia_str = String(dia).padStart(2, "0");
          var fecha_str = anio + "-" + mes_str + "-" + dia_str;
          var datos = per_data.datos[fecha_str];

          if (datos) {
            p_des += datos.Desayuno || 0;
            p_alm += datos.Almuerzo || 0;
            p_mer += datos.Merienda || 0;
            var totalDia =
              (datos.Desayuno || 0) +
              (datos.Almuerzo || 0) +
              (datos.Merienda || 0);
            if (dia <= 15) q1_total += totalDia;
            else q2_total += totalDia;

            detalleHtml += "<tr>";
            detalleHtml +=
              "<td>" + dia_str + "/" + mes_str + "/" + anio + "</td>";
            detalleHtml +=
              '<td style="text-align: center;">' +
              (datos.Desayuno || 0) +
              "</td>";
            detalleHtml +=
              '<td style="text-align: center;">' +
              (datos.Almuerzo || 0) +
              "</td>";
            detalleHtml +=
              '<td style="text-align: center;">' +
              (datos.Merienda || 0) +
              "</td>";
            detalleHtml += "</tr>";

            pdfTablaDiario += "<tr>";
            pdfTablaDiario +=
              "<td>" + dia_str + "/" + mes_str + "/" + anio + "</td>";
            pdfTablaDiario +=
              '<td align="center">' + (datos.Desayuno || 0) + "</td>";
            pdfTablaDiario +=
              '<td align="center">' + (datos.Almuerzo || 0) + "</td>";
            pdfTablaDiario +=
              '<td align="center">' + (datos.Merienda || 0) + "</td>";
            pdfTablaDiario +=
              '<td align="center" style="font-weight:bold;">' +
              totalDia +
              "</td>";
            pdfTablaDiario += "</tr>";

            excelFilas += "<tr>";
            excelFilas += "<td>" + mes_nombre + " " + anio + "</td>";
            excelFilas += "<td>" + per_data.Per_Nom + "</td>";
            excelFilas +=
              "<td>" + dia_str + "/" + mes_str + "/" + anio + "</td>";
            excelFilas += "<td>" + (datos.Desayuno || 0) + "</td>";
            excelFilas += "<td>" + (datos.Almuerzo || 0) + "</td>";
            excelFilas += "<td>" + (datos.Merienda || 0) + "</td>";
            excelFilas += "<td>" + totalDia + "</td>";
            excelFilas += "<td>-</td>";
            excelFilas += "<td>-</td>";
            excelFilas += "<td>-</td>";
            excelFilas += "</tr>";
          }
        }
        detalleHtml += "</tbody></table>";
        pdfTablaDiario += "</table>";
        pdfDetallePersonal += pdfTablaDiario;

        totDes += p_des;
        totAlm += p_alm;
        totMer += p_mer;
        var totalPer = p_des + p_alm + p_mer;

        pdfResumenPersonal += "<tr>";
        pdfResumenPersonal += "<td>" + per_data.Per_Nom + "</td>";
        pdfResumenPersonal += '<td align="center">' + p_des + "</td>";
        pdfResumenPersonal += '<td align="center">' + p_alm + "</td>";
        pdfResumenPersonal += '<td align="center">' + p_mer + "</td>";
        pdfResumenPersonal += '<td align="center">' + q1_total + "</td>";
        pdfResumenPersonal += '<td align="center">' + q2_total + "</td>";
        pdfResumenPersonal +=
          '<td align="center" style="font-weight:bold;">' + totalPer + "</td>";
        pdfResumenPersonal += "</tr>";

        excelFilas +=
          '<tr style="font-weight:bold; background-color:#e2e8f0;">';
        excelFilas += "<td>" + mes_nombre + " " + anio + "</td>";
        excelFilas += "<td>" + per_data.Per_Nom + " (Total)</td>";
        excelFilas += "<td>Resumen Mensual</td>";
        excelFilas += "<td>" + p_des + "</td>";
        excelFilas += "<td>" + p_alm + "</td>";
        excelFilas += "<td>" + p_mer + "</td>";
        excelFilas += "<td>" + totalPer + "</td>";
        excelFilas += "<td>" + q1_total + "</td>";
        excelFilas += "<td>" + q2_total + "</td>";
        excelFilas += "<td>" + totalPer + "</td>";
        excelFilas += "</tr>";

        var perId = "per_" + String(key).replace(/[^a-zA-Z0-9]/g, "");
        vehiculosHtml += `
                <div class="col-md-4 col-sm-6" style="margin-bottom: 20px;">
                    <div style="border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 15px;">
                        <h4 style="font-weight: bold; color: #1e88e5; margin-top: 0;">${per_data.Per_Nom}</h4>
                        <div style="font-size: 13px; color: #475569; margin-bottom: 10px;">
                            <div>Desayunos: <b>${p_des}</b></div>
                            <div>Almuerzos: <b>${p_alm}</b></div>
                            <div>Meriendas: <b>${p_mer}</b></div>
                            <hr style="margin: 8px 0;">
                            <div>Total General: <b>${totalPer}</b></div>
                            <div style="margin-top: 5px;">Primera Quincena: <b>${q1_total}</b> | Segunda Quincena: <b>${q2_total}</b></div>
                        </div>
                        <button class="btn btn-default btn-xs btn-block" type="button" data-toggle="collapse" data-target="#${perId}" aria-expanded="false" aria-controls="${perId}">
                            Ver Detalle Diario <i class="glyphicon glyphicon-chevron-down"></i>
                        </button>
                        <div class="collapse" id="${perId}">
                            ${detalleHtml}
                        </div>
                    </div>
                </div>`;
      });

      if (perCount === 0) {
        $("#divReporte").html(
          '<p style="font-style: italic; color: #888;">No hay datos para mostrar en el período seleccionado.</p>',
        );
        return;
      }

      var totGeneral = totDes + totAlm + totMer;
      var d = new Date();
      var fechaGen =
        d.getDate().toString().padStart(2, "0") +
        "/" +
        (d.getMonth() + 1).toString().padStart(2, "0") +
        "/" +
        d.getFullYear() +
        " " +
        d.getHours().toString().padStart(2, "0") +
        ":" +
        d.getMinutes().toString().padStart(2, "0");

      html += `
            <div id="vistaGerencialWeb">
                <div style="text-align: center; margin-bottom: 25px;">
                    <h3 style="margin-bottom: 5px; color: #334a5f; font-weight: bold;">Control de Alimentación de Personal Interno</h3>
                    <h4 style="margin-top: 0; color: #64748b;">Resumen de Consumo Alimenticio</h4>
                    <div style="font-size: 13px; color: #94a3b8; display: flex; justify-content: center; gap: 15px; margin-top: 10px;">
                        <span><i class="glyphicon glyphicon-calendar"></i> Período: ${mes_nombre} ${anio}</span>
                        <span><i class="glyphicon glyphicon-time"></i> Generado: ${fechaGen}</span>
                    </div>
                </div>
                
                <div class="row" style="margin-bottom: 25px;">
                    <div class="col-xs-6 col-sm-3">
                        <div style="background: #f1f5f9; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="font-size: 12px; color: #64748b; font-weight: bold; text-transform: uppercase;">Total Desayunos</div>
                            <div style="font-size: 24px; color: #334a5f; font-weight: bold;">${totDes}</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div style="background: #f1f5f9; border-left: 4px solid #10b981; padding: 15px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="font-size: 12px; color: #64748b; font-weight: bold; text-transform: uppercase;">Total Almuerzos</div>
                            <div style="font-size: 24px; color: #334a5f; font-weight: bold;">${totAlm}</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div style="background: #f1f5f9; border-left: 4px solid #3b82f6; padding: 15px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="font-size: 12px; color: #64748b; font-weight: bold; text-transform: uppercase;">Total Meriendas</div>
                            <div style="font-size: 24px; color: #334a5f; font-weight: bold;">${totMer}</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div style="background: #f1f5f9; border-left: 4px solid #8b5cf6; padding: 15px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="font-size: 12px; color: #64748b; font-weight: bold; text-transform: uppercase;">Total General</div>
                            <div style="font-size: 24px; color: #334a5f; font-weight: bold;">${totGeneral}</div>
                        </div>
                    </div>
                </div>

                <h4 style="border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; color: #334a5f; margin-bottom: 15px; font-weight:bold;">Resumen por Personal</h4>
                <div class="row">
                    ${vehiculosHtml}
                </div>
            </div>`;

      var htmlPDF = `
            <div id="divReportePrint" style="display:none; width: 100%; max-width: 800px; margin: 0 auto; background: #fff;">
                <div style="font-family: Arial, sans-serif; color: #333;">
                    <div style="text-align: center; border-bottom: 2px solid #1e88e5; padding-bottom: 10px; margin-bottom: 20px;">
                        <h2 style="margin: 0; color: #1e88e5;">CONTROL DE ALIMENTACIÓN DE PERSONAL INTERNO</h2>
                        <h3 style="margin: 5px 0 10px 0; color: #555;">INFORME MENSUAL DE CONSUMO</h3>
                        <p style="margin: 0; font-size: 12px;"><strong>Período:</strong> ${mes_nombre} ${anio} | <strong>Fecha de generación:</strong> ${fechaGen}</p>
                    </div>

                    <h4 style="background-color: #f1f5f9; padding: 5px; margin:0 0 10px 0;">1. RESUMEN EJECUTIVO</h4>
                    <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 12px; border-color:#cbd5e1;">
                        <tr style="background-color: #e2e8f0; font-weight: bold;">
                            <td>Total Desayunos</td><td align="center">Total Almuerzos</td><td align="center">Total Meriendas</td><td align="center">Total General</td>
                        </tr>
                        <tr>
                            <td align="center">${totDes}</td>
                            <td align="center">${totAlm}</td>
                            <td align="center">${totMer}</td>
                            <td align="center" style="font-weight: bold; font-size: 14px;">${totGeneral}</td>
                        </tr>
                    </table>

                    <h4 style="background-color: #f1f5f9; padding: 5px; margin:0 0 10px 0;">2. RESUMEN POR PERSONAL</h4>
                    <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 12px; border-color:#cbd5e1;">
                        <tr style="background-color: #e2e8f0; font-weight: bold;">
                            <td>Personal</td><td align="center">Desayunos</td><td align="center">Almuerzos</td><td align="center">Meriendas</td><td align="center">1ra Quincena</td><td align="center">2da Quincena</td><td align="center">Total</td>
                        </tr>
                        ${pdfResumenPersonal}
                    </table>

                    <h4 style="background-color: #f1f5f9; padding: 5px; margin:0 0 10px 0;">3. DETALLE DIARIO</h4>
                    ${pdfDetallePersonal}

                    <div style="margin-top: 50px; text-align: center; font-size: 12px; page-break-inside: avoid;">
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="width: 33%; text-align: center;">
                                    ________________________<br>Elaborado por
                                </td>
                                <td style="width: 33%; text-align: center;">
                                    ________________________<br>Revisado por
                                </td>
                                <td style="width: 33%; text-align: center;">
                                    ________________________<br>Aprobado por
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <p style="font-size: 10px; color: #777; margin-top: 30px; text-align: justify; page-break-inside: avoid;">
                        <em>Nota: Este reporte consolida las raciones registradas en el sistema durante el período seleccionado y sirve como respaldo para control administrativo, contable y de recursos humanos.</em>
                    </p>
                </div>
            </div>`;

      var htmlExcel = `
            <table id="tablaExcelRRHH" style="display:none;">
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Personal Interno</th>
                        <th>Fecha</th>
                        <th>Desayuno</th>
                        <th>Almuerzo</th>
                        <th>Merienda</th>
                        <th>Total Diario</th>
                        <th>Primera Quincena</th>
                        <th>Segunda Quincena</th>
                        <th>Total Personal</th>
                    </tr>
                </thead>
                <tbody>
                    ${excelFilas}
                </tbody>
            </table>`;

      $("#divReporte").html(html + htmlPDF + htmlExcel);
    },
  });
}
