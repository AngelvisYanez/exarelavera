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

    // Controlar ingreso estricto de fecha de caducidad (dd/mm/aaaa) limitando el año a 4 dígitos
    $('#Cho_Cli').attr('maxlength', '10');
    $('#Cho_Cli').on('input', function () {
        if ($('#Cho_Tli').val() === 'Np') return;
        var val = $(this).val().replace(/[^0-9\/]/g, '');
        var parts = val.split('/');
        if (parts.length > 0 && parts[0].length > 2) parts[0] = parts[0].substring(0, 2);
        if (parts.length > 1 && parts[1].length > 2) parts[1] = parts[1].substring(0, 2);
        if (parts.length > 2 && parts[2].length > 4) parts[2] = parts[2].substring(0, 4);
        if (parts.length > 3) parts = parts.slice(0, 3);
        $(this).val(parts.join('/'));
    });

    $('#Cho_Cli').on('blur', function () {
        var val = $(this).val().trim();
        if (!val || $('#Cho_Tli').val() === 'Np' || val === '00/00/0000') return;
        var parts = val.split('/');
        if (parts.length === 3) {
            if (parts[2].length !== 4) {
                $.alert("El año en la fecha de caducidad debe tener exactamente 4 dígitos (ej: 2026).");
                return;
            }
            var d = parseInt(parts[0], 10);
            var m = parseInt(parts[1], 10);
            var y = parseInt(parts[2], 10);
            if (isNaN(d) || isNaN(m) || isNaN(y) || m < 1 || m > 12 || d < 1 || d > 31 || y < 1900 || y > 2099) {
                $.alert("La fecha de caducidad de la licencia no es válida.");
                return;
            }
        }
    });

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

    // Detectar eventos en cédula de operario para autocompletar y actualizar icono de estado
    $('#Cho_Ced').on('blur', function() {
        var cedula = $(this).val().trim();
        if (cedula.length > 0) {
            buscarPersonaPorCedula(cedula, $('#Cho_Cod').val());
        } else {
            setChoferStatusIcon('neutral');
        }
    });

    $('#Cho_Ced').on('keypress', function(e) {
        if (e.which === 13) { // Enter
            e.preventDefault();
            var cedula = $(this).val().trim();
            if (cedula.length > 0) {
                buscarPersonaPorCedula(cedula, $('#Cho_Cod').val());
            }
        }
    });

    $('#Cho_Ced').on('input', function() {
        var val = $(this).val().trim();
        setChoferStatusIcon('neutral');
        if (val.length === 0) {
            $('#Cho_Cod').val('');
            $('#Prs_Nom').val('');
            $('#Prs_Ape').val('');
            $('#Cho_Tel').val('');
            $('#Cho_Tli').val('');
            $('#Cho_Cli').val('');
            $('#Cho_Tsa').val('');
            evaluarTipoLicencia();
        }
    });

    // Detectar cambio en Tipo de Licencia
    $('#Cho_Tli').on('change', function() {
        evaluarTipoLicencia();
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
 * Actualiza el icono y tooltip del estado de validación de la cédula del operario
 * @param {string} type 'loading' | 'found' | 'new' | 'invalid' | 'neutral'
 * @param {string} [tooltipText]
 */
function setChoferStatusIcon(type, tooltipText) {
    var $iconSpan = $('#iconChoferStatus');
    if (!$iconSpan.length) return;

    // Eliminar cualquier tooltip flotante residual de jQuery UI o Bootstrap para evitar que se quede pegado
    $('.ui-tooltip, .tooltip').remove();

    var iconHtml = '';
    var defaultTooltip = '';

    switch (type) {
        case 'loading':
            iconHtml = '<i class="fa fa-spinner fa-spin" style="color: #337ab7; font-size: 14px;"></i>';
            defaultTooltip = 'Consultando...';
            break;
        case 'found':
            iconHtml = '<i class="glyphicon glyphicon-ok" style="color: #5cb85c; font-size: 14px;"></i>';
            defaultTooltip = 'Operador registrado en persona';
            break;
        case 'new':
            iconHtml = '<i class="glyphicon glyphicon-warning-sign" style="color: #f0ad4e; font-size: 14px;"></i>';
            defaultTooltip = 'Este operador es nuevo';
            break;
        case 'invalid':
            iconHtml = '<i class="glyphicon glyphicon-remove" style="color: #d9534f; font-size: 14px;"></i>';
            defaultTooltip = 'Credencial no válida';
            break;
        case 'neutral':
        default:
            iconHtml = '<i class="glyphicon glyphicon-minus" style="color: #999; font-size: 14px;"></i>';
            defaultTooltip = '';
            break;
    }

    var text = tooltipText !== undefined ? tooltipText : defaultTooltip;
    $iconSpan.html(iconHtml);

    // Usar tooltip flotante CSS (data-tooltip) que desaparece de inmediato en hover-out sin quedarse pegado
    if (text) {
        $iconSpan.attr('data-tooltip', text);
    } else {
        $iconSpan.removeAttr('data-tooltip');
    }

    // Evitar que el tooltip nativo del navegador se solape o se duplique
    $iconSpan.removeAttr('title').removeAttr('data-original-title');
}

/**
 * Evalúa el tipo de licencia seleccionado.
 * Si es 'Np' (NO POSEE), coloca '00/00/0000' en caducidad y bloquea el campo.
 * Si es cualquier otra licencia, desbloquea el campo para su ingreso.
 */
function evaluarTipoLicencia() {
    var tli = $('#Cho_Tli').val();
    var $cli = $('#Cho_Cli');

    if (tli === 'Np') {
        $cli.val('00/00/0000')
            .prop('readonly', true)
            .css({
                'background-color': '#eee',
                'cursor': 'not-allowed',
                'pointer-events': 'none'
            });
        try {
            if ($cli.data('datepicker')) {
                $cli.datepicker('disable');
            }
        } catch (e) {}
    } else {
        if ($cli.val() === '00/00/0000') {
            $cli.val('');
        }
        $cli.prop('readonly', false)
            .css({
                'background-color': '#fff',
                'cursor': 'text',
                'pointer-events': 'auto'
            });
        try {
            if ($cli.data('datepicker')) {
                $cli.datepicker('enable');
            }
        } catch (e) {}
    }
}

/**
 * Busca si una persona existe por su número de identificación y autocompleta el formulario
 * @param {string} cedula 
 * @param {string|number} [choCod]
 */
function buscarPersonaPorCedula(cedula, choCod) {
    cedula = (cedula || '').trim();
    if (!cedula) {
        setChoferStatusIcon('neutral');
        return;
    }

    // 1. Validar formato de la cédula o RUC
    if (typeof validaNoIdentif === 'function') {
        var resVal = validaNoIdentif(cedula);
        if (!resVal.success) {
            setChoferStatusIcon('invalid', 'Credencial no válida');
            return;
        }
    } else if (cedula.length < 10 || cedula.length > 13) {
        setChoferStatusIcon('invalid', 'Credencial no válida');
        return;
    }

    // 2. Icono de cargando al momento de consultar
    setChoferStatusIcon('loading', 'Consultando...');

    var params = { buscarPersonaPorCedulaAjax: 1, cedula: cedula };
    if (choCod) {
        params.cho_cod = choCod;
    }

    $.getJSON('', params, function (res) {
        // Evitar sobreescritura si el usuario ya modificó el input
        if ($('#Cho_Ced').val().trim() !== cedula) return;

        if (res && res.exists) {
            // 3. Encontrado -> Check verde
            setChoferStatusIcon('found', 'Operador registrado en persona');
            $('#Prs_Nom').val(res.Prs_Nom || '');
            $('#Prs_Ape').val(res.Prs_Ape || '');
            $('#Cho_Tel').val(res.Prs_Tel || '');
            
            if (res.Prs_San) {
                var tsa = res.Prs_San.toUpperCase().trim();
                $('#Cho_Tsa').val(tsa);
            }
            
            if (res.Cho_Cod) {
                $('#Cho_Cod').val(res.Cho_Cod);
            }
            
            if (res.isChofer) {
                $('#Cho_Tli').val(res.Cho_Tli || '');
                $('#Cho_Cli').val(res.Cho_Cli || '');
                evaluarTipoLicencia();
            } else {
                $('#Cho_Tli').val('');
                $('#Cho_Cli').val('');
                evaluarTipoLicencia();
            }
        } else {
            // 4. Si es nuevo y no está en persona -> Signo de advertencia con tooltip
            setChoferStatusIcon('new', 'Este operador es nuevo');
            $('#Prs_Nom').val('');
            $('#Prs_Ape').val('');
            $('#Cho_Tel').val('');
            $('#Cho_Tli').val('');
            $('#Cho_Cli').val('');
            $('#Cho_Tsa').val('');
            evaluarTipoLicencia();
        }
    }).fail(function() {
        console.error("Error al buscar persona por cédula o RUC.");
        setChoferStatusIcon('invalid', 'Credencial no válida');
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
            { label: 'Cód. Int.', name: 'Cho_Cod', key: true, width: 65, align: 'center' },
            { label: 'Cédula', name: 'Prs_Ced', width: 100, align: 'center' },
            { label: 'Nombre', name: 'nombre', width: 220 },
            { label: 'Licencia', name: 'Cho_Tli', width: 95, align: 'center', formatter: function(v) {
                if (!v) return '-';
                var valUpper = String(v).trim().toUpperCase();
                if (valUpper === 'NP' || valUpper === 'NO POSEE') {
                    return 'No Posee';
                }
                return 'Tipo: ' + String(v).trim();
            }},
            { label: 'Caducidad', name: 'Cho_Cli', width: 100, align: 'center', formatter: function(v, o, r) {
                var tli = r && r.Cho_Tli ? String(r.Cho_Tli).trim().toUpperCase() : '';
                if (tli === 'NP' || tli === 'NO POSEE' || !v || v === '0000-00-00' || v === '1900-01-01' || v === '00/00/0000') {
                    return '-';
                }
                // Formatear fecha de YYYY-MM-DD a dd/mm/aaaa
                var cleanDate = String(v).trim().split(' ')[0];
                var parts = cleanDate.split('-');
                if (parts.length === 3) {
                    return parts[2] + '/' + parts[1] + '/' + parts[0];
                }
                return cleanDate;
            }},
            { label: 'Acciones', name: 'acciones', width: 90, align: 'center', sortable: false, formatter: function(cellvalue, options, rowObject) {
                var choCod = rowObject.Cho_Cod || options.rowId;
                var cedula = rowObject.Prs_Ced || '';
                return '<button type="button" class="btn btn-xs btn-primary" onclick="editarChofer(\'' + choCod + '\', \'' + cedula + '\')" title="Editar Operario" style="margin-right:5px;"><i class="glyphicon glyphicon-pencil"></i></button>' +
                       '<button type="button" class="btn btn-xs btn-danger" onclick="inactivarChofer(\'' + choCod + '\')" title="Inactivar Operario"><i class="glyphicon glyphicon-trash"></i></button>';
            }}
        ],
        viewrecords: true,
        jsonReader: { root: "rows", page: "page", total: "total", records: "records", repeatitems: false },
        loadComplete: function() {
            customizarTextoVerTodos('#pagerChoferes');
        }
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
            { label: 'Cód. Int.', name: 'Cod_Int', width: 65, align: 'center' },
            { label: 'Clasificación', name: 'Clasificacion', width: 95, align: 'center', formatter: function(v) {
                if (v === 'V') return '<span class="label label-primary" style="font-size: 11px;">Vehículo</span>';
                if (v === 'O') return '<span class="label label-warning" style="font-size: 11px;">Otro (Equipo)</span>';
                return v || '';
            }},
            { label: 'Empresa / Proveedor', name: 'empresa_transporte', width: 180 },
            { label: 'Placa / Serie', name: 'Ide_Pla_Ser', width: 100, align: 'center' },
            { label: 'Marca', name: 'Veh_Mar', width: 110 },
            { label: 'Color', name: 'Veh_Col', width: 80, align: 'center' },
            { label: 'Tipo', name: 'Veh_Tit', width: 110, align: 'center', formatter: function(v) {
                if (v === 'V') return 'Volqueta';
                if (v === 'B') return 'Bus(eta)';
                if (v === 'C') return 'Camioneta';
                if (v === 'T') return 'Tráiler';
                if (v === 'M') return 'Maquinaria';
                return v || '';
            }},
            { label: 'Descripción Adicional', name: 'Veh_Adi', width: 140, formatter: function(v) {
                return v ? $('<div>').text(v).html() : '-';
            }},
            { label: 'Valor Hora', name: 'Veh_Val', width: 85, align: 'right', formatter: function(v, options, rowObject) {
                if (rowObject && (rowObject.Clasificacion === 'O' || String(rowObject.Row_Id).indexOf('M_') === 0)) {
                    return '-';
                }
                if (v === null || v === undefined || v === '') return '-';
                var n = parseFloat(v);
                return isNaN(n) ? '-' : n.toFixed(2);
            }},
            { label: 'Acciones', name: 'acciones', width: 90, align: 'center', sortable: false, formatter: function(cellvalue, options, rowObject) {
                var rowId = rowObject.Row_Id || options.rowId;
                var clasif = rowObject.Clasificacion || (String(rowId).indexOf('M_') === 0 ? 'O' : 'V');
                var identificador = rowObject.Ide_Pla_Ser || '';
                return '<button type="button" class="btn btn-xs btn-primary" onclick="editarVehiculo(\'' + rowId + '\', \'' + clasif + '\', \'' + identificador + '\')" title="Editar" style="margin-right:5px;"><i class="glyphicon glyphicon-pencil"></i></button>' +
                       '<button type="button" class="btn btn-xs btn-danger" onclick="inactivarVehiculo(\'' + rowId + '\', \'' + clasif + '\')" title="Inactivar"><i class="glyphicon glyphicon-trash"></i></button>';
            }}
        ],
        viewrecords: true,
        jsonReader: { root: "rows", page: "page", total: "total", records: "records", repeatitems: false },
        loadComplete: function() {
            customizarTextoVerTodos('#pagerVehiculos');
        }
    }, false, '#pagerVehiculos', { refresh: true, view: false });

    // Personalizar opción -1 inmediatamente al crear los grids
    customizarTextoVerTodos('#pagerChoferes');
    customizarTextoVerTodos('#pagerVehiculos');

    // Botones de exportar a Excel en barra de paginación
    try {
        $('#gridChoferes').navButtonAdd('#pagerChoferes', {
            caption: "Exportar Excel",
            buttonicon: "glyphicon glyphicon-download-alt",
            onClickButton: exportarExcelChoferes,
            position: "last",
            title: "Exportar a Excel"
        });
        $('#gridVehiculos').navButtonAdd('#pagerVehiculos', {
            caption: "Exportar Excel",
            buttonicon: "glyphicon glyphicon-download-alt",
            onClickButton: exportarExcelVehiculos,
            position: "last",
            title: "Exportar a Excel"
        });
    } catch(e) {}
}

var isExportandoExcel = false;
var timerCheckDescargaExcel = null;

function getCookieDescarga(name) {
    var parts = document.cookie.split(';');
    for (var i = 0; i < parts.length; i++) {
        var p = parts[i].trim();
        if (p.indexOf(name + '=') === 0) {
            return decodeURIComponent(p.substring(name.length + 1));
        }
    }
    return null;
}

function limpiarCookieDescarga(name) {
    document.cookie = name + '=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT;';
}

function iniciarDescargaExcel(urlBase) {
    if (isExportandoExcel) return;
    isExportandoExcel = true;

    // Deshabilitar botones de exportar y mostrar loader del sistema
    $('.btn-exportar-excel').prop('disabled', true).addClass('disabled');
    $('#pagerChoferes, #pagerVehiculos').find('.glyphicon-download-alt').closest('td').addClass('ui-state-disabled');
    $('#loader').show();

    var token = 'dl_' + new Date().getTime();
    limpiarCookieDescarga('fileDownloadToken');

    var urlFinal = urlBase + '&fileDownloadToken=' + encodeURIComponent(token);

    // Asignar al iframe oculto para disparar la descarga en segundo plano
    var $iframe = $('#iframeDownloadExcel');
    if (!$iframe.length) {
        $iframe = $('<iframe id="iframeDownloadExcel" style="display:none; width:0; height:0; border:0;"></iframe>').appendTo('body');
    }
    $iframe.attr('src', urlFinal);

    // Chequear periódicamente la cookie de finalización enviada por el servidor
    if (timerCheckDescargaExcel) clearInterval(timerCheckDescargaExcel);
    timerCheckDescargaExcel = setInterval(function() {
        var cookieVal = getCookieDescarga('fileDownloadToken');
        if (cookieVal === token) {
            finalizarDescargaExcel();
        }
    }, 200);

    // Timeout de seguridad (15 segundos) para no dejar bloqueada la UI si hay problemas de red
    setTimeout(function() {
        if (isExportandoExcel) {
            finalizarDescargaExcel();
        }
    }, 15000);
}

function finalizarDescargaExcel() {
    if (timerCheckDescargaExcel) {
        clearInterval(timerCheckDescargaExcel);
        timerCheckDescargaExcel = null;
    }
    limpiarCookieDescarga('fileDownloadToken');
    $('#loader').fadeOut('slow');
    isExportandoExcel = false;
    $('.btn-exportar-excel').prop('disabled', false).removeClass('disabled');
    $('#pagerChoferes, #pagerVehiculos').find('.glyphicon-download-alt').closest('td').removeClass('ui-state-disabled');
}

/**
 * Exporta el listado de Operarios a Excel según los filtros activos
 */
function exportarExcelChoferes() {
    if (isExportandoExcel) return;
    var search = $('#searchChofer').val().trim();
    var op_opciones = $('#opChofer').val() || 'd';
    var url = 'man_alt_vehiculos_choferes.php?exportarExcelChoferesAjax=1' +
              '&search=' + encodeURIComponent(search) +
              '&op_opciones=' + encodeURIComponent(op_opciones);
    iniciarDescargaExcel(url);
}

/**
 * Exporta el listado de Maquinaria y Vehículos a Excel según los filtros activos
 */
function exportarExcelVehiculos() {
    if (isExportandoExcel) return;
    var search = $('#searchVehiculo').val().trim();
    var op_opciones = $('#opVehiculo').val() || 'p';
    var tipo_clasificacion = $('#tipoClasificacionGrid').val() || '';
    var url = 'man_alt_vehiculos_choferes.php?exportarExcelVehiculosAjax=1' +
              '&search=' + encodeURIComponent(search) +
              '&op_opciones=' + encodeURIComponent(op_opciones) +
              '&tipo_clasificacion=' + encodeURIComponent(tipo_clasificacion);
    iniciarDescargaExcel(url);
}

/**
 * Personaliza el texto de la opción -1 en el selector de paginación para mostrar "Todos"
 * @param {string} pagerId Selector del paginador
 */
function customizarTextoVerTodos(pagerId) {
    var $select = $(pagerId).find('.ui-pg-selbox');
    if (!$select.length) return;
    $select.find('option[value="-1"]').text('Todos');
}

/**
 * Cambia el filtro de clasificación (Todos, Vehículos, Otros) en el Grid
 * @param {string} clasif '' | 'V' | 'O'
 */
function cambiarFiltroClasificacion(clasif) {
    if (clasif !== undefined) {
        $('#tipoClasificacionGrid').val(clasif);
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
    $('#Cho_Cod').val('');
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
        setChoferStatusIcon('neutral');
        evaluarTipoLicencia();
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
 * Guarda los datos del Operario / Chofer
 */
var isGuardandoChofer = false;
function guardarChofer() {
    if (isGuardandoChofer) return;

    var ced = $('#Cho_Ced').val().trim();
    var tel = $('#Cho_Tel').val().trim();
    var nom = $('#Prs_Nom').val().trim();
    var ape = $('#Prs_Ape').val().trim();
    var tli = $('#Cho_Tli').val();
    var cli = $('#Cho_Cli').val().trim();
    var tsa = $('#Cho_Tsa').val();

    if (!ced || !tel || !nom || !ape || !tli || !tsa) {
        $.alert("Todos los campos marcados con asterisco (*) son obligatorios.");
        return;
    }

    if (tli !== 'Np') {
        if (!cli || cli === '00/00/0000') {
            $.alert("Debe ingresar una fecha de caducidad válida para la licencia seleccionada.");
            return;
        }

        var regexFecha = /^(\d{2})\/(\d{2})\/(\d{4})$/;
        var match = cli.match(regexFecha);
        if (!match) {
            $.alert("El año de la fecha de caducidad debe tener exactamente 4 dígitos en formato dd/mm/aaaa.");
            return;
        }

        var dia = parseInt(match[1], 10);
        var mes = parseInt(match[2], 10);
        var anio = parseInt(match[3], 10);

        if (anio < 1900 || anio > 2099 || mes < 1 || mes > 12 || dia < 1 || dia > 31) {
            $.alert("La fecha de caducidad ingresada no es válida.");
            return;
        }
    }

    if (typeof validaNoIdentif === 'function') {
        var resVal = validaNoIdentif(ced);
        if (!resVal.success) {
            setChoferStatusIcon('invalid', 'Credencial no válida');
            $.alert(resVal.message || "La cédula o RUC ingresado no es válido.");
            return;
        }
    } else if (ced.length < 10 || ced.length > 13) {
        setChoferStatusIcon('invalid', 'Credencial no válida');
        $.alert("La cédula o RUC debe tener entre 10 y 13 dígitos.");
        return;
    }

    isGuardandoChofer = true;
    $('#loader').show();
    $('#btnGuardarChofer').prop('disabled', true);

    var formData = $('#formChofer').serialize();
    formData += '&saveChoferAjax=1';

    $.post('', formData, function (res) {
        $('#loader').hide();
        isGuardandoChofer = false;
        $('#btnGuardarChofer').prop('disabled', false);
        if (res && res.success) {
            $.alert(res.message || "Operador registrado exitosamente.", function() {
                setChoferStatusIcon('neutral');
                mostrarListado();
                reloadGridChoferes();
            });
        } else {
            $.alert(res.message || "Error al registrar el operador.");
        }
    }, 'json').fail(function() {
        $('#loader').hide();
        isGuardandoChofer = false;
        $('#btnGuardarChofer').prop('disabled', false);
        $.alert("Error de comunicación con el servidor.");
    });
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
    $('#Cho_Cod').val(choCod || '');
    if (cedula) {
        $('#Cho_Ced').val(cedula);
        buscarPersonaPorCedula(cedula, choCod);
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
