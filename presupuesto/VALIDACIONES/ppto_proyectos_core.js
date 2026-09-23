/** Presupuesto proyectos - nucleo (API, catalogos, helpers). */
/* global jQuery, $ */
var API = '../LOGICA/ppto_proyectos_logica.php';
var PPTO_IMPORT_MAX_RUBROS = 2000;
var PPTO_AJAX_TIMEOUT_MS = 90000;
$.ajaxSetup({
  timeout: PPTO_AJAX_TIMEOUT_MS,
  dataFilter: function(data, type) {
    if (typeof data === 'string') {
      data = data.replace(/^\uFEFF+/, '');
      var i = data.indexOf('{');
      var j = data.indexOf('[');
      var start = -1;
      if (i >= 0 && (j < 0 || i <= j)) start = i;
      else if (j >= 0) start = j;
      if (start > 0) data = data.substring(start);
    }
    return data;
  }
});


var partidasRubro = [];
var toastHideTimer = null;
var partidasGrupo = [];

var tonBaseProy = 0;

var pdfImportPayload = null;
var pdfImportConflictos = [];
var pdfImportPreviewTon = 0;
var rubrosCache = [];
var gruposTopeCache = {};
var escenarioActivo = 'esperada';
var escenarioMesesReal = 0;
var ajusteSimCache = null;
/** Simulacion de partida final por escenario (esperada/proyectada/real). */
var ajusteSimByEsc = {};
var ajusteCfgCache = null;
var ajustePreciosCache = [];
var cuadroAnioPrecio = 0;
var escenariosTonMes = { esperada: 0, proyectada: 0, real: 0 };
var escenariosTonAnual = { esperada: 0, proyectada: 0, real: 0 };
var escenariosTonPeriodo = { esperada: 0, proyectada: 0, real: 0 };
var escenariosIngreso = { esperada: 0, proyectada: 0, real: 0 };
var ingresoCfg = { tarifa: 3, iva: 1.15, factor_precio_gasto: 1 };
var cuadroVista = 'anual';
var cuadroMes = (new Date()).getMonth() + 1;
var cuadroMesDefault = cuadroMes;
var cuadroPeriodoLabel = 'Anual';
var ESC_LABEL = { esperada: 'Base PDF (esperada)', proyectada: 'Proyectada', real: 'Real (+proyectado)' };
var MESES_NOM = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

function factorPrecioGastoActivo() {
  var f = parseFloat(ingresoCfg.factor_precio_gasto);
  return (f > 0) ? f : 1;
}

/** Meses de prorrateo del grupo/subgrupo del rubro. */
function rubroMesesProrrateoDesdeRubro(x) {
  var cod = '';
  if (x) {
    cod = x.subgrupo_cod || x.grupo_cod || '';
    if (!cod && x.Ppa_Cla) {
      cod = rubroSubgrupoCod(x.Ppa_Cla) || rubroGrupoPrincipal(x.Ppa_Cla) || '';
    }
  }
  return grupoMesesProrrateo(cod);
}

/** Monto recalc. (ton×$/Ton) → presup. del año: monto ÷ (meses/12). Ej. 60 meses → ÷5. */
function rubroPresupAnualDesdeMonto(monto, x) {
  var m = parseFloat(monto) || 0;
  if (m <= 0) return 0;
  var meses = rubroMesesProrrateoDesdeRubro(x);
  if (meses < 1) meses = 12;
  return m / (meses / 12);
}

function rubroEscEsperadaActual(x) {
  var fac = parseFloat(x && x.Pdp_FacAnualTon) || 0;
  var ton = tonCostoActivo();
  if (fac > 0.0001 && ton > 0) {
    // ton×$/Ton es el total del horizonte (p.ej. 60 meses). Presup. anual = proyección de 1 año.
    return rubroPresupAnualDesdeMonto(ton * fac, x);
  }
  // Pdp_PreAnual en BD ya viene como presup. del año (guardado desde el modal).
  return parseFloat(x && x.Pdp_PreAnual) || 0;
}

function rubroMontoFijoPeriodo(anual) {
  anual = parseFloat(anual) || 0;
  if (cuadroVista === 'anual') return anual;
  if (cuadroVista === 'mes') return anual / 12;
  return anual * (cuadroMes / 12);
}

function rubroAnualPorEscenario(x, esc) {
  var fac = parseFloat(x && x.Pdp_FacAnualTon) || 0;
  var fPrecio = factorPrecioGastoActivo();
  if (fac > 0.0001) {
    var espAnual = rubroEscEsperadaActual(x);
    var tonEspAnual = parseFloat(escenariosTonAnual.esperada) || 0;
    var tonEsp = (cuadroVista === 'anual')
      ? tonEspAnual
      : (parseFloat(escenariosTonPeriodo.esperada) || 0);
    var tonEsc = (cuadroVista === 'anual')
      ? (parseFloat(escenariosTonAnual[esc]) || 0)
      : (parseFloat(escenariosTonPeriodo[esc]) || 0);
    var espBase = (cuadroVista === 'anual' || tonEspAnual <= 0.0001)
      ? espAnual
      : espAnual * (tonEsp / tonEspAnual);
    // Proyecta gastos con el mismo factor del PVP vs anio base (~12% margen)
    if (esc === 'esperada') {
      return espBase * fPrecio;
    }
    if (tonEsp > 0.0001) {
      return espBase * (tonEsc / tonEsp) * fPrecio;
    }
  }
  var key = 'esc_' + esc;
  if (x && x[key] !== undefined && x[key] !== null && x[key] !== '') {
    return parseFloat(x[key]) || 0;
  }
  return rubroMontoFijoPeriodo(x.Pdp_PreAnual) * fPrecio;
}

function rubroAnualEscenario(x) {
  return rubroAnualPorEscenario(x, escenarioActivo);
}

/** Presupuesto anual fijo del Excel/PDF (referencia historica). */
function rubroAnualBasePdf(x) {
  return parseFloat(x && x.Pdp_PreAnual) || 0;
}

function rubroFactorAnualEscenario(x) {
  if (escenarioActivo === 'esperada') {
    return parseFloat(x.Pdp_FacAnualTon) || 0;
  }
  var escAn = rubroAnualEscenario(x);
  var tonAn = (cuadroVista === 'anual')
    ? (parseFloat(escenariosTonAnual[escenarioActivo]) || 0)
    : (parseFloat(escenariosTonPeriodo[escenarioActivo]) || 0);
  if (escAn > 0 && tonAn > 0) {
    return escAn / tonAn;
  }
  return parseFloat(x.Pdp_FacAnualTon) || 0;
}

function rubroTonMesCosto(x) {
  if (escenarioActivo === 'esperada') {
    return tonCostoActivo();
  }
  var tonRubro = normalizarTonMesRubro(x && x.Pdp_TonBase);
  if (tonRubro > 0) {
    return tonRubro;
  }
  return tonCostoActivo();
}

function tonMesActivo() {
  var t = parseFloat(escenariosTonMes[escenarioActivo]) || 0;
  if (t > 0) return t;
  return tonBaseProy > 0 ? tonBaseProy : 0;
}

function normalizarTonMesRubro(ton) {
  ton = parseFloat(ton) || 0;
  var oper = pdfPreviewTonMensCalc(RUBRO_EDIT_TN_DIA, PDF_PREVIEW_DIAS_FIJO);
  if (ton <= 0 || Math.abs(ton - 105000) < 0.01) {
    return oper;
  }
  return ton;
}

function tonBaseVersionMes(ton) {
  ton = parseFloat(ton) || 0;
  return ton > 0 ? ton : 0;
}

function rubroTonMesEscenario(x) {
  return rubroTonMesCosto(x);
}



function hideToast() {
  if (toastHideTimer) {
    clearTimeout(toastHideTimer);
    toastHideTimer = null;
  }
  $('#msg').stop(true, true).fadeOut(180);
}

function scheduleToastHide(ms) {
  if (toastHideTimer) clearTimeout(toastHideTimer);
  toastHideTimer = setTimeout(function() {
    toastHideTimer = null;
    $('#msg').fadeOut(180);
  }, ms || 4000);
}

function toast(msg, ok){

  var m = $('#msg');

  if (toastHideTimer) {
    clearTimeout(toastHideTimer);
    toastHideTimer = null;
  }
  m.stop(true, true).removeClass('alert-success alert-danger alert-warning')
    .addClass('exa-ppto-toast-float alert-' + (ok ? 'success' : 'danger'))
    .text(msg).fadeIn(120);
  scheduleToastHide(ok ? 2500 : 3500);

}

function toastWarn(msg){

  var m = $('#msg');

  if (toastHideTimer) {
    clearTimeout(toastHideTimer);
    toastHideTimer = null;
  }
  m.stop(true, true).removeClass('alert-success alert-danger')
    .addClass('exa-ppto-toast-float alert-warning')
    .text(msg).fadeIn(120);
  scheduleToastHide(4000);

}

function factorMensual(anual){

  var a = parseFloat(anual);

  if (isNaN(a)) return 0;

  return a / 12;

}

function presupuestoMensual(anual, x) {
  var a = parseFloat(anual) || 0;
  if (cuadroVista === 'mes') {
    return a;
  }
  if (cuadroVista === 'acumulado') {
    return a / Math.max(1, cuadroMes);
  }
  // Anual ya es proyección de 1 año (prorrateado por meses del grupo) → mensual = anual ÷ 12.
  return factorMensual(a);
}

function sumRubrosPresupMensual(rows) {
  var sum = 0;
  $.each(rows || [], function(i, x) {
    sum += presupuestoMensual(rubroAnualEscenario(x), x);
  });
  return sum;
}

function grupoMesesProrrateo(grupoCod) {
  var info = grupoTopeInfo(grupoCod);
  var m = info && info.meses_prorrateo ? parseInt(info.meses_prorrateo, 10) : 12;
  return (m > 0) ? m : 12;
}

function presupuestoMensualGrupo(anual, grupoCod) {
  var meses = grupoMesesProrrateo(grupoCod);
  var a = parseFloat(anual) || 0;
  return meses > 0 ? (a / meses) : 0;
}

function factorMensualGrupo(facAnual, grupoCod) {
  var meses = grupoMesesProrrateo(grupoCod);
  var f = parseFloat(facAnual) || 0;
  return meses > 0 ? (f / meses) : 0;
}

function buscarPartidaGrupoPorCod(cod) {
  var found = null;
  $.each(partidasGrupo, function(i, p) {
    if (p.Ppa_Cla === cod) found = p;
  });
  return found;
}

function reloadCatalogosPartidas(cb) {
  $.getJSON(API, {action:'catalogos'}, function(r) {
    partidasRubro = r.partidas || [];
    partidasGrupo = r.partidas_grupo || [];
    fillPartidasRubro();
    if (cb) cb();
  });
}

function abrirModalPartidaRubro(tipo) {
  var padreId = '';
  var clase = 'G';
  var titulo = 'Nuevo grupo principal';
  var ayuda = 'Capitulo de nivel 1 (ej. 05). Clase Grupo.';
  var grupoCod = $('#rub_grupo_cod').val();
  var subgrupoCod = $('#rub_subgrupo_cod').val();

  if (tipo === 'subgrupo') {
    if (!grupoCod) {
      toast('Seleccione primero un grupo principal.', false);
      return;
    }
    var g = buscarPartidaGrupoPorCod(grupoCod);
    if (!g || !g.Ppa_Cod) {
      toast('No se encontro el grupo ' + grupoCod + ' en el catalogo.', false);
      return;
    }
    padreId = g.Ppa_Cod;
    titulo = 'Nuevo subgrupo bajo ' + grupoCod;
    ayuda = 'Subgrupo intermedio (ej. 05.01). Clase Grupo.';
  } else if (tipo === 'detalle') {
    clase = 'D';
    var subs = listSubgrupos(grupoCod);
    var padre = null;
    if (subs.length > 0) {
      if (!subgrupoCod) {
        toast('Seleccione primero el subgrupo.', false);
        return;
      }
      padre = buscarPartidaGrupoPorCod(subgrupoCod);
    } else {
      if (!grupoCod) {
        toast('Seleccione primero el grupo principal.', false);
        return;
      }
      padre = buscarPartidaGrupoPorCod(grupoCod);
    }
    if (!padre || !padre.Ppa_Cod) {
      toast('No se encontro la partida contenedora en el catalogo.', false);
      return;
    }
    padreId = padre.Ppa_Cod;
    titulo = 'Nueva partida detalle';
    ayuda = 'Cuenta imputable donde registrara el rubro driver (ej. 05.01.01).';
  }

  $('#modal_partida_rubro_tipo').val(tipo);
  $('#modal_partida_rubro_padre_id').val(padreId);
  $('#modal_partida_rubro_clase').val(clase);
  $('#modal_partida_rubro_titulo').text(titulo);
  $('#modal_partida_rubro_ayuda').text(ayuda);
  $('#modal_partida_rubro_descripcion').val('');
  $('#modal_partida_rubro_codigo').val('');

  $.getJSON(API, {action:'sugerir_codigo_partida', padre_id: padreId}, function(r) {
    if (r.status === 'success' && r.codigo) {
      $('#modal_partida_rubro_codigo').val(r.codigo);
    }
    $('#modalPartidaRubro').show();
    $('#modal_partida_rubro_descripcion').focus();
  });
}

function cerrarModalPartidaRubro() {
  $('#modalPartidaRubro').hide();
}

function aplicarPartidaCreada(partida) {
  if (!partida) return;
  var cod = partida.Ppa_Cla || '';
  var jer = parsePartidaJerarquia(cod);
  if (partida.Ppa_Clase === 'G') {
    if ((cod.split('.').length === 1)) {
      $('#rub_grupo_cod').val(cod);
      fillSubgruposRubro();
      fillDetallesRubro();
    } else {
      $('#rub_grupo_cod').val(jer.grupo);
      fillSubgruposRubro();
      $('#rub_subgrupo_cod').val(cod);
      fillDetallesRubro();
    }
  } else {
    setRubroPartidaSeleccion(cod, partida.Ppa_Cod);
  }
}

function rubroNombreDesdePartida(ppaId) {
  var id = ppaId || $('#rub_ppa_id').val();
  if (!id) return '';
  var opt = $('#rub_ppa_id option:selected');
  if (String(opt.val()) === String(id)) {
    var d = opt.data('desc');
    if (d) return d;
  }
  var nombre = '';
  $.each(partidasRubro, function(i, p) {
    if (String(p.Ppa_Cod) === String(id)) nombre = p.Ppa_Des || '';
  });
  return nombre;
}

function updateRubroPartidaResumen() {
  var id = $('#rub_ppa_id').val();
  var $el = $('#rubro_partida_resumen').empty();
  if (!id) return;
  var opt = $('#rub_ppa_id option:selected');
  var nombre = rubroNombreDesdePartida(id);
  $('#pdp_rubro_nombre').val(nombre);
  $el.html('Rubro driver: <strong>' + (opt.text() || nombre) + '</strong>');
  actualizarModalRubroMesesDesdePartida();
  if ($('#modalEditRubro').is(':visible')) {
    calcModalEditRubroPreview();
  }
}

function parsePartidaJerarquia(cod) {
  var parts = (cod || '').split('.');
  return {
    grupo: parts[0] || '',
    subgrupo: parts.length >= 3 ? (parts[0] + '.' + parts[1]) : ''
  };
}

function partidaBajoPrefijo(cod, prefijo) {
  if (!prefijo || !cod) return false;
  return cod === prefijo || cod.indexOf(prefijo + '.') === 0;
}

function listGruposPrincipales() {
  var map = {};
  var orden = [];
  $.each(partidasGrupo, function(i, p) {
    var cod = p.Ppa_Cla || '';
    var parts = cod.split('.');
    if (parts.length !== 1) return;
    if (!map[cod]) {
      map[cod] = p;
      orden.push(cod);
    }
  });
  if (!orden.length) {
    $.each(partidasRubro, function(i, p) {
      var gk = (p.Ppa_Cla || '').split('.')[0];
      if (gk && !map[gk]) {
        map[gk] = { Ppa_Cla: gk, Ppa_Des: 'Grupo ' + gk };
        orden.push(gk);
      }
    });
  }
  orden.sort();
  var out = [];
  $.each(orden, function(i, cod) { out.push(map[cod]); });
  return out;
}

function listSubgrupos(grupoCod) {
  if (!grupoCod) return [];
  var out = [];
  $.each(partidasGrupo, function(i, p) {
    var cod = p.Ppa_Cla || '';
    var parts = cod.split('.');
    if (parts.length === 2 && parts[0] === grupoCod) out.push(p);
  });
  out.sort(function(a, b) {
    return (a.Ppa_Cla > b.Ppa_Cla) ? 1 : -1;
  });
  return out;
}

function listDetallesRubro(grupoCod, subgrupoCod) {
  if (!grupoCod) return [];
  var subs = listSubgrupos(grupoCod);
  var prefijo = subgrupoCod || grupoCod;
  if (subs.length > 0 && !subgrupoCod) return [];
  var out = [];
  $.each(partidasRubro, function(i, p) {
    if (p.Ppa_Clase && p.Ppa_Clase !== 'D') return;
    var cod = p.Ppa_Cla || '';
    if (!partidaBajoPrefijo(cod, prefijo)) return;
    if (subgrupoCod) {
      if (cod.split('.').length < 3) return;
    } else if (subs.length === 0) {
      if (cod.split('.').length < 2) return;
    }
    out.push(p);
  });
  out.sort(function(a, b) {
    return (a.Ppa_Cla > b.Ppa_Cla) ? 1 : -1;
  });
  return out;
}

function fillGruposRubro() {
  var sel = $('#rub_grupo_cod');
  var val = sel.val();
  sel.empty().append('<option value="">-- Grupo --</option>');
  $.each(listGruposPrincipales(), function(i, p) {
    sel.append('<option value="' + p.Ppa_Cla + '">' + p.Ppa_Cla + ' - ' + p.Ppa_Des + '</option>');
  });
  if (val) sel.val(val);
}

function fillSubgruposRubro() {
  var grupoCod = $('#rub_grupo_cod').val();
  var sel = $('#rub_subgrupo_cod');
  var val = sel.val();
  sel.empty();
  var subs = listSubgrupos(grupoCod);
  if (!grupoCod) {
    sel.append('<option value="">-- Subgrupo --</option>').prop('disabled', true);
    return;
  }
  if (!subs.length) {
    sel.append('<option value="">(sin subgrupos)</option>').prop('disabled', true);
    return;
  }
  sel.append('<option value="">-- Subgrupo --</option>');
  $.each(subs, function(i, p) {
    sel.append('<option value="' + p.Ppa_Cla + '">' + p.Ppa_Cla + ' - ' + p.Ppa_Des + '</option>');
  });
  sel.prop('disabled', false);
  if (val && subs.length) {
    var ok = false;
    $.each(subs, function(i, p) { if (p.Ppa_Cla === val) ok = true; });
    if (ok) sel.val(val);
  }
}

function fillDetallesRubro() {
  var grupoCod = $('#rub_grupo_cod').val();
  var subgrupoCod = $('#rub_subgrupo_cod').val();
  var sel = $('#rub_ppa_id');
  var val = sel.val();
  sel.empty();
  if (!grupoCod) {
    sel.append('<option value="">-- Detalle --</option>').prop('disabled', true);
    return;
  }
  var subs = listSubgrupos(grupoCod);
  if (subs.length > 0 && !subgrupoCod) {
    sel.append('<option value="">Seleccione subgrupo</option>').prop('disabled', true);
    return;
  }
  var detalles = listDetallesRubro(grupoCod, subgrupoCod);
  if (!detalles.length) {
    sel.append('<option value="">Sin partidas detalle</option>').prop('disabled', true);
    return;
  }
  sel.append('<option value="">-- Detalle --</option>');
  $.each(detalles, function(i, p) {
    sel.append('<option value="' + p.Ppa_Cod + '" data-desc="' + (p.Ppa_Des || '').replace(/"/g, '&quot;') + '">' + p.Ppa_Cla + ' - ' + p.Ppa_Des + '</option>');
  });
  sel.prop('disabled', false);
  if (val) {
    var ok = false;
    $.each(detalles, function(i, p) { if (String(p.Ppa_Cod) === String(val)) ok = true; });
    if (ok) sel.val(val);
  }
  updateRubroPartidaResumen();
}

function fillPartidasRubro() {
  fillGruposRubro();
  fillSubgruposRubro();
  fillDetallesRubro();
}

function setRubroPartidaSeleccion(codigo, ppaId) {
  var jer = parsePartidaJerarquia(codigo || '');
  $('#rub_grupo_cod').val(jer.grupo);
  fillSubgruposRubro();
  if (jer.subgrupo) $('#rub_subgrupo_cod').val(jer.subgrupo);
  fillDetallesRubro();
  if (ppaId) $('#rub_ppa_id').val(ppaId);
}

function versionPpeId(v) {
  if (!v) return '';
  var id = (v.Ppe_Cod != null && v.Ppe_Cod !== '') ? v.Ppe_Cod : (v.ppe_id != null ? v.ppe_id : '');
  return String(id || '');
}

function versionEst(v) {
  return String((v && (v.Ppe_Est || v.ppe_estado)) || '').toUpperCase();
}

function versionAnio(v) {
  return parseInt((v && (v.Ppe_Ani || v.ppe_anio)) || 0, 10);
}

function currentRubPpeId() {
  return String($('#rub_ppe_id').val() || '').trim();
}

function asegurarVersionServidor(cb) {
  $.getJSON(API, {action: 'asegurar_version'}, function(r) {
    if (r && r.status === 'success' && r.Ppe_Cod) {
      $('#rub_ppe_id').val(String(r.Ppe_Cod));
    }
    if (cb) cb(!!currentRubPpeId(), currentRubPpeId());
  }).fail(function() {
    if (cb) cb(false, '');
  });
}

function ensureRubPpeId(done) {
  var ppe = currentRubPpeId();
  if (ppe) {
    if (done) done(true, ppe);
    return;
  }
  asegurarVersionServidor(done);
}

function requireProyectoYPpe(onReady, msgProy) {
  var proy = String($('#rub_proy_id').val() || '').trim();
  if (!proy) {
    toast(msgProy || 'Seleccione un proyecto.', false);
    return;
  }
  ensureRubPpeId(function(ok, ppe) {
    if (!ok) {
      toast('No se pudo obtener la cabecera presupuestaria activa. Recargue la pagina (Ctrl+F5).', false);
      return;
    }
    onReady(proy, ppe);
  });
}

function fillVersionesSelect(versiones, selectedId){
  // Sin selector de version: usa cabecera activa (Ppe_Est=A) o la indicada.
  var list = versiones || [];
  var pick = null;
  var anioNow = (new Date()).getFullYear();
  if (selectedId) {
    $.each(list, function(i, v) {
      if (versionPpeId(v) === String(selectedId)) { pick = v; return false; }
    });
  }
  if (!pick) {
    $.each(list, function(i, v) {
      if (versionEst(v) === 'A' && versionAnio(v) === anioNow) { pick = v; return false; }
    });
  }
  if (!pick) {
    $.each(list, function(i, v) {
      if (versionEst(v) === 'A') { pick = v; return false; }
    });
  }
  if (!pick && list.length) {
    pick = list[0];
  }
  $('#rub_ppe_id').val(pick ? versionPpeId(pick) : '');
}

function loadCatalogos(cb){

  $.getJSON(API, {action:'catalogos'}, function(r){

    $('#Plt_Cod').html('<option value="">-- Sin plantilla --</option>');

    $.each(r.plantillas||[], function(i,p){ $('#Plt_Cod').append('<option value="'+p.Plt_Cod+'">'+p.Plt_Nom+'</option>'); });

    partidasRubro = r.partidas || [];
    partidasGrupo = r.partidas_grupo || [];

    fillPartidasRubro();
    fillVersionesSelect(r.versiones||[]);

    if (!currentRubPpeId()) {
      asegurarVersionServidor(function(){ if (cb) cb(); });
      return;
    }

    if (cb) cb();

  }).fail(function(){
    toast('Error al cargar catalogos. Recargue la pagina (Ctrl+F5).', false);
    asegurarVersionServidor(function(){ if (cb) cb(); });
  });

}

function crearNuevaVersion(){
  var anio = prompt('Anio de la nueva version presupuestaria:', String(new Date().getFullYear()));
  if (anio === null) return;
  anio = parseInt(anio, 10);
  if (!anio || anio < 2000) { toast('Anio invalido.', false); return; }
  var des = prompt('Descripcion (ej. Presupuesto Relavera ' + anio + '):', 'Version proyectos ' + anio);
  if (des === null) return;
  if (!confirm('Se creara solo la cabecera ' + anio + ' (sin rubros corporativos).\nLuego podra importar/cargar rubros del proyecto.\nEstado: Aprobado y Activa.')) return;

  $.post(API, {
    action: 'crear_version',
    Ppe_Ani: anio,
    Ppe_Des: des,
    Ppe_Est: 'A'
  }, function(r){
    if (!r || r.status !== 'success') {
      toast((r && r.message) ? r.message : 'No se pudo crear la version.', false);
      return;
    }
    toast(r.message, true);
    loadCatalogos(function(){
      if (r.Ppe_Cod) {
        $('#rub_ppe_id').val(String(r.Ppe_Cod));
        reloadRubrosSection();
      }
    });
  }, 'json').fail(function(){ toast('Error de red al crear version.', false); });
}

function syncProyectoSelects(sourceId) {
  var val = $('#' + sourceId).val() || '';
  var $targets = $('#rub_proy_id, #cuadro_proy_id').not('#' + sourceId);
  $targets.each(function() {
    if ($(this).val() !== val) {
      $(this).val(val);
    }
  });
}

function loadProyectos(cb){

  $.getJSON(API, {action:'list'}, function(r){

    var tb=$('#tblProy tbody').empty();
    var prev = $('#rub_proy_id').val() || $('#cuadro_proy_id').val() || '';
    var $sels = $('#rub_proy_id, #cuadro_proy_id').empty();

    $.each(r.rows||[], function(i,p){
      var cod = p.Pro_Codigo || p.proy_codigo || p.Pro_Cod;
      tb.append('<tr><td>'+cod+'</td><td>'+p.Pro_Nom+'</td><td>'+p.Pro_Est+'</td><td>'+(p.Plt_Nom||'-')+'</td><td><button class="btn btn-xs btn-default btnEdit" data-json=\''+JSON.stringify(p)+'\'>Editar</button></td></tr>');
      $sels.append('<option value="'+p.Pro_Cod+'">'+cod+' - '+p.Pro_Nom+'</option>');

    });

    if (prev && $sels.find('option[value="' + prev + '"]').length) {
      $sels.val(prev);
    } else if ($sels.first().find('option').length) {
      $sels.val($sels.first().find('option').first().val());
    }

    if (cb) cb();

  }).fail(function(){
    toast('Error al cargar proyectos. Recargue la pagina (Ctrl+F5).', false);
    if (cb) cb();
  });

}

function versionTonPayload(aplicarRubros) {
  return {
    action: 'save_version_ton',
    Pro_Cod: $('#rub_proy_id').val(),
    Ppe_Cod: $('#rub_ppe_id').val(),
    pv_toneladas_base_mes: $('#pv_toneladas_base_mes').val(),
    pv_toneladas_costo_mes: $('#pv_toneladas_costo_mes').val(),
    pv_tarifa_ton_iva: $('#pv_tarifa_ton_iva').val() || 3,
    pv_iva_divisor: $('#pv_iva_divisor').val() || 1.15,
    aplicar_rubros: aplicarRubros ? 1 : 0
  };
}

function loadVersionConfig(cb){

  var proy = $('#rub_proy_id').val();

  var ppe = $('#rub_ppe_id').val() || '';

  if (!proy || !ppe) {

    tonBaseProy = 0;

    $('#pv_toneladas_base_mes').val('');
    $('#pv_toneladas_costo_mes').val('');

    if (cb) cb();

    return;

  }

  $.getJSON(API, {action:'get_version_config', Pro_Cod:proy, Ppe_Cod:ppe}, function(r){

    if (r.status === 'success') {

      tonBaseProy = tonBaseVersionMes(parseFloat(r.pv_toneladas_base_mes) || 0);

      $('#pv_toneladas_base_mes').val(tonBaseProy > 0 ? tonBaseProy : '');

      var tonCosto = parseFloat(r.pv_toneladas_costo_mes) || 0;
      $('#pv_toneladas_costo_mes').val(tonCosto > 0 ? tonCosto : '');

      if (r.pv_tarifa_ton_iva) $('#pv_tarifa_ton_iva').val(r.pv_tarifa_ton_iva);

      if (r.pv_iva_divisor) $('#pv_iva_divisor').val(r.pv_iva_divisor);

    } else {

      tonBaseProy = 0;

    }

    if (cb) cb();

  });

}

function rubroGrupoPrincipal(cod) {
  var c = (cod || '').split('.');
  return c[0] || '00';
}

function normGrupoCod(cod) {
  if (cod === undefined || cod === null) return '';
  return String(cod).trim();
}

function tonCostoActivo() {
  var t = parseFloat($('#pv_toneladas_costo_mes').val());
  if (!isNaN(t) && t > 0) return t;
  return pdfPreviewTonMensCalc(RUBRO_EDIT_TN_DIA, PDF_PREVIEW_DIAS_FIJO);
}

function grupoTonBaseMes(rows) {
  if (escenarioActivo === 'esperada') {
    return tonCostoActivo();
  }
  var tonAn = parseFloat(escenariosTonAnual[escenarioActivo]) || 0;
  if (tonAn > 0) {
    return tonAn / 12;
  }
  var sum = 0;
  var cnt = 0;
  $.each(rows || [], function(i, x) {
    var t = normalizarTonMesRubro(x.Pdp_TonBase);
    if (t > 0) {
      sum += t;
      cnt++;
    }
  });
  if (cnt > 0) {
    return sum / cnt;
  }
  return tonCostoActivo();
}

function grupoFactorAnual(total, tonMes) {
  var t = parseFloat(tonMes) || 0;
  var tot = parseFloat(total) || 0;
  return t > 0 ? (tot / t) : 0;
}

function rubroSubgrupoCod(cod) {
  var p = (cod || '').split('.');
  if (p.length >= 3) return p[0] + '.' + p[1];
  return '';
}

function agruparPorSubgrupo(rows) {
  var subs = {};
  var orden = [];
  var sinSub = [];
  $.each(rows || [], function(i, x) {
    var sk = x.subgrupo_cod || rubroSubgrupoCod(x.Ppa_Cla);
    if (!sk) {
      sinSub.push(x);
      return;
    }
    if (!subs[sk]) {
      subs[sk] = {
        cod: sk,
        nombre: x.subgrupo_descripcion || ('Subgrupo ' + sk),
        rows: [],
        total: 0
      };
      orden.push(sk);
    }
    var anual = rubroAnualEscenario(x);
    subs[sk].rows.push(x);
    subs[sk].total += anual;
  });
  return { subgrupos: subs, orden: orden, sinSub: sinSub };
}

function grupoTopeInfo(cod) {
  return gruposTopeCache[cod] || null;
}

function formatPctTopeInput(pct) {
  var n = parseFloat(pct);
  if (!n || isNaN(n) || n <= 0) return '';
  return String(parseFloat(n.toFixed(4)));
}

function grupoPctCuadroHtml(info) {
  if (!info || !info.Ppa_Cod) return '';
  var val = formatPctTopeInput(parseFloat(info.tope_pct) || 0);
  return '<span class="grupo-pct-wrap">'
    + '<input type="number" class="form-control input-sm grupo-pct-edit" min="0" max="100" step="0.0001" '
    + 'data-ppa-id="' + info.Ppa_Cod + '" data-grupo-cod="' + info.codigo + '" value="' + val + '" title="% tope del grupo" />'
    + '<button type="button" class="btn btn-default btn-xs btn-save-grupo-pct" data-ppa-id="' + info.Ppa_Cod + '" title="Guardar %">OK</button>'
    + '</span>';
}

function grupoTopeCuadroHtml(info) {
  if (!info || !info.Ppa_Cod) return '<span class="text-muted">-</span>';
  var topeAnual = parseFloat(info.tope_anual) || 0;
  var usado = parseFloat(info.usado_pct) || 0;
  if (topeAnual <= 0) return '<span class="text-muted">-</span>';
  var badgeCls = info.excedido ? 'label label-danger grupo-tope-val' : 'label label-default grupo-tope-val';
  var tip = info.formula ? info.formula : 'Tope anual del grupo';
  return '<span class="grupo-tope-wrap" title="' + tip + '">'
    + '<span class="' + badgeCls + '">' + formatCurrency(topeAnual) + '</span>'
    + (usado > 0 ? '<span class="grupo-tope-usado" style="color:' + (info.excedido ? '#c53030' : '#718096') + ';">' + formatNumber(usado, 1) + '% usado</span>' : '')
    + '</span>';
}

function grupoMesesControlHtml(info) {
  if (!info || !info.Ppa_Cod) return '';
  var m = parseInt(info.meses_prorrateo, 10) || 12;
  return '<span class="grupo-meses-wrap" title="Meses del horizonte. Anual = (ton x $/t) / (meses/12); mensual = anual / 12.">'
    + '<span class="grupo-meses-label">Meses</span>'
    + '<input type="number" class="form-control input-sm grupo-meses-edit" min="1" max="999" step="1" '
    + 'data-ppa-id="' + info.Ppa_Cod + '" data-grupo-cod="' + info.codigo + '" value="' + m + '" />'
    + '<button type="button" class="btn btn-default btn-xs btn-save-grupo-meses" data-ppa-id="' + info.Ppa_Cod + '" title="Guardar meses">OK</button>'
    + '</span>';
}

function grupoPctControlHtml(info) {
  if (!info || !info.Ppa_Cod) return '';
  return grupoPctCuadroHtml(info);
}

function subgrupoTopeInfo(sg) {
  if (!sg || !sg.cod) return null;
  return grupoTopeInfo(sg.cod);
}

function cuadroUsaSubgrupos(rows) {
  if (!rows || !rows.length) return false;
  var agr = agruparPorSubgrupo(rows);
  if (agr.sinSub.length > 0) return false;
  if (!agr.orden.length) return false;
  return agr.orden.length > 1 || rubroSubgrupoCod(rows[0].Ppa_Cla) !== '';
}

function rubroRowHtml(x, indent) {
  var f = parseFloat(cuadroFinalFactorCtx) || 1;
  var facAnualBase = rubroFactorAnualEscenario(x);
  var anualBase = rubroAnualEscenario(x);
  var mensualBase = presupuestoMensual(anualBase, x);
  var facAnual = facAnualBase * f;
  var facMes = factorMensual(facAnual);
  var anual = anualBase * f;
  var mensual = presupuestoMensual(anual, x);
  var json = JSON.stringify(x).replace(/'/g, '&#39;');
  var trCls = indent ? ' class="exa-ppto-rubro-indent"' : '';
  var celAnual = (Math.abs(f - 1) < 0.00001)
    ? '<strong>' + formatCurrency(anualBase) + '</strong>'
    : htmlCeldaPresupDual(anualBase, anual);
  var celMensual = (Math.abs(f - 1) < 0.00001)
    ? formatCurrency(mensualBase)
    : htmlCeldaPresupDual(mensualBase, mensual);
  return '<tr' + trCls + '>'
    + '<td><span class="text-muted">' + x.Ppa_Cla + '</span></td>'
    + '<td>' + x.Pdp_Rubro + '</td>'
    + '<td class="text-right">' + formatNumber(rubroTonMesEscenario(x), 2) + '</td>'
    + '<td class="text-right">' + formatNumber(facAnual, 4) + '</td>'
    + '<td class="text-right">' + formatNumber(facMes, 6) + '</td>'
    + '<td class="text-right">' + celAnual + '</td>'
    + '<td class="text-right">' + celMensual + '</td>'
    + '<td class="text-center exa-ppto-rubro-actions-cell"><span class="exa-ppto-rubro-actions">'
    + '<button type="button" class="btn btn-xs btn-info btn-edit-rubro" title="Editar" data-json=\'' + json + '\'><i class="bi bi-pencil-square"></i></button>'
    + '<button type="button" class="btn btn-xs btn-danger btn-del-rubro" title="Eliminar" data-json=\'' + json + '\'><i class="bi bi-trash"></i></button>'
    + '</span></td>'
    + '</tr>';
}

function subgrupoHeadHtml(sg, tonMes, grupoCod) {
  var f = parseFloat(cuadroFinalFactorCtx) || 1;
  var totalBase = sg.total;
  var total = totalBase * f;
  var facAnual = grupoFactorAnual(total, tonMes);
  var facMes = factorMensual(facAnual);
  var totalMesBase = sumRubrosPresupMensual(sg.rows);
  var totalMes = totalMesBase * f;
  var topeInfo = subgrupoTopeInfo(sg);
  var topeHtml = topeInfo ? grupoTopeCuadroHtml(topeInfo) : '';
  var txtAnual = (Math.abs(f - 1) < 0.00001)
    ? formatCurrency(totalBase)
    : (formatCurrency(totalBase) + ' â†’ <strong style="color:#276749;">' + formatCurrency(total) + '</strong>');
  var txtMes = (Math.abs(f - 1) < 0.00001)
    ? formatCurrency(totalMesBase)
    : (formatCurrency(totalMesBase) + ' â†’ <strong style="color:#276749;">' + formatCurrency(totalMes) + '</strong>');
  return '<tr class="exa-ppto-subgrupo-head">'
    + '<td colspan="8">'
    + '<div class="exa-ppto-subgrupo-head-inner">'
    + '<span class="subgrupo-cod">' + sg.cod + '</span>'
    + '<span class="subgrupo-nom">' + sg.nombre + '</span>'
    + (topeHtml ? '<span class="subgrupo-tope-inline">' + topeHtml + '</span>' : '')
    + '<span class="subgrupo-metrics">'
    + '<span class="badge">' + sg.rows.length + ' rubro' + (sg.rows.length === 1 ? '' : 's') + '</span>'
    + '<span class="subgrupo-total">Anual: ' + txtAnual + '</span>'
    + '<span class="subgrupo-total-mes">Mens: ' + txtMes + '</span>'
    + '<span class="subgrupo-ton">$/Ton anual: ' + (facAnual > 0 ? formatNumber(facAnual, 4) : '-') + '</span>'
    + '<span class="subgrupo-ton">$/Ton mens: ' + (facMes > 0 ? formatNumber(facMes, 6) : '-') + '</span>'
    + '</span>'
    + '</div>'
    + '</td>'
    + '</tr>';
}

function subgrupoFootHtml(sg, tonMes, grupoCod) {
  var f = parseFloat(cuadroFinalFactorCtx) || 1;
  var totalBase = sg.total;
  var total = totalBase * f;
  var facAnual = grupoFactorAnual(total, tonMes);
  var facMes = factorMensual(facAnual);
  var totalMesBase = sumRubrosPresupMensual(sg.rows);
  var totalMes = totalMesBase * f;
  return '<tr class="exa-ppto-subgrupo-foot">'
    + '<td colspan="3" class="text-right">Subtotal ' + sg.cod + '</td>'
    + '<td class="text-right">' + (facAnual > 0 ? formatNumber(facAnual, 4) : '-') + '</td>'
    + '<td class="text-right">' + (facMes > 0 ? formatNumber(facMes, 6) : '-') + '</td>'
    + '<td class="text-right">' + htmlCeldaPresupDual(totalBase, total) + '</td>'
    + '<td class="text-right">' + htmlCeldaPresupDual(totalMesBase, totalMes) + '</td><td></td>'
    + '</tr>';
}

function filtrarRubrosGrupo(grupoCod) {
  var needle = normGrupoCod(grupoCod);
  var out = [];
  $.each(rubrosCache || [], function(i, x) {
    var gk = normGrupoCod(x.grupo_cod || rubroGrupoPrincipal(x.Ppa_Cla));
    if (gk === needle) out.push(x);
  });
  return out;
}

function grupoResumenMeta(grupoCod, rows) {
  var nombre = '';
  if (rows.length && rows[0].grupo_descripcion) {
    nombre = rows[0].grupo_descripcion;
  }
  var tonMes = rows.length ? grupoTonBaseMes(rows) : tonCostoActivo();
  var totalAnual = 0;
  $.each(rows, function(i, x) { totalAnual += rubroAnualEscenario(x); });
  var totalMes = sumRubrosPresupMensual(rows);
  var facAnual = grupoFactorAnual(totalAnual, tonMes);
  var facMes = factorMensual(facAnual);
  return {
    cod: normGrupoCod(grupoCod),
    nombre: nombre || ('Grupo ' + normGrupoCod(grupoCod)),
    rows: rows,
    tonMes: tonMes,
    totalAnual: totalAnual,
    totalMes: totalMes,
    facAnual: facAnual,
    facMes: facMes
  };
}

function grupoResumenFilaRubro(x) {
  var facAnual = rubroFactorAnualEscenario(x);
  var facMes = factorMensual(facAnual);
  var anual = rubroAnualEscenario(x);
  var mensual = presupuestoMensual(anual, x);
  var desc = x.Pdp_Rubro || x.Ppa_Des || '';
  return '<tr>'
    + '<td><strong>' + (x.Ppa_Cla || '') + '</strong></td>'
    + '<td>' + desc + '</td>'
    + '<td class="text-right">' + formatNumber(rubroTonMesEscenario(x), 2) + '</td>'
    + '<td class="text-right">' + (facAnual > 0 ? formatNumber(facAnual, 4) : '-') + '</td>'
    + '<td class="text-right">' + (facMes > 0 ? formatNumber(facMes, 6) : '-') + '</td>'
    + '<td class="text-right"><strong>' + formatCurrency(anual) + '</strong></td>'
    + '<td class="text-right">' + formatCurrency(mensual) + '</td>'
    + '</tr>';
}

function renderGrupoResumenModal(grupoCod) {
  var rows = filtrarRubrosGrupo(grupoCod);
  var meta = grupoResumenMeta(grupoCod, rows);
  var topeInfo = grupoTopeInfo(grupoCod);
  $('#grupo_resumen_titulo').text('Resumen grupo ' + meta.cod + ' - ' + meta.nombre);
  $('#grupo_resumen_subtitulo').text(
    rows.length + ' rubro' + (rows.length === 1 ? '' : 's') + ' cargados'
    + (topeInfo && topeInfo.tope_anual > 0 ? ' Â· Tope anual: ' + formatCurrency(topeInfo.tope_anual) : '')
  );
  $('#grupo_resumen_kpi').html(
    '<div class="item"><span class="lbl">Presup. anual</span><span class="val">' + formatCurrency(meta.totalAnual) + '</span></div>'
    + '<div class="item"><span class="lbl">Presup. mensual</span><span class="val val-mes">' + formatCurrency(meta.totalMes) + '</span></div>'
    + '<div class="item"><span class="lbl">$/Ton anual</span><span class="val val-ton">' + (meta.facAnual > 0 ? formatNumber(meta.facAnual, 4) : '-') + '</span></div>'
    + '<div class="item"><span class="lbl">$/Ton mensual</span><span class="val val-ton">' + (meta.facMes > 0 ? formatNumber(meta.facMes, 6) : '-') + '</span></div>'
    + '<div class="item"><span class="lbl">Ton/mes costo</span><span class="val val-ton">' + (meta.tonMes > 0 ? formatNumber(meta.tonMes, 0) : '-') + '</span></div>'
    + '<div class="item"><span class="lbl">Rubros</span><span class="val">' + rows.length + '</span></div>'
  );
  var tb = $('#grupo_resumen_tbody').empty();
  if (!rows.length) {
    tb.append('<tr><td colspan="7" class="text-center text-muted">Sin rubros en este grupo.</td></tr>');
    $('#grupo_resumen_tfoot').empty();
    return;
  }
  if (cuadroUsaSubgrupos(rows)) {
    var agr = agruparPorSubgrupo(rows);
    $.each(agr.orden, function(i, sk) {
      var sg = agr.subgrupos[sk];
      var sgMes = sumRubrosPresupMensual(sg.rows);
      tb.append('<tr class="grupo-resumen-subhead"><td colspan="5"><strong>' + sg.cod + '</strong> - ' + sg.nombre + '</td>'
        + '<td class="text-right"><strong>' + formatCurrency(sg.total) + '</strong></td>'
        + '<td class="text-right"><strong>' + formatCurrency(sgMes) + '</strong></td></tr>');
      $.each(sg.rows, function(j, x) { tb.append(grupoResumenFilaRubro(x)); });
    });
    $.each(agr.sinSub, function(i, x) { tb.append(grupoResumenFilaRubro(x)); });
  } else {
    $.each(rows, function(i, x) { tb.append(grupoResumenFilaRubro(x)); });
  }
  $('#grupo_resumen_tfoot').html(
    '<tr style="background:#f7fafc;font-weight:600;">'
    + '<td colspan="5" class="text-right">Total grupo ' + meta.cod + '</td>'
    + '<td class="text-right">' + formatCurrency(meta.totalAnual) + '</td>'
    + '<td class="text-right">' + formatCurrency(meta.totalMes) + '</td>'
    + '</tr>'
  );
}

function abrirGrupoResumen(grupoCod) {
  if (!grupoCod) return;
  renderGrupoResumenModal(grupoCod);
  $('#modalGrupoResumen').show();
}

function buildGrupoTableRows(rows, tonMesGrupo, grupoCod) {
  // === CUADRO_PARTIDA_FINAL_UI (reversible) ===
  cuadroFinalFactorCtx = factorFinalParaGrupo(grupoCod);
  // === end ===
  var html = '';
  var usarSub = cuadroUsaSubgrupos(rows);
  if (!usarSub) {
    $.each(rows || [], function(i, x) { html += rubroRowHtml(x, false); });
    cuadroFinalFactorCtx = 1;
    return html;
  }
  var agr = agruparPorSubgrupo(rows);
  $.each(agr.orden, function(i, sk) {
    var sg = agr.subgrupos[sk];
    html += subgrupoHeadHtml(sg, tonMesGrupo, grupoCod);
    $.each(sg.rows, function(j, x) { html += rubroRowHtml(x, true); });
    html += subgrupoFootHtml(sg, tonMesGrupo, grupoCod);
  });
  $.each(agr.sinSub, function(i, x) { html += rubroRowHtml(x, false); });
  cuadroFinalFactorCtx = 1;
  return html;
}

var rubrosPageSize = 100;
var rubrosPage = 1;
var rubrosPageRows = [];

function renderTablaRubros(rows) {
  rubrosPageRows = rows || [];
  rubrosPage = 1;
  renderTablaRubrosPage();
}

function renderTablaRubrosPage() {
  var $tb = $('#tblRubros tbody').empty();
  var total = rubrosPageRows.length;
  var $pager = $('#tblRubrosPager');
  if (!total) {
    $tb.append('<tr><td colspan="8" class="text-center text-muted" style="padding:24px;">Sin rubros para este proyecto.</td></tr>');
    if ($pager.length) $pager.hide();
    return;
  }
  var pages = Math.max(1, Math.ceil(total / rubrosPageSize));
  if (rubrosPage > pages) rubrosPage = pages;
  if (rubrosPage < 1) rubrosPage = 1;
  var start = (rubrosPage - 1) * rubrosPageSize;
  var slice = rubrosPageRows.slice(start, start + rubrosPageSize);
  $.each(slice, function(i, x) {
    $tb.append(rubroRowHtml(x, false));
  });
  if ($pager.length) {
    if (total > rubrosPageSize) {
      var from = start + 1;
      var to = start + slice.length;
      $pager.find('.pager-info').text(from + '-' + to + ' de ' + total + ' rubros');
      $pager.find('.pager-page').text('Pag. ' + rubrosPage + ' / ' + pages);
      $pager.find('.btn-pager-prev').prop('disabled', rubrosPage <= 1);
      $pager.find('.btn-pager-next').prop('disabled', rubrosPage >= pages);
      $pager.show();
    } else {
      $pager.hide();
    }
  }
}

function rubrosPagerIr(delta) {
  var pages = Math.max(1, Math.ceil(rubrosPageRows.length / rubrosPageSize));
  rubrosPage = Math.min(pages, Math.max(1, rubrosPage + delta));
  renderTablaRubrosPage();
}

/* === CUADRO_PARTIDA_FINAL_UI (reversible) START === */
var cuadroFinalFactorCtx = 1;

/** Simulacion del escenario activo (capital/GAD recalculados por escenario). */
function ajusteSimActivoActual() {
  var esc = escenarioActivo || 'esperada';
  if (typeof ajusteSimByEsc !== 'undefined' && ajusteSimByEsc && ajusteSimByEsc[esc] && ajusteSimByEsc[esc].ok) {
    return ajusteSimByEsc[esc];
  }
  if (ajusteSimCache && ajusteSimCache.ok) {
    return ajusteSimCache;
  }
  return null;
}

function cuadroUsaPartidaFinal() {
  var checked = $('#aj_activo').is(':checked')
    || (ajusteCfgCache && parseInt(ajusteCfgCache.ajuste_activo, 10) === 1);
  var sim = ajusteSimActivoActual();
  return !!(checked && sim && sim.ok && sim.detalle);
}

function ajusteMapaFinalPorGrupo() {
  var map = {};
  var sim = ajusteSimActivoActual();
  if (!sim || !sim.detalle) return map;
  $.each(sim.detalle, function(i, d) {
    var cod = normGrupoCod(d.grupo_cod);
    map[cod] = {
      partida_base: parseFloat(d.partida_base) || 0,
      partida_final: parseFloat(d.partida_final) || 0,
      final_por_ton: parseFloat(d.final_por_ton) || 0,
      base_por_ton: parseFloat(d.base_por_ton) || 0
    };
  });
  return map;
}

function factorFinalParaGrupo(grupoCod) {
  if (!cuadroUsaPartidaFinal()) return 1;
  var m = ajusteMapaFinalPorGrupo()[normGrupoCod(grupoCod)];
  if (!m) return 1;
  var base = parseFloat(m.partida_base) || 0;
  if (base <= 0.0001) return 1;
  return (parseFloat(m.partida_final) || 0) / base;
}

function cuadroPresupMesDesdePeriodo(montoPeriodo) {
  montoPeriodo = parseFloat(montoPeriodo) || 0;
  if (cuadroVista === 'mes') return montoPeriodo;
  if (cuadroVista === 'acumulado') {
    var m = parseInt(cuadroMes, 10) || 1;
    return m > 0 ? (montoPeriodo / m) : montoPeriodo;
  }
  return montoPeriodo / 12;
}

function htmlGrupoPresupDual(baseVal, finalVal, isMes, targetSel) {
  return '<span class="grupo-metric col-num'
    + (isMes ? ' val-mes' : '')
    + ' grupo-presup-dual cuadro-grupo-toggle" data-target="' + targetSel + '" title="Base: '
    + formatCurrency(baseVal) + ' | Final: ' + formatCurrency(finalVal) + '">'
    + '<span class="presup-base">' + formatCurrency(baseVal) + '</span>'
    + '<span class="presup-final">' + formatCurrency(finalVal) + '</span>'
    + '</span>';
}

function htmlCeldaPresupDual(baseVal, finalVal) {
  baseVal = parseFloat(baseVal) || 0;
  finalVal = parseFloat(finalVal) || 0;
  if (Math.abs(baseVal - finalVal) < 0.005) {
    return '<strong>' + formatCurrency(baseVal) + '</strong>';
  }
  return '<span class="grupo-presup-dual" style="display:inline-flex;flex-direction:column;align-items:flex-end;line-height:1.15;">'
    + '<span class="presup-base">' + formatCurrency(baseVal) + '</span>'
    + '<span class="presup-final">' + formatCurrency(finalVal) + '</span>'
    + '</span>';
}
/* === CUADRO_PARTIDA_FINAL_UI (reversible) END === */

var cuadroGruposLazyMap = {};

function renderCuadroRubros(rows, gruposTope) {
  rubrosCache = rows || [];
  gruposTopeCache = gruposTope || {};
  cuadroGruposLazyMap = {};
  var $acc = $('#rubrosCuadroAccordion').empty();
  var $empty = $('#rubrosCuadroEmpty');
  // === CUADRO_PARTIDA_FINAL_UI (reversible) ===
  var usarFinal = cuadroUsaPartidaFinal();
  var mapaFinal = usarFinal ? ajusteMapaFinalPorGrupo() : {};
  var totalFinalSum = 0;
  var totalFinalMesSum = 0;
  // === end ===

  if (!rows || !rows.length) {
    $empty.show();
    $('#rubrosCuadroHead').hide();
    $('#cuadroKpiGrupos').text('0');
    $('#cuadroKpiRubros').text('0');
    $('#cuadroKpiTon').text(tonMesActivo() > 0 ? formatNumber(tonMesActivo(), 0) : '-');
    $('#cuadroKpiTotal').text(formatCurrency(0));
    $('#cuadroKpiTotalMes').text(formatCurrency(0));
    $('#cuadroKpiTonAnual').text('-');
    $('#cuadroKpiTonMes').text('-');
    return;
  }
  $empty.hide();
  $('#rubrosCuadroHead').show();

  var grupos = {};
  var totalAnual = 0;
  $.each(rows, function(i, x) {
    var gk = normGrupoCod(x.grupo_cod || rubroGrupoPrincipal(x.Ppa_Cla));
    if (!grupos[gk]) {
      grupos[gk] = {
        cod: gk,
        nombre: x.grupo_descripcion || ('Grupo ' + gk),
        rows: [],
        total: 0
      };
    }
    var anual = rubroAnualEscenario(x);
    grupos[gk].rows.push(x);
    grupos[gk].total += anual;
    totalAnual += anual;
  });

  var keys = Object.keys(grupos).sort();
  var totalMesSum = 0;
  $.each(keys, function(idx, gk) {
    var g = grupos[gk];
    var collapseId = 'cuadroGrupo' + gk.replace(/\W/g, '');
    var open = '';
    var headingOpenCls = '';
    var tonMesGrupo = grupoTonBaseMes(g.rows);
    var facGrupoAnual = grupoFactorAnual(g.total, tonMesGrupo);
    var totalMesGrupo = sumRubrosPresupMensual(g.rows);
    var facGrupoMensual = factorMensual(facGrupoAnual);
    totalMesSum += totalMesGrupo;
    // Lazy: no renderizar filas hasta expandir el grupo (solo cabeceras/KPIs al inicio).
    cuadroGruposLazyMap[collapseId] = { rows: g.rows, tonMesGrupo: tonMesGrupo, gk: gk };
    var tableRows = '<tr class="cuadro-grupo-lazy-placeholder"><td colspan="8" class="text-center text-muted" style="padding:14px;">Expanda el grupo para ver los rubros.</td></tr>';
    var topeInfo = grupoTopeInfo(g.cod);
    var panelCls = 'panel panel-default' + ((topeInfo && topeInfo.excedido) ? ' exa-ppto-grupo-excedido' : '');
    var pctHtml = topeInfo ? grupoPctCuadroHtml(topeInfo) : '';
    var topeHtml = topeInfo ? grupoTopeCuadroHtml(topeInfo) : '<span class="text-muted">&mdash;</span>';

    // === CUADRO_PARTIDA_FINAL_UI (reversible) ===
    var htmlPresupAnual = '<span class="grupo-metric col-num cuadro-grupo-toggle" data-target="#' + collapseId + '">' + formatCurrency(g.total) + '</span>';
    var htmlPresupMes = '<span class="grupo-metric col-num val-mes cuadro-grupo-toggle" data-target="#' + collapseId + '">' + formatCurrency(totalMesGrupo) + '</span>';
    var htmlTonAnual = '<span class="grupo-metric col-num grupo-ton-val cuadro-grupo-toggle" data-target="#' + collapseId + '">' + (facGrupoAnual > 0 ? formatNumber(facGrupoAnual, 4) : '-') + '</span>';
    var htmlTonMes = '<span class="grupo-metric col-num grupo-ton-val val-mes cuadro-grupo-toggle" data-target="#' + collapseId + '">' + (facGrupoMensual > 0 ? formatNumber(facGrupoMensual, 6) : '-') + '</span>';
    var footAnual = formatCurrency(g.total);
    var footMes = formatCurrency(totalMesGrupo);
    var footTonA = facGrupoAnual > 0 ? formatNumber(facGrupoAnual, 4) : '-';
    var footTonM = facGrupoMensual > 0 ? formatNumber(facGrupoMensual, 6) : '-';
    if (usarFinal && mapaFinal[gk]) {
      var fin = mapaFinal[gk].partida_final;
      var finMes = cuadroPresupMesDesdePeriodo(fin);
      var finTon = mapaFinal[gk].final_por_ton;
      var finTonMes = finTon > 0 ? (finTon / 12) : 0;
      totalFinalSum += fin;
      totalFinalMesSum += finMes;
      htmlPresupAnual = htmlGrupoPresupDual(g.total, fin, false, '#' + collapseId);
      htmlPresupMes = htmlGrupoPresupDual(totalMesGrupo, finMes, true, '#' + collapseId);
      htmlTonAnual = '<span class="grupo-metric col-num grupo-ton-val cuadro-grupo-toggle" data-target="#' + collapseId + '" title="$/Ton final">'
        + (finTon > 0 ? formatNumber(finTon, 4) : '-') + '</span>';
      htmlTonMes = '<span class="grupo-metric col-num grupo-ton-val val-mes cuadro-grupo-toggle" data-target="#' + collapseId + '" title="$/Ton mensual final">'
        + (finTonMes > 0 ? formatNumber(finTonMes, 6) : '-') + '</span>';
      footAnual = htmlCeldaPresupDual(g.total, fin);
      footMes = htmlCeldaPresupDual(totalMesGrupo, finMes);
      footTonA = finTon > 0 ? formatNumber(finTon, 4) : '-';
      footTonM = finTonMes > 0 ? formatNumber(finTonMes, 6) : '-';
    }
    // === end ===

    $acc.append(
      '<div class="' + panelCls + '">'
      + '<div class="panel-heading cuadro-grupo-heading' + headingOpenCls + '" role="tab" id="heading' + collapseId + '">'
      + '<div class="cuadro-grupo-head exa-ppto-cuadro-grid">'
      + '<span class="grupo-head-left cuadro-grupo-toggle" data-target="#' + collapseId + '">'
      + '<span class="grupo-cod">' + g.cod + '</span>'
      + '<span class="grupo-nom">' + g.nombre + '</span>'
      + '</span>'
      + htmlPresupAnual
      + htmlPresupMes
      + htmlTonAnual
      + htmlTonMes
      + '<span class="grupo-col-pct">' + pctHtml + '</span>'
      + '<span class="grupo-col-tope">' + topeHtml + '</span>'
      + '<span class="grupo-head-right grupo-meta">'
      + '<button type="button" class="btn btn-default btn-xs btn-grupo-resumen" data-grupo-cod="' + gk + '" title="Resumen del grupo (como Excel)"><i class="bi bi-table"></i></button>'
      + '<span class="cuadro-grupo-toggle" data-target="#' + collapseId + '">'
      + '<span class="badge">' + g.rows.length + ' rubro' + (g.rows.length === 1 ? '' : 's') + '</span>'
      + '<i class="bi bi-chevron-down"></i>'
      + '</span>'
      + '</span>'
      + '</div></div>'
      + '<div id="' + collapseId + '" class="panel-collapse collapse' + open + '" role="tabpanel" aria-labelledby="heading' + collapseId + '">'
      + '<div class="panel-body"><div class="table-responsive">'
      + '<table class="table table-hover exa-adq-table">'
      + '<thead><tr><th>Partida</th><th>Rubro</th><th title="Driver egresos Excel (77.000)">Ton/mes costo</th><th>$/Ton anual</th><th>$/Ton mensual</th><th>Presup. anual</th><th>Presup. mensual</th><th style="width:88px;"></th></tr></thead>'
      + '<tbody data-lazy-grupo="1">' + tableRows + '</tbody>'
      + '<tfoot><tr style="background:#f7fafc;font-weight:600;">'
      + '<td colspan="3" class="text-right">Subtotal grupo ' + g.cod + '</td>'
      + '<td class="text-right">' + footTonA + '</td>'
      + '<td class="text-right">' + footTonM + '</td>'
      + '<td class="text-right">' + footAnual + '</td>'
      + '<td class="text-right">' + footMes + '</td><td></td>'
      + '</tr></tfoot>'
      + '</table></div></div></div></div>'
    );
  });

  // Cargar filas del grupo solo al expandir (una vez).
  $acc.off('show.bs.collapse.pptoLazy').on('show.bs.collapse.pptoLazy', function(e) {
    var $panel = $(e.target);
    var id = $panel.attr('id');
    if (!id || !cuadroGruposLazyMap[id]) return;
    var $tb = $panel.find('tbody[data-lazy-grupo="1"]');
    if (!$tb.length || $tb.data('lazy-loaded')) return;
    var meta = cuadroGruposLazyMap[id];
    $tb.html(buildGrupoTableRows(meta.rows, meta.tonMesGrupo, meta.gk));
    $tb.data('lazy-loaded', 1);
  });

  var tonProy = tonMesActivo() > 0 ? tonMesActivo() : grupoTonBaseMes(rows);
  var kpiTotal = totalAnual;
  var totalMes = totalMesSum;
  // === CUADRO_PARTIDA_FINAL_UI (reversible) ===
  if (usarFinal && totalFinalSum > 0) {
    kpiTotal = totalFinalSum;
    totalMes = totalFinalMesSum;
    $('#cuadroColPresupLbl').addClass('col-final-on').html('Presup. <span style="text-transform:none;">base/final</span>');
    $('#cuadroColPresupMesLbl').addClass('col-final-on').html('Mes <span style="text-transform:none;">base/final</span>');
  } else {
    $('#cuadroColPresupLbl').removeClass('col-final-on');
    $('#cuadroColPresupMesLbl').removeClass('col-final-on');
    actualizarCuadroPeriodoUi();
  }
  // === end ===
  var facProyAnual = grupoFactorAnual(kpiTotal, tonProy);
  var facProyMes = tonProy > 0 ? (totalMes / tonProy) : 0;

  $('#cuadroKpiGrupos').text(keys.length);
  $('#cuadroKpiRubros').text(rows.length);
  $('#cuadroKpiTon').text(tonProy > 0 ? formatNumber(tonProy, 0) : '-');
  $('#cuadroKpiTotal').text(formatCurrency(kpiTotal));
  $('#cuadroKpiTotalMes').text(formatCurrency(totalMes));
  $('#cuadroKpiTonAnual').text(facProyAnual > 0 ? formatNumber(facProyAnual, 4) : '-');
  $('#cuadroKpiTonMes').text(facProyMes > 0 ? formatNumber(facProyMes, 6) : '-');
}

function actualizarCuadroPeriodoUi() {
  var esAnual = (cuadroVista === 'anual');
  $('.cuadro-vista-btn').removeClass('active');
  $('.cuadro-vista-btn[data-vista="' + cuadroVista + '"]').addClass('active');
  $('#cuadroMesWrap').toggle(!esAnual);
  $('#cuadroMesLbl').text(cuadroVista === 'mes' ? 'Mes' : 'Hasta mes');
  $('#cuadro_mes_sel').val(String(cuadroMes));
  $('#cuadroPeriodoLbl').html(cuadroPeriodoLabel ? '<strong>' + cuadroPeriodoLabel + '</strong>' : '');

  var titPeriodo = '(anual)';
  var tonLbl = 'Ton anual ingresos';
  var presupLbl = 'Presup. anual';
  var presupMesLbl = 'Presup. mensual';
  var kpiTotalLbl = 'Presupuesto anual total';
  var kpiMesLbl = 'Presupuesto mensual total';
  var kpiMesSub = 'Anual / 12';
  var escBtnSub = 'Gastos anuales';
  if (cuadroVista === 'acumulado') {
    titPeriodo = '(acumulado)';
    tonLbl = 'Ton ingresos acum.';
    presupLbl = 'Presup. acumulado';
    presupMesLbl = 'Prom. mensual';
    kpiTotalLbl = 'Presupuesto acumulado';
    kpiMesLbl = 'Promedio mensual';
    kpiMesSub = 'Acum. / meses';
    escBtnSub = 'Gastos acumulados';
  } else if (cuadroVista === 'mes') {
    titPeriodo = '(mes)';
    tonLbl = 'Ton ingresos mes';
    presupLbl = 'Presup. del mes';
    presupMesLbl = 'Presup. del mes';
    kpiTotalLbl = 'Presupuesto del mes';
    kpiMesLbl = 'Presupuesto del mes';
    kpiMesSub = '';
    escBtnSub = 'Gastos del mes';
  }
  $('#escResumenPeriodoTit').text(titPeriodo);
  $('#escTonRowLbl').text(tonLbl);
  $('#cuadroColPresupLbl').text(presupLbl);
  $('#cuadroColPresupMesLbl').text(presupMesLbl);
  $('#cuadroKpiTotalLbl').text(kpiTotalLbl);
  $('#cuadroKpiTotalMesLbl').text(kpiMesLbl);
  $('#cuadroKpiTotalMesSub').text(kpiMesSub).toggle(kpiMesSub !== '');
  $('.esc-btn-s').text(escBtnSub);
}

function recalcIngresoEsperadaCliente() {
  var tonMes = parseFloat($('#pv_toneladas_base_mes').val()) || tonBaseProy || 0;
  if (tonMes <= 0.0001) return;
  var meses = 12;
  if (cuadroVista === 'mes') {
    meses = 1;
  } else if (cuadroVista === 'acumulado') {
    meses = cuadroMes;
  }
  var tonPeriod = tonMes * meses;
  escenariosTonPeriodo.esperada = tonPeriod;
  if (cuadroVista === 'anual') {
    escenariosTonAnual.esperada = tonPeriod;
  }
  escenariosIngreso.esperada = tonPeriod * ingresoCfg.tarifa / ingresoCfg.iva;
}

function aplicarCuadroPeriodoResponse(r) {
  if (r.cuadro_periodo) {
    cuadroVista = r.cuadro_periodo.vista || cuadroVista;
    cuadroMes = parseInt(r.cuadro_periodo.mes, 10) || cuadroMes;
    cuadroMesDefault = parseInt(r.cuadro_periodo.mes_default, 10) || cuadroMesDefault;
    cuadroPeriodoLabel = r.cuadro_periodo.label || cuadroPeriodoLabel;
  }
  if (r.escenarios_ton_periodo) {
    escenariosTonPeriodo = {
      esperada: parseFloat(r.escenarios_ton_periodo.esperada) || 0,
      proyectada: parseFloat(r.escenarios_ton_periodo.proyectada) || 0,
      real: parseFloat(r.escenarios_ton_periodo.real) || 0
    };
  }
  actualizarCuadroPeriodoUi();
}

function refreshVistaPresupuesto() {
  recalcIngresoEsperadaCliente();
  if (!rubrosCache || !rubrosCache.length) return;
  renderTablaRubros(rubrosCache);
  renderCuadroRubros(rubrosCache, gruposTopeCache);
  actualizarBotonesEscenario(rubrosCache);
}

function pptoTabActiva() {
  var href = $('#pptoProyTabs li.active a').attr('href') || '#tabProyectos';
  return href;
}

function loadRubrosParams(modoCompleto) {
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val() || '';
  var p = {
    action: 'list_rubros',
    Pro_Cod: proy,
    Ppe_Cod: ppe,
    modo: modoCompleto ? 'completa' : 'simple'
  };
  if (modoCompleto) {
    p.cuadro_vista = cuadroVista;
    p.cuadro_mes = cuadroMes;
    p.escenario = escenarioActivo || 'esperada';
    p.anio_precio = cuadroAnioPrecio || $('#cuadro_anio_precio').val() || '';
  }
  return p;
}

