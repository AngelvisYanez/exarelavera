<?php
/**
 * Módulo: Habilitación de la API por Token
 * Área: Herramientas / Administración
 *
 * Genera y administra tokens de acceso a la API REST de EXA Contable,
 * con límite de consultas configurable por token.
 *
 * Autenticación: usa la sesión activa del panel de administración.
 * Si no hay sesión, se solicita un Bearer token (del login de la API).
 */
require_once('../../Librerias/config.php/register_globals.php');
require_once('../LOGICA/seguridad.php');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>EXA Contable - Tokens de Acceso a la API</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background:#f5f6f7; padding:20px; font-family:'Open Sans',Arial,sans-serif; }
        .card { background:#fff; border:1px solid #e3e6ea; border-radius:6px; padding:18px; margin-bottom:18px; }
        .card h3 { margin-top:0; font-size:18px; color:#333; }
        .badge-quota { background:#5bc0de; }
        .badge-limit { background:#d9534f; }
        .codebox { background:#272822; color:#f8f8f2; padding:10px; border-radius:4px; font-family:monospace; word-break:break-all; }
        table td { vertical-align:middle; }
        .token-muted { color:#999; font-size:12px; }
        #tokNuevo { position:relative; }
        .toast-msg { position:fixed; top:60px; right:20px; z-index:9999; }
    </style>
</head>
<body>
    <div class="container-fluid">

        <div class="card" id="authCard" style="display:none;">
            <h3><i class="fa fa-lock"></i> Conexión a la API</h3>
            <p class="text-muted">Para administrar tokens ingresa tu credencial de la API (usuario, contraseña y empresa) o pega un Bearer token válido.</p>
            <div class="row">
                <div class="col-sm-3"><input id="inUser" class="form-control" placeholder="Usuario"></div>
                <div class="col-sm-3"><input id="inPass" type="password" class="form-control" placeholder="Contraseña"></div>
                <div class="col-sm-3">
                    <select id="inEmpresa" class="form-control"><option value="">Empresa...</option></select>
                </div>
                <div class="col-sm-3">
                    <button class="btn btn-primary" onclick="apiLogin()"><i class="fa fa-sign-in"></i> Conectar</button>
                    <button class="btn btn-default" onclick="apiEmpresas()"><i class="fa fa-refresh"></i> Empresas</button>
                </div>
            </div>
            <div style="margin-top:10px">
                <div class="input-group">
                    <input id="inBearer" class="form-control" placeholder="...o pega un Bearer token aquí (base64 de usuario:empresa:time:firma)">
                    <span class="input-group-btn">
                        <button class="btn btn-warning" onclick="useBearer()">Usar token</button>
                    </span>
                </div>
            </div>
        </div>

        <div class="card" id="mainCard" style="display:none;">
            <div class="row">
                <div class="col-sm-9">
                    <h3><i class="fa fa-key"></i> Tokens de acceso a la API</h3>
                </div>
                <div class="col-sm-3 text-right">
                    <button class="btn btn-success" onclick="nuevoToken()"><i class="fa fa-plus"></i> Generar token</button>
                    <button class="btn btn-link" onclick="apiTokenList()"><i class="fa fa-refresh"></i></button>
                </div>
            </div>
            <hr>
            <div id="listVacio" class="alert alert-info" style="display:none;">Aún no hay tokens.</div>
            <table class="table table-condensed table-hover" id="tablaTokens" style="display:none;">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Empresa</th>
                        <th>Cuota</th>
                        <th>Uso</th>
                        <th>Expira</th>
                        <th>Estado</th>
                        <th>Creado</th>
                        <th style="width:180px">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyTokens"></tbody>
            </table>
        </div>

        <div class="card" id="nuevoCard" style="display:none;">
            <h3><i class="fa fa-plus-circle"></i> Generar token de acceso</h3>
            <div class="row">
                <div class="col-sm-4">
                    <label>Nombre del token</label>
                    <input id="nNombre" class="form-control" placeholder="Ej: Integración CRM">
                </div>
                <div class="col-sm-4">
                    <label>Empresa (Emp_Cod)</label>
                    <select id="nEmpresa" class="form-control"></select>
                </div>
                <div class="col-sm-2">
                    <label>Límite de consultas</label>
                    <input id="nCuota" type="number" min="0" class="form-control" value="0" title="0 = ilimitado">
                </div>
                <div class="col-sm-2">
                    <label>Periodo</label>
                    <select id="nPeriodo" class="form-control">
                        <option value="D">Por día</option>
                        <option value="M">Por mes</option>
                    </select>
                </div>
            </div>
            <div class="row" style="margin-top:10px">
                <div class="col-sm-4">
                    <label>Expiración (vacío = no expira)</label>
                    <input id="nExpira" type="date" class="form-control">
                </div>
                <div class="col-sm-8 text-right">
                    <button class="btn btn-primary" onclick="guardarToken()"><i class="fa fa-check"></i> Generar</button>
                    <button class="btn btn-default" onclick="cancelarNuevo()">Cancelar</button>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-sm-12">
                    <label class="control-label">
                        <i class="fa fa-shield"></i> Módulos y procesos de la API que puede consumir
                    </label>
                    <div class="help-block" style="margin-bottom:6px">
                        Marca los procesos a los que tendrá acceso el token. Si no marcas ninguno, el token podrá consumir <b>todos</b> los módulos (sin restricción).
                    </div>
                    <div style="margin-bottom:8px">
                        <label class="checkbox-inline"><input type="checkbox" id="nPermTodo" checked> <b>Todos los módulos</b></label>
                        <button type="button" class="btn btn-xs btn-link" onclick="marcarTodos()">Marcar todos</button>
                        <button type="button" class="btn btn-xs btn-link" onclick="desmarcarTodos()">Desmarcar todos</button>
                    </div>
                    <input id="nPermBuscar" type="text" class="form-control" placeholder="Filtrar módulo o proceso..." style="max-width:420px;margin-bottom:10px" oninput="renderPermisos()">
                    <div id="nPermLista" style="max-height:360px;overflow:auto;border:1px solid #e3e6ea;border-radius:4px;padding:10px"></div>
                </div>
            </div>
        </div>

    </div>

    <div class="modal fade" id="modalToken" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-key"></i> Token generado</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">Guárdalo ahora. Por seguridad <b>no se mostrará de nuevo</b>.</div>
                    <div class="codebox" id="tokValor"></div>
                    <p class="token-muted">Úsalo en el header: <code>Authorization: Bearer &lt;token&gt;</code></p>
                    <div style="margin-top:8px">
                        <button class="btn btn-sm btn-info" onclick="copiarToken()"><i class="fa fa-copy"></i> Copiar</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPermisos" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-shield"></i> Permisos del token: <span id="mpNombre"></span></h4>
                </div>
                <div class="modal-body">
                    <div class="help-block">Los procesos marcados son los que este token podrá consumir. Ninguno marcado = acceso a todos los módulos.</div>
                    <input id="mpBuscar" type="text" class="form-control" placeholder="Filtrar módulo o proceso..." oninput="renderPermisosEdicion()" style="margin-bottom:10px">
                    <div id="mpLista" style="max-height:420px;overflow:auto;border:1px solid #e3e6ea;border-radius:4px;padding:10px"></div>
                    <input type="hidden" id="mpTokId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="guardarPermisos()"><i class="fa fa-save"></i> Guardar permisos</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-msg" id="toast" style="display:none;"></div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
    <script>
    var API_URL = '';
    var bearer = localStorage.getItem('exa_api_bearer') || '';

    function toast(msg, ok){ var t=$('#toast'); t.text(msg).attr('class','toast-msg alert '+(ok?'alert-success':'alert-danger')).fadeIn(150).delay(2600).fadeOut(150); }

    function API(method, path, body){
        return new Promise(function(resolve, reject){
            var headers = {'Content-Type':'application/json'};
            if (bearer) headers['Authorization'] = 'Bearer ' + bearer;
            $.ajax({
                url: API_URL.replace(/\/+$/,'') + path,
                method: method,
                headers: headers,
                data: body ? JSON.stringify(body) : undefined,
                xhrFields: { withCredentials: true },
                success: function(res){ resolve(res); },
                error: function(xhr){
                    if (xhr.status === 401) { mostrarAuth(); }
                    var msg = 'Error ' + xhr.status;
                    try { var j=JSON.parse(xhr.responseText); if(j.error) msg=j.error; } catch(e){}
                    reject({status:xhr.status, error:msg});
                }
            });
        });
    }

    function mostrarAuth(){ $('#authCard').show(); $('#mainCard').hide(); $('#nuevoCard').hide(); }
    function useBearer(){ bearer = $('#inBearer').val().trim(); localStorage.setItem('exa_api_bearer', bearer); cargar(); }
    function apiLogin(){
        API('POST','/v1/auth/login',{username:$('#inUser').val(),password:$('#inPass').val(),empresa:$('#inEmpresa').val()})
        .then(function(r){ if(r.success){ bearer=r.token; localStorage.setItem('exa_api_bearer', bearer); toast('Conectado','ok'); cargar(); } else toast(r.error||'Login fallido'); })
        .catch(function(e){ toast(e.error); });
    }
    function apiEmpresas(){
        API('GET','/v1/admin/api-tokens/empresas').then(function(r){
            var o = '<option value="">Empresa...</option>';
            (r.data||[]).forEach(function(e){ o += '<option value="'+e.Emp_Nom+'">'+e.Emp_Nom+'</option>'; });
            $('#inEmpresa').html(o);
        }).catch(function(e){ toast(e.error); });
    }

    function cargar(){
        $('#mainCard').show(); $('#authCard').hide();
        apiTokenList();
        cargarEmpresasSelect();
    }

    function cargarEmpresasSelect(){
        API('GET','/v1/admin/api-tokens/empresas').then(function(r){
            var o='<option value="">Seleccione...</option>';
            (r.data||[]).forEach(function(e){ o += '<option value="'+e.Emp_Cod+'">['+e.Emp_Cod+'] '+e.Emp_Nom+'</option>'; });
            $('#nEmpresa').html(o);
        }).catch(function(e){ toast(e.error); });
    }

    function estadoBadge(est){
        return est==='A' ? '<span class="badge" style="background:#5cb85c">Activo</span>'
                         : '<span class="badge" style="background:#999">Inactivo</span>';
    }
    function cuotaBadge(c,q){
        if(c<=0) return '<span class="badge" style="background:#5bc0de">Ilimitado</span>';
        var left = c-q; var cls = left<=0 ? '#d9534f' : (left<= (c*0.2) ? '#f0ad4e' : '#5cb85c');
        return '<span class="badge" style="background:'+cls+'">'+q+' / '+c+'</span>';
    }

    function apiTokenList(){
        API('GET','/v1/admin/api-tokens?Emp_Cod='+($('#fEmp').val()||'')).then(function(r){
            var d = r.data||[];
            $('#listVacio').toggle(d.length===0);
            $('#tablaTokens').toggle(d.length>0);
            var h='';
            d.forEach(function(t){
                h += '<tr>';
                h += '<td><b>'+ (t.Tok_Nombre||'') +'</b><br><span class="token-muted">...'+ (t.Tok_Resumen||'') +'</span></td>';
                h += '<td>'+ (t.Tok_Bdd||'') +' / #'+(t.Emp_Cod||'') +'</td>';
                h += '<td>'+ cuotaBadge(parseInt(t.Tok_Cuota||0), parseInt(t.Tok_Usadas||0)) +'</td>';
                h += '<td>'+(t.Tok_Periodo==='M'?'mes':'día')+'</td>';
                h += '<td>'+(t.Tok_Expira ? t.Tok_Expira.slice(0,10) : '—')+'</td>';
                h += '<td>'+ estadoBadge(t.Tok_Est) +'</td>';
                h += '<td class="token-muted">'+(t.Tok_Creado||'').slice(0,10)+'</td>';
                h += '<td>';
                if(t.Tok_Est==='A') h += '<button class="btn btn-xs btn-warning" onclick="revocar('+t.Tok_Id+')" title="Revocar"><i class="fa fa-ban"></i></button> ';
                else h += '<button class="btn btn-xs btn-success" onclick="activar('+t.Tok_Id+')" title="Activar"><i class="fa fa-play"></i></button> ';
                h += '<button class="btn btn-xs btn-info" onclick="resetUso('+t.Tok_Id+')" title="Resetear contador"><i class="fa fa-undo"></i></button> ';
                h += '<button class="btn btn-xs btn-primary" onclick="editarPermisos('+t.Tok_Id+',\''+ (t.Tok_Nombre||'').replace(/'/g,"\\'") +'\')" title="Módulos/Procesos"><i class="fa fa-shield"></i></button> ';
                h += '<button class="btn btn-xs btn-danger" onclick="eliminar('+t.Tok_Id+')" title="Eliminar"><i class="fa fa-trash"></i></button>';
                h += '</td></tr>';
            });
            $('#tbodyTokens').html(h);
        }).catch(function(e){ toast(e.error); });
    }

    function nuevoToken(){ $('#nuevoCard').show(); $('#nuevoCard')[0].scrollIntoView({behavior:'smooth'}); }
    function cancelarNuevo(){ $('#nuevoCard').hide(); }
    function guardarToken(){
        var body = {
            nombre: $('#nNombre').val(),
            Emp_Cod: $('#nEmpresa').val(),
            cuota: parseInt($('#nCuota').val()||0),
            periodo: $('#nPeriodo').val(),
            expira: $('#nExpira').val() || '',
            permisos: todoSel ? [] : Object.keys(sel)
        };
        if(!body.nombre){ toast('El nombre es requerido'); return; }
        if(!body.Emp_Cod){ toast('Seleccione una empresa'); return; }
        API('POST','/v1/admin/api-tokens/generar',body).then(function(r){
            if(r.success){
                $('#tokValor').text(r.data.token);
                $('#modalToken').modal('show');
                toast(r.message||'Token creado','ok');
                cancelarNuevo();
                apiTokenList();
            } else toast(r.error||'No se pudo crear');
        }).catch(function(e){ toast(e.error); });
    }
    function copiarToken(){ var t=$('#tokValor').text(); if(navigator.clipboard){ navigator.clipboard.writeText(t).then(function(){toast('Copiado','ok');}); } }
    function revocar(id){ UI_TOK_ACTION('revocar',id); }
    function activar(id){ UI_TOK_ACTION('activar',id); }
    function resetUso(id){ API('POST','/v1/admin/api-tokens/reset-uso',{Tok_Id:id}).then(function(r){ toast(r.success?'Contador reseteado':'Error','ok'); apiTokenList(); }).catch(function(e){toast(e.error);}); }
    function eliminar(id){ if(!confirm('¿Eliminar definitivamente este token?'))return; API('POST','/v1/admin/api-tokens/eliminar',{Tok_Id:id}).then(function(r){ toast(r.success?'Eliminado':'Error','ok'); apiTokenList(); }).catch(function(e){toast(e.error);}); }
    function UI_TOK_ACTION(act,id){
        API('POST','/v1/admin/api-tokens/'+act,{Tok_Id:id}).then(function(r){ toast(r.success?'Listo':'Error','ok'); apiTokenList(); }).catch(function(e){toast(e.error);});
    }

    /* ===== Permisos por módulo / proceso ===== */
    var catalog = null;
    var sel = {};      // rutas seleccionadas (form nuevo)
    var selE = {};     // rutas seleccionadas (modal edición)
    var todoSel = true;   // form "todos los módulos"
    var todoSelE = false; // edición: ningún permiso = todos

    function loadCatalogo(){
        API('GET','/v1/admin/api-tokens/permisos').then(function(r){
            catalog = r.modulos || [];
            todoSel = true; sel = {};
            renderPermisos();
        }).catch(function(e){ toast(e.error); });
    }

    function rutasFiltradas(m, q){
        var sal = [];
        (m.rutas||[]).forEach(function(r){
            if(!q || (m.name+' '+r.ruta+' '+(r.descripcion||'')).toLowerCase().indexOf(q)>=0) sal.push(r);
        });
        return sal;
    }
    function renderArbol(container, state, todos, rutaOnChange, modOnChange){
        if(!catalog){ $(container).html('<span class="token-muted">Cargando catálogo...</span>'); return; }
        var q = ($(container).attr('data-q')||'').toLowerCase();
        var html='';
        catalog.forEach(function(m){
            var rutas = rutasFiltradas(m,q);
            if(q && !rutas.length && m.name.toLowerCase().indexOf(q)===-1) return;
            var modSel = todos || rutas.every(function(r){ return state[r.ruta]; });
            html += '<div style="margin-bottom:8px">';
            html += '<label class="checkbox-inline" style="font-weight:bold;margin-bottom:4px">';
            html += '<input type="checkbox" '+((todos||modSel)?'checked':'')+(todos?' disabled':'')+' onchange="'+modOnChange+'(this,\''+m.name+'\')"> '+m.name+' <span class="token-muted">('+rutas.length+')</span>';
            html += '</label>';
            if(rutas.length){
                html += '<div style="margin-left:30px">';
                rutas.forEach(function(r){
                    var checked = todos || state[r.ruta];
                    html += '<div><label class="checkbox-inline" style="font-weight:normal">';
                    html += '<input type="checkbox" '+(checked?'checked':'')+(todos?' disabled':'')+' onchange="'+rutaOnChange+'(this,\''+r.ruta.replace(/'/g,"\\'")+'\',\''+m.name+'\')">';
                    html += '<code>'+r.metodo+'</code> <span>'+r.ruta+'</span>';
                    if(r.descripcion) html += ' <span class="token-muted">— '+r.descripcion+'</span>';
                    html += '</label></div>';
                });
                html += '</div>';
            }
            html += '</div>';
        });
        $(container).html(html);
    }

    function renderPermisos(){
        $('#nPermLista').attr('data-q', $('#nPermBuscar').val()||'');
        renderArbol('#nPermLista', sel, todoSel, 'toggleRuta', 'toggleModulo');
    }
    function renderPermisosEdicion(){
        $('#mpLista').attr('data-q', $('#mpBuscar').val()||'');
        renderArbol('#mpLista', selE, todoSelE, 'toggleRutaE', 'toggleModuloE');
    }

    function toggleModulo(cb,mod){
        catalog.forEach(function(m){
            if(m.name===mod) m.rutas.forEach(function(r){ if(cb.checked) sel[r.ruta]=true; else delete sel[r.ruta]; });
        });
        renderPermisos();
    }
    function toggleRuta(cb,ruta){ if(cb.checked) sel[ruta]=true; else delete sel[ruta]; }
    function toggleModuloE(cb,mod){
        catalog.forEach(function(m){
            if(m.name===mod) m.rutas.forEach(function(r){ if(cb.checked) selE[r.ruta]=true; else delete selE[r.ruta]; });
        });
        renderPermisosEdicion();
    }
    function toggleRutaE(cb,ruta){ if(cb.checked) selE[ruta]=true; else delete selE[ruta]; }

    function marcarTodos(){ todoSel=false; catalog.forEach(function(m){ m.rutas.forEach(function(r){ sel[r.ruta]=true; }); }); $('#nPermTodo').prop('checked',false); renderPermisos(); }
    function desmarcarTodos(){ todoSel=false; sel={}; $('#nPermTodo').prop('checked',false); renderPermisos(); }
    $('#nPermTodo').change(function(){ todoSel=this.checked; if(todoSel) sel={}; renderPermisos(); });
    $('#nPermBuscar').on('input', renderPermisos);
    $('#mpBuscar').on('input', renderPermisosEdicion);

    function editarPermisos(id,nombre){
        $('#mpNombre').text(nombre);
        $('#mpTokId').val(id);
        todoSelE=false; selE={};
        API('GET','/v1/admin/api-tokens/permisos?Tok_Id='+id).then(function(r){
            if(!catalog){ catalog = r.modulos || []; }
            selE={};
            (r.permisos||[]).forEach(function(p){ selE[p.Tip_Ruta]=true; });
            todoSelE = Object.keys(selE).length===0;
            renderPermisosEdicion();
            $('#modalPermisos').modal('show');
        }).catch(function(e){ toast(e.error); });
    }
    function guardarPermisos(){
        var permisos = todoSelE ? [] : Object.keys(selE);
        API('POST','/v1/admin/api-tokens/permisos',{Tok_Id:$('#mpTokId').val(), permisos:permisos}).then(function(r){
            toast(r.success?(r.message||'Permisos guardados'):(r.error||'Error'),'ok');
            $('#modalPermisos').modal('hide');
            apiTokenList();
        }).catch(function(e){ toast(e.error); });
    }

    $(function(){
        if (bearer) cargar(); else mostrarAuth();
        if (bearer) { loadCatalogo(); }
    });
    </script>
</body>
</html>
