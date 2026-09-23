/** Presupuesto proyectos - rubros y cuadro. */
/* global jQuery, $, API, toast, loadVersionConfig */

function loadRubros(modoCompleto){
  if (typeof modoCompleto === 'undefined') {
    modoCompleto = (pptoTabActiva() === '#tabCuadro');
  }

  var proy=$('#rub_proy_id').val(); if(!proy) return;

  $.getJSON(API, loadRubrosParams(modoCompleto), function(r){

    var esCompleta = (r.modo !== 'simple') && modoCompleto;

    if (!r.rows || !r.rows.length) {
      rubrosCache = [];
      gruposTopeCache = {};
      renderTablaRubros([]);
      if (esCompleta) {
        escenariosIngreso = { esperada: 0, proyectada: 0, real: 0 };
        escenariosTonAnual = { esperada: 0, proyectada: 0, real: 0 };
        escenariosTonPeriodo = { esperada: 0, proyectada: 0, real: 0 };
        renderCuadroRubros([], {});
        actualizarBotonesEscenario([]);
        aplicarCuadroPeriodoResponse(r);
      }
      return;
    }

    rubrosCache = r.rows;
    renderTablaRubros(rubrosCache);

    if (!esCompleta) {
      return;
    }

    escenarioMesesReal = parseInt(r.escenarios_meses_con_real, 10) || 0;
    if (r.escenarios_ton_mes) {
      escenariosTonMes = {
        esperada: parseFloat(r.escenarios_ton_mes.esperada) || 0,
        proyectada: parseFloat(r.escenarios_ton_mes.proyectada) || 0,
        real: parseFloat(r.escenarios_ton_mes.real) || 0
      };
    }
    if (r.escenarios_ton_anual) {
      escenariosTonAnual = {
        esperada: parseFloat(r.escenarios_ton_anual.esperada) || 0,
        proyectada: parseFloat(r.escenarios_ton_anual.proyectada) || 0,
        real: parseFloat(r.escenarios_ton_anual.real) || 0
      };
    }
    if (r.escenarios_ingreso) {
      escenariosIngreso = {
        esperada: parseFloat(r.escenarios_ingreso.esperada) || 0,
        proyectada: parseFloat(r.escenarios_ingreso.proyectada) || 0,
        real: parseFloat(r.escenarios_ingreso.real) || 0
      };
    }
    if (r.ingreso_cfg) {
      ingresoCfg.tarifa = parseFloat(r.ingreso_cfg.tarifa_ton_iva) || 3;
      ingresoCfg.iva = parseFloat(r.ingreso_cfg.iva_divisor) || 1.15;
      ingresoCfg.factor_precio_gasto = parseFloat(r.ingreso_cfg.factor_precio_gasto) || 1;
      ingresoCfg.anio_base = r.ingreso_cfg.anio_base || null;
      $('#escTarifaLbl').text(formatNumber(ingresoCfg.tarifa, 4));
      $('#escIvaLbl').text(formatNumber(ingresoCfg.iva, 2));
    }
    populateCuadroAnioSelect(
      r.precios_proyeccion || ajustePreciosCache || [],
      r.anio_proyeccion || (r.ingreso_cfg && r.ingreso_cfg.anio),
      r.escenarios_anio
    );
    updateCuadroPrecioLbl(r.ingreso_cfg, r.precio_anio);
    if (r.ajuste_cfg) {
      fillAjusteCfgForm(r.ajuste_cfg);
    }
    if (r.ajuste_financiero_escenarios) {
      ajusteSimByEsc = r.ajuste_financiero_escenarios;
    }
    // Siempre mostrar calculo del escenario activo (no el del ultimo Aplicar).
    var simEsc = (ajusteSimByEsc && ajusteSimByEsc[escenarioActivo])
      ? ajusteSimByEsc[escenarioActivo]
      : r.ajuste_financiero;
    if (simEsc) {
      renderAjusteSim(simEsc);
    }
    aplicarCuadroPeriodoResponse(r);
    renderCuadroRubros(rubrosCache, r.grupos_tope || {});
    actualizarBotonesEscenario(rubrosCache);

  }).fail(function(xhr, status) {
    if (status === 'timeout') {
      toast('La carga de rubros tardo demasiado. Cambie de pestana e intente de nuevo.', false);
    } else {
      toast('Error al cargar rubros. Recargue la pagina (Ctrl+F5).', false);
    }
  });

}

function sumaEscenario(rows, esc) {
  var t = 0;
  $.each(rows || [], function(i, x) {
    t += rubroAnualPorEscenario(x, esc);
  });
  return t;
}

function actualizarBotonesEscenario(rows) {
  $('#escTotEsperada').text(formatCurrency(sumaEscenario(rows, 'esperada')));
  $('#escTotProyectada').text(formatCurrency(sumaEscenario(rows, 'proyectada')));
  $('#escTotReal').text(formatCurrency(sumaEscenario(rows, 'real')));
  var info = escenarioMesesReal > 0
    ? (escenarioMesesReal + ' mes(es) con real; el resto usa proyectada')
    : 'Sin meses con real aÃºn; "Real" usa proyectada';
  $('#escMesesRealInfo').text(info);
  $('.esc-btn').removeClass('active');
  $('.esc-btn[data-esc="' + escenarioActivo + '"]').addClass('active');
  actualizarResumenEconomico(rows);
}

function ajusteSimParaEscenario(esc) {
  if (ajusteSimByEsc && ajusteSimByEsc[esc] && ajusteSimByEsc[esc].ok) {
    return ajusteSimByEsc[esc];
  }
  if (ajusteSimCache && ajusteSimCache.ok && ajusteSimCache.meta
      && ajusteSimCache.meta.escenario === esc) {
    return ajusteSimCache;
  }
  return null;
}

function actualizarResumenEconomico(rows) {
  var escs = ['esperada', 'proyectada', 'real'];
  var checkOn = $('#aj_activo').is(':checked')
    || (ajusteCfgCache && parseInt(ajusteCfgCache.ajuste_activo, 10) === 1);
  $.each(escs, function(i, esc) {
    var gastos = sumaEscenario(rows, esc);
    var ingresos = parseFloat(escenariosIngreso[esc]) || 0;
    var util = ingresos - gastos;
    var tonAn = (cuadroVista === 'anual')
      ? (parseFloat(escenariosTonAnual[esc]) || 0)
      : (parseFloat(escenariosTonPeriodo[esc]) || 0);
    if (checkOn) {
      var simEsc = ajusteSimParaEscenario(esc);
      if (simEsc && simEsc.resumen) {
        // Partida final en los 3 escenarios (no solo el seleccionado).
        gastos = parseFloat(simEsc.resumen.gasto_final) || gastos;
        util = ingresos - gastos;
      }
    }
    $('#escTonAn_' + esc).text(tonAn > 0 ? formatNumber(tonAn, 0) : '-');
    $('#escIng_' + esc).text(formatCurrency(ingresos));
    $('#escGas_' + esc).text(formatCurrency(gastos));
    var $u = $('#escUtil_' + esc);
    $u.text(formatCurrency(util)).removeClass('eco-pos eco-neg');
    if (ingresos > 0 || gastos > 0) {
      $u.addClass(util >= 0 ? 'eco-pos' : 'eco-neg');
    }
  });
  $('.esc-res-col').removeClass('active');
  $('.esc-res-col[data-esc="' + escenarioActivo + '"]').addClass('active');
}

function fillAjusteCfgForm(cfg) {
  if (!cfg) return;
  ajusteCfgCache = cfg;
  $('#aj_capital_pct').val(cfg.costo_capital_pct);
  $('#aj_gad_factor').val(cfg.gad_factor_ton);
  $('#aj_gad_objetivo').val(formatNumber(cfg.gad_monto_objetivo, 2));
  $('#aj_gad_acum').val(formatNumber(cfg.gad_recuperado_acum, 2));
  $('#aj_activo').prop('checked', parseInt(cfg.ajuste_activo, 10) === 1);
  if (cuadroAnioPrecio > 0) {
    $('#aj_anio_precio').val(cuadroAnioPrecio);
  } else if (!$('#aj_anio_precio').val()) {
    var anio = (ajusteSimCache && ajusteSimCache.meta && ajusteSimCache.meta.anio)
      ? ajusteSimCache.meta.anio
      : (new Date()).getFullYear();
    $('#aj_anio_precio').val(anio);
  }
}

function populateCuadroAnioSelect(precios, anioActivo, anioVersion) {
  var $sel = $('#cuadro_anio_precio');
  var prev = parseInt($sel.val(), 10) || cuadroAnioPrecio || 0;
  var map = {};
  var years = [];
  $.each(precios || [], function(i, p) {
    var a = parseInt(p.anio, 10);
    if (a > 0 && !map[a]) {
      map[a] = parseFloat(p.tarifa_ton_iva) || 0;
      years.push(a);
    }
  });
  var base = parseInt(anioVersion, 10) || (new Date()).getFullYear();
  if (years.indexOf(base) < 0) {
    years.push(base);
    if (!map[base]) map[base] = parseFloat(ingresoCfg.tarifa) || 3;
  }
  // Rango de apoyo: base .. base+7 si no hay precios
  if (years.length <= 1) {
    for (var y = base; y <= base + 7; y++) {
      if (years.indexOf(y) < 0) years.push(y);
    }
  }
  years.sort(function(a, b) { return a - b; });
  var want = parseInt(anioActivo, 10) || prev || base;
  $sel.empty();
  $.each(years, function(i, a) {
    var t = map[a] ? (' - $' + formatNumber(map[a], 2)) : '';
    $sel.append('<option value="' + a + '">' + a + t + '</option>');
  });
  if ($sel.find('option[value="' + want + '"]').length) {
    $sel.val(String(want));
  } else {
    $sel.val(String(years[0]));
  }
  cuadroAnioPrecio = parseInt($sel.val(), 10) || base;
  $('#aj_anio_precio').val(cuadroAnioPrecio);
}

function updateCuadroPrecioLbl(ingCfg, precioAnio) {
  if (!ingCfg) {
    $('#cuadroPrecioAnioLbl').text('');
    return;
  }
  var tarifa = parseFloat(ingCfg.tarifa_ton_iva) || 0;
  $('#cuadroPrecioAnioLbl').html('<strong>$' + formatNumber(tarifa, 2) + '</strong>/t');
  if ($('#escTarifaLbl').length) {
    $('#escTarifaLbl').text(formatNumber(tarifa, 4));
  }
}

function renderAjusteSim(sim) {
  ajusteSimCache = sim || null;
  if (sim && sim.ok && sim.meta && sim.meta.escenario) {
    if (!ajusteSimByEsc) ajusteSimByEsc = {};
    ajusteSimByEsc[sim.meta.escenario] = sim;
  }
  var $tb = $('#tblAjusteDist tbody').empty();
  var $tf = $('#tblAjusteDistFoot').empty();
  var $tbTon = $('#tblAjusteDistTon tbody').empty();
  if (!sim || !sim.ok) {
    $('#ajKpiNeto,#ajKpiCapTon,#ajKpiCapTot,#ajKpiGad,#ajKpiSaldo,#ajKpiGastoFin,#ajKpiUtil,#ajKpiRestaTot').text('-');
    if ($('#ajGadTimeline').length) {
      $('#ajGadTimeline').html('Tramos: a&ntilde;os 1-4 y 5-8');
    }
    return;
  }
  var capTot = parseFloat(sim.capital.total) || 0;
  var gadTot = parseFloat(sim.gad.aplicado) || 0;
  var restaTot = capTot + gadTot;
  $('#ajKpiNeto').text(formatNumber(sim.precio.precio_neto, 4));
  $('#ajKpiCapTon').text(formatNumber(sim.capital.por_ton, 4));
  $('#ajKpiCapTot').text(formatCurrency(capTot));
  $('#ajKpiGad').text(formatCurrency(gadTot) + (sim.gad.agotado ? ' (agotado)' : ''));
  $('#ajKpiSaldo').text(formatCurrency(sim.gad.saldo_despues));
  $('#ajKpiGastoFin').text(formatCurrency(sim.resumen.gasto_final));
  $('#ajKpiUtil').text(formatCurrency(sim.resumen.utilidad_base));
  $('#ajKpiRestaTot').text(formatCurrency(restaTot));
  if ($('#ajGadTimeline').length && sim.gad) {
    var obj = parseFloat(sim.gad.monto_objetivo) || 0;
    var acum = parseFloat(sim.gad.recuperado_acum) || 0;
    var saldo = parseFloat(sim.gad.saldo_despues) || 0;
    var periodo = gadTot;
    $('#ajGadTimeline').html(
      'Compromiso ' + formatCurrency(obj)
      + ' | Amort. ' + formatCurrency(acum)
      + ' | Cuota ' + formatCurrency(periodo)
      + ' | Saldo ' + formatCurrency(saldo)
    );
  }
  var sumBase = 0;
  var sumCap = 0;
  var sumGad = 0;
  var sumFinal = 0;
  $.each(sim.detalle || [], function(i, d) {
    var base = parseFloat(d.partida_base) || 0;
    var capM = parseFloat(d.capital_monto) || 0;
    var gadM = parseFloat(d.gad_monto) || 0;
    var fin = parseFloat(d.partida_final) || 0;
    sumBase += base;
    sumCap += capM;
    sumGad += gadM;
    sumFinal += fin;
    $tb.append(
      '<tr>'
      + '<td><strong>' + d.grupo_cod + '</strong> ' + (d.grupo_nombre || '') + '</td>'
      + '<td class="text-right">' + formatCurrency(base) + '</td>'
      + '<td class="text-right">' + formatNumber(d.participacion_pct, 2) + '%</td>'
      + '<td class="text-right col-menos">' + formatCurrency(capM) + '</td>'
      + '<td class="text-right col-menos">' + formatCurrency(gadM) + '</td>'
      + '<td class="text-right"><strong>' + formatCurrency(fin) + '</strong></td>'
      + '</tr>'
    );
    $tbTon.append(
      '<tr>'
      + '<td><strong>' + d.grupo_cod + '</strong></td>'
      + '<td class="text-right">' + formatNumber(d.base_por_ton, 4) + '</td>'
      + '<td class="text-right">' + formatNumber(d.capital_por_ton, 4) + '</td>'
      + '<td class="text-right">' + formatNumber(d.gad_por_ton, 4) + '</td>'
      + '<td class="text-right"><strong>' + formatNumber(d.final_por_ton, 4) + '</strong></td>'
      + '</tr>'
    );
  });
  if ((sim.detalle || []).length) {
    $tf.html(
      '<tr>'
      + '<td>Totales</td>'
      + '<td class="text-right">' + formatCurrency(sumBase) + '</td>'
      + '<td class="text-right">100%</td>'
      + '<td class="text-right col-menos">' + formatCurrency(sumCap) + '</td>'
      + '<td class="text-right col-menos">' + formatCurrency(sumGad) + '</td>'
      + '<td class="text-right">' + formatCurrency(sumFinal) + '</td>'
      + '</tr>'
    );
  }
  // === CUADRO_PARTIDA_FINAL_UI (reversible) ===
  if (cuadroUsaPartidaFinal()) {
    renderCuadroRubros(rubrosCache, gruposTopeCache);
  }
  // === end ===
}

function ajusteOptsFromForm() {
  return {
    Pro_Cod: $('#rub_proy_id').val(),
    Ppe_Cod: $('#rub_ppe_id').val(),
    cuadro_vista: cuadroVista || 'anual',
    cuadro_mes: cuadroMes || '',
    escenario: escenarioActivo || 'esperada',
    costo_capital_pct: $('#aj_capital_pct').val(),
    gad_factor_ton: $('#aj_gad_factor').val(),
    gad_monto_objetivo: pptoParseNumber($('#aj_gad_objetivo').val()),
    gad_recuperado_acum: pptoParseNumber($('#aj_gad_acum').val()),
    anio: $('#aj_anio_precio').val()
  };
}

function loadAjusteCfg(cb) {
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  if (!proy || !ppe) {
    if (cb) cb();
    return;
  }
  $.getJSON(API, { action: 'ajuste_cfg_get', Pro_Cod: proy, Ppe_Cod: ppe }, function(r) {
    if (r.status === 'success') {
      fillAjusteCfgForm(r.cfg);
      ajustePreciosCache = r.precios || [];
    }
    if (cb) cb();
  });
}

function simularAjuste(andApply) {
  requireProyectoYPpe(function() {
  var data = $.extend({
    action: andApply ? 'ajuste_aplicar' : 'ajuste_simular',
    todos_escenarios: 1
  }, ajusteOptsFromForm());
  if (andApply) {
    var anioCfg = $('#aj_anio_precio').val() || '';
    if (!confirm(
      'Aplicar ajuste financiero?\n\n'
      + '- No modifica partidas base\n'
      + '- Activa presupuesto neto en el cuadro\n'
      + '- Guarda historial (usuario / escenario)\n'
      + '- Cuota GAD del año ' + anioCfg + ': si ya se aplicó este año, se REEMPLAZA (no se suma otra vez)\n\n'
      + 'Escenario actual: ' + (escenarioActivo || 'esperada')
    )) {
      return;
    }
    data.observacion = 'Aplicacion desde cuadro presupuestario';
  }
  $.post(API, data, function(r) {
    if (r.status !== 'success') {
      toast(r.message || 'Error en ajuste.', false);
      return;
    }
    if (r.sims) {
      ajusteSimByEsc = r.sims;
    }
    renderAjusteSim(r.sim);
    if (r.cfg) fillAjusteCfgForm(r.cfg);
    if (andApply) {
      toast(r.message || 'Ajuste aplicado.', true);
      loadRubros();
    } else {
      toast('Simulacion lista (sin guardar).', true);
      actualizarResumenEconomico(rubrosCache);
      if (cuadroUsaPartidaFinal()) {
        renderCuadroRubros(rubrosCache, gruposTopeCache);
      }
    }
  }, 'json').fail(function() {
    toast('Error de red al simular ajuste.', false);
  });
  }, 'Seleccione un proyecto.');
}

function renderPreciosRows(precios) {
  var $tb = $('#tblAjustePrecios tbody').empty();
  if (!precios || !precios.length) {
    $tb.append('<tr><td colspan="3" class="text-muted">Sin precios. Use "Cargar ejemplo" o agregue anios.</td></tr>');
    return;
  }
  $.each(precios, function(i, p) {
    $tb.append(
      '<tr>'
      + '<td><input type="number" class="form-control input-sm aj-precio-anio" value="' + p.anio + '" /></td>'
      + '<td><input type="number" step="0.0001" class="form-control input-sm aj-precio-tarifa" value="' + p.tarifa_ton_iva + '" /></td>'
      + '<td><button type="button" class="btn btn-default btn-xs btn-aj-del-precio">&times;</button></td>'
      + '</tr>'
    );
  });
}

function collectPreciosFromModal() {
  var out = [];
  $('#tblAjustePrecios tbody tr').each(function() {
    var anio = parseInt($(this).find('.aj-precio-anio').val(), 10);
    var tarifa = parseFloat($(this).find('.aj-precio-tarifa').val());
    if (anio > 0 && tarifa > 0) {
      out.push({ anio: anio, tarifa_ton_iva: tarifa });
    }
  });
  return out;
}

function sincronizarAjusteConEscenario(esc) {
  esc = esc || escenarioActivo || 'esperada';
  var sim = (ajusteSimByEsc && ajusteSimByEsc[esc]) ? ajusteSimByEsc[esc] : null;
  if (sim && sim.ok) {
    renderAjusteSim(sim);
    return true;
  }
  return false;
}

function setEscenario(esc) {
  if (!ESC_LABEL[esc]) { return; }
  if (esc === escenarioActivo) { return; }
  escenarioActivo = esc;
  // Recalculo inmediato con sims en cache; luego refresh completo del servidor.
  sincronizarAjusteConEscenario(esc);
  renderCuadroRubros(rubrosCache, gruposTopeCache);
  renderTablaRubros(rubrosCache);
  actualizarBotonesEscenario(rubrosCache);
  loadRubros(true);
}

function saveGrupoMeses(ppaId, $input) {
  var meses = $.trim($input.val());
  $.post(API, {
    action: 'save_grupo_meses',
    Ppa_Cod: ppaId,
    Ppa_Meses: meses
  }, function(r) {
    toast(r.message, r.status === 'success');
    if (r.status === 'success') {
      loadRubros();
    }
  }, 'json').fail(function() {
    toast('Error al guardar los meses.', false);
  });
}

function saveGrupoPct(ppaId, $input) {
  var pct = $.trim($input.val());
  $.post(API, {
    action: 'save_grupo_pct',
    Ppa_Cod: ppaId,
    Ppa_Pct: pct
  }, function(r) {
    toast(r.message, r.status === 'success');
    if (r.status === 'success') {
      loadRubros();
    }
  }, 'json').fail(function() {
    toast('Error al guardar el porcentaje.', false);
  });
}

function reloadRubrosSection(){
  var tab = pptoTabActiva();
  if (tab === '#tabProyectos') {
    return;
  }
  var full = (tab === '#tabCuadro');
  loadVersionConfig(function(){ loadRubros(full); });
}

function ensureRubrosForTab(tabHref) {
  if (tabHref === '#tabRubrosTon') {
    loadVersionConfig(function(){ loadRubros(false); });
  } else if (tabHref === '#tabCuadro') {
    loadVersionConfig(function(){ loadRubros(true); });
  }
}

var publicarPreviewCache = null;

function publicarParams() {
  return {
    Pro_Cod: $('#rub_proy_id').val(),
    Ppe_Cod: $('#rub_ppe_id').val() || ''
  };
}

function loadUltimaPublicacion() {
  if (!$('#pubUltimaMeta').length) return;
  var p = publicarParams();
  if (!p.Pro_Cod || !p.Ppe_Cod) {
    $('#pubUltimaMeta').text('Seleccione un proyecto para publicar.');
    return;
  }
  $.getJSON(API, $.extend({ action: 'ultima_publicacion' }, p), function(r) {
    if (r.status !== 'success' || !r.ultima) {
      $('#pubUltimaMeta').text('Sin publicaciones registradas para esta version.');
      return;
    }
    var u = r.ultima;
    var f = (u.pub_fecha_registro || '').replace(' ', ' - ');
    $('#pubUltimaMeta').html(
      'Ultima publicacion: <strong>' + formatCurrency(u.pub_total_nuevo) + '</strong> el ' + f + ' (anio ' + u.pub_anio + ').'
    );
  });
}

function renderPublicarPreview(prev) {
  publicarPreviewCache = prev;
  $('#pubPrevVigente').text(formatCurrency(prev.total_vigente));
  $('#pubPrevNuevo').text(formatCurrency(prev.total_publicar));
  var d = parseFloat(prev.delta) || 0;
  $('#pubPrevDelta').text((d >= 0 ? '+' : '') + formatCurrency(d)).css('color', d >= 0 ? '#276749' : '#c53030');
  $('#pubPrevTon').text(formatNumber(prev.ton_proyectada_anual || 0, 2));
  var $w = $('#pubPrevWarnings').empty().hide();
  if (prev.warnings && prev.warnings.length) {
    $w.html(prev.warnings.join('<br>')).show();
  }
  var $tb = $('#pubPrevTbody').empty();
  $.each(prev.detalle || [], function(i, row) {
    var dd = parseFloat(row.delta) || 0;
    $tb.append(
      '<tr>'
      + '<td>' + htmlspecialchars(row.codigo) + '</td>'
      + '<td>' + htmlspecialchars(row.rubro) + '</td>'
      + '<td class="text-right">' + formatCurrency(row.vigente) + '</td>'
      + '<td class="text-right">' + formatCurrency(row.publicar) + '</td>'
      + '<td class="text-right" style="color:' + (dd >= 0 ? '#276749' : '#c53030') + ';">'
      + (dd >= 0 ? '+' : '') + formatCurrency(dd) + '</td>'
      + '</tr>'
    );
  });
  $('#modalPublicarPreview').show();
}

function previewPublicar() {
  requireProyectoYPpe(function() {
  var p = publicarParams();
  $.getJSON(API, $.extend({ action: 'preview_publicar' }, p), function(r) {
    if (r.status !== 'success') {
      toast(r.message || 'No se pudo generar la vista previa.', false);
      return;
    }
    renderPublicarPreview(r.preview);
  }).fail(function() { toast('Error de red al consultar vista previa.', false); });
  }, 'Seleccione un proyecto.');
}

function ejecutarPublicar(confirmarRepublicacion) {
  requireProyectoYPpe(function() {
  var p = publicarParams();
  var postData = $.extend({ action: 'publish_aprobado' }, p);
  if (confirmarRepublicacion) {
    postData.confirmar_republicacion = '1';
  }
  $.post(API, postData, function(r) {
    if (r.status === 'confirm') {
      if (!confirm('Ya existe una publicacion previa. Â¿Desea republicar y sobrescribir el presupuesto aprobado?')) {
        return;
      }
      ejecutarPublicar(true);
      return;
    }
    if (r.status !== 'success') {
      var msg = r.message || 'No se pudo publicar.';
      if (r.bloqueos && r.bloqueos.length) {
        msg += ' Revise rubros con comprometido/ejecutado superior al nuevo monto.';
      }
      toast(msg, false);
      return;
    }
    $('#modalPublicarPreview').hide();
    toast(r.message, true);
    loadUltimaPublicacion();
    reloadRubrosSection();
  }, 'json').fail(function() { toast('Error de red al publicar.', false); });
  }, 'Seleccione un proyecto.');
}

function pptoClaseEtiqueta(clase) {
  return (clase === 'G') ? 'Grupo' : 'Detalle';
}

function pptoEstadoImportLabel(estado, cat) {
  if (estado === 'conflicto') return '<span class="label label-danger">Conflicto</span>';
  if (estado === 'rubro_existente') {
    var tip = (cat && cat.rubro_nombre_actual) ? (' title="Nombre actual: ' + pdfPreviewEscHtml(cat.rubro_nombre_actual) + '"') : '';
    return '<span class="label label-info"' + tip + '>Se actualiza</span>';
  }
  if (estado === 'existente') return '<span class="label label-default">Partida catalogo</span>';
  return '<span class="label label-success">Nuevo</span>';
}

function pptoEstadoImportRow(codigo, catalogo) {
  var cat = catalogo[codigo] || {};
  var estado = cat.estado || 'nuevo';
  if (cat.rubro_proyecto && estado !== 'conflicto') {
    estado = 'rubro_existente';
  }
  return { estado: estado, cat: cat };
}

function pdfPreviewTonBase() {
  var ton = parseFloat($('#pv_toneladas_base_mes').val());
  if (!isNaN(ton) && ton > 0) return tonBaseVersionMes(ton);
  if (pdfImportPreviewTon > 0) return tonBaseVersionMes(pdfImportPreviewTon);
  return tonBaseProy > 0 ? tonBaseProy : pdfPreviewTonMensCalc(RUBRO_EDIT_TN_DIA, PDF_PREVIEW_DIAS_FIJO);
}

function pdfPreviewEscHtml(s) {
  return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function pdfPreviewNumCell(v, dec) {
  var n = parseFloat(v) || 0;
  return n > 0 ? formatNumber(n, dec) : '<span class="text-muted">-</span>';
}

var PDF_PREVIEW_DIAS_FIJO = 22;
var RUBRO_EDIT_TN_DIA = 3500;
var modalEditRubroCache = null;
var modalRubroModo = '';

function rubroGrupoCodDesdeSeleccion() {
  var sub = $('#rub_subgrupo_cod').val();
  if (sub) return sub;
  return $('#rub_grupo_cod').val() || '';
}

function rubroGrupoMesesDesdeSeleccion() {
  var cod = rubroGrupoCodDesdeSeleccion();
  if (!cod) return 12;
  var info = grupoTopeInfo(cod);
  var m = info && info.meses_prorrateo ? parseInt(info.meses_prorrateo, 10) : 12;
  return (m > 0) ? m : 12;
}

function rubroGrupoPpaIdDesdeSeleccion() {
  var cod = rubroGrupoCodDesdeSeleccion();
  if (!cod) return 0;
  var p = buscarPartidaGrupoPorCod(cod);
  return (p && p.Ppa_Cod) ? parseInt(p.Ppa_Cod, 10) : 0;
}

function resetRubroModalPartidas() {
  $('#rub_grupo_cod').prop('disabled', false).val('');
  $('#rub_subgrupo_cod').prop('disabled', true).html('<option value="">-- Subgrupo --</option>');
  $('#rub_ppa_id').prop('disabled', true).html('<option value="">-- Detalle --</option>');
  $('#pdp_rubro_nombre').val('');
  $('#rubro_partida_resumen').empty();
  if (partidasGrupo.length || partidasRubro.length) {
    fillPartidasRubro();
  }
}

function actualizarModalRubroMesesDesdePartida() {
  if (modalRubroModo !== 'add') return;
  var meses = rubroGrupoMesesDesdeSeleccion();
  var grupoPpaId = rubroGrupoPpaIdDesdeSeleccion();
  $('#modal_edit_meses').val(meses);
  $('#modal_edit_meses_inicial').val(meses);
  $('#modal_edit_grupo_ppa_id').val(grupoPpaId);
}

function rubroGrupoMesesInfo(x) {
  if (!x) return null;
  var cod = x.subgrupo_cod || x.grupo_cod || '';
  if (!cod) return null;
  return grupoTopeInfo(cod);
}

function rubroGrupoMesesPpaId(x) {
  if (!x) return 0;
  if (x.subgrupo_ppa_id && parseInt(x.subgrupo_ppa_id, 10) > 0) {
    return parseInt(x.subgrupo_ppa_id, 10);
  }
  if (x.grupo_ppa_id && parseInt(x.grupo_ppa_id, 10) > 0) {
    return parseInt(x.grupo_ppa_id, 10);
  }
  var info = rubroGrupoMesesInfo(x);
  return info && info.Ppa_Cod ? parseInt(info.Ppa_Cod, 10) : 0;
}

function rubroEditTnDia(x) {
  var tn = parseFloat(x && x.Pdp_TonBase) || 0;
  if (tn >= 50000) {
    var por30 = tn / 30;
    if (por30 >= 3000 && por30 <= 9999) return por30;
  }
  if (tn > 0) {
    var porDias = tn / PDF_PREVIEW_DIAS_FIJO;
    if (porDias >= 3000 && porDias <= 9999) return porDias;
  }
  return RUBRO_EDIT_TN_DIA;
}

function calcModalEditRubroPreview() {
  var factor = parseFloat($('#modal_edit_factor_anual').val()) || 0;
  var meses = parseInt($('#modal_edit_meses').val(), 10) || 12;
  if (meses < 1) meses = 12;
  var tnDia = pptoParseNumber($('#modal_edit_tn_dia').val()) || RUBRO_EDIT_TN_DIA;
  var tonMens = pdfPreviewTonMensCalc(tnDia, PDF_PREVIEW_DIAS_FIJO);
  var usdTonMes = pdfPreviewUsdTonMensualCalc(factor);
  var montoRecalc = pdfPreviewMontoRecalcCalc(tnDia, PDF_PREVIEW_DIAS_FIJO, factor, 0);
  var presupAnual = pdfPreviewPresupAnualCalc(montoRecalc, meses);
  var presupMensual = pdfPreviewPresupMensualCalc(presupAnual);
  $('#modal_edit_ton_mens').val(tonMens > 0 ? formatNumber(tonMens, 0) : '');
  $('#modal_edit_factor_mensual').val(usdTonMes > 0 ? formatNumber(usdTonMes, 6) : '');
  $('#modal_edit_monto_recalc').val(montoRecalc > 0 ? formatCurrency(montoRecalc) : '');
  $('#modal_edit_presup_anual').val(presupAnual > 0 ? formatCurrency(presupAnual) : '');
  $('#modal_edit_presup_mensual').val(presupMensual > 0 ? formatCurrency(presupMensual) : '');
}

function cerrarModalRubro() {
  $('#modalEditRubro').hide();
  modalEditRubroCache = null;
  modalRubroModo = '';
  resetRubroModalPartidas();
}

function abrirModalAgregarRubro() {
  requireProyectoYPpe(function() {
  modalRubroModo = 'add';
  modalEditRubroCache = null;
  resetRubroModalPartidas();
  $('#modal_rubro_titulo').text('Agregar rubro');
  $('#btnSaveEditRubroModal').html('<i class="bi bi-plus-lg"></i> Agregar rubro');
  $('#modal_edit_rubro_resumen').hide();
  $('#modal_rubro_partida_block').show();
  $('#modal_edit_pdp_id').val(0);
  $('#modal_edit_ppa_id').val(0);
  $('#modal_edit_grupo_ppa_id').val(0);
  $('#modal_edit_meses_inicial').val(12);
  $('#modal_edit_tn_dia').val(formatNumber(RUBRO_EDIT_TN_DIA, 0));
  $('#modal_edit_dias').val(String(PDF_PREVIEW_DIAS_FIJO));
  $('#modal_edit_factor_anual').val('');
  $('#modal_edit_meses').val(12);
  calcModalEditRubroPreview();
  $('#modalEditRubro').show();
  }, 'Seleccione un proyecto antes de agregar rubros.');
}

function abrirModalEditRubro(x) {
  if (!x || !x.Pdp_Cod) return;
  modalRubroModo = 'edit';
  modalEditRubroCache = x;
  var mesesInfo = rubroGrupoMesesInfo(x);
  var meses = mesesInfo && mesesInfo.meses_prorrateo ? parseInt(mesesInfo.meses_prorrateo, 10) : 12;
  if (meses < 1) meses = 12;
  var tnDia = rubroEditTnDia(x);
  var etiqueta = (x.Ppa_Cla || '') + ' - ' + (x.Pdp_Rubro || x.Ppa_Des || 'Rubro');
  $('#modal_rubro_titulo').text('Editar rubro');
  $('#btnSaveEditRubroModal').html('<i class="bi bi-check-lg"></i> Guardar cambios');
  $('#modal_edit_rubro_resumen').text(etiqueta).show();
  $('#modal_rubro_partida_block').hide();
  $('#modal_edit_pdp_id').val(x.Pdp_Cod);
  $('#modal_edit_ppa_id').val(x.Ppa_Cod || 0);
  $('#modal_edit_grupo_ppa_id').val(rubroGrupoMesesPpaId(x));
  $('#modal_edit_meses_inicial').val(meses);
  $('#modal_edit_tn_dia').val(formatNumber(tnDia, 0));
  $('#modal_edit_dias').val(String(PDF_PREVIEW_DIAS_FIJO));
  $('#modal_edit_factor_anual').val(x.Pdp_FacAnualTon || '');
  $('#modal_edit_meses').val(meses);
  calcModalEditRubroPreview();
  $('#modalEditRubro').show();
}

function guardarModalEditRubro() {
  var isAdd = modalRubroModo === 'add';
  var x = modalEditRubroCache;
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  var pdpId = isAdd ? 0 : (parseInt($('#modal_edit_pdp_id').val(), 10) || 0);
  var ppaId = isAdd ? (parseInt($('#rub_ppa_id').val(), 10) || 0) : (parseInt($('#modal_edit_ppa_id').val(), 10) || 0);
  var factor = parseFloat($('#modal_edit_factor_anual').val()) || 0;
  var meses = parseInt($('#modal_edit_meses').val(), 10) || 12;
  var mesesInicial = parseInt($('#modal_edit_meses_inicial').val(), 10) || 12;
  var grupoPpaId = parseInt($('#modal_edit_grupo_ppa_id').val(), 10) || 0;
  if (!proy) {
    toast('Seleccione un proyecto.', false);
    return;
  }
  if (!ppe) {
    ensureRubPpeId(function(ok) {
      if (ok) guardarModalEditRubro();
      else toast('No se pudo obtener la cabecera presupuestaria activa. Recargue la pagina (Ctrl+F5).', false);
    });
    return;
  }
  if (!isAdd && (!pdpId || !ppaId)) {
    toast('Rubro invalido.', false);
    return;
  }
  if (isAdd && !ppaId) {
    toast('Seleccione grupo, subgrupo (si aplica) y partida detalle.', false);
    return;
  }
  if (factor <= 0) {
    toast('Ingrese $/Ton anual mayor a cero.', false);
    return;
  }
  if (meses < 1 || meses > 999) {
    toast('Los meses deben estar entre 1 y 999.', false);
    return;
  }
  if (isAdd) {
    grupoPpaId = rubroGrupoPpaIdDesdeSeleccion();
    if (!grupoPpaId) {
      toast('No se encontro el grupo/subgrupo para los meses de prorrateo.', false);
      return;
    }
  }
  var tnDia = pptoParseNumber($('#modal_edit_tn_dia').val()) || RUBRO_EDIT_TN_DIA;
  var tonMens = pdfPreviewTonMensCalc(tnDia, PDF_PREVIEW_DIAS_FIJO);
  var montoRecalc = pdfPreviewMontoRecalcCalc(tnDia, PDF_PREVIEW_DIAS_FIJO, factor, 0);
  var presupAnual = pdfPreviewPresupAnualCalc(montoRecalc, meses);
  if (presupAnual <= 0) {
    toast('No se pudo calcular el presupuesto anual.', false);
    return;
  }
  var rubroNombre = isAdd ? rubroNombreDesdePartida(ppaId) : (x.Pdp_Rubro || x.Ppa_Des || '');

  function postSaveRubro() {
    $.post(API, {
      action: 'save_rubro',
      Pdp_Cod: pdpId,
      Pro_Cod: proy,
      Ppe_Cod: ppe,
      Ppa_Cod: ppaId,
      Pdp_Rubro: rubroNombre,
      Pdp_FacAnualTon: factor,
      Pdp_PreAnual: presupAnual,
      Pdp_TonBase: tonMens,
      pdp_tn_dia: tnDia
    }, function(r) {
      if (r.status !== 'success') {
        toast(r.message || 'No se pudo guardar el rubro.', false);
        return;
      }
      toast(r.message, true);
      if (r.warning) toastWarn(r.warning);
      cerrarModalRubro();
      loadRubros();
    }, 'json').fail(function() {
      toast('Error de red al guardar el rubro.', false);
    });
  }

  if (meses !== mesesInicial && grupoPpaId > 0) {
    $.post(API, {
      action: 'save_grupo_meses',
      Ppa_Cod: grupoPpaId,
      Ppa_Meses: meses
    }, function(r) {
      if (r.status !== 'success') {
        toast(r.message || 'No se pudieron guardar los meses.', false);
        return;
      }
      postSaveRubro();
    }, 'json').fail(function() {
      toast('Error de red al guardar los meses.', false);
    });
    return;
  }
  postSaveRubro();
}

function pdfPreviewDiasFijos() {
  return PDF_PREVIEW_DIAS_FIJO;
}

function pdfPreviewDescCell(codigo, desc) {
  return '<input type="text" class="form-control input-sm pdf-preview-desc" data-codigo="' + pdfPreviewEscHtml(codigo) + '" value="' + pdfPreviewEscHtml(desc) + '" />';
}

function pdfPreviewDiasCellHtml(esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  return '<span class="pdf-preview-dias-fijo">' + PDF_PREVIEW_DIAS_FIJO + '</span>';
}

function pdfPreviewFactorCellHtml(codigo, factor, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var fac = parseFloat(factor);
  var val = (!isNaN(fac) && fac > 0) ? fac : '';
  return '<input type="number" step="0.0000001" min="0" class="form-control input-sm pdf-preview-factor" data-codigo="' + pdfPreviewEscHtml(codigo) + '" value="' + val + '" />';
}

function pdfPreviewMesesGlobalValue() {
  var m = parseInt($('#pdf_preview_meses_global').val(), 10);
  return (m > 0) ? m : 12;
}

function pdfPreviewMesesValue(codigo) {
  if (codigo) {
    var $tr = $('#pdf_preview_tbody tr[data-es-driver="1"]').filter(function() {
      return $(this).find('.pdf-preview-factor').data('codigo') === codigo;
    }).first();
    if ($tr.length) {
      var m = parseInt($tr.attr('data-meses'), 10);
      if (m > 0) return m;
    }
  }
  return pdfPreviewMesesGlobalValue();
}

function pdfPreviewMesesCellHtml(codigo, meses, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var m = parseInt(meses, 10);
  if (!m || m < 1) m = pdfPreviewMesesGlobalValue();
  return '<span class="pdf-preview-meses-val" data-codigo="' + pdfPreviewEscHtml(codigo) + '">' + m + '</span>';
}

function aplicarPdfPreviewMesesGlobal(val) {
  var m = parseInt(val, 10);
  if (!m || m < 1) return;
  $('#pdf_preview_meses_global').val(m);
  $('.pdf-preview-meses-val').text(m);
  $('#pdf_preview_tbody tr[data-es-driver="1"]').attr('data-meses', m);
  recalcPdfPreviewFilas();
}

function pdfPreviewTonMensCalc(tnDia, dias) {
  var tn = parseFloat(tnDia) || 0;
  var d = parseInt(dias, 10) || 0;
  if (tn <= 0 || d <= 0) return 0;
  return tn * d;
}

function pdfPreviewUsdTonMensualCalc(factor) {
  var fac = parseFloat(factor) || 0;
  if (fac <= 0) return 0;
  return fac / 12;
}

function pdfPreviewMontoRecalcCalc(tnDia, dias, factor, pdfMonto) {
  var tonMens = pdfPreviewTonMensCalc(tnDia, dias);
  var fac = parseFloat(factor) || 0;
  if (tonMens > 0 && fac > 0) return tonMens * fac;
  var monto = parseFloat(pdfMonto) || 0;
  if (monto > 0) return monto;
  var ton = pdfPreviewTonBase();
  if (ton > 0 && fac > 0) return ton * fac;
  return 0;
}

function pdfPreviewPresupAnualCalc(montoRecalc, meses) {
  var monto = parseFloat(montoRecalc) || 0;
  if (monto <= 0) return 0;
  var m = parseInt(meses, 10) || 12;
  if (m < 1) m = 12;
  return monto / (m / 12);
}

function pdfPreviewPresupMensualCalc(presupAnual) {
  var anual = parseFloat(presupAnual) || 0;
  if (anual <= 0) return 0;
  return anual / 12;
}

function pdfPreviewRowCalcFromTr($tr) {
  var cod = $tr.find('.pdf-preview-factor').data('codigo');
  var tnDia = parseFloat($tr.attr('data-tn-dia')) || 0;
  var dias = pdfPreviewDiasFijos();
  var factor = parseFloat($tr.find('.pdf-preview-factor').val()) || 0;
  var pdfMonto = parseFloat($tr.attr('data-monto-recalc-pdf')) || 0;
  var meses = pdfPreviewMesesValue(cod);
  var tonMens = pdfPreviewTonMensCalc(tnDia, dias);
  var usdTonMes = pdfPreviewUsdTonMensualCalc(factor);
  var montoRecalc = pdfPreviewMontoRecalcCalc(tnDia, dias, factor, pdfMonto);
  var presupAnual = pdfPreviewPresupAnualCalc(montoRecalc, meses);
  var presupMensual = pdfPreviewPresupMensualCalc(presupAnual);
  return {
    cod: cod,
    tonMens: tonMens,
    usdTonMes: usdTonMes,
    montoRecalc: montoRecalc,
    presupAnual: presupAnual,
    presupMensual: presupMensual,
    factor: factor
  };
}

function pdfPreviewTonMensCellHtml(codigo, tnDia, dias, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var tonMens = pdfPreviewTonMensCalc(tnDia, dias);
  return '<span class="pdf-preview-ton-mens" data-codigo="' + pdfPreviewEscHtml(codigo) + '">' + (tonMens > 0 ? formatNumber(tonMens, 0) : '-') + '</span>';
}

function pdfPreviewUsdTonMensualCellHtml(codigo, factor, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var mes = pdfPreviewUsdTonMensualCalc(factor);
  return '<span class="pdf-preview-usd-ton-mes" data-codigo="' + pdfPreviewEscHtml(codigo) + '">' + (mes > 0 ? formatNumber(mes, 4) : '-') + '</span>';
}

function pdfPreviewMontoRecalcCellHtml(codigo, tnDia, dias, factor, pdfMonto, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var monto = pdfPreviewMontoRecalcCalc(tnDia, dias, factor, pdfMonto);
  return '<span class="pdf-preview-monto-recalc" data-codigo="' + pdfPreviewEscHtml(codigo) + '">' + (monto > 0 ? formatCurrency(monto) : '-') + '</span>';
}

function pdfPreviewPresupMensualCellHtml(codigo, tnDia, dias, factor, pdfMonto, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var meses = pdfPreviewMesesValue(codigo);
  var monto = pdfPreviewMontoRecalcCalc(tnDia, dias, factor, pdfMonto);
  var mensual = pdfPreviewPresupMensualCalc(pdfPreviewPresupAnualCalc(monto, meses));
  return '<span class="pdf-preview-presup-mes" data-codigo="' + pdfPreviewEscHtml(codigo) + '">' + (mensual > 0 ? formatCurrency(mensual) : '-') + '</span>';
}

function pdfPreviewPresupAnualCellHtml(codigo, tnDia, dias, factor, pdfMonto, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var meses = pdfPreviewMesesValue(codigo);
  var monto = pdfPreviewMontoRecalcCalc(tnDia, dias, factor, pdfMonto);
  var anual = pdfPreviewPresupAnualCalc(monto, meses);
  return '<span class="pdf-preview-presup-anual" data-codigo="' + pdfPreviewEscHtml(codigo) + '">' + (anual > 0 ? formatCurrency(anual) : '-') + '</span>';
}

function recalcPdfPreviewFila($tr) {
  if (!$tr.length || $tr.attr('data-es-driver') !== '1') return;
  var row = pdfPreviewRowCalcFromTr($tr);
  $('.pdf-preview-ton-mens[data-codigo="' + row.cod + '"]').text(row.tonMens > 0 ? formatNumber(row.tonMens, 0) : '-');
  $('.pdf-preview-usd-ton-mes[data-codigo="' + row.cod + '"]').text(row.usdTonMes > 0 ? formatNumber(row.usdTonMes, 4) : '-');
  $('.pdf-preview-monto-recalc[data-codigo="' + row.cod + '"]').text(row.montoRecalc > 0 ? formatCurrency(row.montoRecalc) : '-');
  $('.pdf-preview-presup-anual[data-codigo="' + row.cod + '"]').text(row.presupAnual > 0 ? formatCurrency(row.presupAnual) : '-');
  $('.pdf-preview-presup-mes[data-codigo="' + row.cod + '"]').text(row.presupMensual > 0 ? formatCurrency(row.presupMensual) : '-');
}

function recalcPdfPreviewFilas() {
  $('#pdf_preview_tbody tr[data-es-driver="1"]').each(function() {
    recalcPdfPreviewFila($(this));
  });
  recalcPdfPreviewTotales();
}

function pdfPreviewMergeRubroMeta(row, rubroDetailMap) {
  var d = rubroDetailMap[row.codigo] || {};
  row.presup_anual_pdf = parseFloat(d.presup_anual_pdf) || 0;
  row.tn_dia = parseFloat(d.tn_dia) || 0;
  row.dias_laborables = pdfPreviewDiasFijos();
  row.meses = parseFloat(d.meses) || 12;
  if (!row.meses || row.meses <= 0) row.meses = 12;
  row.usd_ton = parseFloat(d.usd_ton) || 0;
  row.monto_recalc = parseFloat(d.monto_recalc) || 0;
  if (row.esDriver && (!row.factor || parseFloat(row.factor) <= 0) && parseFloat(d.factor_anual) > 0) {
    row.factor = d.factor_anual;
  }
  return row;
}

function syncPdfPreviewToPayload() {
  if (!pdfImportPayload) return;
  $('.pdf-preview-desc').each(function() {
    var cod = $(this).data('codigo');
    var val = $.trim($(this).val());
    if (pdfImportPayload.partidas) {
      $.each(pdfImportPayload.partidas, function(i, p) {
        if (p.codigo === cod) p.descripcion = val;
      });
    }
    if (pdfImportPayload.rubros) {
      $.each(pdfImportPayload.rubros, function(i, r) {
        if (r.codigo === cod) r.descripcion = val;
      });
    }
  });
  $('.pdf-preview-factor').each(function() {
    var cod = $(this).data('codigo');
    var val = parseFloat($(this).val()) || 0;
    if (pdfImportPayload.rubros) {
      $.each(pdfImportPayload.rubros, function(i, r) {
        if (r.codigo === cod) r.factor_anual = val;
      });
    }
  });
  var mesesGlobal = pdfPreviewMesesGlobalValue();
  if (pdfImportPayload.partidas) {
    $.each(pdfImportPayload.partidas, function(i, p) {
      if (p.clase === 'G') {
        p.meses_prorrateo = mesesGlobal;
      }
    });
  }
  if (pdfImportPayload.rubros) {
    $.each(pdfImportPayload.rubros, function(i, r) {
      r.dias_laborables = pdfPreviewDiasFijos();
      var $tr = $('#pdf_preview_tbody tr[data-es-driver="1"]').filter(function() {
        return $(this).find('.pdf-preview-factor').data('codigo') === r.codigo;
      }).first();
      if ($tr.length) {
        var mesesRow = parseInt($tr.attr('data-meses'), 10);
        if (mesesRow > 0) r.meses = mesesRow;
        var row = pdfPreviewRowCalcFromTr($tr);
        r.monto_recalc = row.montoRecalc;
        r.presupuesto_anual = row.presupAnual;
        r.presupuesto_mensual = row.presupMensual;
      } else if (!r.meses || r.meses <= 0) {
        r.meses = mesesGlobal;
      }
    });
  }
}

function recalcPdfPreviewAnuales() {
  recalcPdfPreviewFilas();
}

function recalcPdfPreviewTotales() {
  var totalFactor = 0;
  var totalTonMens = 0;
  var totalUsdTonMes = 0;
  var totalPdfAnual = 0;
  var totalMontoRecalc = 0;
  var totalPresupAnual = 0;
  var totalPresupMensual = 0;
  var nDrivers = 0;
  $('#pdf_preview_tbody tr[data-es-driver="1"]').each(function() {
    nDrivers++;
    var $tr = $(this);
    var pdfAnual = parseFloat($tr.attr('data-pdf-anual')) || 0;
    var row = pdfPreviewRowCalcFromTr($tr);
    totalFactor += row.factor;
    totalTonMens += row.tonMens;
    totalUsdTonMes += row.usdTonMes;
    totalPdfAnual += pdfAnual;
    totalMontoRecalc += row.montoRecalc;
    totalPresupAnual += row.presupAnual;
    totalPresupMensual += row.presupMensual;
  });
  var $foot = $('#pdf_preview_tfoot');
  if (!$foot.length || nDrivers === 0) {
    $foot.empty().hide();
    return;
  }
  $foot.show().html(
    '<tr class="pdf-preview-total-row">'
    + '<td colspan="4" class="text-right"><strong>Total importacion</strong> <span class="text-muted">(' + nDrivers + ' rubro' + (nDrivers === 1 ? '' : 's') + ' driver)</span></td>'
    + '<td class="text-right">' + (totalPdfAnual > 0 ? formatCurrency(totalPdfAnual) : '-') + '</td>'
    + '<td colspan="2"></td>'
    + '<td class="text-right"><strong>' + formatNumber(totalTonMens, 0) + '</strong></td>'
    + '<td class="text-right"><strong id="pdf_preview_total_factor">' + formatNumber(totalFactor, 4) + '</strong></td>'
    + '<td class="text-right"><strong>' + formatNumber(totalUsdTonMes, 4) + '</strong></td>'
    + '<td class="text-right">' + (totalMontoRecalc > 0 ? formatCurrency(totalMontoRecalc) : '-') + '</td>'
    + '<td></td>'
    + '<td class="text-right"><strong id="pdf_preview_total_presup_mes">' + formatCurrency(totalPresupMensual) + '</strong></td>'
    + '<td class="text-right"><strong id="pdf_preview_total_presup_anual">' + formatCurrency(totalPresupAnual) + '</strong></td>'
    + '</tr>'
  );
}

function renderPdfPreview(data) {
  var partidas = data.partidas || [];
  var rubros = data.rubros || [];
  var mesesGlobalImport = 0;
  if (data.payload && data.payload.meses_prorrateo_global) {
    mesesGlobalImport = parseInt(data.payload.meses_prorrateo_global, 10) || 0;
  } else if (data.meses_prorrateo_global) {
    mesesGlobalImport = parseInt(data.meses_prorrateo_global, 10) || 0;
  }
  if (mesesGlobalImport > 0) {
    $('#pdf_preview_meses_global').val(mesesGlobalImport);
  }
  var catalogo = data.catalogo || {};
  var conflictos = data.conflictos || [];
  var conflictMap = {};
  $.each(conflictos, function(i, c) {
    if (c.codigo) conflictMap[c.codigo] = c;
  });

  var factorMap = {};
  var driverMap = {};
  var rubroDetailMap = {};
  $.each(rubros, function(i, r) {
    factorMap[r.codigo] = r.factor_anual;
    driverMap[r.codigo] = true;
    rubroDetailMap[r.codigo] = r;
  });

  var rows = [];
  $.each(partidas, function(i, p) {
    var cat = catalogo[p.codigo] || {};
    var estRow = pptoEstadoImportRow(p.codigo, catalogo);
    rows.push({
      codigo: p.codigo,
      descripcion: p.descripcion,
      clase: p.clase || 'G',
      factor: factorMap[p.codigo] || '',
      esDriver: !!driverMap[p.codigo],
      estado: estRow.estado,
      catInfo: estRow.cat,
      conflicto: conflictMap[p.codigo] || null
    });
  });
  $.each(rubros, function(i, r) {
    var found = false;
    $.each(rows, function(j, row) { if (row.codigo === r.codigo) found = true; });
    if (!found) {
      var estRow = pptoEstadoImportRow(r.codigo, catalogo);
      rows.push({
        codigo: r.codigo,
        descripcion: r.descripcion,
        clase: 'D',
        factor: r.factor_anual,
        esDriver: true,
        estado: estRow.estado,
        catInfo: estRow.cat,
        conflicto: conflictMap[r.codigo] || null
      });
    }
  });

  var tb = $('#pdf_preview_tbody').empty();
  if (!rows.length) {
    tb.append('<tr><td colspan="14" class="text-center text-muted">Sin datos detectados.</td></tr>');
    return;
  }
  rows.sort(function(a, b) { return a.codigo > b.codigo ? 1 : (a.codigo < b.codigo ? -1 : 0); });
  $.each(rows, function(i, row) {
    row = pdfPreviewMergeRubroMeta(row, rubroDetailMap);
    if (mesesGlobalImport > 0 && row.esDriver) {
      row.meses = mesesGlobalImport;
    }
    var trClass = row.estado === 'conflicto' ? ' class="danger"' : '';
    var title = row.conflicto && row.conflicto.mensaje ? ' title="' + row.conflicto.mensaje.replace(/"/g, '&quot;') + '"' : '';
    var esDriver = !!row.esDriver;
    var diasVal = pdfPreviewDiasFijos();
    var dataAttrs = ' data-es-driver="' + (esDriver ? '1' : '0') + '"'
      + ' data-pdf-anual="' + (parseFloat(row.presup_anual_pdf) || 0) + '"'
      + ' data-monto-recalc-pdf="' + (parseFloat(row.monto_recalc) || 0) + '"'
      + ' data-tn-dia="' + (parseFloat(row.tn_dia) || 0) + '"'
      + ' data-meses="' + (parseInt(row.meses, 10) || 12) + '"';
    tb.append('<tr' + trClass + dataAttrs + '>'
      + '<td><strong>' + pdfPreviewEscHtml(row.codigo) + '</strong></td>'
      + '<td' + title + '>' + pdfPreviewDescCell(row.codigo, row.descripcion)
      + (row.conflicto && row.conflicto.mensaje ? ' <small class="text-danger">(' + row.conflicto.mensaje + ')</small>' : '') + '</td>'
      + '<td>' + pptoClaseEtiqueta(row.clase) + '</td>'
      + '<td>' + pptoEstadoImportLabel(row.estado, row.catInfo) + '</td>'
      + '<td class="text-right">' + pdfPreviewNumCell(row.presup_anual_pdf, 2) + '</td>'
      + '<td class="text-right">' + pdfPreviewNumCell(row.tn_dia, 0) + '</td>'
      + '<td class="text-right">' + pdfPreviewDiasCellHtml(esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewTonMensCellHtml(row.codigo, row.tn_dia, diasVal, esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewFactorCellHtml(row.codigo, row.factor, esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewUsdTonMensualCellHtml(row.codigo, row.factor, esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewMontoRecalcCellHtml(row.codigo, row.tn_dia, diasVal, row.factor, row.monto_recalc, esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewMesesCellHtml(row.codigo, row.meses, esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewPresupMensualCellHtml(row.codigo, row.tn_dia, diasVal, row.factor, row.monto_recalc, esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewPresupAnualCellHtml(row.codigo, row.tn_dia, diasVal, row.factor, row.monto_recalc, esDriver) + '</td>'
      + '</tr>');
  });
  if (mesesGlobalImport > 0) {
    $('#pdf_preview_meses_global').val(mesesGlobalImport);
    $('.pdf-preview-meses-val').text(mesesGlobalImport);
    $('#pdf_preview_tbody tr[data-es-driver="1"]').attr('data-meses', mesesGlobalImport);
  } else if (rubros.length) {
    var m0 = parseInt(rubros[0].meses, 10);
    if (m0 > 0) $('#pdf_preview_meses_global').val(m0);
  }
  recalcPdfPreviewFilas();
  recalcPdfPreviewTotales();
}

function actualizarPdfImportUi(conflictos, bloqueado) {
  pdfImportConflictos = conflictos || [];
  var hayConflictos = bloqueado || (pdfImportConflictos.length > 0);
  if (hayConflictos) {
    var msgs = [];
    $.each(pdfImportConflictos, function(i, c) {
      if (c.mensaje) msgs.push(c.mensaje);
    });
    $('#pdf_preview_conflictos').html('<strong>No se puede importar hasta corregir en Admin:</strong><ul style="margin:6px 0 0 16px;padding:0;">'
      + $.map(msgs, function(m) { return '<li>' + m + '</li>'; }).join('') + '</ul>').show();
    $('#btnConfirmImportPdf').prop('disabled', true).addClass('disabled');
    $('#pdf_import_status').text('Importacion bloqueada por conflictos de catalogo.');
  } else {
    $('#pdf_preview_conflictos').hide();
    $('#btnConfirmImportPdf').prop('disabled', false).removeClass('disabled');
  }
}

function ensurePptoImportJs(cb) {
  if (typeof parsePdfFile === 'function' && typeof importPdfPayload === 'function') {
    window.__pptoImportJsLoaded = true;
    if (cb) cb();
    return;
  }
  if (window.__pptoImportJsLoading) {
    var tries = 0;
    var t = setInterval(function() {
      tries++;
      if (typeof parsePdfFile === 'function' && typeof importPdfPayload === 'function') {
        clearInterval(t);
        window.__pptoImportJsLoaded = true;
        if (cb) cb();
      } else if (tries > 100) {
        clearInterval(t);
        if (typeof toast === 'function') toast('Timeout cargando modulo de importacion.', false);
      }
    }, 50);
    return;
  }
  window.__pptoImportJsLoading = true;
  var s = document.createElement('script');
  s.src = '../VALIDACIONES/ppto_proyectos_import.js?v=20260817a';
  s.onload = function() {
    window.__pptoImportJsLoaded = true;
    window.__pptoImportJsLoading = false;
    if (cb) cb();
  };
  s.onerror = function() {
    window.__pptoImportJsLoading = false;
    if (typeof toast === 'function') toast('No se pudo cargar el modulo de importacion.', false);
  };
  document.body.appendChild(s);
}
window.ensurePptoImportJs = ensurePptoImportJs;

$(function(){
  actualizarCuadroPeriodoUi();
  loadCatalogos(function(){ loadProyectos(); });

  $('#pptoProyTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
    ensureRubrosForTab($(e.target).attr('href'));
  });

  // Selectores del cuadro/rubros: deben vivir aqui (JS siempre cargado).
  // Si quedan solo en import.js diferido, cambiar proyecto/vista no hace nada.
  $(document).on('change', '#rub_proy_id, #cuadro_proy_id, #rub_ppe_id', function() {
    if (this.id === 'rub_proy_id' || this.id === 'cuadro_proy_id') {
      if (typeof syncProyectoSelects === 'function') {
        syncProyectoSelects(this.id);
      }
    }
    publicarPreviewCache = null;
    if (typeof cerrarModalRubro === 'function') {
      cerrarModalRubro();
    }
    reloadRubrosSection();
  });

  $(document).on('click', '.cuadro-vista-btn', function() {
    var v = $(this).data('vista');
    if (!v || v === cuadroVista) return;
    cuadroVista = v;
    if (v !== 'anual') {
      cuadroMes = cuadroMesDefault || cuadroMes;
    }
    actualizarCuadroPeriodoUi();
    loadRubros(true);
  });

  $(document).on('change', '#cuadro_mes_sel', function() {
    cuadroMes = parseInt($(this).val(), 10) || cuadroMesDefault;
    loadRubros(true);
  });

  $(document).on('change', '#cuadro_anio_precio', function() {
    cuadroAnioPrecio = parseInt($(this).val(), 10) || 0;
    $('#aj_anio_precio').val(cuadroAnioPrecio || '');
    loadRubros(true);
  });

  $(document).on('focus', '.aj-money-input', function() {
    var n = pptoParseNumber($(this).val());
    $(this).val(n ? String(n) : '');
  });
  $(document).on('blur', '.aj-money-input', function() {
    $(this).val(formatNumber(pptoParseNumber($(this).val()), 2));
  });

  $(document).on('click', '#tblRubrosPager .btn-pager-prev', function(){ rubrosPagerIr(-1); });
  $(document).on('click', '#tblRubrosPager .btn-pager-next', function(){ rubrosPagerIr(1); });

  // Si import.js aun no cargo, atender Analizar/Confirmar al vuelo.
  $(document).on('click', '#btnParsePdf', function(e) {
    if (typeof parsePdfFile === 'function') return;
    e.preventDefault();
    e.stopImmediatePropagation();
    ensurePptoImportJs(function(){ parsePdfFile(); });
  });
  $(document).on('click', '#btnImportPdf, #btnConfirmImportPdf', function(e) {
    if (typeof importPdfPayload === 'function') return;
    e.preventDefault();
    e.stopImmediatePropagation();
    ensurePptoImportJs(function(){ importPdfPayload(); });
  });

  if (window.location.search.indexOf('msg=usar_nueva_version') >= 0) {
    toast('Use + Nueva version en esta pantalla (Presupuesto proyectos).', true);
    $('#pptoProyTabs a[href="#tabRubrosTon"]').tab('show');
  }
});

