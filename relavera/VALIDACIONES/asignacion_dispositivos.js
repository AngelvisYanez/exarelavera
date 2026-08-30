$(document).ready(function () {
    initGridAsignados();
    cargarUsuarios();

    // Evento al cambiar de usuario
    $('#cmb_usuario').on('change', function () {
        let usuario_id = $(this).val();
        $('#chk_seleccionar_todo').prop('checked', false).prop('disabled', usuario_id === "");
        
        if (usuario_id !== "" && usuario_id !== null) {
            cargarDispositivosDisponibles(usuario_id);
            
            $("#grid_asignados").jqGrid('setGridParam', {
                url: '../COMPONENTES/getAsignaciones.php?usuario_id=' + usuario_id,
                datatype: "json",
                page: 1
            }).trigger("reloadGrid");
            
            $('#btn_asignar').prop('disabled', false);
        } else {
            $('#div_disponibles').html('<div class="text-center text-muted" style="margin-top: 130px;">Seleccione un usuario para ver dispositivos disponibles</div>');
            $("#grid_asignados").jqGrid('clearGridData');
            $('#btn_asignar').prop('disabled', true);
        }
    });

    // Evento Seleccionar Todo
    $('#chk_seleccionar_todo').on('change', function() {
        let checked = $(this).is(':checked');
        $('input[name="chk_dispositivo"]').prop('checked', checked);
    });
});

function initGridAsignados() {
    $("#grid_asignados").jqGrid({
        datatype: "local",
        colNames: ['ID', 'MAC Address', 'Nombre Equipo', 'Vínculo de Navegador', 'Estado', 'Acciones'],
        colModel: [
            { name: 'id', index: 'id', width: 50, align: 'center', hidden: true },
            { name: 'mac_address', index: 'mac_address', width: 150, align: 'center' },
            { name: 'InvDis_Nom', index: 'InvDis_Nom', width: 200 },
            { name: 'vinculado_id', index: 'vinculado_id', width: 220, align: 'center', sortable: false,
                formatter: function (cell, opt, row) {
                    if (cell && cell > 0) {
                        return '<div style="line-height: 1.2;">' +
                               '<span class="text-primary" style="font-size: 10px; font-weight: bold;"><i class="fa fa-link"></i> OCUPADO</span><br>' +
                               '<small style="font-size: 9px; color: #666;">IP: ' + (row.ip_vinculo || 'N/A') + '</small><br>' +
                               '<button type="button" class="btn btn-xs btn-warning" style="margin-top: 3px; font-size: 10px;" onclick="desvincularNavegador(' + cell + ')" title="Limpiar este cupo para que otro navegador pueda entrar">' +
                               '<i class="fa fa-eraser"></i> Limpiar Cupo</button>' +
                               '</div>';
                    } else {
                        return '<span class="text-muted" style="font-size: 10px;"><i class="fa fa-check-circle"></i> Disponible</span>';
                    }
                }
            },
            { name: 'estado', index: 'estado', width: 80, align: 'center',
                formatter: function (cellvalue) {
                    if (cellvalue === 'A') {
                        return '<span class="label label-success" style="background-color: #28a745; font-size: 11px; padding: 2px 8px;">Activo</span>';
                    } else {
                        return '<span class="label label-danger" style="background-color: #dc3545; font-size: 11px; padding: 2px 8px;">Inactivo</span>';
                    }
                }
            },
            { name: 'acciones', index: 'acciones', width: 80, align: 'center', sortable: false,
                formatter: function (cellvalue, options, rowObject) {
                    let id = rowObject.id;
                    return '<button type="button" class="btn btn-xs btn-danger" onclick="quitarAsignacion(' + id + ')" title="Quitar Asignación"><i class="fa fa-unlink"></i> Quitar</button>';
                }
            }
        ],
        rowNum: 100,
        viewrecords: true,
        autowidth: true,
        height: 350,
        caption: "Dispositivos del Usuario",
        hidegrid: false,
        pager: "#pager_asignados",
        jsonReader: {
            repeatitems: false,
            id: "id",
            root: "rows"
        }
    });

    $("#grid_asignados").jqGrid('navGrid', '#pager_asignados', 
        { edit: false, add: false, del: false, search: false, refresh: true, view: false },
        {}, // edit options
        {}, // add options
        {}, // del options
        {}, // search options
        {}  // view options
    );
}

function cargarUsuarios() {
    $.get('../COMPONENTES/getUsuarios.php', function (data) {
        let html = '<option value="">[Seleccione Usuario]</option>';
        if (data && data.length > 0) {
            $.each(data, function (i, v) {
                html += '<option value="' + v.id + '">' + (v.nombre || 'Usuario ' + v.id) + '</option>';
            });
        }
        
        // Limpiar y cargar opciones
        $('#cmb_usuario').html(html).val('').trigger('change');
        
        // Inicializar o refrescar Select2
        if ($.fn.select2) {
            // Destruir instancia previa si existe para asegurar una carga limpia
            if ($('#cmb_usuario').data('select2')) {
                $('#cmb_usuario').select2('destroy');
            }
            
            $('#cmb_usuario').select2({
                placeholder: "[Seleccione Usuario]",
                allowClear: true,
                minimumResultsForSearch: 0, // Siempre mostrar buscador
                width: '100%',
                language: {
                    noResults: function() { return "No se encontraron resultados"; },
                    searching: function() { return "Buscando..."; }
                }
            });
        }
    }, 'json').fail(function() {
        console.error("Error al cargar usuarios");
    });
}

function cargarDispositivosDisponibles(usuario_id) {
    $('#div_disponibles').html('<div class="text-center" style="margin-top: 130px;"><i class="fa fa-spinner fa-spin"></i> Cargando...</div>');
    $('#chk_seleccionar_todo').prop('checked', false);
    
    $.get('../COMPONENTES/getDispositivosDisponibles.php', { usuario_id: usuario_id }, function (data) {
        let html = '';
        if (data && data.length > 0) {
            $.each(data, function (i, v) {
                html += '<div class="checkbox" style="margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">' +
                        '  <label style="width: 100%; cursor: pointer;">' +
                        '    <input type="checkbox" name="chk_dispositivo" value="' + v.InvDis_Cod + '" onchange="verificarSeleccionTodo()"> ' +
                        '    <strong>' + v.mac_address + '</strong><br>' +
                        '    <small class="text-muted">' + v.InvDis_Nom + '</small>' +
                        '  </label>' +
                        '</div>';
            });
            $('#chk_seleccionar_todo').prop('disabled', false);
        } else {
            html = '<div class="text-center text-muted" style="margin-top: 130px;">No hay dispositivos disponibles</div>';
            $('#chk_seleccionar_todo').prop('disabled', true);
        }
        $('#div_disponibles').html(html);
    }, 'json');
}

function verificarSeleccionTodo() {
    let total = $('input[name="chk_dispositivo"]').length;
    let seleccionados = $('input[name="chk_dispositivo"]:checked').length;
    $('#chk_seleccionar_todo').prop('checked', total > 0 && total === seleccionados);
}

function asignarDispositivos() {
    let usuario_id = $('#cmb_usuario').val();
    let seleccionados = [];
    $('input[name="chk_dispositivo"]:checked').each(function () {
        seleccionados.push($(this).val());
    });

    if (seleccionados.length === 0) {
        $.alert("Debe seleccionar al menos un dispositivo para asignar", null, 'remove');
        return;
    }

    $('#loader').show();
    $.post('../COMPONENTES/saveAsignacion.php', {
        action: 'assign',
        usuario_id: usuario_id,
        dispositivos: seleccionados
    }, function (resp) {
        $('#loader').fadeOut('slow');
        if (resp.success) {
            $.alert(resp.message, null, 'ok');
            cargarDispositivosDisponibles(usuario_id);
            $("#grid_asignados").trigger("reloadGrid");
        } else {
            $.alert(resp.message, null, 'remove');
        }
    }, 'json').fail(function () {
        $('#loader').fadeOut('slow');
        $.alert("Error de conexión al asignar");
    });
}

function quitarAsignacion(id_asignacion) {
    let usuario_id = $('#cmb_usuario').val();
    $.createDialogConfirm("¿Está seguro de quitar esta asignación?", null, function () {
        $('#loader').show();
        $.post('../COMPONENTES/saveAsignacion.php', {
            action: 'unassign',
            id_asignacion: id_asignacion
        }, function (resp) {
            $('#loader').fadeOut('slow');
            if (resp.success) {
                $.alert(resp.message, null, 'ok');
                cargarDispositivosDisponibles(usuario_id);
                $("#grid_asignados").trigger("reloadGrid");
            } else {
                $.alert(resp.message, null, 'remove');
            }
        }, 'json').fail(function () {
            $('#loader').fadeOut('slow');
            $.alert("Error de conexión al quitar asignación");
        });
    });
}

function desvincularNavegador(vinculado_id) {
    let usuario_id = $('#cmb_usuario').val();
    $.createDialogConfirm("¿Está seguro de limpiar este cupo? El navegador actual perderá el acceso y el equipo quedará libre para una nueva vinculación.", null, function () {
        $('#loader').show();
        $.post('../COMPONENTES/saveAsignacion.php', {
            action: 'unlink_browser',
            vinculado_id: vinculado_id
        }, function (resp) {
            $('#loader').fadeOut('slow');
            if (resp.success) {
                $.alert(resp.message, null, 'ok');
                $("#grid_asignados").trigger("reloadGrid");
            } else {
                $.alert(resp.message, null, 'remove');
            }
        }, 'json').fail(function () {
            $('#loader').fadeOut('slow');
            $.alert("Error de conexión al desvincular");
        });
    });
}
