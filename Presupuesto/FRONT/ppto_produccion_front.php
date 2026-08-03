<?php require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ppto_format_helpers.php'); ?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Produccion Fisica y Toneladas - EXA</title>
    <?php require_once dirname(dirname(__DIR__)) . '/contabilidad/FRONT/con_model3_assets.php'; ?>
    <script src="../VALIDACIONES/ppto_format.js"></script>
    <style>
        .exa-pre-form-panel label { display: block; font-size: 11px; font-weight: 600; margin-bottom: 4px; color: #4a5568; }
        .cell-esp-edit, .cell-proy-edit { cursor: text; min-width: 90px; }
        .cell-esp-edit { background: #f5fff8; }
        .cell-proy-edit { background: #fffef5; }
        .cell-esp-edit:focus, .cell-proy-edit:focus { outline: 2px solid #5bc0de; background: #fff; }
        .cell-esp-edit.cell-dirty, .cell-proy-edit.cell-dirty { background: #fff3cd; }
        #tblPlan tfoot td { font-weight: 700; background: #f0f4f8; }
        .badge-aprob-mes { display: inline-block; font-size: 11px; padding: 3px 7px; border-radius: 4px; background: #c6f6d5; color: #22543d; font-weight: 700; cursor: pointer; white-space: nowrap; }
        .badge-aprob-mes:hover { background: #9ae6b4; }
        .badge-aprob-mes.sin { background: #edf2f7; color: #718096; font-weight: 500; cursor: help; }
        #modalAprobarMesPreview .pub-kpi { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
        #modalAprobarMesPreview .pub-kpi .item { flex: 1; min-width: 120px; background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; }
        #modalAprobarMesPreview .pub-kpi .lbl { font-size: 10px; text-transform: uppercase; color: #718096; font-weight: 600; }
        #modalAprobarMesPreview .pub-kpi .val { font-size: 15px; font-weight: 700; color: #1a365d; }
        .d2-panel { max-width: 720px; }
        .d2-sn { font-size: 18px; padding: 6px 14px; }
        .d2-compare td { font-size: 13px; vertical-align: middle !important; }
        .d2-compare .d2-val { font-size: 20px; font-weight: 700; }
        #ppto_prod_relavera_panel {
            margin: 12px 0 16px;
            padding: 12px 14px;
            border: 1px solid #f6ad55;
            border-radius: 6px;
            background: #fffaf0;
            display: none;
        }
        .exa-aprob-tt {
            position: fixed;
            z-index: 9999;
            width: 268px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.18);
            font-size: 12px;
            color: #1a202c;
            opacity: 0;
            transform: translateY(4px);
            transition: opacity .12s ease, transform .12s ease;
            pointer-events: none;
            overflow: hidden;
        }
        .exa-aprob-tt.show { opacity: 1; transform: translateY(0); }
        .exa-aprob-tt .tt-head {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 14px;
            background: linear-gradient(135deg, #276749 0%, #2f855a 100%);
            color: #fff;
        }
        .exa-aprob-tt .tt-head i { font-size: 15px; }
        .exa-aprob-tt .tt-head .tt-title { font-weight: 700; font-size: 12px; letter-spacing: .3px; }
        .exa-aprob-tt .tt-head .tt-sub { font-size: 10px; opacity: .85; margin-left: auto; }
        .exa-aprob-tt .tt-amount {
            padding: 12px 14px 8px;
            text-align: center;
            border-bottom: 1px dashed #e2e8f0;
        }
        .exa-aprob-tt .tt-amount .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: .6px; color: #a0aec0; font-weight: 700; }
        .exa-aprob-tt .tt-amount .val { font-size: 20px; font-weight: 800; color: #22543d; line-height: 1.2; }
        .exa-aprob-tt .tt-rows { padding: 8px 14px 12px; }
        .exa-aprob-tt .tt-row { display: flex; align-items: center; justify-content: space-between; padding: 4px 0; }
        .exa-aprob-tt .tt-row + .tt-row { border-top: 1px solid #f1f5f9; }
        .exa-aprob-tt .tt-row .k { color: #718096; font-size: 11px; display: flex; align-items: center; gap: 6px; }
        .exa-aprob-tt .tt-row .k i { font-size: 12px; color: #a0aec0; }
        .exa-aprob-tt .tt-row .v { font-weight: 600; font-size: 11px; color: #2d3748; text-align: right; }
        .exa-aprob-tt .tt-row .v.up { color: #276749; }
        .exa-aprob-tt .tt-row .v.down { color: #c53030; }
        .exa-aprob-tt .tt-foot { padding: 7px 14px; background: #f7fafc; border-top: 1px solid #edf2f7; font-size: 10px; color: #a0aec0; display: flex; justify-content: space-between; }
    </style>
</head>
<body class="exa-ui-fill-root">
<div class="panel panel-main exa-ui-panel exa-ui-fill-page">
    <div class="panel-heading exa-header exa-header-flex">
        <h3 class="panel-title"><i class="bi bi-speedometer2"></i> Produccion Fisica y Toneladas</h3>
    </div>
    <div class="panel-body exa-body">
        <div class="exa-ui-page-view">
            <div id="msg" class="alert" style="display:none;"></div>
            <div id="ppto_prod_relavera_panel">
                <strong style="color:#c05621;"><i class="bi bi-recycle"></i> Modo reinversion Relavera activo</strong>
            </div>
            <ul class="nav nav-tabs exa-ui-nav-tabs" id="pptoProdTabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#tabConfig" role="tab" data-toggle="tab"><i class="bi bi-gear"></i> Configuracion origen</a>
                </li>
                <li role="presentation">
                    <a href="#tabPlan" role="tab" data-toggle="tab"><i class="bi bi-calendar3"></i> Plan mensual (Ton)</a>
                </li>
                <li role="presentation">
                    <a href="#tabD2" role="tab" data-toggle="tab"><i class="bi bi-shuffle"></i> Alineacion D2</a>
                </li>
            </ul>
            <div class="tab-content exa-ui-tab-content panels-area">
                <div class="tab-pane active" id="tabConfig" role="tabpanel">
                    <div class="row exa-pre-form-panel">
                        <div class="col-md-4"><label>Proyecto</label><select id="cfg_proy_id" class="form-control input-sm"></select></div>
                        <div class="col-md-3"><label>De donde tomar el real</label><select id="pco_origen" class="form-control input-sm">
                            <option value="relavera">Relavera - peso de manifiestos</option>
                            <option value="manual">Manual - sin automatico</option>
                            <option value="ventas">Ventas - facturas despachadas</option>
                            <option value="produccion">Produccion - partes diarios</option>
                        </select></div>
                        <div class="col-md-3" style="padding-top:22px;">
                            <button id="btnSaveCfg" class="btn btn-success btn-sm">Guardar config</button>
                            <button id="btnSync" class="btn btn-primary btn-sm">Sincronizar real</button>
                        </div>
                    </div>
                    <input type="hidden" id="pco_campo" value="Man_Pes" />
                    <input type="hidden" id="pco_extra_config" value='{"tabla":"manifiesto","divisor":1000}' />
                    <div class="exa-adq-table-wrap">
                        <table class="table table-bordered exa-adq-table" id="tblCfg">
                            <thead><tr><th>Proyecto</th><th>Origen</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane" id="tabPlan" role="tabpanel">
                    <div class="row exa-pre-form-panel">
                        <div class="col-md-4"><label>Proyecto</label><select id="plan_proy_id" class="form-control input-sm"></select></div>
                        <div class="col-md-2"><label>Anio</label><input id="prd_anio" type="number" class="form-control input-sm" value="<?php echo date('Y'); ?>" /></div>
                        <div class="col-md-2"><label>Ver mes</label><select id="plan_mes_filtro" class="form-control input-sm">
                            <option value="">Todos (12 meses)</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>"><?php echo ppto_nombre_mes($m); ?></option>
                            <?php endfor; ?>
                        </select></div>
                        <div class="col-md-2" style="padding-top:22px;">
                            <button id="btnGuardarPlan" class="btn btn-success btn-sm" title="Guarda esperada y proyectada editadas">Guardar</button>
                            <button id="btnSyncEsperadaPdf" class="btn btn-primary btn-sm" type="button" title="Copiar ton base ingresos a todos los meses">Desde ingresos</button>
                        </div>
                    </div>
                    <div class="exa-adq-table-wrap">
                        <table class="table table-bordered exa-adq-table" id="tblPlan">
                            <thead><tr><th>Mes</th><th title="Ton base ingresos (Proyectos)">Esperada</th><th title="Toneladas reales del periodo">Real</th><th title="Base para egresos proyectados y Aprobar $">Proyectada</th><th title="Presupuesto vigente aprobado">Aprobado $</th><th>Estado</th><th class="text-center" style="width:260px;">Accion</th></tr></thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <td><strong>TOTAL</strong></td>
                                    <td class="text-right" id="totEsperada">0.00</td>
                                    <td class="text-right" id="totReal">0.00</td>
                                    <td class="text-right" id="totProyectada">0.00</td>
                                    <td class="text-right" id="totAprobado">0.00</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="tab-pane" id="tabD2" role="tabpanel">
                    <div class="row exa-pre-form-panel">
                        <div class="col-md-4"><label>Proyecto</label><select id="d2_proy_id" class="form-control input-sm"></select></div>
                        <div class="col-md-2"><label>Anio</label><input id="d2_anio" type="number" class="form-control input-sm" value="<?php echo date('Y'); ?>" /></div>
                        <div class="col-md-3" style="padding-top:22px;">
                            <button type="button" id="btnD2Refresh" class="btn btn-default btn-sm"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                        </div>
                    </div>
                    <div class="d2-panel" style="margin-top:8px;">
                        <div id="d2_empty" class="alert alert-info" style="font-size:12px;">Seleccione proyecto y pulse Actualizar.</div>
                        <div id="d2_content" style="display:none;">
                            <div style="margin-bottom:16px;">
                                <span class="text-muted" style="font-size:11px; font-weight:700; text-transform:uppercase;">Estado D2</span>
                                <div style="margin-top:6px;">
                                    <span id="d2_sn" class="label d2-sn label-success">S</span>
                                    <span id="d2_sn_txt" style="margin-left:10px; font-size:14px; font-weight:600;">Alineado</span>
                                </div>
                            </div>
                            <table class="table table-bordered d2-compare" style="margin-bottom:16px;">
                                <thead><tr><th>Concepto</th><th class="text-right">Proyectada (Ton)</th><th class="text-right">Real (Ton)</th><th class="text-right">Diferencia</th></tr></thead>
                                <tbody>
                                    <tr>
                                        <td><span id="d2_rubro">—</span></td>
                                        <td class="text-right d2-val" id="d2_plan">0</td>
                                        <td class="text-right d2-val" id="d2_base">0</td>
                                        <td class="text-right"><span id="d2_pct" class="label label-default">0%</span></td>
                                    </tr>
                                    <tr class="active">
                                        <td colspan="2"><small class="text-muted">Meses cerrados</small></td>
                                        <td class="text-right" id="d2_base_mes">0</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div id="d2_warning" class="alert alert-warning" style="display:none; font-size:12px;"></div>
                            <div id="d2_ok" class="alert alert-success" style="display:none; font-size:12px;">Proyectada y real dentro del umbral permitido.</div>
                            <p style="font-size:12px; margin-top:12px;">
                                <strong>Reajustar proyectada:</strong>
                                <button type="button" class="btn btn-link btn-sm" id="btnD2GoPlan" style="padding:0 8px;">Ir a Plan mensual (Ton)</button>
                                &bull;
                                <a href="ppto_proyectos_front.php" class="btn btn-link btn-sm" style="padding:0 8px;">Ir a Proyectos (base rubro)</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modalAprobarMesPreview" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box" style="width:680px;max-width:96%;">
        <span class="exa-pre-modal-close" id="btnCloseAprobarMesModal">&times;</span>
        <h3 class="exa-adq-section-title" style="margin-top:0;">Aprobar presupuesto del mes</h3>
        <div class="pub-kpi">
            <div class="item"><div class="lbl">Mes</div><div class="val" id="apMesNom">—</div></div>
            <div class="item"><div class="lbl">Ton proyectada</div><div class="val" id="apTon">0</div></div>
            <div class="item"><div class="lbl">Vigente actual</div><div class="val" id="apVigente">$0.00</div></div>
            <div class="item"><div class="lbl">A aprobar</div><div class="val" id="apNuevo" style="color:#276749;">$0.00</div></div>
            <div class="item"><div class="lbl">Delta</div><div class="val" id="apDelta">$0.00</div></div>
        </div>
        <div id="apReaprobAviso" class="alert alert-warning" style="display:none;font-size:11px;padding:8px 10px;"></div>
        <div style="margin-top:12px;text-align:right;">
            <button type="button" class="btn btn-default btn-sm" id="btnCancelAprobarMesModal">Cancelar</button>
            <button type="button" class="btn btn-success btn-sm" id="btnConfirmAprobarMes"><i class="bi bi-check2"></i> Confirmar aprobacion</button>
        </div>
    </div>
</div>

<div id="aprobTooltip" class="exa-aprob-tt" style="display:none;"></div>

<div id="modalHistMes" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box" style="width:720px;max-width:96%;">
        <span class="exa-pre-modal-close" id="btnCloseHistMes">&times;</span>
        <h3 class="exa-adq-section-title" style="margin-top:0;" id="histMesTitulo">Historial de aprobaciones</h3>
        <div id="histMesBody"></div>
        <div style="margin-top:12px;text-align:right;">
            <button type="button" class="btn btn-default btn-sm" id="btnCloseHistMes2">Cerrar</button>
        </div>
    </div>
</div>
<script>
var API = '../LOGICA/ppto_produccion_logica.php';
var ORIGEN_CFG={
  relavera:{campo:'Man_Pes', extra:{tabla:'manifiesto',divisor:1000}},
  manual:{campo:'manual_valor', extra:{valor_defecto:0}},
  ventas:{campo:'Vet_Can', extra:{tabla:'ventas'}},
  produccion:{campo:'Prd_Vol', extra:{tabla:'prd_partes_diarios'}}
};
var ORIGEN_LABEL={
  relavera:'Relavera - manifiestos',
  manual:'Manual',
  ventas:'Ventas',
  produccion:'Produccion'
};
function actualizarPanelRelavera(){
  var proy=$('#cfg_proy_id').val();
  var orig=($('#pco_origen').val()||'').toLowerCase();
  $('#ppto_prod_relavera_panel').toggle(orig==='relavera'&&!!proy);
}
function aplicarPresetOrigen(origen, campoOpt, extraOpt){
  var preset=ORIGEN_CFG[origen]||ORIGEN_CFG.manual;
  var campo=campoOpt||preset.campo;
  var extra=extraOpt||preset.extra;
  $('#pco_campo').val(campo);
  $('#pco_extra_config').val(JSON.stringify(extra));
  $('#btnSync').toggle(origen!=='manual');
  actualizarPanelRelavera();
}
function cargarConfigProyecto(){
  var proy=$('#cfg_proy_id').val();
  if(!proy){
    aplicarPresetOrigen($('#pco_origen').val());
    return;
  }
  $.getJSON(API, {action:'get_config', proy_id:proy}, function(r){
    if(r.config){
      var c=r.config;
      $('#pco_origen').val(c.pco_origen||'relavera');
      var extra={};
      try{ extra=JSON.parse(c.pco_extra_config||'{}'); }catch(e){}
      aplicarPresetOrigen(c.pco_origen, c.pco_campo, extra);
    }else{
      aplicarPresetOrigen($('#pco_origen').val());
    }
  });
}
var MESES_NOM=PPTO_MESES_NOM;
function mesNombre(n){ return pptoNombreMes(n); }
function formatCurrency(v){
  var n=parseFloat(v)||0;
  return '$'+formatNumber(n,2);
}
function htmlspecialchars(s){
  return String(s==null?'':s)
    .replace(/&/g,'&amp;').replace(/"/g,'&quot;')
    .replace(/'/g,'&#039;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function toast(msg, ok){ $('#msg').removeClass('alert-success alert-danger').addClass(ok?'alert-success':'alert-danger').text(msg).show(); }
var aprobarMesCache=null;
var aprobMapActual={};

function refreshPlanDataFromDom(){
  var rows=[];
  $('#tblPlan tbody tr').each(function(){
    var mes=parseInt($(this).data('mes'),10);
    var $tds=$(this).find('td');
    rows.push({
      mes:mes,
      esperada:pptoParseNumber($(this).find('.cell-esp-edit').text()),
      real:pptoParseNumber($tds.eq(2).text()),
      proyectada:pptoParseNumber($(this).find('.cell-proy-edit').text())
    });
  });
  return rows;
}
function getPlanVal(rows, mes, field){
  for(var i=0;i<rows.length;i++){
    if(rows[i].mes===mes){ return rows[i][field]; }
  }
  return 0;
}
function calcProyectadaLocal(mesDestino){
  var rows=refreshPlanDataFromDom();
  if(mesDestino===1){
    var esp=getPlanVal(rows,1,'esperada');
    if(esp<=0){
      return {ok:false, message:'No hay toneladas esperadas en '+mesNombre(1)+'. Guarde el plan primero.'};
    }
    return {ok:true, valor:esp, modo:'esperada', message:'Proyectada '+mesNombre(1)+' = esperada: '+formatNumber(esp,2)+' Ton (sin guardar).'};
  }
  var mesRef=mesDestino-1;
  var mesUltReal=0;
  var m;
  for(m=1;m<=mesRef;m++){
    if(getPlanVal(rows,m,'real')>0){ mesUltReal=m; }
  }
  if(mesUltReal<=0){
    return {ok:false, message:'No hay produccion real en los meses anteriores.'};
  }
  var lastProyectada=function(){
    var p;
    for(m=mesDestino-1;m>=1;m--){
      p=getPlanVal(rows,m,'proyectada');
      if(p>0){ return p; }
    }
    return 0;
  };
  var mesIni=1, mesFin=mesRef, divisor=mesRef;
  if(mesDestino>=4){
    mesIni=mesDestino-3;
    mesFin=mesDestino-1;
    divisor=3;
  }
  if(mesRef>mesUltReal){
    var last=lastProyectada();
    if(last<=0){
      return {ok:false, message:'No hay proyectada previa para '+mesNombre(mesDestino)+'.'};
    }
    return {ok:true, valor:last, modo:'mantenida', message:'Proyectada '+mesNombre(mesDestino)+' = '+formatNumber(last,2)+' Ton (mantenida, sin guardar).'};
  }
  var acum=0;
  for(m=mesIni;m<=mesFin;m++){ acum+=getPlanVal(rows,m,'real'); }
  if(acum<=0){
    var lastZero=lastProyectada();
    if(lastZero<=0){
      return {ok:false, message:'No hay real en la ventana ni proyectada previa para '+mesNombre(mesDestino)+'.'};
    }
    return {ok:true, valor:lastZero, modo:'mantenida', message:'Proyectada '+mesNombre(mesDestino)+' = '+formatNumber(lastZero,2)+' Ton (mantenida, sin guardar).'};
  }
  var valor=acum/divisor;
  var msg;
  if(mesDestino>=4){
    msg='Proyectada '+mesNombre(mesDestino)+': promedio '+mesNombre(mesIni)+'-'+mesNombre(mesFin)+' '
      +formatNumber(acum,2)+' / 3 = '+formatNumber(valor,2)+' Ton (sin guardar).';
  } else {
    msg='Proyectada '+mesNombre(mesDestino)+': '+formatNumber(acum,2)+' / '+divisor+' = '+formatNumber(valor,2)+' Ton (sin guardar).';
  }
  return {ok:true, valor:valor, modo:'calculada', message:msg};
}
function applyProyectada(mesDestino){
  var r=calcProyectadaLocal(mesDestino);
  if(!r.ok){ toast(r.message,false); return; }
  var $cell=$('#tblPlan tbody tr[data-mes="'+mesDestino+'"] .cell-proy-edit');
  $cell.text(formatNumber(r.valor,2)).addClass('cell-dirty');
  updatePlanTotals();
  toast(r.message,true);
}
function filtrarPlanPorMes(){
  var f=$('#plan_mes_filtro').val();
  $('#tblPlan tbody tr').each(function(){
    if(!f){ $(this).show(); return; }
    $(this).toggle(String($(this).data('mes'))===String(f));
  });
  $('#tblPlan tfoot tr').toggle(!f);
}
function updatePlanTotals(aprob){
  var te=0, tr=0, tp=0, ta=0;
  $('#tblPlan tbody tr').each(function(){
    var $tds=$(this).find('td');
    te+=pptoParseNumber($(this).find('.cell-esp-edit').text());
    tr+=pptoParseNumber($tds.eq(2).text());
    tp+=pptoParseNumber($(this).find('.cell-proy-edit').text());
  });
  if(aprob){
    for(var k in aprob){
      if(aprob.hasOwnProperty(k) && aprob[k].total){
        ta+=parseFloat(aprob[k].total)||0;
      }
    }
  }
  $('#totEsperada').text(formatNumber(te,2));
  $('#totReal').text(formatNumber(tr,2));
  $('#totProyectada').text(formatNumber(tp,2));
  if(aprob){
    $('#totAprobado').text(formatCurrency(ta));
  }
}
function guardarCuadro(){
  var proy=$('#plan_proy_id').val();
  if(!proy){ toast('Seleccione un proyecto.', false); return; }
  var items=[];
  $('#tblPlan tbody tr').each(function(){
    items.push({
      mes:parseInt($(this).data('mes'),10),
      esperada:pptoParseNumber($(this).find('.cell-esp-edit').text()),
      proyectada:pptoParseNumber($(this).find('.cell-proy-edit').text())
    });
  });
  $.post(API, {
    action:'save_cuadro',
    proy_id:proy,
    anio:$('#prd_anio').val(),
    filas:JSON.stringify(items)
  }, function(r){
    toast(r.message, r.status==='success');
    if(r.status==='success'){
      $('.cell-esp-edit,.cell-proy-edit').removeClass('cell-dirty');
      loadPlan();
      if($('#tabD2').hasClass('active')){ loadD2(); }
    }
  }, 'json').fail(function(){ toast('Error al guardar el cuadro.', false); });
}
function loadProyectos(){
  $.getJSON(API, {action:'proyectos'}, function(r){
    var opts='<option value="">-- Seleccione proyecto --</option>';
    $.each(r.rows||[], function(i,p){ opts+='<option value="'+p.proy_id+'">'+p.proy_nombre+'</option>'; });
    $('#cfg_proy_id,#plan_proy_id,#d2_proy_id').html(opts);
    if(!r.rows || !r.rows.length){ toast('No hay proyectos activos. Cree uno en Proyectos Presupuestarios.', false); }
  }).fail(function(){ toast('Error al cargar proyectos. Recargue la pagina o inicie sesion de nuevo.', false); });
}
function renderD2(d2){
  if(!d2){
    $('#d2_content').hide();
    $('#d2_empty').show().text('No hay datos D2 para este proyecto.');
    return;
  }
  var proyPer=parseFloat(d2.ton_proyectada_periodo!==undefined?d2.ton_proyectada_periodo:d2.ton_esperada_anual)||0;
  var realPer=parseFloat(d2.ton_real_periodo!==undefined?d2.ton_real_periodo:d2.ton_base_anual)||0;
  var mesesReal=parseInt(d2.meses_con_real,10)||0;
  if(mesesReal<=0){
    $('#d2_content').hide();
    $('#d2_empty').show().text(d2.mensaje||'Aun no hay meses cerrados con real para comparar contra la proyectada.');
    return;
  }
  $('#d2_empty').hide();
  $('#d2_content').show();
  var sn=d2.alineado_sn||(d2.alineado?'S':'N');
  var $badge=$('#d2_sn');
  $badge.text(sn).removeClass('label-success label-danger').addClass(sn==='S'?'label-success':'label-danger');
  $('#d2_sn_txt').text(sn==='S'?'Alineado':(d2.requiere_reajuste?'Proyectada corta':'No alineado'));
  $('#d2_rubro').text(
    (d2.partida_driver_codigo ? d2.partida_driver_codigo + ' — ' : '') + (d2.rubro_driver || 'Rubro driver')
  );
  $('#d2_plan').text(formatNumber(proyPer, 2));
  $('#d2_base').text(formatNumber(realPer, 2));
  $('#d2_base_mes').text(mesesReal+' mes(es) · base '+formatNumber(d2.ton_base_mensual||0, 2)+' Ton/mes');
  var pct=parseFloat(d2.pct_diferencia)||0;
  var $pct=$('#d2_pct');
  $pct.text((realPer>=proyPer?'+':'-')+formatNumber(pct,2)+'%');
  $pct.removeClass('label-success label-warning label-danger').addClass(
    sn==='S'?'label-success':(pct>10?'label-danger':'label-warning')
  );
  if(d2.warning && d2.mensaje){
    $('#d2_warning').text(d2.mensaje).show();
    $('#d2_ok').hide();
  }else{
    $('#d2_warning').hide().text('');
    $('#d2_ok').show();
  }
}
function loadD2(){
  var proy=$('#d2_proy_id').val();
  var anio=$('#d2_anio').val();
  if(!proy){
    $('#d2_content').hide();
    $('#d2_empty').show().text('Seleccione un proyecto.');
    return;
  }
  $.getJSON(API, {action:'divergencia_d2', proy_id:proy, anio:anio}, function(r){
    if(r.status!=='success'){ toast(r.message||'Error D2', false); return; }
    renderD2(r.divergencia_d2);
  }).fail(function(){ toast('Error al cargar alineacion D2.', false); });
}
function loadCfg(){
  $.getJSON(API, {action:'list'}, function(r){
    var tb=$('#tblCfg tbody').empty();
    $.each(r.rows||[], function(i,c){
      var orig=(c.pco_origen||'').toLowerCase();
      tb.append('<tr><td>'+c.proy_nombre+'</td><td>'+(ORIGEN_LABEL[orig]||c.pco_origen)+'</td></tr>');
    });
  });
}
function estadoLabel(est){
  var map={sin_dato:'Sin dato',en_curso:'En curso',cerrado:'Cerrado'};
  return map[est]||est||'Sin dato';
}
function accionesPeriodo(p, aprob){
  var est=(p.prd_estado||'sin_dato').toLowerCase();
  var mes=parseInt(p.prd_mes,10);
  var btns='<button type="button" class="btn btn-primary btn-xs btn-insert-proy" data-mes="'+mes+'">Proyectada</button> ';
  btns+='<button type="button" class="btn btn-success btn-xs btn-aprobar-mes" data-mes="'+mes+'" title="Aprobar presupuesto $ del mes">Aprobar $</button> ';
  if(est==='cerrado'){
    btns+='<button type="button" class="btn btn-warning btn-xs btn-reabrir" data-mes="'+mes+'">Reabrir</button>';
  }else{
    btns+='<button type="button" class="btn btn-default btn-xs btn-cerrar" data-mes="'+mes+'">Cerrar</button>';
  }
  return btns;
}
function fechaHoraLegible(fs){
  if(!fs){ return '—'; }
  var parts=fs.split(' ');
  var f=parts[0].split('-');
  var fecha=(f.length===3)?(f[2]+'/'+f[1]+'/'+f[0]):parts[0];
  var hora=parts.length>1?parts[1].substring(0,5):'';
  return hora?(fecha+' '+hora):fecha;
}
function badgeAprobacion(mes, aprob){
  var a=aprob&&aprob[mes];
  if(!a||!a.fecha){
    return '<span class="badge-aprob-mes sin" data-mes="'+mes+'" data-aprob="0">Pendiente</span>';
  }
  return '<span class="badge-aprob-mes" data-mes="'+mes+'" data-aprob="1">'+formatCurrency(a.total)+'</span>';
}
function fechaSoloHora(fs){
  if(!fs){ return ''; }
  var parts=fs.split(' ');
  return parts.length>1?parts[1].substring(0,5):'';
}
function fechaSoloDia(fs){
  if(!fs){ return '—'; }
  var f=fs.split(' ')[0].split('-');
  return (f.length===3)?(f[2]+'/'+f[1]+'/'+f[0]):fs;
}
function construirTooltipAprob(mes){
  var a=aprobMapActual&&aprobMapActual[mes];
  if(!a||!a.fecha){
    return '<div class="tt-head" style="background:linear-gradient(135deg,#718096 0%,#a0aec0 100%);">'
      +'<i class="bi bi-hourglass-split"></i><span class="tt-title">'+mesNombre(mes)+'</span>'
      +'<span class="tt-sub">Sin aprobar</span></div>'
      +'<div class="tt-rows"><div class="tt-row"><span class="k"><i class="bi bi-info-circle"></i> Estado</span>'
      +'<span class="v">Pendiente</span></div>'
      +'<div class="tt-row"><span class="k"><i class="bi bi-lightbulb"></i> Accion</span>'
      +'<span class="v">Use "Aprobar $"</span></div></div>';
  }
  var delta=parseFloat(a.delta)||0;
  var dCls=delta>0?'up':(delta<0?'down':'');
  var dSig=delta>0?'+':'';
  var modoTxt=(a.modo==='proyectada_mes')?'Proyectada (mes)':(a.modo||'—');
  var veces=parseInt(a.veces,10)||1;
  var vecesRow=(veces>1)
    ?('<div class="tt-row"><span class="k"><i class="bi bi-clock-history"></i> Aprobaciones</span>'
       +'<span class="v" style="color:#c05621;">'+veces+' veces</span></div>')
    :'';
  var footHist=(veces>1)?'<span style="color:#3182ce;">clic: ver historial</span>':'<span>ton proy. x $/Ton</span>';
  return ''
    +'<div class="tt-head"><i class="bi bi-check2-circle"></i>'
    +'<span class="tt-title">Aprobado · '+mesNombre(mes)+'</span>'
    +'<span class="tt-sub">'+fechaSoloDia(a.fecha)+'</span></div>'
    +'<div class="tt-amount"><div class="lbl">Presupuesto aprobado'+(veces>1?(' · vig. #'+veces):'')+'</div>'
    +'<div class="val">'+formatCurrency(a.total)+'</div></div>'
    +'<div class="tt-rows">'
    +'<div class="tt-row"><span class="k"><i class="bi bi-clock-history"></i> Anterior</span>'
    +'<span class="v">'+formatCurrency(a.total_anterior||0)+'</span></div>'
    +'<div class="tt-row"><span class="k"><i class="bi bi-arrow-left-right"></i> Cambio</span>'
    +'<span class="v '+dCls+'">'+dSig+formatCurrency(delta)+'</span></div>'
    +'<div class="tt-row"><span class="k"><i class="bi bi-calendar-check"></i> Fecha</span>'
    +'<span class="v">'+fechaSoloDia(a.fecha)+(fechaSoloHora(a.fecha)?(' · '+fechaSoloHora(a.fecha)):'')+'</span></div>'
    +'<div class="tt-row"><span class="k"><i class="bi bi-person-badge"></i> Usuario</span>'
    +'<span class="v">'+htmlspecialchars(a.usuario||('Usuario '+(a.Usu_Cod||'?')))+'</span></div>'
    +'<div class="tt-row"><span class="k"><i class="bi bi-sliders"></i> Modo</span>'
    +'<span class="v">'+htmlspecialchars(modoTxt)+'</span></div>'
    +vecesRow
    +'</div>'
    +'<div class="tt-foot"><span>Reg. #'+(a.pub_id||'—')+'</span>'+footHist+'</div>';
}
function abrirHistorialMes(mes){
  var proy=$('#plan_proy_id').val();
  if(!proy){ return; }
  ocultarTooltipAprob();
  $('#histMesTitulo').text('Historial de aprobaciones · '+mesNombre(mes));
  $('#histMesBody').html('<div class="text-center text-muted" style="padding:20px;">Cargando…</div>');
  $('#modalHistMes').show();
  $.getJSON(API, {action:'hist_aprobaciones_mes', proy_id:proy, anio:$('#prd_anio').val(), prd_mes:mes}, function(r){
    if(r.status!=='success'){ $('#histMesBody').html('<div class="alert alert-danger">'+(r.message||'Error')+'</div>'); return; }
    var rows=r.rows||[];
    if(!rows.length){ $('#histMesBody').html('<div class="alert alert-info" style="font-size:12px;">Este mes aún no tiene aprobaciones registradas.</div>'); return; }
    var h='<div style="font-size:11px;color:#718096;margin-bottom:10px;">'+rows.length+' aprobación(es) registradas. La más reciente es la vigente.</div>';
    h+='<div class="exa-adq-table-wrap"><table class="table table-condensed table-bordered exa-adq-table" style="font-size:11px;">';
    h+='<thead><tr><th style="width:36px;">#</th><th>Fecha</th><th>Usuario</th><th class="text-right">Anterior</th><th class="text-right">Aprobado</th><th class="text-right">Cambio</th><th>Estado</th></tr></thead><tbody>';
    $.each(rows, function(i,x){
      var d=parseFloat(x.delta)||0;
      var dCol=d>0?'#276749':(d<0?'#c53030':'#718096');
      var dSig=d>0?'+':'';
      var badge=x.es_actual
        ? '<span class="label label-success">Vigente</span>'
        : '<span class="label label-default">Histórico</span>';
      h+='<tr'+(x.es_actual?' style="background:#f0fff4;"':'')+'>'
        +'<td class="text-center">'+x.orden+'</td>'
        +'<td>'+fechaSoloDia(x.fecha)+' '+fechaSoloHora(x.fecha)+'</td>'
        +'<td>'+htmlspecialchars(x.usuario||('Usuario '+x.Usu_Cod))+'</td>'
        +'<td class="text-right">'+formatCurrency(x.total_anterior)+'</td>'
        +'<td class="text-right" style="font-weight:700;">'+formatCurrency(x.total)+'</td>'
        +'<td class="text-right" style="color:'+dCol+';">'+dSig+formatCurrency(d)+'</td>'
        +'<td>'+badge+'</td>'
        +'</tr>';
    });
    h+='</tbody></table></div>';
    $('#histMesBody').html(h);
  }).fail(function(){ $('#histMesBody').html('<div class="alert alert-danger">Error de red al cargar historial.</div>'); });
}
function mostrarTooltipAprob($badge){
  var mes=parseInt($badge.data('mes'),10);
  var $tt=$('#aprobTooltip');
  $tt.html(construirTooltipAprob(mes));
  $tt.css({left:'-9999px', top:'0px'}).show();
  var r=$badge[0].getBoundingClientRect();
  var ttW=$tt.outerWidth(), ttH=$tt.outerHeight();
  var left=r.left+(r.width/2)-(ttW/2);
  var top=r.top-ttH-8;
  if(top<8){ top=r.bottom+8; }
  if(left<8){ left=8; }
  if(left+ttW>window.innerWidth-8){ left=window.innerWidth-ttW-8; }
  $tt.css({left:left+'px', top:top+'px'}).addClass('show');
}
function ocultarTooltipAprob(){
  $('#aprobTooltip').removeClass('show');
  setTimeout(function(){ if(!$('#aprobTooltip').hasClass('show')){ $('#aprobTooltip').hide(); } }, 140);
}
function renderAprobarMesPreview(prev){
  aprobarMesCache=prev;
  $('#apMesNom').text(mesNombre(prev.mes));
  $('#apTon').text(formatNumber(prev.ton_proyectada,2));
  $('#apVigente').text(formatCurrency(prev.total_vigente));
  $('#apNuevo').text(formatCurrency(prev.total_publicar));
  var d=parseFloat(prev.delta)||0;
  $('#apDelta').text((d>=0?'+':'')+formatCurrency(d)).css('color',d>=0?'#276749':'#c53030');
  if(prev.es_reaprobacion){
    $('#apReaprobAviso').text('Este mes ya fue aprobado. Al confirmar se reaprobara con la proyectada actual.').show();
  }else{
    $('#apReaprobAviso').hide();
  }
  $('#modalAprobarMesPreview').show();
}
function guardarProyectadaMes(mes, valor, cb){
  $.post(API, {
    action:'save_proyectada',
    proy_id:$('#plan_proy_id').val(),
    anio:$('#prd_anio').val(),
    prd_mes:mes,
    prd_proyectada:valor
  }, function(r){
    if(r.status!=='success'){
      toast(r.message||'No se guardo la proyectada.', false);
      return;
    }
    if(cb){ cb(); }
  }, 'json').fail(function(){ toast('Error al guardar proyectada.', false); });
}
function previewAprobarMes(mes){
  var proy=$('#plan_proy_id').val();
  if(!proy){ toast('Seleccione un proyecto.', false); return; }
  var rows=refreshPlanDataFromDom();
  var ton=getPlanVal(rows, mes, 'proyectada');
  if(ton<=0){
    toast('Indique tonelada proyectada para '+mesNombre(mes)+'.', false);
    return;
  }
  var dirty=$('#tblPlan tbody tr[data-mes="'+mes+'"] .cell-proy-edit').hasClass('cell-dirty');
  var runPreview=function(){
    $.getJSON(API, {
      action:'preview_aprobar_mes',
      proy_id:proy,
      anio:$('#prd_anio').val(),
      prd_mes:mes,
      prd_proyectada:ton
    }, function(r){
      if(r.status!=='success'){
        toast(r.message||'No se pudo generar vista previa.', false);
        return;
      }
      renderAprobarMesPreview(r.preview);
    }).fail(function(){ toast('Error al consultar vista previa.', false); });
  };
  if(dirty){
    guardarProyectadaMes(mes, ton, runPreview);
  }else{
    runPreview();
  }
}
function ejecutarAprobarMes(confirmarReaprobacion){
  if(!aprobarMesCache){ return; }
  var mes=aprobarMesCache.mes;
  var proy=$('#plan_proy_id').val();
  var postData={
    action:'aprobar_mes',
    proy_id:proy,
    anio:$('#prd_anio').val(),
    prd_mes:mes,
    prd_proyectada:aprobarMesCache.ton_proyectada
  };
  if(confirmarReaprobacion){ postData.confirmar_reaprobacion=1; }
  $.post(API, postData, function(r){
    if(r.status==='confirm'){
      if(!confirm(r.message||'¿Reaprobar este mes?')){ return; }
      ejecutarAprobarMes(true);
      return;
    }
    if(r.status!=='success'){
      toast(r.message||'No se pudo aprobar.', false);
      return;
    }
    $('#modalAprobarMesPreview').hide();
    aprobarMesCache=null;
    toast(r.message, true);
    loadPlan();
  }, 'json').fail(function(){ toast('Error al aprobar mes.', false); });
}
function cerrarPeriodo(mes){
  var proy=$('#plan_proy_id').val();
  if(!proy){ toast('Seleccione un proyecto.', false); return; }
  var rows=refreshPlanDataFromDom();
  var realVal=getPlanVal(rows, mes, 'real');
  var input=prompt('Confirme el valor real (Ton) para cerrar '+mesNombre(mes)+':', formatNumber(realVal,2));
  if(input===null){ return; }
  realVal=pptoParseNumber(input);
  $.post(API, {
    action:'cerrar_periodo',
    proy_id:proy,
    anio:$('#prd_anio').val(),
    prd_mes:mes,
    prd_real:realVal
  }, function(r){
    toast(r.message, r.status==='success');
    if(r.status==='success'){ loadPlan(); }
  }, 'json').fail(function(){ toast('Error al cerrar periodo.', false); });
}
function reabrirPeriodo(mes){
  var proy=$('#plan_proy_id').val();
  if(!proy){ toast('Seleccione un proyecto.', false); return; }
  var motivo=prompt('Motivo de reapertura para '+mesNombre(mes)+':');
  if(!motivo){ toast('Motivo requerido.', false); return; }
  $.post(API, {
    action:'reabrir_periodo',
    proy_id:proy,
    anio:$('#prd_anio').val(),
    prd_mes:mes,
    motivo:motivo
  }, function(r){
    toast(r.message, r.status==='success');
    if(r.status==='success'){ loadPlan(); }
  }, 'json').fail(function(){ toast('Error al reabrir periodo.', false); });
}
function loadPlan(){
  $.getJSON(API, {action:'list_periodos', proy_id:$('#plan_proy_id').val(), anio:$('#prd_anio').val()}, function(r){
    var map={};
    $.each(r.rows||[], function(i,p){ map[parseInt(p.prd_mes,10)]=p; });
    var aprob=r.aprobaciones||{};
    aprobMapActual=aprob;
    var tonPdf=parseFloat(r.ton_base_pdf)||0;
    if(tonPdf>0){
      $('#btnSyncEsperadaPdf').attr('title', 'Cargar '+formatNumber(tonPdf,0)+' Ton/mes (base ingresos)');
    }else{
      $('#btnSyncEsperadaPdf').attr('title', 'Defina ton ingresos en Proyectos');
    }
    var tb=$('#tblPlan tbody').empty();
    for(var mes=1;mes<=12;mes++){
      var p=map[mes]||{prd_mes:mes,prd_esperada:0,prd_real:0,prd_proyectada:0,prd_estado:'sin_dato'};
      p.prd_mes=mes;
      var esp=parseFloat(p.prd_esperada)||0;
      if(esp<=0 && tonPdf>0){ esp=tonPdf; }
      var est=(p.prd_estado||'sin_dato').toLowerCase();
      var apMonto=(aprob[mes]&&aprob[mes].total)?aprob[mes].total:0;
      tb.append(
        '<tr data-mes="'+mes+'">'+
        '<td>'+mesNombre(mes)+'</td>'+
        '<td class="text-right cell-esp-edit" contenteditable="true" data-mes="'+mes+'">'+formatNumber(esp,2)+'</td>'+
        '<td class="text-right">'+formatNumber(p.prd_real,2)+'</td>'+
        '<td class="text-right cell-proy-edit" contenteditable="true" data-mes="'+mes+'">'+formatNumber(p.prd_proyectada,2)+'</td>'+
        '<td class="text-center">'+badgeAprobacion(mes, aprob)+'</td>'+
        '<td><span class="label label-'+(est==='cerrado'?'success':(est==='en_curso'?'info':'default'))+'">'+estadoLabel(est)+'</span></td>'+
        '<td class="text-center">'+accionesPeriodo(p, aprob)+'</td>'+
        '</tr>'
      );
    }
    updatePlanTotals(aprob);
    filtrarPlanPorMes();
  });
}
function syncEsperadaDesdePdf(forzar){
  var proy=$('#plan_proy_id').val();
  if(!proy){ toast('Seleccione un proyecto.', false); return; }
  if(forzar && !confirm('Sobrescribir Esperada en todos los meses (excepto cerrados si no fuerza) con la ton base ingresos (30 d)?')){ return; }
  $.post(API, {
    action:'sync_esperada_pdf',
    proy_id:proy,
    anio:$('#prd_anio').val(),
    forzar:forzar?1:0
  }, function(r){
    toast(r.message, r.status==='success');
    if(r.status==='success'){ loadPlan(); }
  }, 'json').fail(function(){ toast('Error al sincronizar desde PDF.', false); });
}
$('#btnSaveCfg').click(function(){
  $.post(API, {action:'save_config', proy_id:$('#cfg_proy_id').val(), pco_origen:$('#pco_origen').val(), pco_campo:$('#pco_campo').val(), pco_extra_config:$('#pco_extra_config').val()}, function(r){
    toast(r.message,r.status==='success');
    loadCfg();
    actualizarPanelRelavera();
  }, 'json');
});
$('#btnSync').click(function(){
  $.post(API, {action:'sync_relavera', proy_id:$('#cfg_proy_id').val(), anio:$('#prd_anio').val()}, function(r){ toast(r.message,r.status==='success'); loadPlan(); }, 'json');
});
$('#pco_origen').change(function(){ aplicarPresetOrigen($(this).val()); });
$('#cfg_proy_id').change(cargarConfigProyecto);
$('#btnGuardarPlan').click(guardarCuadro);
$('#btnSyncEsperadaPdf').click(function(){ syncEsperadaDesdePdf(true); });
$(document).on('mouseenter', '.badge-aprob-mes', function(){
  mostrarTooltipAprob($(this));
});
$(document).on('mouseleave', '.badge-aprob-mes', function(){
  ocultarTooltipAprob();
});
$(document).on('click', '.badge-aprob-mes', function(){
  if($(this).data('aprob')==1){
    abrirHistorialMes(parseInt($(this).data('mes'),10));
  }
});
$('#btnCloseHistMes, #btnCloseHistMes2').click(function(){ $('#modalHistMes').hide(); });
$(document).on('click', '.btn-aprobar-mes', function(){
  previewAprobarMes(parseInt($(this).data('mes'),10));
});
$('#btnConfirmAprobarMes').click(function(){ ejecutarAprobarMes(false); });
$('#btnCloseAprobarMesModal, #btnCancelAprobarMesModal').click(function(){
  $('#modalAprobarMesPreview').hide();
  aprobarMesCache=null;
});
$(document).on('click', '.btn-insert-proy', function(){
  applyProyectada(parseInt($(this).data('mes'),10));
});
$(document).on('click', '.btn-cerrar', function(){
  cerrarPeriodo(parseInt($(this).data('mes'),10));
});
$(document).on('click', '.btn-reabrir', function(){
  reabrirPeriodo(parseInt($(this).data('mes'),10));
});
$(document).on('blur', '.cell-esp-edit,.cell-proy-edit', function(){
  var $cell=$(this);
  var val=pptoParseNumber($cell.text());
  $cell.text(formatNumber(val,2)).addClass('cell-dirty');
  updatePlanTotals();
});
$(document).on('keydown', '.cell-esp-edit,.cell-proy-edit', function(e){
  if(e.keyCode===13){
    e.preventDefault();
    $(this).blur();
  }
});
$('#plan_proy_id,#prd_anio').change(loadPlan);
$('#plan_mes_filtro').change(filtrarPlanPorMes);
$('#d2_proy_id,#d2_anio').change(loadD2);
$('#btnD2Refresh').click(loadD2);
$('#btnD2GoPlan').click(function(){
  var proy=$('#d2_proy_id').val();
  var anio=$('#d2_anio').val();
  if(proy){ $('#plan_proy_id').val(proy); }
  if(anio){ $('#prd_anio').val(anio); }
  loadPlan();
  $('a[href="#tabPlan"]').tab('show');
});
$('a[href="#tabD2"]').on('shown.bs.tab', function(){
  if($('#d2_proy_id').val()==='' && $('#plan_proy_id').val()!==''){
    $('#d2_proy_id').val($('#plan_proy_id').val());
  }
  if($('#d2_anio').val()!==$('#prd_anio').val()){
    $('#d2_anio').val($('#prd_anio').val());
  }
  loadD2();
});
$(function(){ loadProyectos(); loadCfg(); loadPlan(); aplicarPresetOrigen('relavera'); });
</script>
</body>
</html>
