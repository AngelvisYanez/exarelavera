/**
 * Validación, Grids e Interactividad de Dos Ambientes para vehículos y choferes
 * @author Sistema EXA
 * @version 1.1
 */
$(function () {
    // Inicializar los Grids del Ambiente 1 (Consulta)
    initGrids();

    // Configurar Datepicker para la caducidad de licencia
    if (typeof $.createDatePickers === 'function') {
        $.createDatePickers("#Cho_Cli");
    } else if ($.fn.datepicker) {
        $("#Cho_Cli").datepicker({
            dateFormat: 'dd/mm/yy',
            changeMonth: true,
            changeYear: true
        });
    }

    // Cargar dinámicamente las empresas de transporte para el selector
    $.getJSON('', { listTransportesAjax: true }, function (data) {
        var $select = $('#Mat_Cod');
        $select.empty().append('<option value="">Seleccione...</option>');
        $.each(data, function (i, item) {
            $select.append($('<option>', {
                value: item.Mat_Cod,
                text: item.Mat_Des
            }));
        });
    }).fail(function() {
        console.error("Error al cargar las empresas de transporte.");
    });

    // Detectar Enter en inputs de búsqueda
    $('#searchChofer').on('keypress', function(e) {
        if (e.which === 13) reloadGridChoferes();
    });
    $('#searchVehiculo').on('keypress', function(e) {
        if (e.which === 13) reloadGridVehiculos();
    });

    // Detectar cuando el usuario termina de escribir la cédula para autocompletar
    $('#Cho_Ced').on('blur', function() {
        var cedula = $(this).val().trim();
        if (cedula.length >= 10) {
            buscarPersonaPorCedula(cedula);
        }
    });

    // Reajustar jqGrid al cambiar de tab
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        $(window).trigger('resize');
    });
});

/**
 * Busca si una persona existe por su número de identificación y autocompleta el formulario
 * @param {string} cedula 
 */
function buscarPersonaPorCedula(cedula) {
    $.getJSON('', { buscarPersonaPorCedulaAjax: 1, cedula: cedula }, function (res) {
        if (res && res.exists) {
            $('#Prs_Nom').val(res.Prs_Nom);
            $('#Prs_Ape').val(res.Prs_Ape);
            $('#Cho_Tel').val(res.Prs_Tel);
            
            if (res.Prs_San) {
                var tsa = res.Prs_San.toUpperCase().trim();
                $('#Cho_Tsa').val(tsa);
            }
            
            if (res.isChofer) {
                $('#Cho_Tli').val(res.Cho_Tli);
                $('#Cho_Cli').val(res.Cho_Cli);
            } else {
                $('#Cho_Tli').val('');
                $('#Cho_Cli').val('');
            }
        }
    }).fail(function() {
        console.error("Error al buscar persona por cédula o RUC.");
    });
}

/**
 * Inicializa los Grids de Choferes y Vehículos en Ambiente 1
 */
function initGrids() {
    $('#gridChoferes').createGrid({
        caption: '',
        url: window.location.href,
        postData: { listChoferesGridAjax: 1 },
        height: 300,
        rowNum: 50,
        rowList: [10, 25, 50, 100, -1],
        colModel: [
            { label: 'Código', name: 'Cho_Cod', key: true, hidden: true, width: 50, align: 'center' },
            { label: 'Cédula', name: 'Prs_Ced', width: 100, align: 'center' },
            { label: 'Nombre', name: 'nombre', width: 220 },
            { label: 'Licencia', name: 'Cho_Tli', width: 80, align: 'center' },
            { label: 'Caducidad', name: 'Cho_Cli', width: 110, align: 'center', formatter: function(v) {
                if (!v) return '';
                // Formatear fecha de YYYY-MM-DD a dd/mm/aaaa
                var parts = v.split('-');
                if (parts.length === 3) {
                    return parts[2] + '/' + parts[1] + '/' + parts[0];
                }
                return v;
            }}
        ],
        viewrecords: true,
        jsonReader: { root: "rows", page: "page", total: "total", records: "records", repeatitems: false }
    }, false, '#pagerChoferes', { refresh: true, view: false });

    $('#gridVehiculos').createGrid({
        caption: '',
        url: window.location.href,
        postData: { listVehiculosGridAjax: 1 },
        height: 300,
        rowNum: 50,
        rowList: [10, 25, 50, 100, -1],
        colModel: [
            { label: 'Código', name: 'Veh_Cod', key: true, hidden: true, width: 50, align: 'center' },
            { label: 'Placa', name: 'Veh_Pla', width: 100, align: 'center' },
            { label: 'Marca', name: 'Veh_Mar', width: 140 },
            { label: 'Color', name: 'Veh_Col', width: 100, align: 'center' },
            { label: 'Tipo', name: 'Veh_Tit', width: 110, align: 'center', formatter: function(v) {
                if (v === 'V') return 'Volqueta';
                if (v === 'B') return 'Bus';
                if (v === 'C') return 'Camioneta';
                if (v === 'T') return 'Tráiler';
                if (v === 'M') return 'Mixer';
                return v || '';
            }},
            { label: 'Empresa Transporte', name: 'empresa_transporte', width: 200 }
        ],
        viewrecords: true,
        jsonReader: { root: "rows", page: "page", total: "total", records: "records", repeatitems: false }
    }, false, '#pagerVehiculos', { refresh: true, view: false });
}

/**
 * Recarga el Grid de Choferes
 */
function reloadGridChoferes() {
    var search = $('#searchChofer').val().trim();
    var op_opciones = $('#opChofer').val() || 'd';
    $('#gridChoferes').jqGrid('setGridParam', {
        postData: {
            listChoferesGridAjax: 1,
            search: search,
            op_opciones: op_opciones
        },
        page: 1
    }).trigger('reloadGrid');
}

/**
 * Recarga el Grid de Vehículos
 */
function reloadGridVehiculos() {
    var search = $('#searchVehiculo').val().trim();
    var op_opciones = $('#opVehiculo').val() || 'p';
    $('#gridVehiculos').jqGrid('setGridParam', {
        postData: {
            listVehiculosGridAjax: 1,
            search: search,
            op_opciones: op_opciones
        },
        page: 1
    }).trigger('reloadGrid');
}

/**
 * Regresa al Ambiente 1 (Listado)
 */
function mostrarListado() {
    // Restaurar título del panel original
    $('.panel-main .panel-heading').html('<span class="glyphicon glyphicon-edit"></span> » Gestión de Vehículos y Choferes por Planta');
    $('#divFormulario').hide();
    $('#divListado').fadeIn(400, function() {
        // Reajustar dimensiones de los grids una vez visible
        $(window).trigger('resize');
    });
}

/**
 * Cambia al Ambiente 2 (Registro)
 * @param {string} tipo 'chofer' o 'vehiculo'
 */
function mostrarFormulario(tipo) {
    // Resetear formularios
    $('#formChofer')[0].reset();
    $('#formVehiculo')[0].reset();
    $('#Veh_Tit').val('V'); // Volqueta por defecto

    $('#divListado').hide();
    $('#divFormulario').fadeIn();

    // Mostrar dinámicamente solo el formulario del tipo seleccionado y cambiar título
    if (tipo === 'chofer') {
        $('.panel-main .panel-heading').html('<span class="glyphicon glyphicon-edit"></span> » Registrar Nuevo Chofer');
        $('#divFormTabVehiculo').hide();
        $('#divFormTabChofer').show();
    } else {
        $('.panel-main .panel-heading').html('<span class="glyphicon glyphicon-edit"></span> » Registrar Nuevo Vehículo');
        $('#divFormTabChofer').hide();
        $('#divFormTabVehiculo').show();
    }
}

/**
 * Guarda los datos del Chofer
 */
function guardarChofer() {
    var ced = $('#Cho_Ced').val().trim();
    var tel = $('#Cho_Tel').val().trim();
    var nom = $('#Prs_Nom').val().trim();
    var ape = $('#Prs_Ape').val().trim();
    var tli = $('#Cho_Tli').val();
    var cli = $('#Cho_Cli').val().trim();
    var tsa = $('#Cho_Tsa').val();

    if (!ced || !tel || !nom || !ape || !tli || !cli || !tsa) {
        $.alert("Todos los campos marcados con asterisco (*) son obligatorios.");
        return;
    }

    // Validar Cédula o RUC ecuatoriano
    if (typeof ValidarCedula === 'function') {
        if (!ValidarCedula(ced)) {
            $.alert("La identificación ingresada no es válida.");
            return;
        }
    } else {
        if (ced.length < 10 || ced.length > 13) {
            $.alert("La longitud del número de identificación (Cédula o RUC) no es válida.");
            return;
        }
    }

    $('#loader').show();
    var formData = $('#formChofer').serialize();
    formData += '&saveChoferAjax=1';

    $.post('', formData, function (res) {
        $('#loader').hide();
        if (res && res.success) {
            $.alert(res.message || "Chofer registrado exitosamente.", function() {
                mostrarListado();
                reloadGridChoferes();
            });
        } else {
            $.alert(res.message || "Error al registrar el chofer.");
        }
    }, 'json').fail(function() {
        $('#loader').hide();
        $.alert("Error de comunicación con el servidor.");
    });
}

/**
 * Guarda los datos del Vehículo
 */
function guardarVehiculo() {
    var prv = $('#Prv_Cod').val();
    var pla = $('#Veh_Pla').val().trim().toUpperCase();
    var mar = $('#Veh_Mar').val().trim();
    var col = $('#Veh_Col').val().trim();
    var tit = $('#Veh_Tit').val();

    // Guardar placa en mayúsculas
    $('#Veh_Pla').val(pla);

    if (!prv || !pla || !mar || !col || !tit) {
        $.alert("Todos los campos marcados con asterisco (*) son obligatorios, incluido el Proveedor.");
        return;
    }

    // Validar placa (Formato ecuatoriano: AAA-1234 o similar, 7 u 8 caracteres)
    var placaRegex = /^[A-Z]{3}-\d{3,4}$/i;
    if (!placaRegex.test(pla)) {
        $.alert("El formato de la placa no es válido (Ejemplo: ABC-1234 o ABC-123).");
        return;
    }

    $('#loader').show();
    var formData = $('#formVehiculo').serialize();
    formData += '&saveVehiculoAjax=1';

    $.post('', formData, function (res) {
        $('#loader').hide();
        if (res && res.success) {
            $.alert(res.message || "Vehículo registrado exitosamente.", function() {
                mostrarListado();
                reloadGridVehiculos();
            });
        } else {
            $.alert(res.message || "Error al registrar el vehículo.");
        }
    }, 'json').fail(function() {
        $('#loader').hide();
        $.alert("Error de comunicación con el servidor.");
    });
}

/**
 * Busca un proveedor por cédula/RUC y auto-completa el input
 */
function buscarProveedor(cedula) {
    if (!cedula) {
        $.alert("Ingrese una cédula o RUC para buscar.");
        return;
    }
    
    $('#loader').show();
    $.post('', { buscarProveedorAjax: 1, cedula: cedula }, function(res) {
        $('#loader').hide();
        if (res && res.success) {
            $('#Prv_Cod').val(res.Prv_Cod);
            $('#Prv_Nom').val(res.Prv_Nom);
        } else {
            $('#Prv_Cod').val('');
            $('#Prv_Nom').val('');
            $.alert(res.message || "Proveedor no encontrado.");
        }
    }, 'json').fail(function() {
        $('#loader').hide();
        $.alert("Error de comunicación con el servidor.");
    });
}
