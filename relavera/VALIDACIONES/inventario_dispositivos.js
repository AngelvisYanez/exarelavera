$(document).ready(function () {
    initGrid();
    
    // Inicializar segment controls (radioset)
    $('.radioset').buttonset();
    
    // Formatear MAC automáticamente mientras se escribe en el modal
    $('#mac_address').on('input', function() {
        let value = $(this).val().toUpperCase().replace(/[^0-9A-F]/g, '');
        let formatted = "";
        for (let i = 0; i < value.length && i < 12; i++) {
            if (i > 0 && i % 2 === 0) formatted += ":";
            formatted += value[i];
        }
        $(this).val(formatted);
    });

    // Formatear MAC en el filtro de búsqueda solo si está seleccionado "MAC Address"
    $('#txt_busqueda').on('input', function() {
        let tipo = $('input[name="tipo_busqueda"]:checked').val();
        if (tipo === 'mac') {
            let value = $(this).val().toUpperCase().replace(/[^0-9A-F]/g, '');
            let formatted = "";
            for (let i = 0; i < value.length && i < 12; i++) {
                if (i > 0 && i % 2 === 0) formatted += ":";
                formatted += value[i];
            }
            $(this).val(formatted);
        }
    });
    
    // Cambiar placeholder según el tipo de búsqueda seleccionado
    $('input[name="tipo_busqueda"]').on('change', function() {
        if ($(this).val() === 'mac') {
            $('#txt_busqueda').attr('placeholder', 'XX:XX:XX:XX:XX:XX').val('');
        } else {
            $('#txt_busqueda').attr('placeholder', 'Ingrese nombre del equipo...').val('');
        }
    });

    // Permitir buscar al presionar Enter en el campo de texto
    $('#txt_busqueda').on('keypress', function(e) {
        if (e.which === 13) {
            buscarGrid();
        }
    });
});

function initGrid() {
    $("#grid_dispositivos").jqGrid({
        url: '../COMPONENTES/getInventarioDispositivos.php',
        datatype: "json",
        colNames: ['ID', 'MAC Address', 'Nombre Equipo', 'Fecha Registro', 'Descripción', 'Tipo', 'Estado', 'Acciones'],
        colModel: [
            { name: 'InvDis_Cod', index: 'InvDis_Cod', width: 50, align: 'center', hidden: true },
            { name: 'mac_address', index: 'mac_address', width: 150, align: 'center' },
            { name: 'InvDis_Nom', index: 'InvDis_Nom', width: 200 },
            { name: 'InvDis_Fec', index: 'InvDis_Fec', width: 130, align: 'center' },
            { name: 'InvDis_Des', index: 'InvDis_Des', hidden: true },
            { name: 'InvDis_Cupos', index: 'InvDis_Cupos', width: 60, align: 'center' },
            { name: 'InvDis_Tipo', index: 'InvDis_Tipo', width: 80, align: 'center',
                formatter: function(cell) {
                    var val = (cell || '').toString().toUpperCase();
                    if (val === 'MOVIL') {
                        return '<i class="fa fa-mobile"></i> Movil';
                    } else {
                        return '<i class="fa fa-desktop"></i> PC';
                    }
                }
            },
            { name: 'InvDis_Est', index: 'InvDis_Est', width: 90, align: 'center',
                formatter: function (cellvalue) {
                    if (cellvalue === 'A') {
                        return '<span class="label label-success" style="background-color: #28a745; font-size: 12px; padding: 3px 10px;">Activo</span>';
                    } else {
                        return '<span class="label label-danger" style="background-color: #dc3545; font-size: 12px; padding: 3px 10px;">Inactivo</span>';
                    }
                }
            },
            { name: 'acciones', index: 'acciones', width: 40, align: 'center', sortable: false, search: false,
                formatter: function (cellvalue, options, rowObject) {
                    let id = rowObject.InvDis_Cod;
                    let estado = rowObject.InvDis_Est;
                    let btnEstado = '';
                    
                    if (estado === 'A') {
                        btnEstado = '<button class="btn btn-xs btn-danger" onclick="cambiarEstado(' + id + ', \'I\')" title="Inactivar"><i class="fa fa-ban"></i></button>';
                    } else {
                        btnEstado = '<button class="btn btn-xs btn-success" onclick="cambiarEstado(' + id + ', \'A\')" title="Activar"><i class="fa fa-check"></i></button>';
                    }
                    
                    return '<button class="btn btn-xs btn-primary" onclick="abrirModalEditar(' + options.rowId + ')" title="Editar"><i class="fa fa-edit"></i></button> ' + btnEstado;
                }
            }
        ],
        rowNum: 100,
        rowList: [100, 200, 300, 500],
        pager: '#pager_dispositivos',
        sortname: 'InvDis_Cod',
        viewrecords: true,
        sortorder: "desc",
        autowidth: true,
        height: 400,
        caption: "Listado de Dispositivos",
        hidegrid: false,
        loadonce: false,
        jsonReader: {
            repeatitems: false,
            id: "InvDis_Cod",
            root: "rows"
        }
    });
}

function buscarGrid() {
    let tipo = $('input[name="tipo_busqueda"]:checked').val();
    let query = $('#txt_busqueda').val();
    let estado = $('input[name="filtro_estado"]:checked').val();
    
    let mac = tipo === 'mac' ? query : '';
    let nombre = tipo === 'nombre' ? query : '';
    
    $("#grid_dispositivos").jqGrid('setGridParam', {
        postData: {
            filters: JSON.stringify({
                groupOp: "AND",
                rules: [
                    { field: "mac_address", op: "cn", data: mac },
                    { field: "InvDis_Nom", op: "cn", data: nombre },
                    { field: "InvDis_Est", op: "eq", data: estado }
                ]
            })
        },
        page: 1
    }).trigger("reloadGrid");
}

// function limpiarBusqueda() {
//     $('#txt_busqueda').val('');
//     $('#rad_mac').prop('checked', true);
//     $('#est_todos').prop('checked', true);
//     $('.radioset').buttonset('refresh');
//     buscarGrid();
// }

function abrirModalNuevo() {
    $('#tbody_masivo').empty();
    agregarFilaMasiva(); // Empezar con una fila
    $('#modal_masivo').modal('show');
}

function agregarFilaMasiva() {
    let row = '<tr>' +
        '<td><input type="text" class="input-masivo mac-input" maxlength="17" placeholder="XX:XX:XX:XX:XX:XX"></td>' +
        '<td><input type="text" class="input-masivo" maxlength="100" placeholder="Nombre del equipo"></td>' +
        '<td><input type="text" class="input-masivo" placeholder="Opcional..."></td>' +
        '<td><select class="input-masivo"><option value="PC">PC</option><option value="MOVIL">Movil</option></select></td>' +
        '<td><input type="number" class="input-masivo text-center" min="1" max="99" value="1"></td>' +
        '<td><select class="input-masivo"><option value="A">Activo</option><option value="I">Inactivo</option></select></td>' +
        '<td class="text-center"><button type="button" class="btn btn-xs btn-danger" onclick="eliminarFilaMasiva(this)"><i class="fa fa-trash"></i></button></td>' +
        '</tr>';
    $('#tbody_masivo').append(row);
    
    // Aplicar formato MAC a la nueva fila
    $('.mac-input').off('input').on('input', function() {
        let value = $(this).val().toUpperCase().replace(/[^0-9A-F]/g, '');
        let formatted = "";
        for (let i = 0; i < value.length && i < 12; i++) {
            if (i > 0 && i % 2 === 0) formatted += ":";
            formatted += value[i];
        }
        $(this).val(formatted);
    });
}

function eliminarFilaMasiva(btn) {
    if ($('#tbody_masivo tr').length > 1) {
        $(btn).closest('tr').remove();
    } else {
        $.alert("Debe haber al menos una fila");
    }
}

function guardarMasivo() {
    let dispositivos = [];
    let error = false;
    
    $('#tbody_masivo tr').each(function() {
        let mac = $(this).find('td:eq(0) input').val().toUpperCase();
        let nombre = $(this).find('td:eq(1) input').val();
        let desc = $(this).find('td:eq(2) input').val();
        let tipo = $(this).find('td:eq(3) select').val();
        let cupos = $(this).find('td:eq(4) input').val();
        let estado = $(this).find('td:eq(5) select').val();
        
        if (mac === "" || nombre === "") {
            $.alert("MAC y Nombre son obligatorios en todas las filas");
            error = true;
            return false;
        }
        
        if (!validarMAC(mac)) {
            $.alert("Formato de MAC inválido en una de las filas: " + mac);
            error = true;
            return false;
        }
        
        dispositivos.push({
            mac_address: mac,
            nombre_equipo: nombre,
            descripcion: desc,
            tipo_equipo: tipo,
            cupos: cupos,
            estado: estado
        });
    });
    
    if (error || dispositivos.length === 0) return;
    
    $('#loader').show();
    $.post('../COMPONENTES/saveInventarioDispositivo.php', {
        action: 'bulk_save',
        dispositivos: dispositivos
    }, function(resp) {
        $('#loader').fadeOut('slow');
        if (resp.success) {
            $('#modal_masivo').modal('hide');
            $("#grid_dispositivos").trigger("reloadGrid");
            $.alert(resp.message, null, 'ok');
        } else {
            $.alert(resp.message, null, 'remove');
        }
    }, 'json').fail(function() {
        $('#loader').fadeOut('slow');
        $.alert("Error de conexión al guardar registros masivos");
    });
}

function abrirModalEditar(rowId) {
    let rowData = $("#grid_dispositivos").jqGrid('getRowData', rowId);
    
    $('#dispositivo_id').val(rowData.InvDis_Cod);
    $('#mac_address').val(rowData.mac_address).prop('readonly', true);
    $('#nombre_equipo').val(rowData.InvDis_Nom);
    $('#descripcion').val(rowData.InvDis_Des);
    $('#tipo_dispositivo').val(rowData.InvDis_Tipo);
    $('#cupos_dispositivo').val(rowData.InvDis_Cupos || 1);
    $('#estado').val(rowData.InvDis_Est.includes('Activo') ? 'A' : 'I');
    
    $('#modal_titulo').text('Editar Dispositivo');
    $('#modal_dispositivo').modal('show');
}

function validarMAC(mac) {
    const regex = /^([0-9A-F]{2}[:]){5}([0-9A-F]{2})$/;
    return regex.test(mac.toUpperCase());
}

function guardarDispositivo() {
    let id = $('#dispositivo_id').val();
    let mac = $('#mac_address').val().toUpperCase();
    let nombre = $('#nombre_equipo').val();
    let descripcion = $('#descripcion').val();
    let tipo = $('#tipo_dispositivo').val();
    let cupos = $('#cupos_dispositivo').val() || 1;
    let estado = $('#estado').val();
    
    if (mac === "") {
        $.alert("La dirección MAC es obligatoria");
        return;
    }
    
    if (!validarMAC(mac)) {
        $.alert("Formato de MAC inválido (XX:XX:XX:XX:XX:XX)");
        return;
    }
    
    if (nombre === "") {
        $.alert("El nombre del equipo es obligatorio");
        return;
    }
    
    // Validación de duplicado vía AJAX antes de guardar
    $('#loader').show();
    $.post('../COMPONENTES/validarMac.php', { mac_address: mac, id: id }, function (resp) {
        $('#loader').fadeOut('slow');
        if (resp.existe) {
            $.alert("La dirección MAC ya existe en el sistema", null, 'remove');
        } else {
            // Proceder a guardar
            $('#loader').show();
            $.post('../COMPONENTES/saveInventarioDispositivo.php', {
                id: id,
                mac_address: mac,
                nombre_equipo: nombre,
                descripcion: descripcion,
                tipo_equipo: tipo,
                cupos: cupos,
                estado: estado,
                action: 'save'
            }, function (result) {
                $('#loader').fadeOut('slow');
                if (result.success) {
                    $('#modal_dispositivo').modal('hide');
                    $("#grid_dispositivos").trigger("reloadGrid");
                    $.alert(result.message, null, 'ok');
                } else {
                    $.alert(result.message, null, 'remove');
                }
            }, 'json').fail(function() {
                $('#loader').fadeOut('slow');
                $.alert("Error al guardar los datos");
            });
        }
    }, 'json').fail(function() {
        $('#loader').show().fadeOut('slow');
        $.alert("Error de conexión con el servidor");
    });
}

function cambiarEstado(id, nuevoEstado) {
    let msg = nuevoEstado === 'I' ? "¿Está seguro de inactivar este dispositivo?" : "¿Está seguro de activar este dispositivo?";
    
    $.createDialogConfirm(msg, null, function() {
        $('#loader').show();
        $.post('../COMPONENTES/saveInventarioDispositivo.php', {
            id: id,
            estado: nuevoEstado,
            action: 'change_status'
        }, function (result) {
            $('#loader').fadeOut('slow');
            if (result.success) {
                $("#grid_dispositivos").trigger("reloadGrid");
                $.alert(result.message, null, 'ok');
            } else {
                $.alert(result.message, null, 'remove');
            }
        }, 'json').fail(function() {
            $('#loader').fadeOut('slow');
            $.alert("Error al procesar la solicitud");
        });
    });
}
