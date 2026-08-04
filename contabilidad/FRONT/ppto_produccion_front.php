<?php require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/ppto_format_helpers.php'); ?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Produccion Fisica y Toneladas - EXA</title>
    <?php require_once __DIR__ . '/con_model3_assets.php'; ?>
    <script src="../VALIDACIONES/ppto_format.js"></script>
    <style>
        .exa-pre-form-panel label { display: block; font-size: 11px; font-weight: 600; margin-bottom: 4px; color: #4a5568; }
        .cell-esp-edit, .cell-proy-edit { cursor: text; min-width: 90px; }
        .cell-esp-edit { background: #f5fff8; }
        .cell-proy-edit { background: #fffef5; }
        .cell-esp-edit:focus, .cell-proy-edit:focus { outline: 2px solid #5bc0de; background: #fff; }
        .cell-esp-edit.cell-dirty, .cell-proy-edit.cell-dirty { background: #fff3cd; }
        #tblPlan tfoot td { font-weight: 700; background: #f0f4f8; }
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
            <ul class="nav nav-tabs exa-ui-nav-tabs" id="pptoProdTabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#tabConfig" role="tab" data-toggle="tab"><i class="bi bi-gear"></i> Configuracion origen</a>
                </li>
                <li role="presentation">
                    <a href="#tabPlan" role="tab" data-toggle="tab"><i class="bi bi-calendar3"></i> Plan mensual (Ton)</a>
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
                            <button id="btnGuardarPlan" class="btn btn-success btn-sm">Guardar</button>
                        </div>
                    </div>
                    <p class="text-muted" style="font-size:11px; margin:0 0 10px 22px;">
                        Edite el cuadro y pulse <strong>Guardar</strong>.
                    </p>
                    <div class="exa-adq-table-wrap">
                        <table class="table table-bordered exa-adq-table" id="tblPlan">
                            <thead><tr><th>Mes</th><th>Esperada</th><th>Real</th><th>Proyectada</th><th class="text-center" style="width:110px;">Accion</th></tr></thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <td><strong>TOTAL</strong></td>
                                    <td class="text-right" id="totEsperada">0.00</td>
                                    <td class="text-right" id="totReal">0.00</td>
                                    <td class="text-right" id="totProyectada">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
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
function aplicarPresetOrigen(origen, campoOpt, extraOpt){
  var preset=ORIGEN_CFG[origen]||ORIGEN_CFG.manual;
  var campo=campoOpt||preset.campo;
  var extra=extraOpt||preset.extra;
  $('#pco_campo').val(campo);
  $('#pco_extra_config').val(JSON.stringify(extra));
  $('#btnSync').toggle(origen!=='manual');
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
function mesNombre(n){ return pptoNombreMes(n); }
function toast(msg, ok){ $('#msg').removeClass('alert-success alert-danger').addClass(ok?'alert-success':'alert-danger').text(msg).show(); }
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
  if(mesRef>mesUltReal){
    var last=0;
    for(m=mesDestino-1;m>=1;m--){
      var p=getPlanVal(rows,m,'proyectada');
      if(p>0){ last=p; break; }
    }
    if(last<=0){
      var acumFb=0;
      for(m=1;m<=mesUltReal;m++){ acumFb+=getPlanVal(rows,m,'real'); }
      last=acumFb/mesUltReal;
    }
    return {ok:true, valor:last, modo:'mantenida', message:'Proyectada '+mesNombre(mesDestino)+' = '+formatNumber(last,2)+' Ton (mantenida, sin guardar).'};
  }
  var acum=0;
  for(m=1;m<=mesRef;m++){ acum+=getPlanVal(rows,m,'real'); }
  if(acum<=0){
    return {ok:false, message:'El acumulado real es cero de '+mesNombre(1)+' a '+mesNombre(mesRef)+'.'};
  }
  var valor=acum/mesRef;
  return {ok:true, valor:valor, modo:'calculada', message:'Proyectada '+mesNombre(mesDestino)+': '+formatNumber(acum,2)+' / '+mesRef+' = '+formatNumber(valor,2)+' Ton (sin guardar).'};
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
function updatePlanTotals(){
  var te=0, tr=0, tp=0;
  $('#tblPlan tbody tr').each(function(){
    var $tds=$(this).find('td');
    te+=pptoParseNumber($(this).find('.cell-esp-edit').text());
    tr+=pptoParseNumber($tds.eq(2).text());
    tp+=pptoParseNumber($(this).find('.cell-proy-edit').text());
  });
  $('#totEsperada').text(formatNumber(te,2));
  $('#totReal').text(formatNumber(tr,2));
  $('#totProyectada').text(formatNumber(tp,2));
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
    }
  }, 'json').fail(function(){ toast('Error al guardar el cuadro.', false); });
}
function loadProyectos(){
  $.getJSON(API, {action:'proyectos'}, function(r){
    var opts='<option value="">-- Seleccione proyecto --</option>';
    $.each(r.rows||[], function(i,p){ opts+='<option value="'+p.proy_id+'">'+p.proy_nombre+'</option>'; });
    $('#cfg_proy_id,#plan_proy_id').html(opts);
    if(!r.rows || !r.rows.length){ toast('No hay proyectos activos. Cree uno en Proyectos Presupuestarios.', false); }
  }).fail(function(){ toast('Error al cargar proyectos. Recargue la pagina o inicie sesion de nuevo.', false); });
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
function loadPlan(){
  $.getJSON(API, {action:'list_periodos', proy_id:$('#plan_proy_id').val(), anio:$('#prd_anio').val()}, function(r){
    var map={};
    $.each(r.rows||[], function(i,p){ map[parseInt(p.prd_mes,10)]=p; });
    var tb=$('#tblPlan tbody').empty();
    for(var mes=1;mes<=12;mes++){
      var p=map[mes]||{prd_esperada:0,prd_real:0,prd_proyectada:0};
      tb.append(
        '<tr data-mes="'+mes+'">'+
        '<td>'+mesNombre(mes)+'</td>'+
        '<td class="text-right cell-esp-edit" contenteditable="true" data-mes="'+mes+'">'+formatNumber(p.prd_esperada,2)+'</td>'+
        '<td class="text-right">'+formatNumber(p.prd_real,2)+'</td>'+
        '<td class="text-right cell-proy-edit" contenteditable="true" data-mes="'+mes+'">'+formatNumber(p.prd_proyectada,2)+'</td>'+
        '<td class="text-center"><button type="button" class="btn btn-primary btn-xs btn-insert-proy" data-mes="'+mes+'">Proyectada</button></td>'+
        '</tr>'
      );
    }
    updatePlanTotals();
    filtrarPlanPorMes();
  });
}
$('#btnSaveCfg').click(function(){
  $.post(API, {action:'save_config', proy_id:$('#cfg_proy_id').val(), pco_origen:$('#pco_origen').val(), pco_campo:$('#pco_campo').val(), pco_extra_config:$('#pco_extra_config').val()}, function(r){ toast(r.message,r.status==='success'); loadCfg(); }, 'json');
});
$('#btnSync').click(function(){
  $.post(API, {action:'sync_relavera', proy_id:$('#cfg_proy_id').val(), anio:$('#prd_anio').val()}, function(r){ toast(r.message,r.status==='success'); loadPlan(); }, 'json');
});
$('#pco_origen').change(function(){ aplicarPresetOrigen($(this).val()); });
$('#cfg_proy_id').change(cargarConfigProyecto);
$('#btnGuardarPlan').click(guardarCuadro);
$(document).on('click', '.btn-insert-proy', function(){
  applyProyectada(parseInt($(this).data('mes'),10));
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
$(function(){ loadProyectos(); loadCfg(); loadPlan(); aplicarPresetOrigen('relavera'); });
</script>
</body>
</html>
