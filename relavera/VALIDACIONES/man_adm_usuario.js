var container = $("#container");
var EP_URL = '../../administrador/FRONT/adm_alt_user_2.0.php';

$(function () {
    container.createGrid({
        caption:"LISTADO DE USUARIOS", selectGridRows:false,
        sortname: "Usu_Est, Usuario", sortorder: "asc", datatype: 'json',
        postData: $("#searchUsr").getData("usrAjax"),
        height: 300, stateCol:'Usu_Est',
        colModel: [         
            { label:'Cod. Int.', name: 'Usu_Cod', align: "center", hidden: false, key: true, width: 2 },
            { label:'C&eacute;dula/RUC', name: 'Usu_Ced', align: "center", width: 3 },
            { label:'Usuario', name: 'Usuario', width: 10 },
            { label:'Planta', name: 'Planta', width: 15, hidden: true },
            { label:'Perfiles', name: 'Perfiles', width: 12, formatter:'tags', formatoptions:{label:'Per_Des',type:'purple'} },
            { label:'Estado', name: 'Usu_Est', align: "center", width: 3, formatter:'estado', formatoptions:{full:true} },
            { label:$.createIcon('pencil'), name:'editPlanta', width:2, formatter:'gridButton', formatoptions:{
                action:'editarClientePlanta', data:['Usu_Cod','Usuario','Usu_Ced'], icon:'pencil', type:'warning', title:'Editar Cliente/Planta',
                conditional:function(o){
                    // Aparece para todos los registros si estamos en la pestaña de Plantas
                    return $('#tabType').val() === 'plantas';
                },
                caseFalse:function(){ return ''; }
            } },
            { label:$.createIcon('cog'), name:'actReg', width:2, formatter:'gridButton',
                formatoptions:{ 
                    action:'changeUsuario', data:['Usu_Cod','Usuario',{Usu_Est:'I'}], icon:'ban-circle', type:'danger', title:'<u class="red">Desactivar</u> Usuario', 
                    conditional:function(o){ 
                        var isAdmin = $.grep(o.Perfiles || [], function(p){ return p.Per_Des === 'Administrador de Sistemas'; }).length > 0;
                        return o.Usu_Est==='A' && !isAdmin; 
                    }, 
                    caseFalse:function(o){ 
                        var isAdmin = $.grep(o.Perfiles || [], function(p){ return p.Per_Des === 'Administrador de Sistemas'; }).length > 0;
                        if(isAdmin && o.Usu_Est === 'A') return '';
                        return $.getGridButton('changeUsuario',$.newObj(['Usu_Cod','Usuario',{Usu_Est:'A'}],o),'<u class="green">Activar</u> Usuario','ok'); 
                    } 
                }
            },
            { label:$.createIcon('eye-open'), name:'Usu_Vis', align: "center", width:2, 
                formatter:function(cv,o,r){
                    if(r.Usu_Est === 'I'){
                        var chk = (cv === 'S') ? 'checked' : '';
                        return '<input type="checkbox" '+chk+' onchange="updateUsuVis('+r.Usu_Cod+', this.checked)">';
                    }
                    return '';
                }
            }
        ]
    }, true, "#containerPager", { view: false, refresh: true }).gridButtonsAdd([null, 
        { caption: "Exportar Excel&nbsp;", buttonicon: "download-alt", onClickButton: function () {
                container.jqGrid('exportGridExcel', {
                    nombre: "Control_Usuarios",
                    hoja: "HOJA 1",
                    footer: true
                });
            },
            position: "last"
        }
    ]);

    $('#userTabs a').on('shown.bs.tab', function (e) {
        var type = $(e.target).data('type');
        $('#tabType').val(type);

        if (type === 'plantas') {
            container.jqGrid('showCol', 'Planta');
        } else {
            container.jqGrid('hideCol', 'Planta');
        }

        // Redimensionar el grid al ancho del contenedor con un pequeño delay
        setTimeout(function() {
            container.jqGrid('setGridWidth', $("#tablasContainer").width());
        }, 100);
        
        $('#searchUsr').submit();
    });

    $(window).on('resize', function() {
        container.jqGrid('setGridWidth', $("#tablasContainer").width());
    });

    // Redimensionar al cargar la página
    setTimeout(function() {
        container.jqGrid('setGridWidth', $("#tablasContainer").width());
    }, 200);
});

function changeUsuario(usu){ 
    usu['updateUsuario']=true;
    //console.log(usu);
    $.createDialogConfirm(`¿Est&aacute; seguro que desea `+(usu['Usu_Est']==='A'?'<strong class="green">ACTIVAR</strong> a <u class="red">':'<strong class="red">DESACTIVAR</strong> a <u class="green">')+`${usu['Usuario']}</u>  ?`,usu,function(){
        $('#loader').fadeIn();
        $.saveDataJson("", usu, function(r){                 
            $('#loader').fadeOut();
            container.changeRowData(usu.Usu_Cod,usu);
            return false;
        });
    });
}

function updateUsuVis(usuCod, isChecked) {
    var val = isChecked ? 'S' : 'N';
    $('#loader').fadeIn();
    $.saveDataJson("", { updateUsuVis: true, Usu_Cod: usuCod, Usu_Vis: val }, function(r) {
        $('#loader').fadeOut();
        if (r.success) {
                // Recargar el grid después de que el usuario cierre el mensaje de éxito
                $.alert(r.message, function(){
                    container.trigger("reloadGrid");
                }, 'ok');
                return false; // Evitar que $.saveDataJson muestre su propio alert
            }
        });
    }

    /* =========================================================
    *  EDITAR CLIENTE / PLANTA  (solo para perfil Plantas+Prueba)
    * ========================================================= */
function editarClientePlanta(usu) {
    var realCod = usu.Usu_Cod;
    var rowId = realCod;
    
    $('#epUsu_Cod').val(realCod);
    $('#epPrs_Ced').val('');
    $('#epCliNom').val('');
    $('#epCli_Cod').val('');
    $('#epPla_Cod').html('<option value="">-- Seleccione --</option>');
    
    // Cargar datos del Usuario en los campos informativos
    $('#epUserCed').val(usu.Usu_Ced || '');
    $('#epUserNom').val(usu.Usuario || '');

    // Manejo dinámico de Tabs basado en perfiles del usuario
    // Obtenemos el HTML directamente de la celda del grid para mayor seguridad
    var $gridCell = container.find('tr#' + rowId + ' td[aria-describedby$="_Perfiles"]');
    var totalPerfiles = $gridCell.find('span, label, div[class*="label"]').length;
    var textoPrimerPerfil = $gridCell.find('span, label, div[class*="label"]').first().text().trim();
    
    var soloPlantas = (totalPerfiles === 1 && textoPrimerPerfil === 'Plantas');

    if (soloPlantas) {
        // Solo tiene perfil Plantas: ocultamos tab de planta y activamos el de contraseña
        $('#modalEpTabs a[href="#epTabPlanta"]').parent().hide();
        $('#modalEpTabs a[href="#epTabPass"]').tab('show');
    } else {
        // Tiene más perfiles: mostramos ambos tabs y activamos el de Planta por defecto
        $('#modalEpTabs a[href="#epTabPlanta"]').parent().show();
        $('#modalEpTabs a[href="#epTabPlanta"]').tab('show');
    }

    $('#epNewPass, #epConfPass').val('');

    $.getJSON(EP_URL, { getClienteByUser: true, Usu_Cod: realCod }, function(r) {
        if (r.success && r.cliente && r.cliente.Cli_Cod) {
            var c = r.cliente;
            $('#epPrs_Ced').val(c.Prs_Ced || '');
            $('#epCliNom').val(c.nombre   || '');
            $('#epCli_Cod').val(c.Cli_Cod || '');
            epCargarPlantas(c.Cli_Cod, c.Pla_Cod);
        }
    });

    $('#editPlantaDialog')
        .dialog('option', 'title', 'Editar Planta/Cliente \u2014 ' + (usu.Usuario || ''))
        .dialog('option', 'height', 'auto')
        .dialog('open');
}

function epCargarPlantas(Cli_Cod, Pla_Cod_sel) {
    $.getJSON(EP_URL, { AjaxPlanta: true, Cli_Cod: Cli_Cod }, function(r) {
        var $sel = $('#epPla_Cod').empty().append('<option value="">-- Seleccione --</option>');
        if (r.success && r.rows) {
            $.each(r.rows, function(i, row) {
                var txt  = (row.Pla_Nom || '') + (row.Pla_Lic ? ' - ' + row.Pla_Lic : '');
                var $opt = $('<option>').val(row.Pla_Cod).text(txt);
                if (Pla_Cod_sel && row.Pla_Cod == Pla_Cod_sel) $opt.prop('selected', true);
                $sel.append($opt);
            });
        }
    });
}

function epBuscarClientes() {
    var dato   = $('#epCliSearch').val();
    var filtro = $('input[name="epOp"]:checked').val() || 'd';
    $('#epCliResultBody').html('<tr><td colspan="3" class="text-center"><i class="glyphicon glyphicon-refresh"></i> Buscando...</td></tr>');
    $.getJSON(EP_URL, { clientesAjax: true, op_opciones: filtro, searchCli: dato, rows: 30, page: 1 }, function(r) {
        var $tbody = $('#epCliResultBody').empty();
        if (r.rows && r.rows.length > 0) {
            $.each(r.rows, function(i, row) {
                var $btn = $('<button type="button" class="btn btn-xs btn-primary epSelCli"><span class="glyphicon glyphicon-ok"></span> Sel.</button>');
                $btn.data('row', row);
                var $tr = $('<tr>').append(
                    $('<td>').text(row.Prs_Ced || ''),
                    $('<td>').text(row.nombre  || ''),
                    $('<td style="text-align:center;">').append($btn)
                );
                $tbody.append($tr);
            });
        } else {
            $tbody.html('<tr><td colspan="3" class="text-center text-muted">Sin resultados.</td></tr>');
        }
    });
}

$(function() {
    $('#editPlantaDialog').dialog({ autoOpen: false, modal: true, width: 650, resizable: false, title: 'Editar Planta/Cliente' });
    $('#epCliDialog').dialog({ autoOpen: false, modal: false, width: 620, height: 430, resizable: false, title: 'B\u00fasqueda de Clientes' });

    // Ajustar altura del modal al cambiar de pestaña
    $('#modalEpTabs a').on('shown.bs.tab', function() {
        $('#editPlantaDialog').dialog('option', 'height', 'auto');
    });

    $(document).on('click', '#epBtnGuardar', function() {
        var Usu_Cod = $('#epUsu_Cod').val();
        if (!Usu_Cod) { $.alert('Error: usuario no identificado.'); return; }

        var activeTab = $('#modalEpTabs li.active a').attr('href');

        if (activeTab === '#epTabPlanta') {
            var Cli_Cod = $('#epCli_Cod').val();
            var Pla_Cod = $('#epPla_Cod').val();
            $('#loader').fadeIn();
            $.getJSON(EP_URL, { actualizarClientePlantaAjax: true, Usu_Cod: Usu_Cod, Cli_Cod: Cli_Cod, Pla_Cod: Pla_Cod }, function(r) {
                $('#loader').fadeOut();
                if (r.success) {
                    $.alert('Planta/Cliente actualizado correctamente.');
                    $('#editPlantaDialog').dialog('close');
                } else {
                    $.alert('Error: ' + (r.message || 'No se pudo guardar.'));
                }
            });
        } else {
            var pass  = $('#epNewPass').val();
            var conf  = $('#epConfPass').val();
            var regex = /^[a-z0-9]+$/i;

            if (pass === '') { $.alert('Ingrese la nueva contrase&ntilde;a.'); return; }
            if (!regex.test(pass)) { $.alert('La contrase&ntilde;a solo debe contener letras y n&uacute;meros.'); return; }
            if (pass !== conf) { $.alert('Las contrase&ntilde;as no coinciden.'); return; }

            var passMD5 = md5(pass);
            $('#loader').fadeIn();
            $.getJSON('', { changePassAjax: true, Usu_Cod: Usu_Cod, Usu_Pal: passMD5 }, function(r) {
                $('#loader').fadeOut();
                if (r.success) {
                    $.alert('Contrase&ntilde;a actualizada correctamente.');
                    $('#editPlantaDialog').dialog('close');
                } else {
                    $.alert('Error: ' + (r.message || 'No se pudo actualizar la contrase&ntilde;a.'));
                }
            });
        }
    });

    $(document).on('click', '#epBtnLimpiar', function() {
        $('#epPrs_Ced').val('');
        $('#epCliNom').val('');
        $('#epCli_Cod').val('');
        $('#epPla_Cod').html('<option value="">-- Seleccione --</option>');
    });

    $(document).on('click', '#epBtnBusCli', function() {
        $('#epCliResultBody').html('<tr><td colspan="3" class="text-center text-muted">Realice una b&uacute;squeda...</td></tr>');
        $('#epCliSearch').val('');
        $('#epCliDialog').dialog('open');
    });

    $(document).on('click', '#epCliBtnBuscar', function() { epBuscarClientes(); });
    $(document).on('keypress', '#epCliSearch', function(e) {
        if (e.which === 13) epBuscarClientes();
    });

    $(document).on('click', '.epSelCli', function() {
        var row = $(this).data('row');
        $('#epPrs_Ced').val(row.Prs_Ced || '');
        $('#epCliNom').val(row.nombre   || '');
        $('#epCli_Cod').val(row.Cli_Cod || '');
        $('#epPla_Cod').html('<option value="">-- Seleccione --</option>');
        if (row.Cli_Cod) epCargarPlantas(row.Cli_Cod, null);
        $('#epCliDialog').dialog('close');
    });
});