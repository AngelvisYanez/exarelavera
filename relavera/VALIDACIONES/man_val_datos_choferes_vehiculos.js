
// Función de alerta estética UI con jQuery UI (Sustituye popups nativos del navegador de forma segura)
function mostrarAlertaUI(titulo, mensaje, tipo, callback) {
  var $dlg = $("#alertCustomDialog");

  // Inicializar diálogo si aún no ha sido creado en el DOM
  if (!$dlg.hasClass("ui-dialog-content")) {
    $dlg.dialog({
      autoOpen: false,
      modal: true,
      resizable: false,
      width: 440,
      appendTo: ".exa-ui-panel",
      dialogClass: "exa-ui-panel exa-ui-dialog",
    });
  }

  $dlg.dialog("option", "title", titulo || "Notificación");
  $("#alertCustomMessage").html(mensaje || "");

  var $icon = $("#alertCustomIcon");
  $icon.removeClass(
    "glyphicon-info-sign glyphicon-ok-sign glyphicon-warning-sign glyphicon-remove-sign text-primary text-success text-warning text-danger",
  );

  if (tipo === "success") {
    $icon.addClass("glyphicon-ok-sign text-success");
  } else if (tipo === "warning") {
    $icon.addClass("glyphicon-warning-sign text-warning");
  } else if (tipo === "error" || tipo === "danger") {
    $icon.addClass("glyphicon-remove-sign text-danger");
  } else {
    $icon.addClass("glyphicon-info-sign text-primary");
  }

  $dlg.dialog("option", "buttons", {
    Aceptar: function () {
      $(this).dialog("close");
      if (typeof callback === "function") callback();
    },
  });
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
            appendTo: ".exa-ui-panel",
            dialogClass: "exa-ui-panel exa-ui-dialog",
          });
        }
        $dlg.dialog("option", "title", titleOrOptions.title || "Confirmación");
        $("#alertCustomMessage").html(titleOrOptions.text || "");
        $("#alertCustomIcon").attr(
          "class",
          "glyphicon glyphicon-question-sign text-warning",
        );
        $dlg.dialog("option", "buttons", {
          Aceptar: function () {
            $(this).dialog("close");
            if (typeof cb === "function") cb(true);
          },
          Cancelar: function () {
            $(this).dialog("close");
            if (typeof cb === "function") cb(false);
          },
        });
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
  // Inicialización de Segmented Control (Radio Buttonsets)
  if ($.fn.buttonset) {
    $(".radioset").buttonset();
  }

  // Inicialización de Grids si existen en el DOM
  if ($("#gridEmpresasTransporte").length) initGridEmpresasTransporte();
  if ($("#gridChoferes").length) initGridChoferes();
  if ($("#gridVehiculos").length) initGridVehiculos();

  // Listener para pestañas
  $('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
    if ($.fn.buttonset) {
      $(".radioset").buttonset("refresh");
    }
    if (typeof exaUiAfterViewChange === "function") {
      exaUiAfterViewChange(".exa-ui-panel");
    }
  });

  // Configuración de Modales con Clases de Model3 y responsividad
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

  $("#empresaTransporteDialog").dialog(
    $.extend({}, modalOptions, { width: getModalWidth(500) }),
  );
  $("#choferDialog").dialog(
    $.extend({}, modalOptions, { width: getModalWidth(960) }),
  );
  $("#vehiculoDialog").dialog(
    $.extend({}, modalOptions, { width: getModalWidth(1250) }),
  );
  $("#qrVehiculoDialog").dialog(
    $.extend({}, modalOptions, { width: getModalWidth(350) }),
  );
  $("#previewDocModal").dialog(
    $.extend({}, modalOptions, { width: getModalWidth(720) }),
  );
  $("#alertCustomDialog").dialog(
    $.extend({}, modalOptions, { width: getModalWidth(440) }),
  );

  // Listener estricto para formatear el año en campos de fecha a máximo 4 dígitos (AAAA)
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

  // Listener estricto para el Número de Licencia (#Cho_Nli): Solo permite Letras y Números (No caracteres especiales)
  $(document).on("input keyup blur", "#Cho_Nli", function () {
    this.value = this.value.replace(/[^a-zA-Z0-9]/g, "");
  });

  // Listener de selección de archivo con pre-compresión automática de imágenes y validación estricta
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
      // PDF
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

  // Inicializar Dropzones unificados de archivos
  initExaDropzones();

  // Variable global para rastrear el último campo de archivo interactuado
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

  // Clic en el contenedor unificado de Dropzone
  $(document).on("click", ".exa-file-dropzone-container", function (e) {
    // Si hace clic en el botón de ver preview, no hacer nada
    if ($(e.target).closest("button").length || $(e.target).is("button")) {
      return;
    }

    var $input = $(this).find('input[type="file"]');
    if ($input.length) {
      $ultimoInputArchivo = $input;
      $(".exa-file-dropzone-container").removeClass("active-target");
      $(this).addClass("active-target");

      // Abrir el selector explorador NATIVO ÚNICAMENTE si hizo clic en el botón "Examinar"
      if (
        $(e.target).hasClass("btn-browse") ||
        $(e.target).closest(".btn-browse").length
      ) {
        $input[0].click();
      }
    }
  });

  // 1. FUNCIONALIDAD DE ARRASTRAR Y SOLTAR (DRAG AND DROP)
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

  // 2. FUNCIONALIDAD DE COPIAR Y PEGAR (CTRL + V) SIN ABRIR SELECTOR NATIVO NI POPUPS DE ÉXITO
  $(document).on("paste", function (e) {
    var activeTag = document.activeElement
      ? document.activeElement.tagName.toLowerCase()
      : "";
    var activeType = document.activeElement ? document.activeElement.type : "";
    if (
      (activeTag === "input" && activeType !== "file") ||
      activeTag === "textarea"
    ) {
      return; // Permitir pegado normal de texto en campos de texto
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
      e.preventDefault(); // ¡Evita abrir el diálogo selector de archivos nativo!
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

  // Ajuste dinámico de modales en cambio de tamaño de ventana
  $(window).on("resize", function () {
    if ($("#choferDialog").is(":visible")) {
      $("#choferDialog").dialog("option", "width", getModalWidth(960));
    }
    if ($("#vehiculoDialog").is(":visible")) {
      $("#vehiculoDialog").dialog("option", "width", getModalWidth(1250));
    }
    if ($("#previewDocModal").is(":visible")) {
      $("#previewDocModal").dialog("option", "width", getModalWidth(720));
    }
    if ($("#alertCustomDialog").is(":visible")) {
      $("#alertCustomDialog").dialog("option", "width", getModalWidth(440));
    }
  });

  // Limpieza estricta de tooltips colgados del navegador e inicialización de Chosen en modales
  $(document).on("dialogopen", function (event) {
    var $dialog = $(event.target).closest(".ui-dialog");
    $dialog.find(".ui-dialog-titlebar-close").removeAttr("title").blur();
    $(
      '.ui-tooltip, .tooltip, [role="tooltip"], .bs-tooltip-top, .bs-tooltip-bottom',
    ).remove();

    // Asegurar recálculo de ancho óptimo al abrir el modal de choferes
    if (event.target && event.target.id === "choferDialog") {
      $("#choferDialog").dialog("option", "width", getModalWidth(960));
    }

    // Inicializar y refrescar Chosen (Buscador interno en selects) al abrir modal
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

  // Refrescar layouts de Model3 tras carga inicial
  setTimeout(function () {
    if ($.fn.buttonset) {
      $(".radioset").buttonset("refresh");
    }
    if (typeof exaUiAfterViewChange === "function") {
      exaUiAfterViewChange(".exa-ui-panel");
    }
  }, 200);
});

/* ==========================================================================
   HELPER FUNCTIONS: LOADERS, EDAD, LICENCIA Y VISOR DE DOCUMENTOS
   ========================================================================== */

function bloquearModalYMostrarLoader(modalId, textoLoader) {
  var $modal = $(modalId);
  if (!$modal.length) return;

  // Deshabilitar únicamente botones para prevenir múltiples envíos
  $modal.find("button").prop("disabled", true);
  var $widget = $modal.dialog("widget");
  if ($widget && $widget.length) {
    $widget.find(".ui-dialog-titlebar-close").hide();
    $widget.find(".ui-dialog-buttonpane button").prop("disabled", true);
  }

  // Limpiar loader previo si existía
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
    $("#Cho_Edad").val("");
    return;
  }
  var hoy = new Date();
  var nac = new Date(fechaNacStr);
  var edad = hoy.getFullYear() - nac.getFullYear();
  var m = hoy.getMonth() - nac.getMonth();
  if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) {
    edad--;
  }
  $("#Cho_Edad").val((edad >= 0 ? edad : 0) + " años");
}

function evaluarEstadoLicencia() {
  var fechaVencStr = $("#Cho_Cli").val();
  var $badge = $("#badgeLicencia");

  // Únicamente se muestra en automático cuando esté colocada la fecha de vencimiento
  if (!fechaVencStr) {
    $badge.hide().text("").removeClass("label-success label-danger");
    return;
  }

  var hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  var venc = new Date(fechaVencStr);
  venc.setHours(0, 0, 0, 0);

  $badge.show();
  if (venc >= hoy) {
    $badge
      .removeClass("label-danger label-default")
      .addClass("label-success")
      .text("VIGENTE");
  } else {
    $badge
      .removeClass("label-success label-default")
      .addClass("label-danger")
      .text("CADUCADA");
  }
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
    var cleanPath = "../../" + filePath;
    var ext = filePath
      .split("?")[0]
      .split("#")[0]
      .split(".")
      .pop()
      .toLowerCase();
    var isPdf = ext === "pdf";
    var icon = isPdf ? "glyphicon-file" : "glyphicon-picture";
    var tagLabel = isPdf ? "Ver PDF" : "Ver Foto";

    var html = '<div class="btn-group btn-group-xs" style="margin:0;">';
    html +=
      '<button type="button" class="btn btn-info btn-xs btn-preview-inline" onclick="abrirModalDocumento(\'' +
      cleanPath +
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

  var isPdfDoc = false;
  if (typeof isPdf === "boolean") {
    isPdfDoc = isPdf;
  } else {
    var cleanPath = filePath.split("?")[0].split("#")[0];
    var ext = cleanPath.split(".").pop().toLowerCase();
    isPdfDoc = ext === "pdf";
  }

  $("#previewDocModal").dialog(
    "option",
    "title",
    titulo || "Previsualización de Documento",
  );

  if (isPdfDoc) {
    $("#previewDocImg").hide().attr("src", "");
    $("#previewDocPdf").attr("src", filePath).show();
  } else {
    $("#previewDocPdf").hide().attr("src", "about:blank");
    $("#previewDocImg").attr("src", filePath).show();
  }
  $("#previewDocModal").dialog("open");
}

/* ==========================================================================
   TAB 1: EMPRESAS DE TRANSPORTE
   ========================================================================== */

function initGridEmpresasTransporte() {
  var $grid = $("#gridEmpresasTransporte");
  if (!$grid.length) return;
  $grid.jqGrid({
    url: "?listEmpresasTransporteGridAjax=true",
    datatype: "json",
    colNames: [
      "Código",
      "Descripción / Empresa",
      "Licencia MAE / RUC",
      "Teléfono",
      "Contacto",
      "Dirección",
      "Estado",
      "Acciones",
    ],
    colModel: [
      {
        name: "Mat_Cod",
        index: "Mat_Cod",
        width: 60,
        align: "center",
        key: true,
      },
      { name: "Mat_Des", index: "Mat_Des", width: 220, align: "left" },
      { name: "Mat_Mae", index: "Mat_Mae", width: 140, align: "center" },
      { name: "Mat_Tel", index: "Mat_Tel", width: 90, align: "center" },
      { name: "Mat_Pco", index: "Mat_Pco", width: 130, align: "left" },
      { name: "Mat_Dir", index: "Mat_Dir", width: 150, align: "left" },
      {
        name: "Mat_Est",
        index: "Mat_Est",
        width: 80,
        align: "center",
        formatter: function (cellvalue) {
          if (cellvalue === "A")
            return '<span class="label label-success">ACTIVO</span>';
          return '<span class="label label-danger">INACTIVO</span>';
        },
      },
      {
        name: "acciones",
        index: "acciones",
        width: 100,
        align: "center",
        sortable: false,
        formatter: function (cellvalue, options, rowObject) {
          var editBtn =
            '<button type="button" class="btn btn-primary btn-xs" onclick="abrirModalEmpresaTransporte(' +
            options.rowId +
            ')" title="Editar"><i class="glyphicon glyphicon-pencil"></i></button> ';
          var deleteBtn =
            '<button type="button" class="btn btn-danger btn-xs" onclick="anularEmpresaTransporte(' +
            options.rowId +
            ')" title="Anular"><i class="glyphicon glyphicon-trash"></i></button>';
          return editBtn + deleteBtn;
        },
      },
    ],
    rownumbers: true,
    rownumWidth: 40,
    cmTemplate: { sortable: false },
    rowNum: 50,
    rowList: [50, 100, 200, 500, 999999],
    pager: "#gridEmpresasTransportePager",
    sortname: "Mat_Des",
    sortorder: "asc",
    viewrecords: true,
    autowidth: true,
    height: 350,
    emptyrecords: "No se encontraron empresas de transporte",
    loadComplete: function () {
      $grid.jqGrid("setLabel", "rn", "#");
      $('.ui-pg-selbox option[value="999999"]').text("Todos");
    },
  });

  $grid
    .jqGrid("navGrid", "#gridEmpresasTransportePager", {
      edit: false,
      add: false,
      del: false,
      search: false,
      refresh: true,
    })
    .jqGrid("navButtonAdd", "#gridEmpresasTransportePager", {
      caption: "Exportar Excel",
      buttonicon: "glyphicon glyphicon-download-alt",
      title: "Exportar a Excel",
      onClickButton: function () {
        if (
          $.fn.jqGrid &&
          typeof $grid.jqGrid("exportGridExcel") === "function"
        ) {
          $grid.jqGrid("exportGridExcel", {
            nombre: "Empresas_Transporte",
            hoja: "Empresas de Transporte",
            footer: true,
            removeHiddens: true,
            removeCols: ["acciones"],
          });
        } else {
          mostrarAlertaUI(
            "Información",
            "Preparando exportación a Excel...",
            "info",
          );
        }
      },
    });
}

function actualizarGridEmpresasTransporte() {
  var formData = $("#filtroEmpresasTransporteForm").serializeArray();
  var postData = {};
  $.each(formData, function (i, field) {
    postData[field.name] = field.value;
  });
  $("#gridEmpresasTransporte")
    .jqGrid("setGridParam", {
      postData: postData,
      page: 1,
    })
    .trigger("reloadGrid");
}

function abrirModalEmpresaTransporte(id) {
  $("#empresaTransporteForm")[0].reset();
  $("#Mat_Cod").val("");
  if (id) {
    var row = $("#gridEmpresasTransporte").jqGrid("getRowData", id);
    $("#Mat_Cod").val(row.Mat_Cod);
    $("#Mat_Des").val(row.Mat_Des);
    $("#Mat_Mae").val(row.Mat_Mae);
    $("#Mat_Tel").val(row.Mat_Tel || "");
    $("#Mat_Pco").val(row.Mat_Pco || "");
    $("#Mat_Dir").val(row.Mat_Dir || "");
    $("#empresaTransporteDialog").dialog(
      "option",
      "title",
      "Editar Empresa de Transporte",
    );
  } else {
    $("#empresaTransporteDialog").dialog(
      "option",
      "title",
      "Registrar Empresa de Transporte",
    );
  }
  $("#empresaTransporteDialog").dialog("open");
}

function guardarEmpresaTransporte() {
  var Mat_Des = $("#Mat_Des").val().trim();
  if (!Mat_Des) {
    mostrarAlertaUI(
      "Atención",
      "Ingrese el nombre o descripción de la empresa de transporte",
      "warning",
    );
    return;
  }

  var $btnSave = $("#btnGuardarEmpresa");
  $btnSave
    .prop("disabled", true)
    .html(
      '<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Guardando...',
    );
  bloquearModalYMostrarLoader(
    "#empresaTransporteDialog",
    "Guardando Empresa...",
  );

  var data =
    $("#empresaTransporteForm").serialize() + "&saveEmpresaTransporteAjax=true";
  $.post(
    "",
    data,
    function (r) {
      if (r.success) {
        mostrarAlertaUI(
          "Éxito",
          "Empresa de Transporte guardada correctamente",
          "success",
          function () {
            $("#empresaTransporteDialog").dialog("close");
            actualizarGridEmpresasTransporte();
          },
        );
      } else {
        mostrarAlertaUI(
          "Error",
          r.message || "No se pudo guardar la información",
          "error",
        );
      }
    },
    "json",
  )
    .fail(function () {
      mostrarAlertaUI("Error", "Ocurrió un error en el servidor", "error");
    })
    .always(function () {
      desbloquearModalYOcultarLoader("#empresaTransporteDialog");
      $btnSave
        .prop("disabled", false)
        .html('<i class="glyphicon glyphicon-floppy-disk"></i> Guardar');
    });
}

function anularEmpresaTransporte(Mat_Cod) {
  swal(
    {
      title: "¿Está seguro?",
      text: "¿Desea anular esta empresa de transporte?",
      type: "warning",
      showCancelButton: true,
    },
    function (isConfirm) {
      if (isConfirm) {
        $.post(
          "",
          { anularEmpresaTransporteAjax: true, Mat_Cod: Mat_Cod },
          function (r) {
            if (r.success) {
              mostrarAlertaUI(
                "Éxito",
                "Empresa de Transporte anulada correctamente",
                "success",
                function () {
                  actualizarGridEmpresasTransporte();
                },
              );
            } else {
              mostrarAlertaUI(
                "Error",
                r.message || "No se pudo anular el registro",
                "error",
              );
            }
          },
          "json",
        );
      }
    },
  );
}

/* ==========================================================================
   TAB 2: CHOFERES
   ========================================================================== */

function initGridChoferes() {
  var $grid = $("#gridChoferes");
  if (!$grid.length) return;
  $grid.jqGrid({
    url: "?listChoferesGridAjax=true",
    datatype: "json",
    colNames: [
      "Código",
      "Cédula",
      "Chofer / Nombres",
      "Licencia",
      "Estado Lic.",
      "Tipo Sangre",
      "Caducidad",
      "Planta",
      "Pla_Cod",
      "Teléfono",
      "Estado",
      "Acciones",
    ],
    colModel: [
      {
        name: "Cho_Cod",
        index: "Cho_Cod",
        width: 60,
        align: "center",
        key: true,
      },
      { name: "Prs_Ced", index: "Prs_Ced", width: 100, align: "center" },
      { name: "nombre", index: "nombre", width: 200, align: "left" },
      { name: "Cho_Tli", index: "Cho_Tli", width: 70, align: "center" },
      {
        name: "Cho_Lic_Est",
        index: "Cho_Lic_Est",
        width: 100,
        align: "center",
        sortable: false,
        formatter: function (cellvalue, options, rowObject) {
          if (!rowObject.Cho_Cli) return "";
          var hoy = new Date();
          hoy.setHours(0, 0, 0, 0);
          var venc = new Date(rowObject.Cho_Cli);
          venc.setHours(0, 0, 0, 0);
          if (venc >= hoy) {
            return '<span class="label label-success">VIGENTE</span>';
          } else {
            return '<span class="label label-danger">CADUCADA</span>';
          }
        },
      },
      { name: "Cho_Tsa", index: "Cho_Tsa", width: 75, align: "center" },
      { name: "Cho_Cli", index: "Cho_Cli", width: 95, align: "center" },
      {
        name: "Pla_Nom",
        index: "Pla_Nom",
        width: 140,
        align: "left",
        hidden: true,
      },
      { name: "Pla_Cod", index: "Pla_Cod", hidden: true },
      { name: "Cho_Tel", index: "Cho_Tel", width: 100, align: "center" },
      {
        name: "Cho_Est",
        index: "Cho_Est",
        width: 90,
        align: "center",
        formatter: function (cellvalue) {
          if (cellvalue === "A")
            return '<span class="label label-success">ACTIVO</span>';
          if (cellvalue === "S")
            return '<span class="label label-warning">SUSPENDIDO</span>';
          return '<span class="label label-danger">INACTIVO</span>';
        },
      },
      {
        name: "acciones",
        index: "acciones",
        width: 160,
        align: "center",
        sortable: false,
        formatter: function (cellvalue, options, rowObject) {
          var editBtn =
            '<button type="button" class="btn btn-primary btn-xs" onclick="abrirModalChofer(' +
            options.rowId +
            ')" title="Editar"><i class="glyphicon glyphicon-pencil"></i></button> ';
          var sendBtn =
            '<button type="button" class="btn btn-success btn-xs btn-enviar-notif-chofer" id="btnEnviarChofer_' +
            options.rowId +
            '" onclick="enviarNotifCapacitacionChofer(' +
            options.rowId +
            ', this)" title="Enviar WhatsApp y correo"><i class="glyphicon glyphicon-send"></i> Enviar</button> ';
          var deleteBtn =
            '<button type="button" class="btn btn-danger btn-xs" onclick="anularChoferGrid(' +
            options.rowId +
            ')" title="Anular"><i class="glyphicon glyphicon-trash"></i></button>';
          return editBtn + sendBtn + deleteBtn;
        },
      },
    ],
    rownumbers: true,
    rownumWidth: 40,
    cmTemplate: { sortable: false },
    rowNum: 50,
    rowList: [50, 100, 200, 500, 999999],
    pager: "#gridChoferesPager",
    sortname: "nombre",
    sortorder: "asc",
    viewrecords: true,
    autowidth: true,
    height: 350,
    emptyrecords: "No se encontraron choferes",
    loadComplete: function () {
      $grid.jqGrid("setLabel", "rn", "#");
      $('.ui-pg-selbox option[value="999999"]').text("Todos");
    },
  });

  $grid
    .jqGrid("navGrid", "#gridChoferesPager", {
      edit: false,
      add: false,
      del: false,
      search: false,
      refresh: true,
    })
    .jqGrid("navButtonAdd", "#gridChoferesPager", {
      caption: "Exportar Excel",
      buttonicon: "glyphicon glyphicon-download-alt",
      title: "Exportar a Excel",
      onClickButton: function () {
        if (
          $.fn.jqGrid &&
          typeof $grid.jqGrid("exportGridExcel") === "function"
        ) {
          $grid.jqGrid("exportGridExcel", {
            nombre: "Choferes",
            hoja: "Choferes",
            footer: true,
            removeHiddens: true,
            removeCols: ["acciones"],
          });
        } else {
          mostrarAlertaUI(
            "Información",
            "Preparando exportación a Excel...",
            "info",
          );
        }
      },
    });
}

function actualizarGridChoferes() {
  var formData = $("#filtroChoferesForm").serializeArray();
  var postData = {};
  $.each(formData, function (i, field) {
    postData[field.name] = field.value;
  });
  $("#gridChoferes")
    .jqGrid("setGridParam", {
      postData: postData,
      page: 1,
    })
    .trigger("reloadGrid");
}

function buscarPersonaCedula(cedula) {
  if (!cedula || cedula.length < 10) return;
  $.get(
    "",
    { buscarPersonaCedulaAjax: true, Prs_Ced: cedula },
    function (r) {
      if (r.success && r.existe) {
        if (r.esChofer && r.chofer) {
          poblarFormularioChofer(r.chofer);
          var nomComp = r.chofer.nombre || ((r.chofer.Prs_Nom || "") + " " + (r.chofer.Prs_Ape || "")).trim();
          $("#choferDialog").dialog("option", "title", "Editar Chofer - " + nomComp);
          mostrarAlertaUI(
            "Chofer Ya Registrado",
            "El chofer con Cédula <b>" +
              cedula +
              "</b> ya se encuentra registrado en el sistema.<br><br>Se han cargado sus datos completos para su consulta o modificación.",
            "warning",
          );
        } else if (r.persona) {
          $("#Prs_Nom").val(r.persona.Prs_Nom || "");
          $("#Prs_Ape").val(r.persona.Prs_Ape || "");
          $("#Cho_Tel").val(r.persona.Prs_Tel || "");
          $("#Cho_Cor").val(r.persona.Prs_Cor || "");
          $("#Cho_Dir").val(r.persona.Prs_Dir || "");
          if (r.persona.Prs_Fec) {
            $("#Prs_Fec").val(r.persona.Prs_Fec);
            calcularEdad(r.persona.Prs_Fec);
          }
          $("#Prs_Cod").val(r.persona.Prs_Cod || "");
        }
      }
    },
    "json",
  );
}

function buscarPersonaPropietario(cedula) {
  var $est = $("#Mat_Pro_Id_Est");
  if (!cedula || cedula.length < 5) {
    if ($est.length) $est.empty();
    return;
  }
  $.get(
    "",
    { buscarPersonaCedulaAjax: true, Prs_Ced: cedula },
    function (r) {
      if (r.success && r.existe) {
        var nom = (
          (r.persona.Prs_Nom || "") +
          " " +
          (r.persona.Prs_Ape || "")
        ).trim();
        $("#Mat_Pro_Nom").val(nom);
        if (r.persona.Prs_Tel) $("#Mat_Pro_Tel").val(r.persona.Prs_Tel);
        if (r.persona.Prs_Dir) $("#Mat_Pro_Dir").val(r.persona.Prs_Dir);
        if ($est.length) {
          $est.html(
            '<span class="text-success" style="font-size: 10px; font-weight: bold;"><i class="glyphicon glyphicon-ok"></i> Registrado en Sistema</span>',
          );
        }
      } else {
        if ($est.length) {
          $est.html(
            '<span class="text-muted" style="font-size: 10px;"><i class="glyphicon glyphicon-pencil"></i> Nuevo Propietario</span>',
          );
        }
      }
    },
    "json",
  );
}

function abrirModalChofer(id) {
  $("#choferForm")[0].reset();
  $("#Cho_Cod").val("");
  $("#Prs_Cod").val("");
  $(".preview-doc-box").empty();
  $("#badgeLicencia").hide().text("").removeClass("label-success label-danger");
  $("#Cho_Edad").val("");
  $("#choferForm select.chosen-select").trigger("chosen:updated");

  var winWidth = $(window).width();
  var modalW = winWidth < 1250 ? Math.floor(winWidth * 0.96) : 1250;
  $("#choferDialog").dialog("option", "width", modalW);

  if (id) {
    // Carga Completa mediante AJAX desde el servidor para garantizar 100% de los campos
    $.get(
      "",
      { getChoferByIdAjax: true, Cho_Cod: id },
      function (r) {
        if (r.success && r.chofer) {
          poblarFormularioChofer(r.chofer);
          $("#choferDialog").dialog(
            "option",
            "title",
            "Editar Chofer - " + r.chofer.nombre,
          );
          $("#choferDialog").dialog("open");
        } else {
          mostrarAlertaUI(
            "Error",
            "No se pudo cargar la información completa del chofer",
            "error",
          );
        }
      },
      "json",
    ).fail(function () {
      mostrarAlertaUI(
        "Error",
        "Error de conexión al obtener los datos del chofer",
        "error",
      );
    });
  } else {
    $("#choferDialog").dialog("option", "title", "Registrar Chofer");
    $("#choferDialog").dialog("open");
  }
}

/**
  * Poblar datos de chofer y documentos adjuntos en el formulario modal
  */
function poblarFormularioChofer(row) {
  if (!row) return;
  $("#Cho_Cod").val(row.Cho_Cod || "");
  $("#Prs_Cod").val(row.Prs_Cod || "");
  $("#Cho_Ced").val(row.Prs_Ced || "");
  $("#Prs_Nom").val(row.Prs_Nom || "");
  $("#Prs_Ape").val(row.Prs_Ape || "");
  if (row.Prs_Fec) {
    $("#Prs_Fec").val(row.Prs_Fec);
    calcularEdad(row.Prs_Fec);
  }

  $("#Cho_Nac").val(row.Cho_Nac || "Ecuatoriana");
  $("#Cho_Eci").val(row.Cho_Eci || "Soltero/a");
  $("#Cho_Pla_Cod").val(row.Pla_Cod || "");
  $("#Cho_Car").val(row.Cho_Car || "Chofer");
  $("#Cho_Est").val(row.Cho_Est || "A");
  $("#Cho_Tco").val(row.Cho_Tco || "Indefinido");

  $("#Cho_Tli").val(row.Cho_Tli || "");
  $("#Cho_Nli").val(row.Cho_Nli || "");
  $("#Cho_Fei").val(row.Cho_Fei || "");
  $("#Cho_Cli").val(row.Cho_Cli || "");
  evaluarEstadoLicencia();

  $("#Cap_Bas_Obli").val(row.Cap_Bas_Obli || "N");
  $("#Cap_Bas_Fec").val(row.Cap_Bas_Fec || "");
  $("#Cap_Bas_Vig").val(row.Cap_Bas_Vig || "");

  $("#Cap_Mat_Peli").val(row.Cap_Mat_Peli || "N");
  $("#Cap_Mat_Fec").val(row.Cap_Mat_Fec || "");
  $("#Cap_Mat_Vig").val(row.Cap_Mat_Vig || "");

  $("#Cho_Tsa").val(row.Cho_Tsa || "");
  $("#Cho_Tel").val(row.Cho_Tel || row.Prs_Tel_Base || "");
  $("#Cho_Cor").val(row.Cho_Cor || row.Prs_Cor || "");
  $("#Cho_Dir").val(row.Cho_Dir || row.Prs_Dir_Base || "");
  $("#Cho_Nem").val(row.Cho_Nem || "");
  $("#Cho_Tem").val(row.Cho_Tem || "");

  // Actualizar Chosen en selects del modal
  $("#choferForm select.chosen-select").trigger("chosen:updated");

  // Previews de documentos adjuntos
  renderDocPreview("preview_Cho_Img_Lic_Anv", row.Cho_Img_Lic_Anv, "Licencia Anverso");
  renderDocPreview("preview_Cho_Img_Lic_Rev", row.Cho_Img_Lic_Rev, "Licencia Reverso");
  renderDocPreview("preview_Cap_Bas_Adj", row.Cap_Bas_Adj, "Cert. Básico");
  renderDocPreview("preview_Cap_Mat_Adj", row.Cap_Mat_Adj, "Cert. Mat. Pelig");
  renderDocPreview("preview_Cho_Doc_Ced", row.Cho_Doc_Ced, "Cédula Anverso");
  renderDocPreview("preview_Cho_Doc_Ced_Rev", row.Cho_Doc_Ced_Rev, "Cédula Reverso");
  renderDocPreview("preview_Cho_Doc_Vot", row.Cho_Doc_Vot, "Certif. Votación");
  renderDocPreview("preview_Cho_Doc_Fot", row.Cho_Doc_Fot, "Foto Carnet");
  renderDocPreview("preview_Cho_Doc_Ldi", row.Cho_Doc_Ldi, "Licencia Digital");
  renderDocPreview("preview_Cho_Doc_Ant", row.Cho_Doc_Ant, "Antecedentes Penales");
  renderDocPreview("preview_Cho_Doc_San", row.Cho_Doc_San, "Carnet Sangre");
}

/**
 * Optimización y Compresión de Imágenes en Navegador (HTML5 Canvas)
 */
function optimizarImagenCliente(file, maxDim, quality) {
  return new Promise(function (resolve) {
    if (
      !file ||
      !file.type ||
      !file.type.match(/^image\/(jpeg|jpg|png|webp)$/i)
    ) {
      resolve(file);
      return;
    }

    var reader = new FileReader();
    reader.onload = function (e) {
      var img = new Image();
      img.onload = function () {
        var width = img.width;
        var height = img.height;
        if (width <= maxDim && height <= maxDim && file.size <= 1024 * 1024) {
          resolve(file);
          return;
        }

        var ratio = width / height;
        if (width > maxDim || height > maxDim) {
          if (ratio > 1) {
            width = maxDim;
            height = Math.round(maxDim / ratio);
          } else {
            height = maxDim;
            width = Math.round(maxDim * ratio);
          }
        }

        var canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
        var ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0, width, height);

        canvas.toBlob(
          function (blob) {
            if (!blob) {
              resolve(file);
              return;
            }
            var ext = file.type === "image/png" ? ".png" : ".jpg";
            var newName = file.name.replace(/\.[^/.]+$/, "") + ext;
            var compressed = new File([blob], newName, {
              type: blob.type,
              lastModified: Date.now(),
            });
            resolve(compressed);
          },
          "image/jpeg",
          quality || 0.85,
        );
      };
      img.onerror = function () {
        resolve(file);
      };
      img.src = e.target.result;
    };
    reader.onerror = function () {
      resolve(file);
    };
    reader.readAsDataURL(file);
  });
}

function guardarChofer() {
  var formEl = $("#choferForm")[0];
  var formData = new FormData(formEl); // Capturar FormData antes de bloquear

  var ced = $("#Cho_Ced").val().trim();
  var nom = $("#Prs_Nom").val().trim();
  var ape = $("#Prs_Ape").val().trim();
  var tel = $("#Cho_Tel").val().trim();
  var tli = $("#Cho_Tli").val();

  if (!ced || !nom || !ape || !tel || !tli) {
    mostrarAlertaUI(
      "Atención",
      "Complete la Cédula/Identificación, Nombres, Apellidos, Teléfono Celular y Tipo de Licencia",
      "warning",
    );
    return;
  }

  var $btnSave = $("#btnGuardarChofer");
  $btnSave
    .prop("disabled", true)
    .html(
      '<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Comprimiendo y Guardando...',
    );
  bloquearModalYMostrarLoader(
    "#choferDialog",
    "Comprimiendo y Guardando Chofer...",
  );

  // Recolectar insumos y procesar imágenes en cliente
  var inputs = $(formEl).find('input[type="file"]');
  var promises = [];
  var fileFieldsMeta = [];

  inputs.each(function () {
    var fieldName = $(this).attr("name");
    var files = this.files;
    if (files && files.length > 0) {
      var f = files[0];
      fileFieldsMeta.push({ field: fieldName, originalFile: f });
      promises.push(optimizarImagenCliente(f, 1920, 0.85));
    }
  });

  Promise.all(promises)
    .then(function (processedFiles) {
      formData.append("saveChoferAjax", "true");

      // Reemplazar archivos procesados en FormData y calcular diagnósticos de tamaño
      var diagFiles = [];
      var totalBytes = 0;

      for (var i = 0; i < fileFieldsMeta.length; i++) {
        var meta = fileFieldsMeta[i];
        var procFile = processedFiles[i];
        formData.set(meta.field, procFile);

        var origKB = (meta.originalFile.size / 1024).toFixed(1);
        var procKB = (procFile.size / 1024).toFixed(1);
        totalBytes += procFile.size;

        diagFiles.push({
          Campo: meta.field,
          Nombre: meta.originalFile.name,
          "Orig KB": origKB,
          "Enviado KB": procKB,
          Tipo: procFile.type,
        });
      }

      var totalMB = (totalBytes / (1024 * 1024)).toFixed(2);

      // IMPRIMIR DIAGNÓSTICO EN CONSOLA NAVEGADOR ("Imprimir en pantalla lo que está subiéndose")
      console.group(
        "================ DIAGNÓSTICO DE ENVÍO CHOFER ================",
      );
      console.log("Cédula:", ced);
      console.log("Nombres:", nom, ape);
      console.log("Teléfono:", tel);
      console.log("Licencia:", tli);
      console.log(
        "Total peso archivos a enviar:",
        totalMB + " MB (" + totalBytes + " bytes)",
      );
      if (diagFiles.length > 0) {
        console.table(diagFiles);
      } else {
        console.log("No se adjuntaron nuevos archivos.");
      }
      console.groupEnd();

      // Ejecutar AJAX con manejo exhaustivo de errores
      $.ajax({
        url: "",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (r) {
          if (r.success) {
            var successMsg = "Chofer guardado correctamente.";
            if (r.debug_info && r.debug_info.archivos_recibidos) {
              console.log("=== CONFIRMACIÓN SERVIDOR ===", r.debug_info);
            }
            mostrarAlertaUI("Éxito", successMsg, "success", function () {
              $("#choferDialog").dialog("close");
              actualizarGridChoferes();
            });
          } else {
            var errContent =
              "<b>" +
              (r.message || "No se pudo guardar la información") +
              "</b>";

            // Si el servidor incluyó información de diagnóstico, mostrarla en pantalla
            if (r.debug_info) {
              errContent +=
                "<br><br><small><b>Resumen del envío (Diagnóstico):</b><br>";
              errContent +=
                "• Archivos procesados: " +
                Object.keys(r.debug_info.archivos_recibidos || {}).length +
                "<br>";
              errContent += "• Peso total archivos: " + totalMB + " MB</small>";
            }

            mostrarAlertaUI(
              "Atención - No se pudo guardar",
              errContent,
              "warning",
            );
          }
        },
        error: function (xhr, status, error) {
          var msgError =
            "Ocurrió un error en el servidor al enviar los datos (Código HTTP: " +
            xhr.status +
            ").";

          if (xhr.responseJSON && xhr.responseJSON.message) {
            msgError = xhr.responseJSON.message;
          } else if (xhr.responseText) {
            var rawText = xhr.responseText
              .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, "")
              .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, "")
              .replace(/<[^>]*>?/gm, " ")
              .replace(/\s+/g, " ")
              .trim();
            if (rawText.length > 0) {
              msgError +=
                "<br><br><b>Respuesta del Servidor (Diagnóstico):</b><br>";
              msgError +=
                '<div style="max-height: 140px; overflow-y: auto; background: #fff3f3; padding: 6px; border: 1px solid #f5c6cb; font-family: monospace; font-size: 11px; word-break: break-word;">';
              msgError += rawText.substring(0, 450) + "...</div>";
            }
          }

          if (diagFiles.length > 0) {
            msgError +=
              "<br><small><b>Archivos que intentaban subirse (" +
              totalMB +
              " MB):</b><br>";
            $.each(diagFiles, function (idx, df) {
              msgError +=
                "• " +
                df.Campo +
                ": " +
                df.Nombre +
                " (" +
                df["Enviado KB"] +
                " KB)<br>";
            });
            msgError += "</small>";
          }

          mostrarAlertaUI("Error de Servidor", msgError, "error");
        },
        complete: function () {
          desbloquearModalYOcultarLoader("#choferDialog");
          $btnSave
            .prop("disabled", false)
            .html(
              '<i class="glyphicon glyphicon-floppy-disk"></i> Guardar Chofer',
            );
        },
      });
    })
    .catch(function (err) {
      desbloquearModalYOcultarLoader("#choferDialog");
      $btnSave
        .prop("disabled", false)
        .html('<i class="glyphicon glyphicon-floppy-disk"></i> Guardar Chofer');
      mostrarAlertaUI(
        "Error de Optimización",
        "Ocurrió un problema al procesar las imágenes en el navegador: " + err,
        "error",
      );
    });
}

function anularChoferGrid(Cho_Cod) {
  swal(
    {
      title: "¿Está seguro?",
      text: "¿Desea anular este chofer?",
      type: "warning",
      showCancelButton: true,
    },
    function (isConfirm) {
      if (isConfirm) {
        $.post(
          "",
          { anularChoferAjax: true, Cho_Cod: Cho_Cod },
          function (r) {
            if (r.success) {
              mostrarAlertaUI(
                "Éxito",
                "Chofer anulado correctamente",
                "success",
                function () {
                  actualizarGridChoferes();
                },
              );
            } else {
              mostrarAlertaUI(
                "Error",
                r.message || "No se pudo anular el chofer",
                "error",
              );
            }
          },
          "json",
        );
      }
    },
  );
}

var _enviandoNotifChofer = {};

function setEstadoBtnEnviarChofer($btn, enviando) {
  if (!$btn || !$btn.length) return;
  if (enviando) {
    $btn
      .prop("disabled", true)
      .addClass("disabled")
      .html('<i class="glyphicon glyphicon-refresh"></i> Enviando...');
  } else {
    $btn
      .prop("disabled", false)
      .removeClass("disabled")
      .html('<i class="glyphicon glyphicon-send"></i> Enviar');
  }
}

function enviarNotifCapacitacionChofer(Cho_Cod, btnEl) {
  if (!Cho_Cod) {
    mostrarAlertaUI("Error", "No se identificó el chofer.", "error");
    return;
  }
  if (_enviandoNotifChofer[Cho_Cod]) {
    return;
  }

  var $btn = btnEl
    ? $(btnEl)
    : $("#btnEnviarChofer_" + Cho_Cod);
  if ($btn.length && $btn.prop("disabled")) {
    return;
  }

  swal(
    {
      title: "Enviar notificación",
      text: "¿Enviar por WhatsApp y correo el mensaje de registro de capacitación a este chofer?",
      type: "info",
      showCancelButton: true,
      confirmButtonText: "Enviar",
      cancelButtonText: "Cancelar",
    },
    function (isConfirm) {
      if (!isConfirm) return;
      if (_enviandoNotifChofer[Cho_Cod]) return;

      _enviandoNotifChofer[Cho_Cod] = true;
      setEstadoBtnEnviarChofer($btn, true);

      $.post(
        "",
        { enviarNotifCapacitacionChoferAjax: true, Cho_Cod: Cho_Cod },
        function (r) {
          if (r && r.success) {
            mostrarAlertaUI(
              "Éxito",
              r.message || "Notificación enviada correctamente.",
              "success",
            );
          } else {
            mostrarAlertaUI(
              "Error",
              (r && r.message) || "No se pudo enviar la notificación.",
              "error",
            );
          }
        },
        "json",
      )
        .fail(function () {
          mostrarAlertaUI(
            "Error",
            "Ocurrió un error de comunicación con el servidor.",
            "error",
          );
        })
        .always(function () {
          _enviandoNotifChofer[Cho_Cod] = false;
          setEstadoBtnEnviarChofer($btn, false);
        });
    },
  );
}

/* ==========================================================================
   TAB 3: VEHÍCULOS
   ========================================================================== */

function initGridVehiculos() {
  var $grid = $("#gridVehiculos");
  if (!$grid.length) return;
  $grid.jqGrid({
    url: "?listVehiculosGridAjax=true",
    datatype: "json",
    colNames: [
      "Código",
      "Placa",
      "Planta",
      "Pla_Cod",
      "Empresa Transp.",
      "Mat_Cod",
      "Marca",
      "Color",
      "Capacidad (Kg)",
      "Tipo",
      "Acciones",
    ],
    colModel: [
      {
        name: "Veh_Cod",
        index: "Veh_Cod",
        width: 70,
        align: "center",
        key: true,
      },
      { name: "Veh_Pla", index: "Veh_Pla", width: 100, align: "center" },
      { name: "Pla_Nom", index: "Pla_Nom", width: 160, align: "left" },
      { name: "Pla_Cod", index: "Pla_Cod", hidden: true },
      { name: "Mat_Des", index: "Mat_Des", width: 180, align: "left" },
      { name: "Mat_Cod", index: "Mat_Cod", hidden: true },
      { name: "Veh_Mar", index: "Veh_Mar", width: 120, align: "left" },
      { name: "Veh_Col", index: "Veh_Col", width: 100, align: "center" },
      {
        name: "Veh_Cap",
        index: "Veh_Cap",
        width: 110,
        align: "right",
        formatter: "number",
        formatoptions: { decimalPlaces: 2 },
      },
      {
        name: "Veh_Tit",
        index: "Veh_Tit",
        width: 110,
        align: "center",
        formatter: function (cellvalue) {
          if (cellvalue === "V")
            return '<span class="label label-primary">VOLQUETA</span>';
          if (cellvalue === "D")
            return '<span class="label label-warning">TIPO DUMPER</span>';
          if (cellvalue === "C")
            return '<span class="label label-info">CAMION</span>';
          return cellvalue || "-";
        },
      },
      {
        name: "acciones",
        index: "acciones",
        width: 140,
        align: "center",
        sortable: false,
        formatter: function (cellvalue, options, rowObject) {
          var editBtn =
            '<button type="button" class="btn btn-primary btn-xs" onclick="abrirModalVehiculo(' +
            options.rowId +
            ')" title="Editar"><i class="glyphicon glyphicon-pencil"></i></button> ';
          var qrBtn =
            '<button type="button" class="btn btn-default btn-xs" onclick="verQrVehiculo(' +
            options.rowId +
            ", '" +
            rowObject.Veh_Pla +
            '\')" title="Ver QR"><i class="glyphicon glyphicon-qrcode"></i></button> ';
          var deleteBtn =
            '<button type="button" class="btn btn-danger btn-xs" onclick="anularVehiculoGrid(' +
            options.rowId +
            ')" title="Anular"><i class="glyphicon glyphicon-trash"></i></button>';
          return editBtn + qrBtn + deleteBtn;
        },
      },
    ],
    rownumbers: true,
    rownumWidth: 40,
    cmTemplate: { sortable: false },
    rowNum: 50,
    rowList: [50, 100, 200, 500, 999999],
    pager: "#gridVehiculosPager",
    sortname: "Veh_Pla",
    sortorder: "asc",
    viewrecords: true,
    autowidth: true,
    height: 350,
    emptyrecords: "No se encontraron vehículos",
    loadComplete: function () {
      $grid.jqGrid("setLabel", "rn", "#");
      $('.ui-pg-selbox option[value="999999"]').text("Todos");
    },
  });

  $grid
    .jqGrid("navGrid", "#gridVehiculosPager", {
      edit: false,
      add: false,
      del: false,
      search: false,
      refresh: true,
    })
    .jqGrid("navButtonAdd", "#gridVehiculosPager", {
      caption: "Exportar Excel",
      buttonicon: "glyphicon glyphicon-download-alt",
      title: "Exportar a Excel",
      onClickButton: function () {
        if (
          $.fn.jqGrid &&
          typeof $grid.jqGrid("exportGridExcel") === "function"
        ) {
          $grid.jqGrid("exportGridExcel", {
            nombre: "Vehiculos",
            hoja: "Vehiculos",
            footer: true,
            removeHiddens: true,
            removeCols: ["acciones"],
          });
        } else {
          mostrarAlertaUI(
            "Información",
            "Preparando exportación a Excel...",
            "info",
          );
        }
      },
    });
}

function actualizarGridVehiculos() {
  var formData = $("#filtroVehiculosForm").serializeArray();
  var postData = {};
  $.each(formData, function (i, field) {
    postData[field.name] = field.value;
  });
  $("#gridVehiculos")
    .jqGrid("setGridParam", {
      postData: postData,
      page: 1,
    })
    .trigger("reloadGrid");
}

function validarPlacaVehiculo(placa) {
  if (!placa) return;
  $.post(
    "",
    { validarPlacaVehiculoAjax: true, Veh_Pla: placa },
    function (r) {
      if (r.existe) {
        $("#Veh_Pla_Est").html(
          '<span class="text-danger"><i class="glyphicon glyphicon-remove"></i> Placa en uso</span>',
        );
      } else {
        $("#Veh_Pla_Est").html(
          '<span class="text-success"><i class="glyphicon glyphicon-ok"></i> Disponible</span>',
        );
      }
    },
    "json",
  );
}

function abrirModalVehiculo(id) {
  $("#vehiculoForm")[0].reset();
  $("#Veh_Cod").val("");
  $("#Veh_Pla_Est").html("");
  $("#Veh_Pla_Cod").val("");
  $("#Mat_Cod").val("");
  $("#vehiculoForm select.chosen-select").trigger("chosen:updated");

  if (id) {
    $.get(
      "",
      { getVehiculoByIdAjax: true, Veh_Cod: id },
      function (r) {
        if (r.success && r.vehiculo) {
          var row = r.vehiculo;
          $("#Veh_Cod").val(row.Veh_Cod || "");
          $("#Veh_Pla_Cod").val(row.Pla_Cod || "");
          $("#Mat_Cod").val(row.Mat_Cod || "");
          $("#Veh_Pla").val(row.Veh_Pla || "");
          $("#Mat_Pan").val(row.Mat_Pan || "");
          $("#Veh_Est").val(row.Veh_Est || "A");

          // Datos Propietario
          $("#Mat_Pro_Nom").val(row.Mat_Pro_Nom || "");
          $("#Mat_Pro_Id").val(row.Mat_Pro_Id || "");
          $("#Mat_Pro_Prv").val(row.Mat_Pro_Prv || "");
          $("#Mat_Pro_Can").val(row.Mat_Pro_Can || "");
          $("#Mat_Pro_Dir").val(row.Mat_Pro_Dir || "");
          $("#Mat_Pro_Tel").val(row.Mat_Pro_Tel || "");

          // Especificaciones Técnicas y Colores
          $("#Veh_Mar").val(row.Veh_Mar || row.Mat_Mar || "");
          $("#Mat_Mde").val(row.Mat_Mde || "");
          $("#Mat_Ano").val(row.Mat_Ano || "");
          $("#Mat_Amo").val(row.Mat_Amo || "");
          $("#Veh_Col").val(row.Veh_Col || row.Mat_Co1 || "");
          $("#Mat_Co2").val(row.Mat_Co2 || "");
          $("#Veh_Cap").val(row.Veh_Cap || "");
          $("#Mat_Ton").val(row.Mat_Ton || "");
          $("#Veh_Tit").val(row.Veh_Tit || "V");

          // Motor, Chasis y Mecánica
          $("#Mat_Nmo").val(row.Mat_Nmo || "");
          $("#Mat_Cha").val(row.Mat_Cha || "");
          $("#Mat_Ram").val(row.Mat_Ram || "");
          $("#Mat_Cil").val(row.Mat_Cil || "");
          $("#Mat_Tco").val(row.Mat_Tco || "D");
          $("#Mat_Cve").val(row.Mat_Cve || "");
          $("#Mat_Tip").val(row.Mat_Mat_Tip || row.Mat_Tip || "");
          $("#Mat_Car").val(row.Mat_Car || "");
          $("#Mat_Tpe").val(row.Mat_Tpe || "PESADO (>3.5T)");
          $("#Mat_Npa").val(row.Mat_Npa || "");
          $("#Mat_Ori").val(row.Mat_Ori || "");

          // Matrícula, Fechas y Avalúo
          $("#Mat_Nma").val(row.Mat_Nma || "");
          $("#Mat_Fem").val(row.Mat_Fem || "");
          $("#Mat_Fve").val(row.Mat_Fve || "");
          $("#Mat_Lem").val(row.Mat_Lem || "");
          $("#Mat_Fco").val(row.Mat_Fco || "");
          $("#Mat_Ava").val(row.Mat_Ava || "");
          $("#Mat_Vma").val(row.Mat_Vma || "");

          // Operación, Flags y Observaciones
          $("#Mat_Dis").val(row.Mat_Dis || "");
          $("#Mat_Ort").val(row.Mat_Ort || "N");
          $("#Mat_Rem").val(row.Mat_Rem || "N");
          $("#Mat_Dig").val(row.Mat_Dig || "");
          $("#Mat_Obs").val(row.Mat_Obs || "");

          // Refrescar Chosen (Buscador interno)
          $("#vehiculoForm select.chosen-select").trigger("chosen:updated");

          $("#vehiculoDialog").dialog(
            "option",
            "title",
            "Editar Vehículo - Placa: " + row.Veh_Pla,
          );
          $("#vehiculoDialog").dialog("open");
        } else {
          mostrarAlertaUI(
            "Error",
            "No se pudo obtener la información del vehículo",
            "error",
          );
        }
      },
      "json",
    ).fail(function () {
      mostrarAlertaUI(
        "Error",
        "Error de conexión al obtener datos del vehículo",
        "error",
      );
    });
  } else {
    $("#vehiculoDialog").dialog("option", "title", "Registrar Vehículo");
    $("#vehiculoDialog").dialog("open");
  }
}

function guardarVehiculo() {
  var pla = $("#Veh_Pla").val().trim();
  var mar = $("#Veh_Mar").val().trim();
  if (!pla || !mar) {
    mostrarAlertaUI(
      "Atención",
      "Complete la Placa y la Marca del vehículo",
      "warning",
    );
    return;
  }

  var $btnSave = $("#btnGuardarVehiculo");
  $btnSave
    .prop("disabled", true)
    .html(
      '<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Guardando...',
    );
  bloquearModalYMostrarLoader("#vehiculoDialog", "Guardando Vehículo...");

  var data = $("#vehiculoForm").serialize() + "&saveVehiculoAjax=true";
  $.post(
    "",
    data,
    function (r) {
      if (r.success) {
        mostrarAlertaUI(
          "Éxito",
          "Vehículo guardado correctamente",
          "success",
          function () {
            $("#vehiculoDialog").dialog("close");
            actualizarGridVehiculos();
          },
        );
      } else {
        mostrarAlertaUI(
          "Error",
          r.message || "No se pudo guardar el vehículo",
          "error",
        );
      }
    },
    "json",
  )
    .fail(function () {
      mostrarAlertaUI(
        "Error",
        "Ocurrió un error al procesar el vehículo",
        "error",
      );
    })
    .always(function () {
      desbloquearModalYOcultarLoader("#vehiculoDialog");
      $btnSave
        .prop("disabled", false)
        .html(
          '<i class="glyphicon glyphicon-floppy-disk"></i> Guardar Vehículo',
        );
    });
}

function anularVehiculoGrid(Veh_Cod) {
  swal(
    {
      title: "¿Está seguro?",
      text: "¿Desea anular este vehículo?",
      type: "warning",
      showCancelButton: true,
    },
    function (isConfirm) {
      if (isConfirm) {
        $.post(
          "",
          { anularVehiculoAjax: true, Veh_Cod: Veh_Cod },
          function (r) {
            if (r.success) {
              mostrarAlertaUI(
                "Éxito",
                "Vehículo anulado correctamente",
                "success",
                function () {
                  actualizarGridVehiculos();
                },
              );
            } else {
              mostrarAlertaUI(
                "Error",
                r.message || "No se pudo anular el vehículo",
                "error",
              );
            }
          },
          "json",
        );
      }
    },
  );
}

function verQrVehiculo(Veh_Cod, Veh_Pla) {
  $("#qrVehiculoTitulo").text("Placa: " + Veh_Pla);
  var qrUrl =
    "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" +
    encodeURIComponent(Veh_Pla);
  $("#qrVehiculoImg").attr("src", qrUrl);
  $("#qrVehiculoDialog").dialog("open");
}

function setfocus(elem) {
  if (elem) elem.focus();
}
