/** Presupuesto proyectos - import Excel/PDF. */
/* global jQuery, $, API, toast, PPTO_IMPORT_MAX_RUBROS */

function parsePdfFile() {
  var fileInput = document.getElementById('pdf_import_file');
  if (!fileInput || !fileInput.files || !fileInput.files.length) {
    toast('Seleccione un archivo PDF, Excel o CSV.', false);
    return;
  }
  requireProyectoYPpe(function(proy, ppe) {
  var fd = new FormData();
  fd.append('action', 'parse_pdf');
  fd.append('pdf_file', fileInput.files[0]);
  fd.append('Pro_Cod', proy);
  fd.append('Ppe_Cod', ppe);

  $('#pdf_import_status').text('Analizando archivo...');
  $.ajax({
    url: API,
    type: 'POST',
    data: fd,
    processData: false,
    contentType: false,
    dataType: 'json',
    timeout: (typeof PPTO_AJAX_TIMEOUT_MS !== 'undefined') ? PPTO_AJAX_TIMEOUT_MS : 90000,
    success: function(r) {
      if (r.status !== 'success') {
        $('#pdf_import_status').text('');
        toast(r.message || 'No se pudo analizar el archivo.', false);
        return;
      }
      var nRubrosChk = (r.rubros && r.rubros.length) ? r.rubros.length : 0;
      var maxRub = (typeof PPTO_IMPORT_MAX_RUBROS !== 'undefined') ? PPTO_IMPORT_MAX_RUBROS : 2000;
      if (nRubrosChk > maxRub) {
        $('#pdf_import_status').text('');
        toast('El archivo tiene ' + nRubrosChk + ' rubros (maximo ' + maxRub + '). Divida el Excel e importe por partes.', false);
        return;
      }
      pdfImportPayload = r.payload || null;
      var tonCosto = parseFloat(r.ton_costo_mes) || 0;
      if (tonCosto <= 0 && parseFloat(r.ton_detectada) > 0) {
        var det = parseFloat(r.ton_detectada) || 0;
        tonCosto = (det >= 70000 && det < 95000) ? det : pdfPreviewTonMensCalc(RUBRO_EDIT_TN_DIA, PDF_PREVIEW_DIAS_FIJO);
      }
      if (tonCosto <= 0) {
        tonCosto = pdfPreviewTonMensCalc(RUBRO_EDIT_TN_DIA, PDF_PREVIEW_DIAS_FIJO);
      }
      pdfImportPreviewTon = tonCosto;
      $('#pv_toneladas_costo_mes').val(tonCosto);
      var tonIngresoSrv = tonBaseVersionMes(parseFloat(r.ton_ingreso_mes) || parseFloat(r.ton_base) || 0);
      var tonIngresoActual = parseFloat($('#pv_toneladas_base_mes').val()) || tonBaseProy || 0;
      if (tonIngresoActual <= 0 && tonIngresoSrv > 0) {
        $('#pv_toneladas_base_mes').val(tonIngresoSrv);
        tonBaseProy = tonIngresoSrv;
        tonIngresoActual = tonIngresoSrv;
      } else if (tonIngresoActual > 0) {
        tonBaseProy = tonIngresoActual;
      }
      recalcPdfPreviewAnuales();
      var resumen = 'Archivo: ' + (r.archivo || '')
        + ' | Partidas: ' + (r.partidas ? r.partidas.length : 0)
        + ' | Rubros driver: ' + (r.rubros ? r.rubros.length : 0)
        + ' | Ton/mes costo (egreso): ' + formatNumber(tonCosto, 0)
        + ' | Ton ingresos (mes): ' + formatNumber(tonIngresoActual > 0 ? tonIngresoActual : tonIngresoSrv, 0);
      $('#pdf_preview_resumen').text(resumen);
      var cr = r.catalogo_resumen || {};
      var nEx = parseInt(cr.existentes, 10) || 0;
      var nNue = parseInt(cr.nuevas, 10) || 0;
      var nNom = parseInt(cr.nombre_distinto, 10) || 0;
      window.pdfCatalogoResumen = { existentes: nEx, nuevas: nNue, nombre_distinto: nNom };
      if (nEx || nNue) {
        var avi = '<strong>Catalogo compartido:</strong> revise las opciones abajo antes de confirmar. '
          + 'Los montos siempre se cargan solo en este proyecto.';
        $('#pdf_preview_catalogo_aviso').html(avi).show();
        $('#pdf_dec_existentes').text(nEx);
        $('#pdf_dec_nuevas').text(nNue);
        $('#pdf_dec_nombre').text(nNom);
        $('#pdf_opt_crear_nuevas').prop('checked', true);
        $('#pdf_opt_actualizar_nombres').prop('checked', false);
        $('#pdf_preview_decision').show();
      } else {
        $('#pdf_preview_catalogo_aviso').hide();
        $('#pdf_preview_decision').hide();
      }
      if (r.warnings && r.warnings.length) {
        $('#pdf_preview_warnings').text(r.warnings.join(' ')).show();
      } else {
        $('#pdf_preview_warnings').hide();
      }
      renderPdfPreview(r);
      actualizarPdfImportUi(r.conflictos, r.import_bloqueado);
      $('#btnImportPdf').show();
      if (!r.import_bloqueado) {
        $('#pdf_import_status').text('Listo para importar.');
      }
      $('#modalPdfPreview').show();
    },
    error: function(xhr, status) {
      $('#pdf_import_status').text('');
      var msg = 'Error al analizar el archivo.';
      if (status === 'timeout') {
        msg = 'El analisis tardo demasiado. Pruebe un Excel mas pequeno o PDF mas liviano.';
      } else if (xhr && xhr.responseText) {
        try {
          var j = JSON.parse(xhr.responseText);
          if (j && j.message) {
            msg = j.message;
          }
        } catch (e) {
          if (xhr.status === 403) {
            msg = 'Sin permiso. Recargue la sesion e intente de nuevo.';
          } else if (xhr.status === 500) {
            msg = 'Error interno al leer el archivo (muy grande o formato no soportado).';
          }
        }
      }
      toast(msg, false);
    }
  });
  }, 'Seleccione un proyecto antes de analizar el archivo.');
}

function importPdfPayload() {
  if (!pdfImportPayload) {
    toast('Primero analice un PDF.', false);
    return;
  }
  if (pdfImportConflictos && pdfImportConflictos.length) {
    toast('Corrija los conflictos de Grupo/Detalle en Admin antes de importar.', false);
    return;
  }
  requireProyectoYPpe(function(proy, ppe) {
  var ton = $('#pv_toneladas_base_mes').val();
  var cr = window.pdfCatalogoResumen || {};
  var nEx = parseInt(cr.existentes, 10) || 0;
  var nNue = parseInt(cr.nuevas, 10) || 0;
  var nNom = parseInt(cr.nombre_distinto, 10) || 0;
  var crearNuevas = $('#pdf_opt_crear_nuevas').length ? ($('#pdf_opt_crear_nuevas').is(':checked') ? 1 : 0) : 1;
  var actNombres = $('#pdf_opt_actualizar_nombres').is(':checked') ? 1 : 0;
  if (!crearNuevas && nNue > 0 && nEx === 0) {
    toast('No hay partidas existentes para reutilizar y desactivo crear nuevas. Active "Crear partidas nuevas" o use codigos del catalogo.', false);
    return;
  }
  var msg = 'Confirmar importacion en este proyecto:\n'
    + '- Reutilizar ' + nEx + ' partida(s) existente(s)\n'
    + '- ' + (crearNuevas ? ('Crear ' + nNue + ' partida(s) nueva(s)') : ('Omitir ' + nNue + ' partida(s) nueva(s)')) + '\n'
    + '- Actualizar nombres del catalogo: ' + (actNombres ? 'SI (afecta a todos los proyectos)' : 'NO') + '\n'
    + (nNom && !actNombres ? '- ' + nNom + ' con nombre distinto: se conserva el del catalogo\n' : '')
    + '- Montos solo en el proyecto seleccionado\n\nContinuar?';
  if (!confirm(msg)) {
    return;
  }

  syncPdfPreviewToPayload();

  $.ajax({
    url: API,
    type: 'POST',
    data: {
      action: 'import_pdf',
      Pro_Cod: proy,
      Ppe_Cod: ppe,
      pv_toneladas_base_mes: ton,
      crear_nuevas: crearNuevas,
      actualizar_nombres: actNombres,
      payload_json: JSON.stringify(pdfImportPayload)
    },
    dataType: 'json',
    timeout: (typeof PPTO_AJAX_TIMEOUT_MS !== 'undefined') ? PPTO_AJAX_TIMEOUT_MS : 90000,
    success: function(r) {
      if (r.status === 'success') {
        toast(r.message, true);
        $('#modalPdfPreview').hide();
        $('#pdf_preview_decision').hide();
        pdfImportPayload = null;
        window.pdfCatalogoResumen = null;
        $('#btnImportPdf').hide();
        $('#pdf_import_status').text('');
        if (r.pv_toneladas_base_mes) {
          tonBaseProy = tonBaseVersionMes(parseFloat(r.pv_toneladas_base_mes) || tonBaseProy);
          $('#pv_toneladas_base_mes').val(tonBaseProy);
        }
        if (r.stats && r.stats.ton_costo_mes) {
          $('#pv_toneladas_costo_mes').val(parseFloat(r.stats.ton_costo_mes));
        }
        loadCatalogos(function() {
          reloadRubrosSection();
        });
      } else {
        toast(r.message || 'Error al importar.', false);
        if (r.conflictos && r.conflictos.length) {
          actualizarPdfImportUi(r.conflictos, true);
        }
      }
    },
    error: function() {
      toast('Error de red al importar el PDF.', false);
    }
  });
  }, 'Seleccione un proyecto antes de importar.');
}

if (window.__pptoImportHandlersBound) {
  /* script ya enlazado: no re-bind */
} else {
window.__pptoImportHandlersBound = true;

$('#btnSaveProy').click(function(){

  $.post(API, {
    action:'save',
    is_edit:$('#is_edit').val(),
    proy_id:$('#proy_id').val(),
    Pro_Cod:$('#Pro_Cod').val(),
    Pro_Nom:$('#Pro_Nom').val(),
    Pro_Est:$('#Pro_Est').val(),
    Plt_Cod:$('#Plt_Cod').val()
  }, function(r){

    toast(r.message, r.status==='success');
    if(r.status==='success'){
      loadProyectos();
      $('#is_edit').val(0);
      $('#proy_id').val('');
      $('#Pro_Cod').prop('readonly', false);
    }

  }, 'json');

});

$('#btnSaveTonBase').click(function(){

  requireProyectoYPpe(function() {

  $.post(API, versionTonPayload(0), function(r){

    toast(r.message, r.status==='success');

    if (r.status==='success') {
      if (r.pv_toneladas_base_mes) {
        tonBaseProy = tonBaseVersionMes(parseFloat(r.pv_toneladas_base_mes) || tonBaseProy);
        $('#pv_toneladas_base_mes').val(tonBaseProy);
      }
      if (r.pv_toneladas_costo_mes) {
        $('#pv_toneladas_costo_mes').val(parseFloat(r.pv_toneladas_costo_mes));
      }
      reloadRubrosSection();
    }

  }, 'json');
  }, 'Seleccione un proyecto.');

});

$('#btnAplicarTonRubros').click(function(){

  requireProyectoYPpe(function() {

  var tonCosto = $.trim($('#pv_toneladas_costo_mes').val());

  if (!tonCosto || parseFloat(tonCosto) <= 0) {
    toast('Ingrese ton costo egreso (mes), ej. 77000.', false);
    return;
  }

  if (!confirm('Aplicar ' + formatNumber(parseFloat(tonCosto), 0) + ' ton/mes (costo egreso) a todos los rubros?\n\n'
    + '$/Ton anual y mensual quedan fijos (como a 77.000).\n'
    + 'Se recalcula el presupuesto Base PDF (anual = ton x $/Ton).\n'
    + 'Proyectada y Real siguen con el presupuesto anual original del Excel.')) return;

  $.post(API, versionTonPayload(1), function(r){

    toast(r.message, r.status==='success');

    if (r.status==='success') {
      if (r.pv_toneladas_base_mes) {
        tonBaseProy = tonBaseVersionMes(parseFloat(r.pv_toneladas_base_mes) || tonBaseProy);
        $('#pv_toneladas_base_mes').val(tonBaseProy);
      }
      if (r.pv_toneladas_costo_mes) {
        $('#pv_toneladas_costo_mes').val(parseFloat(r.pv_toneladas_costo_mes) || tonCosto);
      }
      reloadRubrosSection();
    }

  }, 'json');
  }, 'Seleccione un proyecto.');

});

$('#btnAbrirAgregarRubro').click(abrirModalAgregarRubro);

$(document).on('click','.btnEdit', function(){

  var p=JSON.parse($(this).attr('data-json'));

  $('#is_edit').val(1);
  $('#proy_id').val(p.proy_id || p.Pro_Cod);
  $('#Pro_Cod').val(p.Pro_Codigo || p.proy_codigo || '').prop('readonly',false);

  $('#Pro_Nom').val(p.Pro_Nom); $('#Pro_Est').val(p.Pro_Est); $('#Plt_Cod').val(p.Plt_Cod||'');

});

$(document).on('click','.btn-edit-rubro', function(){
  var x = JSON.parse($(this).attr('data-json'));
  abrirModalEditRubro(x);
});

$(document).on('click', '.btn-del-rubro', function() {
  var x = JSON.parse($(this).attr('data-json'));
  requireProyectoYPpe(function(proy, ppe) {
  var etiqueta = (x.Ppa_Cla || '') + ' - ' + (x.Pdp_Rubro || 'rubro');
  if (!confirm('Eliminar el rubro "' + etiqueta + '" de este proyecto?\n\nEsta accion no se puede deshacer.')) {
    return;
  }
  $.post(API, {
    action: 'delete_rubro',
    Pdp_Cod: x.Pdp_Cod,
    Pro_Cod: proy,
    Ppe_Cod: ppe
  }, function(r) {
    if (r.status !== 'success') {
      toast(r.message || 'No se pudo eliminar el rubro.', false);
      return;
    }
    if (modalEditRubroCache && modalEditRubroCache.Pdp_Cod == x.Pdp_Cod) {
      cerrarModalRubro();
    }
    toast(r.message || 'Rubro eliminado.', true);
    loadRubros();
  }, 'json').fail(function() {
    toast('Error de red al eliminar el rubro.', false);
  });
  }, 'Seleccione un proyecto.');
});

$('#btnNuevaVersion').click(function(){ crearNuevaVersion(); });

/* Cambio proyecto/vista/mes/anio: handlers en ppto_proyectos_rubros.js (carga inmediata). */

$('#pv_toneladas_base_mes').on('input', function(){
  tonBaseProy = tonBaseVersionMes($(this).val());
  recalcPdfPreviewAnuales();
  refreshVistaPresupuesto();
});

$('#pv_toneladas_costo_mes').on('input change', function() {
  refreshVistaPresupuesto();
});

$('#btnExportCuadroExcel').click(function() {
  requireProyectoYPpe(function(proy, ppe) {
  var qs = $.param({
    Pro_Cod: proy,
    Ppe_Cod: ppe,
    cuadro_vista: cuadroVista || 'anual',
    cuadro_mes: cuadroMes || '',
    escenario: escenarioActivo || 'esperada',
    anio_precio: cuadroAnioPrecio || $('#cuadro_anio_precio').val() || ''
  });
  window.open('ppto_proyectos_cuadro_export.php?' + qs, '_blank');
  }, 'Seleccione un proyecto antes de exportar.');
});

$('#btnAjGuardarCfg').click(function() {
  requireProyectoYPpe(function(proy, ppe) {
  $.post(API, {
    action: 'ajuste_cfg_save',
    Pro_Cod: proy,
    Ppe_Cod: ppe,
    costo_capital_pct: $('#aj_capital_pct').val(),
    gad_factor_ton: $('#aj_gad_factor').val(),
    gad_monto_objetivo: pptoParseNumber($('#aj_gad_objetivo').val()),
    gad_recuperado_acum: pptoParseNumber($('#aj_gad_acum').val()),
    ajuste_activo: $('#aj_activo').is(':checked') ? 1 : 0
  }, function(r) {
    toast(r.message || '', r.status === 'success');
    if (r.status === 'success' && r.cfg) {
      fillAjusteCfgForm(r.cfg);
      loadRubros();
    }
  }, 'json');
  }, 'Seleccione un proyecto.');
});

$('#btnAjSimular').click(function() { simularAjuste(false); });
$('#btnAjAplicar').click(function() { simularAjuste(true); });

/* === CUADRO_PARTIDA_FINAL_UI (reversible) START === */
$('#aj_activo').on('change', function() {
  if (ajusteCfgCache) {
    ajusteCfgCache.ajuste_activo = $(this).is(':checked') ? 1 : 0;
  }
  renderCuadroRubros(rubrosCache, gruposTopeCache);
  actualizarResumenEconomico(rubrosCache);
});
/* === CUADRO_PARTIDA_FINAL_UI (reversible) END === */

$('#btnAjPrecios').click(function() {
  requireProyectoYPpe(function(proy, ppe) {
  $.getJSON(API, { action: 'ajuste_precios_list', Pro_Cod: proy, Ppe_Cod: ppe }, function(r) {
    ajustePreciosCache = (r.status === 'success') ? (r.precios || []) : [];
    renderPreciosRows(ajustePreciosCache);
    $('#modalAjustePrecios').show();
  });
  }, 'Seleccione un proyecto.');
});

$('#btnAjAddPrecio').click(function() {
  var anio = (new Date()).getFullYear();
  var last = $('#tblAjustePrecios .aj-precio-anio').last().val();
  if (last) anio = parseInt(last, 10) + 1;
  if ($('#tblAjustePrecios tbody tr td.text-muted').length) {
    $('#tblAjustePrecios tbody').empty();
  }
  $('#tblAjustePrecios tbody').append(
    '<tr><td><input type="number" class="form-control input-sm aj-precio-anio" value="' + anio + '" /></td>'
    + '<td><input type="number" step="0.0001" class="form-control input-sm aj-precio-tarifa" value="3" /></td>'
    + '<td><button type="button" class="btn btn-default btn-xs btn-aj-del-precio">&times;</button></td></tr>'
  );
});

$('#btnAjSeedPrecios').click(function() {
  var base = parseInt($('#aj_anio_precio').val(), 10) || (new Date()).getFullYear();
  var seed = [
    { anio: base, tarifa_ton_iva: 3 },
    { anio: base + 1, tarifa_ton_iva: 3 },
    { anio: base + 2, tarifa_ton_iva: 3.25 },
    { anio: base + 3, tarifa_ton_iva: 3.25 },
    { anio: base + 4, tarifa_ton_iva: 3.25 },
    { anio: base + 5, tarifa_ton_iva: 3.5 },
    { anio: base + 6, tarifa_ton_iva: 3.5 },
    { anio: base + 7, tarifa_ton_iva: 3.75 }
  ];
  renderPreciosRows(seed);
});

$(document).on('click', '.btn-aj-del-precio', function() {
  $(this).closest('tr').remove();
});

$('#btnSaveAjustePrecios').click(function() {
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  $.post(API, {
    action: 'ajuste_precios_save',
    Pro_Cod: proy,
    Ppe_Cod: ppe,
    precios_json: JSON.stringify(collectPreciosFromModal())
  }, function(r) {
    toast(r.message || '', r.status === 'success');
    if (r.status === 'success') {
      ajustePreciosCache = r.precios || [];
      $('#modalAjustePrecios').hide();
      loadRubros();
    }
  }, 'json');
});

$('#btnCloseAjustePrecios, #btnCancelAjustePrecios').click(function() {
  $('#modalAjustePrecios').hide();
});

$('#btnAjHistorial').click(function() {
  requireProyectoYPpe(function(proy, ppe) {
  $.getJSON(API, { action: 'ajuste_historial', Pro_Cod: proy, Ppe_Cod: ppe }, function(r) {
    var $tb = $('#tblAjusteHistorial tbody').empty();
    if (r.status !== 'success' || !(r.rows || []).length) {
      $tb.append('<tr><td colspan="9" class="text-muted">Sin aplicaciones registradas.</td></tr>');
    } else {
      $.each(r.rows, function(i, x) {
        var usu = x.usuario_nombre || x.Usu_Nom || ('Usuario ' + (x.Usu_Cod || '—'));
        $tb.append(
          '<tr>'
          + '<td>' + x.ajc_id + '</td>'
          + '<td>' + (x.ajc_fecha_registro || '') + '</td>'
          + '<td>' + usu + '</td>'
          + '<td>' + (x.ajc_escenario || '') + '</td>'
          + '<td>' + (x.ajc_vista || '') + '</td>'
          + '<td class="text-right">' + formatCurrency(x.ajc_capital_total) + '</td>'
          + '<td class="text-right">' + formatCurrency(x.ajc_gad_aplicado) + '</td>'
          + '<td class="text-right">' + formatCurrency(x.ajc_gad_acum_despues) + '</td>'
          + '<td class="text-right">' + formatCurrency(x.ajc_gad_saldo_despues) + '</td>'
          + '</tr>'
        );
      });
    }
    $('#modalAjusteHistorial').show();
  });
  }, 'Seleccione un proyecto.');
});

$('#btnCloseAjusteHistorial, #btnCancelAjusteHistorial').click(function() {
  $('#modalAjusteHistorial').hide();
});

$('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
  var target = $(e.target).attr('href') || '';
  if (target === '#tabCuadro' || target === '#tabRubrosTon') {
    if (typeof refreshVistaPresupuesto === 'function') {
      refreshVistaPresupuesto();
    }
  }
});

$(document).on('input', '.pdf-preview-factor', function() {
  recalcPdfPreviewFila($(this).closest('tr'));
  recalcPdfPreviewTotales();
});

$(document).on('input change', '#pdf_preview_meses_global', function() {
  aplicarPdfPreviewMesesGlobal($(this).val());
});

$('#rub_grupo_cod').change(function() {
  fillSubgruposRubro();
  fillDetallesRubro();
  actualizarModalRubroMesesDesdePartida();
  if ($('#modalEditRubro').is(':visible')) calcModalEditRubroPreview();
});

$('#rub_subgrupo_cod').change(function() {
  fillDetallesRubro();
  actualizarModalRubroMesesDesdePartida();
  if ($('#modalEditRubro').is(':visible')) calcModalEditRubroPreview();
});

$('#rub_ppa_id').change(function(){
  updateRubroPartidaResumen();
});

$('#btnCloseEditRubroModal, #btnCancelEditRubroModal').click(cerrarModalRubro);
$('#btnSaveEditRubroModal').click(guardarModalEditRubro);
$('#modal_edit_factor_anual, #modal_edit_meses').on('input change', calcModalEditRubroPreview);

$(document).on('mousedown click focusin', '.grupo-col-pct, .grupo-col-tope, .btn-grupo-resumen', function(e) {
  e.stopPropagation();
});

$(document).on('click', '.btn-grupo-resumen', function(e) {
  e.preventDefault();
  e.stopPropagation();
  abrirGrupoResumen($(this).attr('data-grupo-cod'));
});

$(document).on('click', '.cuadro-grupo-toggle', function(e) {
  e.preventDefault();
  e.stopPropagation();
  var target = $(this).data('target');
  if (!target) return;
  $(target).collapse('toggle');
});

$('#rubrosCuadroAccordion').on('shown.bs.collapse hidden.bs.collapse', function(e) {
  var $heading = $(e.target).prev('.cuadro-grupo-heading');
  $heading.toggleClass('is-open', e.type === 'shown');
});

$(document).on('click', '.btn-save-grupo-meses', function() {
  var ppaId = parseInt($(this).data('ppa-id'), 10);
  var $input = $(this).siblings('.grupo-meses-edit');
  if (!ppaId || !$input.length) return;
  saveGrupoMeses(ppaId, $input);
});

$(document).on('click', '.btn-save-grupo-pct', function() {
  var ppaId = parseInt($(this).data('ppa-id'), 10);
  var $input = $(this).siblings('.grupo-pct-edit');
  if (!ppaId || !$input.length) return;
  saveGrupoPct(ppaId, $input);
});

$('#btnParsePdf').off('click.pptoImp').on('click.pptoImp', parsePdfFile);

$(document).on('click', '.btn-add-partida', function() {
  abrirModalPartidaRubro($(this).data('partida-tipo'));
});

$('#btnSavePartidaRubroModal').click(function() {
  var codigo = $.trim($('#modal_partida_rubro_codigo').val());
  var descripcion = $.trim($('#modal_partida_rubro_descripcion').val());
  if (!codigo || !descripcion) {
    toast('Ingrese codigo y descripcion de la partida.', false);
    return;
  }
  $.post(API, {
    action: 'save_partida_catalogo',
    Ppa_Cla: codigo,
    Ppa_Des: descripcion,
    Ppa_Clase: $('#modal_partida_rubro_clase').val(),
    Ppa_Pad: $('#modal_partida_rubro_padre_id').val(),
    Ppa_Tip: 'G',
    Ppa_Nat: 'OPE'
  }, function(r) {
    if (r.status !== 'success') {
      toast(r.message || 'No se pudo crear la partida.', false);
      return;
    }
    toast(r.message || 'Partida creada.', true);
    cerrarModalPartidaRubro();
    reloadCatalogosPartidas(function() {
      aplicarPartidaCreada(r.partida);
    });
  }, 'json').fail(function() {
    toast('Error de red al crear la partida.', false);
  });
});

$('#btnClosePartidaRubroModal, #btnCancelPartidaRubroModal').click(cerrarModalPartidaRubro);

$('#btnImportPdf, #btnConfirmImportPdf').off('click.pptoImp').on('click.pptoImp', importPdfPayload);

$(document).on('click', '.esc-btn', function() { setEscenario($(this).data('esc')); });

$('#btnPreviewPublicar').click(previewPublicar);
$('#btnPublicarAprobado').click(function() {
  if (publicarPreviewCache) {
    $('#modalPublicarPreview').show();
    return;
  }
  previewPublicar();
});
$('#btnConfirmPublicar').click(function() { ejecutarPublicar(false); });
$('#btnClosePublicarModal, #btnCancelPublicarModal').click(function() {
  $('#modalPublicarPreview').hide();
});


$('#btnClosePdfModal, #btnCancelPdfModal').click(function() {

  $('#modalPdfPreview').hide();

});

$('#btnCloseGrupoResumenModal').click(function() {

  $('#modalGrupoResumen').hide();

});

} /* fin guard __pptoImportHandlersBound */

/* Init de pagina vive en ppto_proyectos_rubros.js para permitir carga diferida de este archivo. */

