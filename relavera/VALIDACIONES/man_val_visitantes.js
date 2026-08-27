// Función de alerta estética UI con jQuery UI (Sustituye popups nativos del navegador de forma profesional)
function mostrarAlertaUI(titulo, mensaje, tipo, callback) {
  var $dlg = $("#alertCustomDialog");

  if (!$dlg.hasClass("ui-dialog-content")) {
    $dlg.dialog({
      autoOpen: false,
      modal: true,
      resizable: false,
      width: 440,
      appendTo: "body",
      dialogClass: "exa-ui-panel exa-ui-dialog exa-alert-modal-pro",
      show: { effect: "fade", duration: 150 },
      hide: { effect: "fade", duration: 120 },
    });
  }

  var titleText = titulo || "Notificación";
  var typeClass = "info";
  var iconClass = "glyphicon-info-sign";
  var bgIconColor = "#eff6ff";
  var iconColor = "#2563eb";

  if (tipo === "success") {
    typeClass = "success";
    iconClass = "glyphicon-ok-circle";
    bgIconColor = "#ecfdf5";
    iconColor = "#059669";
  } else if (tipo === "warning") {
    typeClass = "warning";
    iconClass = "glyphicon-exclamation-sign";
    bgIconColor = "#fffbeb";
    iconColor = "#d97706";
  } else if (tipo === "error" || tipo === "danger") {
    typeClass = "error";
    iconClass = "glyphicon-remove-circle";
    bgIconColor = "#fef2f2";
    iconColor = "#dc2626";
  }

  $dlg.dialog("option", "title", titleText);

  var htmlContent =
    '<div class="exa-alert-card exa-alert-' + typeClass + '" style="display: flex; align-items: flex-start; gap: 14px; padding: 14px 10px 10px 10px;">' +
    '  <div class="exa-alert-icon-box" style="flex-shrink: 0; width: 44px; height: 44px; border-radius: 50%; background: ' + bgIconColor + '; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.05);">' +
    '    <i class="glyphicon ' + iconClass + '" style="font-size: 24px; color: ' + iconColor + ';"></i>' +
    "  </div>" +
    '  <div class="exa-alert-text-box" style="flex-grow: 1;">' +
    '    <h4 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 700; color: #1e293b; line-height: 1.3;">' + titleText + '</h4>' +
    '    <div style="font-size: 12.5px; line-height: 1.5; color: #475569;">' + (mensaje || "") + '</div>' +
    "  </div>" +
    "</div>";

  $dlg.html(htmlContent);

  $dlg.dialog("option", "buttons", [
    {
      text: "Aceptar",
      class: "btn btn-sm btn-primary exa-alert-btn-confirm",
      click: function () {
        $(this).dialog("close");
        if (typeof callback === "function") callback();
      },
    },
  ]);

  $dlg.dialog("open");
}

// Fallback universal para SweetAlert (swal) apuntando a la UI de Model3
if (typeof window.swal !== "function") {
  window.swal = function (titleOrOptions, message, type) {
    if (typeof titleOrOptions === "object") {
      var cb = arguments[1];
      if (titleOrOptions.showCancelButton) {
        var $dlg = $("#alertCustomDialog");
        if (!$dlg.hasClass("ui-dialog-content")) {
          $dlg.dialog({
            autoOpen: false,
            modal: true,
            resizable: false,
            width: 440,
            appendTo: "body",
            dialogClass: "exa-ui-panel exa-ui-dialog exa-alert-modal-pro",
            show: { effect: "fade", duration: 150 },
            hide: { effect: "fade", duration: 120 },
          });
        }

        var titleText = titleOrOptions.title || "Confirmación";
        var msgText = titleOrOptions.text || "";
        $dlg.dialog("option", "title", titleText);

        var htmlContent =
          '<div class="exa-alert-card exa-alert-warning" style="display: flex; align-items: flex-start; gap: 14px; padding: 14px 10px 10px 10px;">' +
          '  <div class="exa-alert-icon-box" style="flex-shrink: 0; width: 44px; height: 44px; border-radius: 50%; background: #fffbeb; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.05);">' +
          '    <i class="glyphicon glyphicon-question-sign" style="font-size: 24px; color: #d97706;"></i>' +
          "  </div>" +
          '  <div class="exa-alert-text-box" style="flex-grow: 1;">' +
          '    <h4 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 700; color: #1e293b; line-height: 1.3;">' + titleText + '</h4>' +
          '    <div style="font-size: 12.5px; line-height: 1.5; color: #475569;">' + msgText + '</div>' +
          "  </div>" +
          "</div>";

        $dlg.html(htmlContent);

        $dlg.dialog("option", "buttons", [
          {
            text: "Aceptar",
            class: "btn btn-sm btn-primary exa-alert-btn-confirm",
            click: function () {
              $(this).dialog("close");
              if (typeof cb === "function") cb(true);
            },
          },
          {
            text: "Cancelar",
            class: "btn btn-sm btn-default exa-alert-btn-cancel",
            click: function () {
              $(this).dialog("close");
              if (typeof cb === "function") cb(false);
            },
          },
        ]);
        $dlg.dialog("open");
      } else {
        mostrarAlertaUI(
          titleOrOptions.title,
          titleOrOptions.text,
          titleOrOptions.type,
          cb,
        );
      }
    } else {
      mostrarAlertaUI(titleOrOptions, message, type);
    }
  };
}

$(document).ready(function () {
  $("form").attr("autocomplete", "off");
  $("input, select, textarea").attr("autocomplete", "off");
  $("#visitanteForm input").attr("autocomplete", "nope");

  if ($.fn.buttonset) {
    $(".radioset").buttonset();
  }

  if ($("#filtroVisitantesEventoForm .chosen-select").length && $.fn.chosen) {
    $("#filtroVisitantesEventoForm .chosen-select").chosen({
      width: "100%",
      search_contains: true,
      no_results_text: "No se encontraron resultados: ",
    });
  }

  if ($("#gridVisitantesEvento").length) {
    initGridVisitantesEvento();
  }

  $('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
    if ($.fn.buttonset) {
      $(".radioset").buttonset("refresh");
    }
    if (typeof exaUiAfterViewChange === "function") {
      exaUiAfterViewChange(".exa-ui-panel");
    }
  });

  var getModalWidth = function (targetWidth) {
    var winWidth = $(window).width();
    return winWidth < targetWidth ? Math.floor(winWidth * 0.96) : targetWidth;
  };

  var modalOptions = {
    autoOpen: false,
    modal: true,
    resizable: false,
    appendTo: ".exa-ui-panel",
    dialogClass: "exa-ui-panel exa-ui-dialog",
  };

  $("#visitanteDialog").dialog(
    $.extend({}, modalOptions, { width: getModalWidth(960) }),
  );
  $("#previewDocModal").dialog(
    $.extend({}, modalOptions, { width: getModalWidth(720) }),
  );
  $("#alertCustomDialog").dialog(
    $.extend({}, modalOptions, { width: getModalWidth(440) }),
  );

  $(document).on("change blur input", 'input[type="date"]', function () {
    var val = $(this).val();
    if (val) {
      var parts = val.split("-");
      if (parts[0] && parts[0].length > 4) {
        parts[0] = parts[0].substring(0, 4);
        $(this).val(parts.join("-"));
      }
    }
  });

  $(document).on("change", 'input[type="file"]', function () {
    var file = this.files[0];
    var fieldId = $(this).attr("id");
    var $box = $("#preview_" + fieldId);

    if (!file) {
      if ($box.length) $box.empty();
      return;
    }

    var ext = file.name ? file.name.split(".").pop().toLowerCase() : "";
    var mimeType = file.type || "";
    var isPdf = ext === "pdf" || mimeType === "application/pdf";
    var isImage =
      ["jpg", "jpeg", "png", "webp", "gif"].indexOf(ext) !== -1 ||
      mimeType.indexOf("image/") !== -1;

    if (!isPdf && !isImage) {
      mostrarAlertaUI(
        "Formato No Soportado",
        "El archivo seleccionado (<b>" +
          file.name +
          "</b>) no es un formato válido.<br><br>Por favor adjunte únicamente imágenes (JPG, PNG, WebP) o documentos PDF.",
        "warning",
      );
      $(this).val("");
      if ($box.length) $box.empty();
      return;
    }

    var $input = $(this);

    if (isImage) {
      comprimirImagenSiExcede(file, 5, function (processedFile) {
        if (processedFile.size > 5 * 1024 * 1024) {
          var sizeMB = (processedFile.size / (1024 * 1024)).toFixed(2);
          mostrarAlertaUI(
            "Imagen Excede Límite de 5 MB",
            "La imagen seleccionada (<b>" +
              processedFile.name +
              "</b> - " +
              sizeMB +
              " MB) supera el límite máximo de 5.00 MB aún después de su compresión.<br><br>Por favor elija una imagen de menor tamaño o resolución.",
            "warning",
          );
          $input.val("");
          if ($box.length) $box.empty();
          return;
        }

        if (processedFile !== file) {
          try {
            var dt = new DataTransfer();
            dt.items.add(processedFile);
            $input[0].files = dt.files;
          } catch (e) {}
        }

        generarBotonPreview($box, processedFile, false);
      });
    } else {
      if (file.size > 5 * 1024 * 1024) {
        var sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        mostrarAlertaUI(
          "PDF Excede Límite de 5 MB",
          "El archivo PDF seleccionado (<b>" +
            file.name +
            "</b> - " +
            sizeMB +
            " MB) supera el límite máximo de 5.00 MB.<br><br>Por favor elija un archivo PDF más liviano.",
          "warning",
        );
        $input.val("");
        if ($box.length) $box.empty();
        return;
      }

      generarBotonPreview($box, file, true);
    }
  });

  function generarBotonPreview($box, fileObj, isPdf) {
    if ($box && $box.length) {
      var fieldId = $box.attr("id")
        ? $box.attr("id").replace("preview_", "")
        : "";
      var objectUrl = URL.createObjectURL(fileObj);
      var icon = isPdf ? "glyphicon-file" : "glyphicon-picture";
      var tagLabel = isPdf ? "Ver PDF" : "Ver Foto";

      var html = '<div class="btn-group btn-group-xs" style="margin:0;">';
      html +=
        '<button type="button" class="btn btn-info btn-xs btn-preview-inline" onclick="abrirModalDocumento(\'' +
        objectUrl +
        "', '" +
        tagLabel +
        "', " +
        isPdf +
        ');"><i class="glyphicon ' +
        icon +
        '"></i> ' +
        tagLabel +
        "</button>";
      html +=
        '<button type="button" class="btn btn-danger btn-xs btn-preview-inline" onclick="limpiarArchivoAdjunto(\'' +
        fieldId +
        '\');" title="Quitar este archivo adjunto"><i class="glyphicon glyphicon-remove"></i></button>';
      html += "</div>";
      $box.html(html);
    }
  }

  initExaDropzones();

  var $ultimoInputArchivo = null;

  $(document).on(
    "click focus mouseenter",
    '.exa-file-dropzone-container, input[type="file"]',
    function () {
      var $input = $(this).is('input[type="file"]')
        ? $(this)
        : $(this).find('input[type="file"]');
      if ($input.length) {
        $ultimoInputArchivo = $input;
        $(".exa-file-dropzone-container").removeClass("active-target");
        $input
          .closest(".exa-file-dropzone-container")
          .addClass("active-target");
      }
    },
  );

  $(document).on("click", ".exa-file-dropzone-container", function (e) {
    if ($(e.target).closest("button").length || $(e.target).is("button")) {
      return;
    }

    var $input = $(this).find('input[type="file"]');
    if ($input.length) {
      $ultimoInputArchivo = $input;
      $(".exa-file-dropzone-container").removeClass("active-target");
      $(this).addClass("active-target");

      if (
        $(e.target).hasClass("btn-browse") ||
        $(e.target).closest(".btn-browse").length
      ) {
        $input[0].click();
      }
    }
  });

  $(document).on(
    "dragover dragenter",
    ".exa-file-dropzone-container",
    function (e) {
      e.preventDefault();
      e.stopPropagation();
      $(this).addClass("drag-over");
    },
  );

  $(document).on(
    "dragleave dragend drop",
    ".exa-file-dropzone-container",
    function (e) {
      e.preventDefault();
      e.stopPropagation();
      $(this).removeClass("drag-over");
    },
  );

  $(document).on("drop", ".exa-file-dropzone-container", function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $input = $(this).find('input[type="file"]');
    var files = e.originalEvent.dataTransfer
      ? e.originalEvent.dataTransfer.files
      : null;
    if (files && files.length > 0 && $input.length) {
      try {
        var dataTransfer = new DataTransfer();
        dataTransfer.items.add(files[0]);
        $input[0].files = dataTransfer.files;
        $input.trigger("change");
      } catch (err) {
        console.error("Error al procesar drop file:", err);
      }
    }
  });

  $(document).on("paste", function (e) {
    var activeTag = document.activeElement
      ? document.activeElement.tagName.toLowerCase()
      : "";
    var activeType = document.activeElement ? document.activeElement.type : "";
    if (
      (activeTag === "input" && activeType !== "file") ||
      activeTag === "textarea"
    ) {
      return;
    }

    var clipboardData = e.originalEvent ? e.originalEvent.clipboardData : null;
    if (!clipboardData) return;

    var items = clipboardData.items;
    var files = clipboardData.files;
    var fileToPaste = null;

    if (files && files.length > 0) {
      fileToPaste = files[0];
    } else if (items && items.length > 0) {
      for (var i = 0; i < items.length; i++) {
        if (items[i].kind === "file") {
          fileToPaste = items[i].getAsFile();
          break;
        }
      }
    }

    if (fileToPaste) {
      e.preventDefault();
      e.stopPropagation();

      var $targetInput = $ultimoInputArchivo;
      if (!$targetInput || !$targetInput.length) {
        var $modalAbierto = $(".ui-dialog:visible");
        if ($modalAbierto.length) {
          $targetInput = $modalAbierto.find('input[type="file"]:first');
        }
      }

      if ($targetInput && $targetInput.length) {
        if (!fileToPaste.name || fileToPaste.name === "image.png") {
          var timestamp = new Date().getTime();
          var ext = fileToPaste.type.split("/")[1] || "png";
          fileToPaste = new File(
            [fileToPaste],
            "captura_pegada_" + timestamp + "." + ext,
            { type: fileToPaste.type },
          );
        }

        try {
          var dataTransfer = new DataTransfer();
          dataTransfer.items.add(fileToPaste);
          $targetInput[0].files = dataTransfer.files;
          $targetInput.trigger("change");
        } catch (err) {
          console.error("Error al pegar archivo del portapapeles:", err);
        }
      }
    }
  });

  function initExaDropzones() {
    $('input[type="file"]').each(function () {
      var $input = $(this);
      if ($input.parent().hasClass("exa-file-dropzone-container")) return;

      var fieldId = $input.attr("id") || "";

      var $container = $(
        '<div class="exa-file-dropzone-container" tabindex="0" data-field-id="' +
          fieldId +
          '"></div>',
      );
      var $text = $(
        '<div class="dropzone-text"><i class="glyphicon glyphicon-cloud-upload" style="margin-right:3px; color:#2563eb;"></i><b class="btn-browse" title="Haga clic para buscar archivos locales">Examinar</b> o arrastrar / pegar <span class="paste-tag">(Ctrl+V)</span></div>',
      );

      $input.before($container);
      $container.append($text).append($input);

      var $box = $("#preview_" + fieldId);
      if ($box.length) {
        $container.append($box);
      }
    });
  }

  $(window).on("resize", function () {
    if ($("#visitanteDialog").is(":visible")) {
      $("#visitanteDialog").dialog("option", "width", getModalWidth(960));
    }
    if ($("#previewDocModal").is(":visible")) {
      $("#previewDocModal").dialog("option", "width", getModalWidth(720));
    }
    if ($("#alertCustomDialog").is(":visible")) {
      $("#alertCustomDialog").dialog("option", "width", getModalWidth(440));
    }
  });

  $(document).on("dialogopen", function (event) {
    var $dialog = $(event.target).closest(".ui-dialog");
    $dialog.find(".ui-dialog-titlebar-close").removeAttr("title").blur();
    $(
      '.ui-tooltip, .tooltip, [role="tooltip"], .bs-tooltip-top, .bs-tooltip-bottom',
    ).remove();

    if (event.target && event.target.id === "visitanteDialog") {
      $("#visitanteDialog").dialog("option", "width", getModalWidth(960));
    }

    var $selects = $(event.target).find(".chosen-select");
    if ($selects.length && $.fn.chosen) {
      $selects
        .chosen({
          width: "100%",
          search_contains: true,
          no_results_text: "No se encontraron resultados: ",
        })
        .trigger("chosen:updated");
    }
  });

  $(document).on("dialogclose", function (event) {
    $(
      '.ui-tooltip, .tooltip, [role="tooltip"], .bs-tooltip-top, .bs-tooltip-bottom',
    ).remove();
    if (event.target && event.target.id === "previewDocModal") {
      $("#previewDocImg").attr("src", "").hide();
      $("#previewDocPdf").attr("src", "about:blank").hide();
    }
  });

  setTimeout(function () {
    if ($.fn.buttonset) {
      $(".radioset").buttonset("refresh");
    }
    if (typeof exaUiAfterViewChange === "function") {
      exaUiAfterViewChange(".exa-ui-panel");
    }
    verificarVigenciaBotonRegistrar();
  }, 200);
});

/* ==========================================================================
   TAB EVENTOS Y VISITANTES
   ========================================================================== */

function obtenerFechaHoyYMD() {
  var d = new Date();
  var month = "" + (d.getMonth() + 1);
  var day = "" + d.getDate();
  var year = d.getFullYear();
  if (month.length < 2) month = "0" + month;
  if (day.length < 2) day = "0" + day;
  return [year, month, day].join("-");
}

function verificarVigenciaBotonRegistrar() {
  var $btn = $("#btnAbrirModalRegistrar");
  if (!$btn.length) return true;

  var $sel = $("#selMan_Eve");
  var fefStr = "";

  if ($sel.length && $sel.val()) {
    var $opt = $sel.find("option:selected");
    fefStr = $opt.attr("data-fef") || "";
  } else {
    fefStr = $("#hdn_man_eve_vigente_fef").val() || "";
  }

  if (!fefStr) {
    $btn.show();
    return true;
  }

  var hoyStr = obtenerFechaHoyYMD();
  // Permitido si hoy <= fefStr (hasta el mismo día de la fecha fin).
  // Si hoyStr > fefStr (pasó al día siguiente o después), ya expiró y se oculta el botón.
  if (hoyStr > fefStr) {
    $btn.hide();
    return false;
  } else {
    $btn.show();
    return true;
  }
}

function initGridVisitantesEvento() {
  var $grid = $("#gridVisitantesEvento");
  if (!$grid.length) return;

  var initialManEve = $("#selMan_Eve").val() || $("#hdn_man_eve_vigente").val() || "";

  $grid.jqGrid({
    url: "?listVisitantesEventoGridAjax=true",
    postData: { Man_Eve: initialManEve },
    datatype: "json",
    colNames: [
      "Código",
      "Cédula",
      "Nombres y Apellidos",
      "Teléfono",
      "Correo Electrónico",
      "Tipo Sangre",
      "Observaciones",
      "Estado",
      "Acciones"
    ],
    colModel: [
      { name: "MVis_Cod", index: "MVis_Cod", width: 65, align: "center", key: true },
      { name: "Prs_Ced", index: "Prs_Ced", width: 100, align: "center" },
      { name: "nombre", index: "nombre", width: 220, align: "left" },
      { name: "Prs_Tel", index: "Prs_Tel", width: 100, align: "center" },
      { name: "Prs_Cor", index: "Prs_Cor", width: 160, align: "left" },
      { name: "MVis_Tsa", index: "MVis_Tsa", width: 80, align: "center" },
      { name: "MVis_Obs", index: "MVis_Obs", width: 180, align: "left" },
      {
        name: "MVis_Est",
        index: "MVis_Est",
        width: 85,
        align: "center",
        formatter: function (cellvalue) {
          if (cellvalue === "A" || cellvalue === "ACTIVO" || !cellvalue) return '<span class="label label-success">ACTIVO</span>';
          return '<span class="label label-danger">INACTIVO</span>';
        }
      },
      {
        name: "acciones",
        index: "acciones",
        width: 175,
        align: "center",
        sortable: false,
        formatter: function (cellvalue, options, rowObject) {
          var manEve = (rowObject && rowObject.Man_Eve) ? rowObject.Man_Eve : ($("#selMan_Eve").val() || "");
          var yaEnviado = (rowObject && (rowObject.MVis_Cer_Env === 'S' || rowObject.MVis_Cer_Env === 's'));
          var sendBtnClass = yaEnviado ? 'btn-warning' : 'btn-success';
          var sendBtnIcon = yaEnviado ? 'glyphicon-repeat' : 'glyphicon-send';
          var sendBtnText = yaEnviado ? 'Reenviar' : 'Enviar';
          var sendBtnTitle = yaEnviado ? 'Reenviar Certificado PDF' : 'Enviar Certificado PDF';

          var editBtn = '<button type="button" class="btn btn-primary btn-xs" onclick="abrirModalVisitante(' + rowObject.MVis_Cod + ')" title="Editar Visitante"><i class="glyphicon glyphicon-pencil"></i></button> ';
          var certBtn = '<button type="button" class="btn btn-default btn-xs" onclick="verCertificadoPdf(\'' + (rowObject.Prs_Nom || '') + '\', \'' + (rowObject.Prs_Ape || '') + '\', \'' + (rowObject.Prs_Ced || '') + '\', \'' + manEve + '\')" title="Ver Certificado PDF" style="color: #b45309; border-color: #f59e0b;"><i class="glyphicon glyphicon-certificate"></i></button> ';
          var sendBtn = '<button type="button" class="btn ' + sendBtnClass + ' btn-xs" data-sent="' + (yaEnviado ? '1' : '0') + '" onclick="enviarCertificadoVisitanteEvento(' + rowObject.MVis_Cod + ', this)" title="' + sendBtnTitle + '"><i class="glyphicon ' + sendBtnIcon + '"></i> ' + sendBtnText + '</button> ';
          var deleteBtn = '<button type="button" class="btn btn-danger btn-xs" onclick="anularVisitanteGrid(' + rowObject.MVis_Cod + ')" title="Anular Visitante"><i class="glyphicon glyphicon-trash"></i></button>';
          return editBtn + certBtn + sendBtn + deleteBtn;
        }
      }
    ],
    rownumbers: true,
    rownumWidth: 40,
    cmTemplate: { sortable: false },
    rowNum: 50,
    rowList: [50, 100, 200, 500, 999999],
    caption: "Historial de Registro",
    hidegrid: false,
    pager: "#gridVisitantesEventoPager",
    sortname: "nombre",
    sortorder: "asc",
    viewrecords: true,
    autowidth: true,
    height: 350,
    emptyrecords: "No se encontraron visitantes para este evento",
    loadComplete: function () {
      $grid.jqGrid("setLabel", "rn", "#");
      $('.ui-pg-selbox option[value="999999"]').text("Todos");
      verificarVigenciaBotonRegistrar();
    }
  });

  $grid.jqGrid("navGrid", "#gridVisitantesEventoPager", {
    edit: false, add: false, del: false, search: false, refresh: true
  }).jqGrid("navButtonAdd", "#gridVisitantesEventoPager", {
    caption: "Exportar Excel",
    buttonicon: "glyphicon glyphicon-download-alt",
    title: "Exportar a Excel",
    onClickButton: function () {
      if ($.fn.jqGrid && typeof $grid.jqGrid("exportGridExcel") === "function") {
        $grid.jqGrid("exportGridExcel", {
          nombre: "Visitantes_Evento",
          hoja: "Visitantes del Evento",
          footer: true,
          removeCols: ["acciones"]
        });
      }
    }
  });
}

function actualizarGridVisitantesEvento() {
  verificarVigenciaBotonRegistrar();

  var formData = $("#filtroVisitantesEventoForm").serializeArray();
  var postData = {};
  $.each(formData, function (i, field) {
    postData[field.name] = field.value;
  });

  if (!postData.Man_Eve) {
    postData.Man_Eve = $("#selMan_Eve").val() || $("#hdn_man_eve_vigente").val() || "";
  }

  $("#gridVisitantesEvento")
    .jqGrid("setGridParam", {
      postData: postData,
      page: 1
    })
    .trigger("reloadGrid");
}

function abrirModalVisitante(MVis_Cod) {
  if (!MVis_Cod) {
    if (!verificarVigenciaBotonRegistrar()) {
      mostrarAlertaUI(
        "Evento Concluido",
        "La fecha fin de este evento ya ha concluido. No se pueden registrar nuevos visitantes.",
        "warning"
      );
      return;
    }
  }

  limpiarFormularioVisitante();
  var winWidth = $(window).width();
  var modalW = winWidth < 960 ? Math.floor(winWidth * 0.96) : 960;
  $("#visitanteDialog").dialog("option", "width", modalW);

  var selEve = $("#selMan_Eve").val() || $("#hdn_man_eve_vigente").val() || "";
  $("#Man_Eve").val(selEve);

  if (MVis_Cod) {
    $.get(
      "",
      { getVisitanteByIdAjax: true, MVis_Cod: MVis_Cod },
      function (r) {
        if (r.success && r.visitante) {
          poblarFormularioVisitante(r.visitante, true);
          $("#visitanteDialog").dialog("option", "title", "Editar Visitante - " + (r.visitante.nombre || ""));
          $("#visitanteDialog").dialog("open");
        } else {
          mostrarAlertaUI("Error", "No se pudo cargar la información del visitante.", "error");
        }
      },
      "json"
    ).fail(function () {
      mostrarAlertaUI("Error", "Error de conexión al cargar datos del visitante.", "error");
    });
  } else {
    $("#visitanteDialog").dialog("option", "title", "Registrar Visitante en Evento");
    $("#visitanteDialog").dialog("open");
  }
}

function limpiarFormularioVisitante() {
  $("#visitanteForm")[0].reset();
  $("#Vis_Cod").val("");
  $("#MVis_Cod").val("");
  $("#Prs_Cod").val("");
  $("#Vis_Edad").val("");
  $("#chk_es_visitante").val("1");
  $("#visitanteForm .preview-doc-box").empty();
  $("#visitanteForm select.chosen-select").val("").trigger("chosen:updated");
  setModoEdicionCedula(false);
}

function setModoEdicionCedula(isEdit) {
  var $ced = $("#Vis_Ced");
  if (isEdit) {
    $ced.prop("readonly", true).css("background-color", "#e9ecef").attr("title", "No se permite modificar la Cédula de un registro existente.");
  } else {
    $ced.prop("readonly", false).css("background-color", "#ffffff").attr("title", "");
  }
}

function buscarPersonaCedula(cedula) {
  if (!cedula || cedula.length < 10) return;
  $.get(
    "",
    { buscarPersonaCedulaAjax: true, Prs_Ced: cedula },
    function (r) {
      if (r.success && r.existe) {
        if (r.esVisitante && r.visitante) {
          poblarFormularioVisitante(r.visitante, false);
        } else if (r.persona) {
          $("#Prs_Nom").val(r.persona.Prs_Nom || "");
          $("#Prs_Ape").val(r.persona.Prs_Ape || "");
          $("#Vis_Tel").val(r.persona.Prs_Tel || "");
          $("#Vis_Cor").val(r.persona.Prs_Cor || "");
          $("#Vis_Dir").val(r.persona.Prs_Dir || "");
          if (r.persona.Prs_Fec) {
            $("#Prs_Fec").val(r.persona.Prs_Fec);
            calcularEdad(r.persona.Prs_Fec);
          }
          $("#Prs_Cod").val(r.persona.Prs_Cod || "");
        }
      }
    },
    "json"
  );
}

function poblarFormularioVisitante(row, esEdicion) {
  if (!row) return;

  if (esEdicion) {
    $("#MVis_Cod").val(row.MVis_Cod || "");
    $("#Vis_Cod").val(row.MVis_Cod || "");
    if (row.Man_Eve) $("#Man_Eve").val(row.Man_Eve);
    setModoEdicionCedula(true);
  } else {
    // Al auto-completar datos de una persona existente para un NUEVO registro:
    // MVis_Cod debe quedar VACÍO para que PHP realice INSERT (nuevo código de visita)
    $("#MVis_Cod").val("");
    $("#Vis_Cod").val("");
    // Man_Eve se mantiene en el evento actualmente seleccionado o el evento en vigencia
    var eveActual = $("#selMan_Eve").val() || $("#hdn_man_eve_vigente").val() || "";
    $("#Man_Eve").val(eveActual);
    setModoEdicionCedula(false);
  }

  $("#Prs_Cod").val(row.Prs_Cod || "");
  $("#Vis_Ced").val(row.Prs_Ced || "");
  $("#Prs_Nom").val(row.Prs_Nom || "");
  $("#Prs_Ape").val(row.Prs_Ape || "");
  if (row.Prs_Fec) {
    $("#Prs_Fec").val(row.Prs_Fec);
    calcularEdad(row.Prs_Fec);
  }
  $("#Vis_Nac").val(row.MVis_Nac || "Ecuatoriana");
  $("#Vis_Eci").val(row.MVis_Eci || "Soltero/a");
  $("#Vis_Tsa").val(row.MVis_Tsa || "");
  $("#Vis_Tel").val(row.Prs_Tel || row.Prs_Tel_Base || "");
  $("#Vis_Cor").val(row.Prs_Cor || "");
  $("#Vis_Dir").val(row.Prs_Dir || row.Prs_Dir_Base || "");
  $("#Vis_Nem").val(row.MVis_Nem || "");
  $("#Vis_Tem").val(row.MVis_Tem || "");
  $("#MVis_Obs").val(row.MVis_Obs || "");

  $("#visitanteForm select.chosen-select").trigger("chosen:updated");

  renderDocPreview("preview_Vis_Doc_Ced", row.MVis_Doc_Ced, "Cédula Anverso");
  renderDocPreview("preview_Vis_Doc_Ced_Rev", row.MVis_Doc_Ced_Rev, "Cédula Reverso");
  renderDocPreview("preview_Vis_Doc_Vot", row.MVis_Doc_Vot, "Certif. Votación");
  renderDocPreview("preview_Vis_Doc_Fot", row.MVis_Doc_Fot, "Foto Carnet");
}

function guardarVisitante() {
  var formEl = $("#visitanteForm")[0];
  var ced = $("#Vis_Ced").val().trim();
  var nom = $("#Prs_Nom").val().trim();
  var ape = $("#Prs_Ape").val().trim();
  var tel = $("#Vis_Tel").val().trim();

  if (!ced || !nom || !ape || !tel) {
    mostrarAlertaUI("Atención", "Complete la Cédula/Identificación, Nombres, Apellidos y Teléfono Celular.", "warning");
    return;
  }

  swal(
    {
      title: "Confirmar Registro",
      text: "¿Desea guardar los datos del visitante?",
      type: "info",
      showCancelButton: true,
      confirmButtonText: "Guardar",
      cancelButtonText: "Cancelar"
    },
    function (isConfirm) {
      if (!isConfirm) return;

      var formData = new FormData(formEl);
      formData.append("saveVisitanteAjax", "true");
      formData.append("es_visitante", "1");

      var $btnSave = $("#btnGuardarVisitante");
      $btnSave.prop("disabled", true).html('<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Guardando...');
      bloquearModalYMostrarLoader("#visitanteDialog", "Guardando Visitante...");

      $.ajax({
        url: "",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (r) {
          if (r.success) {
            mostrarAlertaUI("Éxito", r.message || "Visitante guardado correctamente.", "success", function () {
              $("#visitanteDialog").dialog("close");
              actualizarGridVisitantesEvento();
            });
          } else {
            mostrarAlertaUI("Atención", r.message || "No se pudo guardar la información.", "warning");
          }
        },
        error: function () {
          mostrarAlertaUI("Error", "Ocurrió un error en el servidor al enviar los datos.", "error");
        },
        complete: function () {
          desbloquearModalYOcultarLoader("#visitanteDialog");
          $btnSave.prop("disabled", false).html('<i class="glyphicon glyphicon-floppy-disk"></i> Guardar Visitante');
        }
      });
    }
  );
}

function verCertificadoPdf(prsNom, prsApe, prsCed, manEve) {
  var eve = manEve || $("#selMan_Eve").val() || "";
  var url = "?verCertificadoPdfAjax=true&Prs_Nom=" + encodeURIComponent(prsNom) + "&Prs_Ape=" + encodeURIComponent(prsApe) + "&Prs_Ced=" + encodeURIComponent(prsCed) + "&Man_Eve=" + encodeURIComponent(eve) + "&es_visitante=1";
  window.open(url, "_blank");
}

function enviarCertificadoVisitanteEvento(MVis_Cod, btnEl) {
  if (!MVis_Cod) return;
  var $btn = $(btnEl);
  var eraEnviado = ($btn.attr("data-sent") === "1");
  var labelOriginal = eraEnviado ? 'Reenviar' : 'Enviar';
  var iconOriginal = eraEnviado ? 'glyphicon-repeat' : 'glyphicon-send';

  $btn.prop("disabled", true).html('<i class="glyphicon glyphicon-refresh spin"></i>');

  swal({
    title: (eraEnviado ? "Reenviar Certificado" : "Enviar Certificado"),
    text: "¿Desea enviar el certificado PDF por WhatsApp y Correo?",
    type: "info",
    showCancelButton: true,
    confirmButtonText: (eraEnviado ? "Reenviar" : "Enviar"),
    cancelButtonText: "Cancelar"
  }, function (isConfirm) {
    if (!isConfirm) {
      $btn.prop("disabled", false).html('<i class="glyphicon ' + iconOriginal + '"></i> ' + labelOriginal);
      return;
    }

    var manEve = $("#selMan_Eve").val() || "";
    var payload = { enviarCertificadoVisitanteEventoAjax: true, MVis_Cod: MVis_Cod, Man_Eve: manEve };

    console.log("[EnviarCertificado] POST payload:", payload);

    $.ajax({
      url: "",
      type: "POST",
      data: payload,
      dataType: "json",
      success: function (r) {
        console.log("[EnviarCertificado] Respuesta JSON:", r);
        if (r && r.success) {
          mostrarAlertaUI("Éxito", r.message || "Certificado PDF notificado correctamente.", "success");
          $btn.attr("data-sent", "1")
              .removeClass("btn-success")
              .addClass("btn-warning")
              .attr("title", "Reenviar Certificado PDF")
              .html('<i class="glyphicon glyphicon-repeat"></i> Reenviar');
        } else {
          console.warn("[EnviarCertificado] success=false. message:", r && r.message);
          console.warn("[EnviarCertificado] debug_info:", r && r.debug_info);
          mostrarAlertaUI("Error", (r && r.message) || "No se pudo enviar el certificado.", "error");
        }
      },
      error: function (xhr, status, err) {
        console.error("[EnviarCertificado] Error AJAX. Status:", status, "| Error:", err);
        console.error("[EnviarCertificado] responseText:", xhr.responseText);
        mostrarAlertaUI("Error", "Error de comunicación con el servidor. Ver consola.", "error");
      },
      complete: function () {
        var isNowSent = ($btn.attr("data-sent") === "1");
        $btn.prop("disabled", false);
        if (isNowSent) {
          $btn.removeClass("btn-success").addClass("btn-warning").attr("title", "Reenviar Certificado PDF").html('<i class="glyphicon glyphicon-repeat"></i> Reenviar');
        } else {
          $btn.removeClass("btn-warning").addClass("btn-success").attr("title", "Enviar Certificado PDF").html('<i class="glyphicon glyphicon-send"></i> Enviar');
        }
      }
    });
  });
}


function anularVisitanteGrid(MVis_Cod) {
  if (!MVis_Cod) return;
  swal({
    title: "¿Está seguro?",
    text: "¿Desea anular este visitante?",
    type: "warning",
    showCancelButton: true
  }, function (isConfirm) {
    if (isConfirm) {
      $.post("", { anularVisitanteAjax: true, MVis_Cod: MVis_Cod }, function (r) {
        if (r.success) {
          mostrarAlertaUI("Éxito", "Visitante anulado correctamente.", "success", function () {
            actualizarGridVisitantesEvento();
          });
        } else {
          mostrarAlertaUI("Error", r.message || "No se pudo anular el registro.", "error");
        }
      }, "json");
    }
  });
}

/* ==========================================================================
   HELPER FUNCTIONS: LOADERS, EDAD Y VISOR DE DOCUMENTOS
   ========================================================================== */

function bloquearModalYMostrarLoader(modalId, textoLoader) {
  var $modal = $(modalId);
  if (!$modal.length) return;

  $modal.find("button").prop("disabled", true);
  var $widget = $modal.dialog("widget");
  if ($widget && $widget.length) {
    $widget.find(".ui-dialog-titlebar-close").hide();
    $widget.find(".ui-dialog-buttonpane button").prop("disabled", true);
  }

  $modal.find(".exa-modal-loader-overlay").remove();

  var txt = textoLoader || "Guardando datos...";
  var html = '<div class="exa-modal-loader-overlay">';
  html += '  <div class="spinner-box">';
  html += '    <i class="glyphicon glyphicon-refresh spinner-icon"></i>';
  html += '    <p class="spinner-text">' + txt + "</p>";
  html += "  </div>";
  html += "</div>";

  $modal.css("position", "relative").append(html);
}

function desbloquearModalYOcultarLoader(modalId) {
  var $modal = $(modalId);
  if (!$modal.length) return;

  $modal.find(".exa-modal-loader-overlay").remove();
  $modal.find("input, select, textarea, button").prop("disabled", false);
  var $widget = $modal.dialog("widget");
  if ($widget && $widget.length) {
    $widget.find(".ui-dialog-titlebar-close").show();
    $widget.find(".ui-dialog-buttonpane button").prop("disabled", false);
  }
}

function calcularEdad(fechaNacStr) {
  if (!fechaNacStr) {
    $("#Vis_Edad").val("");
    return;
  }
  var hoy = new Date();
  var nac = new Date(fechaNacStr);
  var edad = hoy.getFullYear() - nac.getFullYear();
  var m = hoy.getMonth() - nac.getMonth();
  if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) {
    edad--;
  }
  $("#Vis_Edad").val((edad >= 0 ? edad : 0) + " años");
}

function comprimirImagenSiExcede(file, maxSizeMB, callback) {
  var maxBytes = maxSizeMB * 1024 * 1024;
  var mimeType = file.type || "";

  if (!mimeType.match(/image.*/) || file.size <= maxBytes) {
    callback(file);
    return;
  }

  var reader = new FileReader();
  reader.onload = function (e) {
    var img = new Image();
    img.onload = function () {
      var canvas = document.createElement("canvas");
      var ctx = canvas.getContext("2d");

      var width = img.width;
      var height = img.height;
      var maxDimension = 1920;

      if (width > maxDimension || height > maxDimension) {
        if (width > height) {
          height = Math.round((height * maxDimension) / width);
          width = maxDimension;
        } else {
          width = Math.round((width * maxDimension) / height);
          height = maxDimension;
        }
      }

      canvas.width = width;
      canvas.height = height;
      ctx.drawImage(img, 0, 0, width, height);

      canvas.toBlob(
        function (blob) {
          if (!blob) {
            callback(file);
            return;
          }
          var compressedFile = new File(
            [blob],
            file.name || "imagen_adjunta.jpg",
            {
              type: "image/jpeg",
              lastModified: Date.now(),
            },
          );
          callback(compressedFile);
        },
        "image/jpeg",
        0.8,
      );
    };
    img.onerror = function () {
      callback(file);
    };
    img.src = e.target.result;
  };
  reader.onerror = function () {
    callback(file);
  };
  reader.readAsDataURL(file);
}

function limpiarArchivoAdjunto(fieldId) {
  if (!fieldId) return;
  var $input = $("#" + fieldId);
  if ($input.length) {
    $input.val("");
  }
  var $box = $("#preview_" + fieldId);
  if ($box.length) {
    $box.empty();
  }
  var $prevHidden = $("#" + fieldId + "_Prev");
  if ($prevHidden.length) {
    $prevHidden.val("");
  }
}

function normalizarRutaArchivo(filePath) {
  if (!filePath || filePath === "null" || filePath === "undefined" || filePath === "") return "";
  var path = filePath.trim();
  path = path.replace(/^(\.\.\/)+/, "../");
  if (!path.startsWith("../") && !path.startsWith("/") && !path.startsWith("http")) {
    path = "../" + path;
  }
  return path;
}

function renderDocPreview(containerId, filePath, labelTitulo) {
  var $box = $("#" + containerId);
  if (!$box.length) return;
  $box.empty();

  if (
    filePath &&
    filePath !== "null" &&
    filePath !== "" &&
    filePath !== "undefined"
  ) {
    var fieldId = containerId.replace("preview_", "");
    var cleanPath = normalizarRutaArchivo(filePath);
    var ext = cleanPath
      .split("?")[0]
      .split("#")[0]
      .split(".")
      .pop()
      .toLowerCase();
    var isPdf = ext === "pdf";
    var icon = isPdf ? "glyphicon-file" : "glyphicon-picture";
    var tagLabel = isPdf ? "Ver PDF" : "Ver Foto";
    var displayTitle = labelTitulo ? labelTitulo : tagLabel;

    var html = '<div class="btn-group btn-group-xs" style="margin-top: 4px;">';
    html +=
      '  <button type="button" class="btn btn-info btn-xs btn-preview-inline" onclick="abrirModalDocumento(\'' +
      cleanPath +
      "', '" +
      displayTitle +
      "', " +
      isPdf +
      ');" title="Ver ' + displayTitle + '"><i class="glyphicon ' +
      icon +
      '"></i> ' +
      displayTitle +
      '</button>';
    html +=
      '  <button type="button" class="btn btn-danger btn-xs btn-preview-inline" onclick="limpiarArchivoAdjunto(\'' +
      fieldId +
      '\');" title="Quitar este archivo adjunto"><i class="glyphicon glyphicon-remove"></i></button>';
    html += '</div>';
    $box.html(html);
  }
}

function abrirModalDocumento(filePath, titulo, isPdf) {
  if (
    !filePath ||
    filePath === "undefined" ||
    filePath === "null" ||
    filePath.trim() === ""
  ) {
    mostrarAlertaUI(
      "Sin Documento",
      "No se ha proporcionado un archivo válido para visualizar.",
      "warning",
    );
    return;
  }

  var cleanPath = normalizarRutaArchivo(filePath);

  var isPdfDoc = false;
  if (typeof isPdf === "boolean") {
    isPdfDoc = isPdf;
  } else {
    var ext = cleanPath.split("?")[0].split("#")[0].split(".").pop().toLowerCase();
    isPdfDoc = ext === "pdf";
  }

  $("#previewDocModal").dialog(
    "option",
    "title",
    titulo || "Previsualización de Documento",
  );

  if (isPdfDoc) {
    $("#previewDocImg").hide().attr("src", "");
    $("#previewDocPdf").attr("src", cleanPath).show();
  } else {
    $("#previewDocPdf").hide().attr("src", "about:blank");
    $("#previewDocImg").attr("src", cleanPath).show();
  }
  $("#previewDocModal").dialog("open");
}

function setfocus(elem) {
  if (elem) elem.focus();
}
