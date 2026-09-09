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

    // Detectar cuando el usuario termina de escribir la placa para autocompletar
    $('#Veh_Pla').on('blur', function() {
        var placa = $(this).val().trim().toUpperCase();
        $(this).val(placa);
        if (placa.length >= 7) {
            buscarVehiculoPorPlaca(placa);
        }
    });

    // Detectar cuando el usuario termina de escribir la serie para autocompletar maquinaria
    $('#Maq_Ser').on('blur', function() {
        var serie = $(this).val().trim().toUpperCase();
        $(this).val(serie);
        if (serie.length >= 2) {
            buscarMaquinariaPorSerie(serie);
        }
    });

    // Eventos para la búsqueda de Proveedor
    $('#Prv_Ced').on('blur', function() {
        var cedula = $(this).val().trim();
        if (cedula.length === 10 || cedula.length === 13) {
            buscarProveedor(cedula);
        }
    });

    $('#Prv_Ced').on('keypress', function(e) {
        if (e.which === 13) { // Enter
            e.preventDefault();
            var cedula = $(this).val().trim();
            if (cedula.length === 10 || cedula.length === 13) {
                buscarProveedor(cedula);
            }
        }
    });

    $('#Prv_Ced').on('input', function() {
        $('#iconProveedorStatus').html('<i class="glyphicon glyphicon-minus" style="color: #999;"></i>');
        $('#Prv_Nom').val('');
        $('#Prv_Cod').val('');
    });

    // Sincronizar checkbox RUC con Select Documento en Modal Proveedor
    $('#Reg_isRuc').on('change', function() {
        var isChecked = $(this).is(':checked');
        var currentVal = $('#Reg_Ide_Cod').val();
        
        var cedulaInput = $('#Reg_Prs_Ced');
        var valCed = cedulaInput.val().trim();
        
        if (isChecked) {
            if (valCed.length === 10) {
                cedulaInput.val(valCed + "001");
            }
            if (currentVal !== '1') {
                $('#Reg_Ide_Cod').val('1').trigger('change');
            }
        } else {
            if (valCed.length === 13 && valCed.endsWith("001")) {
                cedulaInput.val(valCed.substring(0, 10));
            }
            if (currentVal === '1') {
                $('#Reg_Ide_Cod').val('2').trigger('change');
            }
        }
    });

    $('#Reg_Ide_Cod').on('change', function() {
        var val = $(this).val();
        var isRucChecked = $('#Reg_isRuc').is(':checked');
        
        var cedulaInput = $('#Reg_Prs_Ced');
        var valCed = cedulaInput.val().trim();
        
        if (val === '1' && !isRucChecked) {
            if (valCed.length === 10) {
                cedulaInput.val(valCed + "001");
            }
            $('#Reg_isRuc').prop('checked', true);
        } else if (val !== '1' && isRucChecked) {
            if (valCed.length === 13 && valCed.endsWith("001")) {
                cedulaInput.val(valCed.substring(0, 10));
            }
            $('#Reg_isRuc').prop('checked', false);
        }
        
        // Ajustar maxlength del campo cédula/RUC
        if (val === '1') {
            $('#Reg_Prs_Ced').attr('maxlength', '13');
        } else if (val === '2') {
            $('#Reg_Prs_Ced').attr('maxlength', '10');
        } else {
            $('#Reg_Prs_Ced').attr('maxlength', '13');
        }
    });

    // Hacer modal draggable si existe jQuery UI
    if ($.fn.draggable) {
        $('#modalProveedor .modal-dialog').draggable({
            handle: ".modal-header"
        });
    }

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
 * Busca si un vehículo existe por su placa y autocompleta el formulario para edición
 * @param {string} placa 
 */
function buscarVehiculoPorPlaca(placa) {
    $.post('', { buscarVehiculoPorPlacaAjax: 1, placa: placa }, function (res) {
        if (res && res.success) {
            $('#Veh_Mar').val(res.Veh_Mar);
            $('#Veh_Col').val(res.Veh_Col);
            $('#Veh_Tit').val(res.Veh_Tit);
            $('#Veh_Val').val(res.Veh_Val);
            $('#Veh_Adi').val(res.Veh_Adi);
            $('#Prv_Cod').val(res.Prv_Cod);
            if (res.Prv_Nom) {
                $('#Prv_Nom').val(res.Prv_Nom);
            }
            if (res.Veh_Val !== undefined && res.Veh_Val !== null) {
                $('#Veh_Val').val(res.Veh_Val);
            }
        }
    }, 'json').fail(function() {
        console.error("Error al buscar vehículo por placa.");
    });
}

/**
 * Busca si una maquinaria existe por su serie y autocompleta el formulario para edición
 * @param {string} serie 
 */
function buscarMaquinariaPorSerie(serie) {
    $.post('', { buscarMaquinariaPorSerieAjax: 1, serie: serie }, function (res) {
        if (res && res.success) {
            $('#Maq_Cod').val(res.Maq_Cod);
            $('#Maq_Tip').val(res.Maq_Tip);
            $('#Veh_Mar').val(res.Maq_Mar);
            $('#Veh_Col').val(res.Maq_Col);
            $('#Veh_Adi').val(res.Maq_Adi);
        }
    }, 'json').fail(function() {
        console.error("Error al buscar maquinaria por serie.");
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
            }},
            { label: 'Acciones', name: 'acciones', width: 90, align: 'center', sortable: false, formatter: function(cellvalue, options, rowObject) {
                var choCod = rowObject.Cho_Cod || options.rowId;
                var cedula = rowObject.Prs_Ced || '';
                return '<button type="button" class="btn btn-xs btn-primary" onclick="editarChofer(\'' + choCod + '\', \'' + cedula + '\')" title="Editar Operario" style="margin-right:5px;"><i class="glyphicon glyphicon-pencil"></i></button>' +
                       '<button type="button" class="btn btn-xs btn-danger" onclick="inactivarChofer(\'' + choCod + '\')" title="Inactivar Operario"><i class="glyphicon glyphicon-trash"></i></button>';
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
            { label: 'ID', name: 'Row_Id', key: true, hidden: true, width: 50, align: 'center' },
            { label: 'Clasificación', name: 'Clasificacion', width: 95, align: 'center', formatter: function(v) {
                if (v === 'V') return '<span class="label label-primary" style="font-size: 11px;">Vehículo</span>';
                if (v === 'O') return '<span class="label label-warning" style="font-size: 11px;">Otro (Equipo)</span>';
                return v || '';
            }},
            { label: 'Placa / Serie', name: 'Ide_Pla_Ser', width: 110, align: 'center' },
            { label: 'Marca', name: 'Veh_Mar', width: 130 },
            { label: 'Color', name: 'Veh_Col', width: 90, align: 'center' },
            { label: 'Tipo', name: 'Veh_Tit', width: 120, align: 'center', formatter: function(v) {
                if (v === 'V') return 'Volqueta';
                if (v === 'B') return 'Bus(eta)';
                if (v === 'C') return 'Camioneta';
                if (v === 'T') return 'Tráiler';
                if (v === 'M') return 'Maquinaria';
                return v || '';
            }},
            { label: 'Valor Hora', name: 'Veh_Val', width: 90, align: 'right', formatter: 'number', formatoptions: { decimalSeparator: ".", thousandsSeparator: "", decimalPlaces: 2 } },
            { label: 'Empresa / Proveedor', name: 'empresa_transporte', width: 190 },
            { label: 'Acciones', name: 'acciones', width: 90, align: 'center', sortable: false, formatter: function(cellvalue, options, rowObject) {
                var rowId = rowObject.Row_Id || options.rowId;
                var clasif = rowObject.Clasificacion || (String(rowId).indexOf('M_') === 0 ? 'O' : 'V');
                var identificador = rowObject.Ide_Pla_Ser || '';
                return '<button type="button" class="btn btn-xs btn-primary" onclick="editarVehiculo(\'' + rowId + '\', \'' + clasif + '\', \'' + identificador + '\')" title="Editar" style="margin-right:5px;"><i class="glyphicon glyphicon-pencil"></i></button>' +
                       '<button type="button" class="btn btn-xs btn-danger" onclick="inactivarVehiculo(\'' + rowId + '\', \'' + clasif + '\')" title="Inactivar"><i class="glyphicon glyphicon-trash"></i></button>';
            }}
        ],
        viewrecords: true,
        jsonReader: { root: "rows", page: "page", total: "total", records: "records", repeatitems: false }
    }, false, '#pagerVehiculos', { refresh: true, view: false });
}

/**
 * Cambia el filtro de clasificación (Todos, Vehículos, Otros) en el Grid
 * @param {string} clasif '' | 'V' | 'O'
 */
function cambiarFiltroClasificacion(clasif) {
    $('#tipoClasificacionGrid').val(clasif);
    $('#btnClasifTodos, #btnClasifVeh, #btnClasifMaq').removeClass('active').css({'color':'#000','background-color':'#e6e6e6'});
    if (clasif === 'V') {
        $('#btnClasifVeh').addClass('active').css({'color':'#e67e22','background-color':'#fff'});
    } else if (clasif === 'O') {
        $('#btnClasifMaq').addClass('active').css({'color':'#e67e22','background-color':'#fff'});
    } else {
        $('#btnClasifTodos').addClass('active').css({'color':'#e67e22','background-color':'#fff'});
    }
    reloadGridVehiculos();
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
 * Recarga el Grid de Vehículos y Maquinarias
 */
function reloadGridVehiculos() {
    var search = $('#searchVehiculo').val().trim();
    var op_opciones = $('#opVehiculo').val() || 'p';
    var tipo_clasificacion = $('#tipoClasificacionGrid').val() || '';
    $('#gridVehiculos').jqGrid('setGridParam', {
        postData: {
            listVehiculosGridAjax: 1,
            search: search,
            op_opciones: op_opciones,
            tipo_clasificacion: tipo_clasificacion
        },
        page: 1
    }).trigger('reloadGrid');
}

/**
 * Regresa al Ambiente 1 (Listado)
 */
function mostrarListado() {
    // Restaurar título del panel original
    $('.panel-main .panel-heading').html('<span class="glyphicon glyphicon-edit"></span> » Gestión de Operador y Maquinaria');
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
    $('#Maq_Tip').val('GENERADOR');
    $('#tipoClasificacionForm').val('V');
    cambiarTipoClasificacionForm('V');

    $('#divListado').hide();
    $('#divFormulario').fadeIn();

    // Mostrar dinámicamente solo el formulario del tipo seleccionado y cambiar título
    if (tipo === 'chofer') {
        $('.panel-main .panel-heading').html('<span class="glyphicon glyphicon-edit"></span> » Registrar Nuevo Operario');
        $('#divFormTabVehiculo').hide();
        $('#divFormTabChofer').show();
    } else {
        $('.panel-main .panel-heading').html('<span class="glyphicon glyphicon-edit"></span> » Registrar Maquinaria / Vehículo');
        $('#divFormTabChofer').hide();
        $('#divFormTabVehiculo').show();
    }
}

/**
 * Alterna los campos del formulario según la clasificación elegida:
 * 'V' = Maquinaria (Vehículos con placa y proveedor)
 * 'O' = Otros (Equipos / Maquinarias sin placa, con serie obligatoria)
 */
function cambiarTipoClasificacionForm(tipo) {
    if (tipo === 'O') {
        // Modo OTROS (Maquinaria / Equipo)
        $('#legendVehiculo').text('Datos Técnicos del Equipo / Maquinaria');
        $('#lblTipoElemento').html('Tipo Maquinaria:<span class="text-danger">*</span>');
        $('.row-vehiculo-only').hide();
        $('.row-otros-only').show();
        $('#btnGuardarVehiculo').html('<span class="glyphicon glyphicon-floppy-disk"></span> Guardar Maquinaria / Equipo');
    } else {
        // Modo VEHICULO (Transporte con placa)
        $('#legendVehiculo').text('Datos Técnicos del Vehículo');
        $('#lblTipoElemento').html('Tipo Vehículo:<span class="text-danger">*</span>');
        $('.row-vehiculo-only').show();
        $('.row-otros-only').hide();
        $('#btnGuardarVehiculo').html('<span class="glyphicon glyphicon-floppy-disk"></span> Guardar Vehículo');
    }
}

/**
 * Abre el mini modal para agregar tipo según la clasificación activa
 */
function abrirMiniModalTipoDinamico() {
    var clasif = $('#tipoClasificacionForm').val();
    if (clasif === 'O') {
        abrirMiniModal('Maq_Tip', 'Tipo Maquinaria');
    } else {
        abrirMiniModal('Veh_Tit', 'Tipo Vehículo');
    }
}

/**
 * Guarda los datos del Vehículo o de la Maquinaria / Equipo
 */
var isGuardandoVehiculo = false;
function guardarVehiculo() {
    if (isGuardandoVehiculo) return;

    var clasif = $('#tipoClasificacionForm').val();
    var mar = $('#Veh_Mar').val();
    var col = $('#Veh_Col').val();
    var val = $('#Veh_Val').val().trim();
    var adi = $('#Veh_Adi').val().trim();

    if (clasif === 'O') {
        // ==================== GUARDAR OTROS (MAQUINARIA / EQUIPO) ====================
        var ser = $('#Maq_Ser').val().trim().toUpperCase();
        var tip = $('#Maq_Tip').val();
        $('#Maq_Ser').val(ser);

        if (!ser || !tip || !mar || !col) {
            $.alert("Todos los campos marcados con asterisco (*) son obligatorios (Serie, Tipo, Marca, Color).");
            return;
        }

        isGuardandoVehiculo = true;
        $('#loader').show();
        $('#btnGuardarVehiculo').prop('disabled', true);

        var dataSend = {
            saveMaquinariaAjax: 1,
            Maq_Ser: ser,
            Maq_Tip: tip,
            Maq_Mar: mar,
            Maq_Col: col,
            Maq_Adi: adi
        };

        $.post('', dataSend, function (res) {
            $('#loader').hide();
            isGuardandoVehiculo = false;
            $('#btnGuardarVehiculo').prop('disabled', false);
            if (res && res.success) {
                $.alert(res.message || "Maquinaria / Equipo registrado exitosamente.", function() {
                    mostrarListado();
                    reloadGridVehiculos();
                });
            } else {
                $.alert(res.message || "Error al registrar la maquinaria/equipo.");
            }
        }, 'json').fail(function() {
            $('#loader').hide();
            isGuardandoVehiculo = false;
            $('#btnGuardarVehiculo').prop('disabled', false);
            $.alert("Error de comunicación con el servidor.");
        });

    } else {
        // ==================== GUARDAR VEHICULO (CON PLACA Y PROVEEDOR) ====================
        var prv = $('#Prv_Cod').val();
        var pla = $('#Veh_Pla').val().trim().toUpperCase();
        var tit = $('#Veh_Tit').val();
        $('#Veh_Pla').val(pla);

        if (!prv || !pla || !mar || !col || !tit) {
            $.alert("Todos los campos marcados con asterisco (*) son obligatorios, incluido el Proveedor.");
            return;
        }

        if (val !== '') {
            var numVal = parseFloat(val);
            if (isNaN(numVal) || numVal < 0) {
                $.alert("El valor pactado por hora debe ser un número mayor o igual a 0.");
                return;
            }
            var partes = val.split('.');
            if (partes[0].length > 10 || (partes[1] && partes[1].length > 2)) {
                $.alert("El valor pactado por hora no cumple con el formato (máximo 10 enteros y 2 decimales).");
                return;
            }
        }

        // Validar placa (Formato ecuatoriano: AAA-1234 o similar, 7 u 8 caracteres)
        var placaRegex = /^[A-Z]{3}-\d{3,4}$/i;
        if (!placaRegex.test(pla)) {
            $.alert("El formato de la placa no es válido (Ejemplo: ABC-1234 o ABC-123).");
            return;
        }

        isGuardandoVehiculo = true;
        $('#loader').show();
        $('#btnGuardarVehiculo').prop('disabled', true);

        var formData = $('#formVehiculo').serialize();
        formData += '&saveVehiculoAjax=1';

        $.post('', formData, function (res) {
            $('#loader').hide();
            isGuardandoVehiculo = false;
            $('#btnGuardarVehiculo').prop('disabled', false);
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
            isGuardandoVehiculo = false;
            $('#btnGuardarVehiculo').prop('disabled', false);
            $.alert("Error de comunicación con el servidor.");
        });
    }
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
            $('#iconProveedorStatus').html('<i class="glyphicon glyphicon-ok" style="color: green;"></i>');
        } else {
            $('#Prv_Cod').val('');
            $('#Prv_Nom').val('');
            $('#iconProveedorStatus').html('<i class="glyphicon glyphicon-remove" style="color: red;"></i>');
            var msg = res.message || "Proveedor no encontrado. ¿Desea registrarlo ahora?";
            if (typeof $.createDialogConfirm === 'function') {
                $.createDialogConfirm(msg, null, function() {
                    abrirModalProveedor(cedula);
                });
            } else {
                if (confirm(msg)) {
                    abrirModalProveedor(cedula);
                }
            }
        }
    }, 'json').fail(function() {
        $('#loader').hide();
        $.alert("Error de comunicación con el servidor.");
    });
}

function abrirModalProveedor(cedula) {
    var isRuc = false;
    var currentCod = '2'; // Cédula por defecto
    if (cedula && cedula.length === 13) {
        isRuc = true;
        currentCod = '1';
    }

    document.getElementById('formRegistroProveedor').reset();
    $('#Reg_Prs_Ced').val(cedula || '');
    $('#Reg_isRuc').prop('checked', isRuc);
    $('#Reg_Ide_Cod').val(currentCod).trigger('change');
    toggleTiposProveedor('N');

    $('#modalProveedor').modal('show');
}

function abrirMiniModal(selectId, label) {
    $('#tituloMiniModal').text('Agregar ' + label);
    $('#miniModalSelectId').val(selectId);
    $('#miniModalInput').val('');
    $('#modalMiniOpcion').modal('show');
    
    setTimeout(function() {
        $('#miniModalInput').focus();
    }, 500);
}

function guardarMiniModal() {
    var val = $('#miniModalInput').val().trim().toUpperCase();
    var selectId = $('#miniModalSelectId').val();
    
    if (val !== '') {
        var exists = false;
        $('#' + selectId + ' option').each(function() {
            if ($(this).val().toUpperCase() === val) {
                exists = true;
                return false;
            }
        });
        
        if (!exists) {
            $('#' + selectId).append($('<option>', {
                value: val,
                text: val
            }));
        }
        
        $('#' + selectId).val(val);
    }
    
    $('#modalMiniOpcion').modal('hide');
}

function toggleTiposProveedor(tipo) {
    if (tipo === 'J') {
        $('.reg_natural').hide();
        $('.reg_juridico').show();
    } else {
        $('.reg_natural').show();
        $('.reg_juridico').hide();
    }
}

var guardandoProveedor = false;
function guardarProveedorRapido() {
    if (guardandoProveedor) return;

    var form = document.getElementById('formRegistroProveedor');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Validar identificación usando la librería del framework
    var cedula = $('#Reg_Prs_Ced').val().trim();
    if (typeof validaNoIdentif === 'function') {
        var resVal = validaNoIdentif(cedula);
        if (!resVal.success) {
            $.alert(resVal.message || "La identificación ingresada no es válida.");
            return;
        }
    } else {
        if (cedula.length < 10 || cedula.length > 13) {
            $.alert("La identificación debe tener entre 10 y 13 dígitos.");
            return;
        }
    }

    var formData = new FormData(form);
    formData.append('saveProveedorRapidoAjax', 1);

    guardandoProveedor = true;
    $("#loader").show();
    $('#modalProveedor .btn-primary').prop('disabled', true);

    $.ajax({
        url: '',
        type: 'POST',
        dataType: 'json',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            $("#loader").hide();
            guardandoProveedor = false;
            $('#modalProveedor .btn-primary').prop('disabled', false);

            if (response && response.success) {
                $('#modalProveedor').modal('hide');
                
                $('#Prv_Ced').val(response.Prs_Ced);
                $('#Prv_Cod').val(response.Prv_Cod);
                $('#Prv_Nom').val(response.Prv_Nom);
                $('#iconProveedorStatus').html('<i class="glyphicon glyphicon-ok" style="color: green;"></i>');
            } else {
                $.alert(response.message || "Ocurrió un error al registrar el proveedor.");
            }
        },
        error: function () {
            $("#loader").hide();
            guardandoProveedor = false;
            $('#modalProveedor .btn-primary').prop('disabled', false);
            $.alert('Error de conexión al servidor.');
        }
    });
}

/**
 * Genera una placa provisional en el formato XXX-0000
 */
function generarPlacaProvisional() {
    var numeros = '0123456789';
    var placa = 'XXX-';
    for (var i = 0; i < 4; i++) {
        placa += numeros.charAt(Math.floor(Math.random() * numeros.length));
    }
    // Almacenar y forzar el evento blur por si hay un autocompletar adjunto
    $('#Veh_Pla').val(placa).trigger('blur');
}

/**
 * Carga un operario/chofer para su edición
 * @param {string|number} choCod
 * @param {string} cedula
 */
function editarChofer(choCod, cedula) {
    mostrarFormulario('chofer');
    $('.panel-main .panel-heading').html('<span class="glyphicon glyphicon-edit"></span> » Editar Operario');
    if (cedula) {
        $('#Cho_Ced').val(cedula);
        buscarPersonaPorCedula(cedula);
    }
}

/**
 * Carga un vehículo o maquinaria para su edición
 * @param {string|number} rowId
 * @param {string} clasif 'V' o 'O'
 * @param {string} identificador Placa o Serie
 */
function editarVehiculo(rowId, clasif, identificador) {
    mostrarFormulario('vehiculo');
    if (clasif === 'O' || String(rowId).indexOf('M_') === 0) {
        $('#tipoClasificacionForm').val('O');
        cambiarTipoClasificacionForm('O');
        $('.panel-main .panel-heading').html('<span class="glyphicon glyphicon-edit"></span> » Editar Maquinaria / Equipo');
        if (identificador) {
            $('#Maq_Ser').val(identificador);
            buscarMaquinariaPorSerie(identificador);
        }
    } else {
        $('#tipoClasificacionForm').val('V');
        cambiarTipoClasificacionForm('V');
        $('.panel-main .panel-heading').html('<span class="glyphicon glyphicon-edit"></span> » Editar Vehículo');
        if (identificador) {
            $('#Veh_Pla').val(identificador);
            buscarVehiculoPorPlaca(identificador);
        }
    }
}

/**
 * Inactiva un chofer / operario a voluntad del usuario
 * @param {string|number} choCod
 */
function inactivarChofer(choCod) {
    if (!choCod) return;
    
    var msgConfirm = "¿Está seguro de que desea inactivar este operario?";
    var ejecutarInactivar = function() {
        $('#loader').show();
        $.post('', { inactivarChoferAjax: 1, Cho_Cod: choCod }, function(res) {
            $('#loader').hide();
            if (res && res.success) {
                $.alert(res.message || "Operario inactivado correctamente.", function() {
                    reloadGridChoferes();
                });
            } else {
                $.alert(res.message || "No se pudo inactivar el operario.");
            }
        }, 'json').fail(function() {
            $('#loader').hide();
            $.alert("Error de comunicación con el servidor.");
        });
    };

    if (typeof $.createDialogConfirm === 'function') {
        $.createDialogConfirm(msgConfirm, null, ejecutarInactivar);
    } else {
        if (confirm(msgConfirm)) {
            ejecutarInactivar();
        }
    }
}

/**
 * Inactiva un vehículo o maquinaria a voluntad del usuario
 * @param {string|number} rowId
 * @param {string} clasif 'V' o 'O'
 */
function inactivarVehiculo(rowId, clasif) {
    if (!rowId) return;

    var tipoDesc = (clasif === 'O' || String(rowId).indexOf('M_') === 0) ? 'esta maquinaria / equipo' : 'este vehículo';
    var msgConfirm = "¿Está seguro de que desea inactivar " + tipoDesc + "?";

    var ejecutarInactivar = function() {
        $('#loader').show();
        $.post('', { inactivarVehiculoAjax: 1, rowId: rowId, clasif: clasif }, function(res) {
            $('#loader').hide();
            if (res && res.success) {
                $.alert(res.message || "Registro inactivado correctamente.", function() {
                    reloadGridVehiculos();
                });
            } else {
                $.alert(res.message || "No se pudo inactivar el registro.");
            }
        }, 'json').fail(function() {
            $('#loader').hide();
            $.alert("Error de comunicación con el servidor.");
        });
    };

    if (typeof $.createDialogConfirm === 'function') {
        $.createDialogConfirm(msgConfirm, null, ejecutarInactivar);
    } else {
        if (confirm(msgConfirm)) {
            ejecutarInactivar();
        }
    }
}
