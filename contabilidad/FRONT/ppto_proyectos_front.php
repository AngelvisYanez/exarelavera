<?php require_once('../../administrador/LOGICA/seguridad.php'); ?>
<!DOCTYPE html>
<html lang="es" class="exa-ui-fill-root">
<head>
    <meta charset="UTF-8">
    <title>Proyectos Presupuestarios - EXA</title>
    <?php require_once __DIR__ . '/con_model3_assets.php'; ?>
    <style>
        .exa-pre-form-panel label { display: block; font-size: 11px; font-weight: 600; margin-bottom: 4px; color: #4a5568; }
        .exa-pre-form-panel h5 { margin: 0 0 12px; font-weight: 700; color: #1a365d; }
    </style>
</head>
<body class="exa-ui-fill-root">
<div class="panel panel-main exa-ui-panel exa-ui-fill-page">
    <div class="panel-heading exa-header exa-header-flex">
        <h3 class="panel-title"><i class="bi bi-folder2-open"></i> Proyectos Presupuestarios</h3>
    </div>
    <div class="panel-body exa-body">
        <div class="exa-ui-page-view">
            <div id="msg" class="alert" style="display:none;"></div>
            <div class="exa-pre-form-panel">
                <h5>Nuevo / Editar proyecto</h5>
                <input type="hidden" id="is_edit" value="0" />
                <div class="row">
                    <div class="col-md-2"><label>Codigo</label><input id="proy_id" class="form-control input-sm" /></div>
                    <div class="col-md-4"><label>Nombre</label><input id="proy_nombre" class="form-control input-sm" /></div>
                    <div class="col-md-2"><label>Estado</label><select id="proy_estado" class="form-control input-sm"><option value="A">Activo</option><option value="I">Inactivo</option></select></div>
                    <div class="col-md-3"><label>Plantilla</label><select id="plt_id" class="form-control input-sm"></select></div>
                    <div class="col-md-1" style="padding-top:22px;"><button id="btnSaveProy" class="btn btn-success btn-sm">Guardar</button></div>
                </div>
            </div>
            <div class="exa-adq-table-wrap">
                <table class="table table-bordered exa-adq-table" id="tblProy">
                    <thead><tr><th>Codigo</th><th>Nombre</th><th>Estado</th><th>Plantilla</th><th>Acciones</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            <hr/>
            <h5 style="font-weight:700;color:#1a365d;margin:18px 0 12px;">Rubros y toneladas planificadas</h5>
            <div class="exa-pre-form-panel">
                <div class="row">
                    <div class="col-md-2"><label>Proyecto</label><select id="rub_proy_id" class="form-control input-sm"></select></div>
                    <div class="col-md-2"><label>Version</label><select id="rub_ppe_id" class="form-control input-sm"></select></div>
                    <div class="col-md-2"><label>Partida</label><select id="rub_ppa_id" class="form-control input-sm"></select></div>
                    <div class="col-md-2"><label>Rubro</label><input id="pdp_rubro" class="form-control input-sm" placeholder="Ej. Relaves" /></div>
                    <div class="col-md-1"><label>Ton base</label><input id="pdp_toneladas_base" type="number" step="0.0001" class="form-control input-sm" /></div>
                    <div class="col-md-1"><label>$/Ton</label><input id="pdp_factor_anual_tonelada" type="number" step="0.01" class="form-control input-sm" /></div>
                    <div class="col-md-1" style="padding-top:22px;"><button id="btnSaveRubro" class="btn btn-primary btn-sm">Agregar</button></div>
                </div>
            </div>
            <div class="exa-adq-table-wrap">
                <table class="table table-bordered exa-adq-table" id="tblRubros">
                    <thead><tr><th>Partida</th><th>Rubro</th><th>Ton base</th><th>Factor</th><th>Presup. anual</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
var API = '../LOGICA/ppto_proyectos_logica.php';
function toast(msg, ok){ var m=$('#msg'); m.removeClass('alert-success alert-danger').addClass(ok?'alert-success':'alert-danger').text(msg).show(); }
function loadCatalogos(){
  $.getJSON(API, {action:'catalogos'}, function(r){
    $('#plt_id').html('<option value="">-- Sin plantilla --</option>');
    $.each(r.plantillas||[], function(i,p){ $('#plt_id').append('<option value="'+p.plt_id+'">'+p.plt_nombre+'</option>'); });
    $('#rub_ppa_id').html('');
    $.each(r.partidas||[], function(i,p){ $('#rub_ppa_id').append('<option value="'+p.ppa_id+'">'+p.ppa_codigo_clasificacion+' - '+p.ppa_descripcion+'</option>'); });
    $('#rub_ppe_id').html('');
    $.each(r.versiones||[], function(i,v){ $('#rub_ppe_id').append('<option value="'+v.ppe_id+'">'+v.ppe_anio+' V'+v.ppe_version+'</option>'); });
  });
}
function loadProyectos(){
  $.getJSON(API, {action:'list'}, function(r){
    var tb=$('#tblProy tbody').empty(), sel=$('#rub_proy_id').empty();
    $.each(r.rows||[], function(i,p){
      tb.append('<tr><td>'+p.proy_id+'</td><td>'+p.proy_nombre+'</td><td>'+p.proy_estado+'</td><td>'+(p.plt_nombre||'-')+'</td><td><button class="btn btn-xs btn-default btnEdit" data-json=\''+JSON.stringify(p)+'\'>Editar</button></td></tr>');
      sel.append('<option value="'+p.proy_id+'">'+p.proy_nombre+'</option>');
    });
  });
}
function loadRubros(){
  var proy=$('#rub_proy_id').val(); if(!proy) return;
  $.getJSON(API, {action:'list_rubros', proy_id:proy}, function(r){
    var tb=$('#tblRubros tbody').empty();
    $.each(r.rows||[], function(i,x){
      tb.append('<tr><td>'+x.ppa_codigo_clasificacion+'</td><td>'+x.pdp_rubro+'</td><td>'+x.pdp_toneladas_base+'</td><td>'+x.pdp_factor_anual_tonelada+'</td><td>'+x.pdp_presupuesto_anual+'</td></tr>');
    });
  });
}
$('#btnSaveProy').click(function(){
  $.post(API, {action:'save', is_edit:$('#is_edit').val(), proy_id:$('#proy_id').val(), proy_nombre:$('#proy_nombre').val(), proy_estado:$('#proy_estado').val(), plt_id:$('#plt_id').val()}, function(r){
    toast(r.message, r.status==='success'); if(r.status==='success'){ loadProyectos(); $('#is_edit').val(0); }
  }, 'json');
});
$('#btnSaveRubro').click(function(){
  $.post(API, {action:'save_rubro', proy_id:$('#rub_proy_id').val(), ppe_id:$('#rub_ppe_id').val(), ppa_id:$('#rub_ppa_id').val(), pdp_rubro:$('#pdp_rubro').val(), pdp_toneladas_base:$('#pdp_toneladas_base').val(), pdp_factor_anual_tonelada:$('#pdp_factor_anual_tonelada').val()}, function(r){
    toast(r.message, r.status==='success'); if(r.status==='success') loadRubros();
  }, 'json');
});
$(document).on('click','.btnEdit', function(){
  var p=JSON.parse($(this).attr('data-json'));
  $('#is_edit').val(1); $('#proy_id').val(p.proy_id).prop('readonly',true);
  $('#proy_nombre').val(p.proy_nombre); $('#proy_estado').val(p.proy_estado); $('#plt_id').val(p.plt_id||'');
});
$('#rub_proy_id').change(loadRubros);
$(function(){ loadCatalogos(); loadProyectos(); });
</script>
</body>
</html>
