
function selectCliente(cliente) {
    var reset = ($('#reset').val() !== '0');
    $('#plantaForm').setData($.extend(cliente, {
        op_opciones: 'c'
    }), 'name').find('.dialogSearch').addClass('x');
    $('#cliente').val(cliente.Cliente);
    // Actualizar la variable global Cli_Cod
    Cli_Cod = cliente.Cli_Cod;
    // Actualizar el texto del cliente en el encabezado
    $('#nombreClienteHeader').text(cliente.Cliente || 'N/A');
    $('#codigoClienteHeader').text(cliente.Cli_Cod || '');
    // Los grids de plantas muestran todas las plantas de la empresa (no filtran por cliente)
    // Solo actualizar choferes y vehículos si necesitan filtrar por cliente
    $('#cliDialog').dialog('close');
}


$(function () {
    // console.log("DOM Ready - Iniciando configuración");

    ///alert("DOM Ready - Iniciando configuración");

    //DIALOG BUSCAR CLIENTE
    $('#cliDialog').createSearchDialog({
        colModel: [{
            label: 'C&oacute;d.Int.',
            name: 'Cli_Cod',
            key: true,
            width: 15,
            align: "center",
            hidden: true
        },
        {
            label: 'Cédula/RUC',
            name: 'Prs_Ced',
            width: 50
        },
        {
            label: 'Cliente',
            name: 'Cliente',
            width: 90
        },
        {
            label: 'Dir.',
            name: 'Prs_Dir',
            width: 50,
            align: "center"
        },
        {
            label: '&nbsp;',
            name: 'act1',
            width: 20,
            align: 'center',
            viewable: false,
            formatter: 'gridButton',
            formatoptions: {
                action: selectCliente
            }
        }
        ]
    }, {
        title: 'cliente'
    });
    $('#sancionVeh_Msa_Cho').on('input', function () {
        this.value = this.value.toUpperCase();
    });




    // Inicializar diálogos
    $("#plantaDialog").createDialog({
        width: 600,
        height: 490,
        icon: 'home'
    });

    $("#clientePlantaDialog").createDialog({
        width: 700,
        height: 500,
        icon: 'search'
    });
    $("#empresaTransporteDialog").createDialog({
        width: 550,
        height: 400,
        icon: 'truck'
    });
    $("#choferDialog").createDialog({
        width: 550,
        height: 420,
        icon: 'user'
    });
    $("#vehiculoDialog").createDialog({
        width: 450,
        height: 350,
        icon: 'road'
    });
    $("#celdaDialog").createDialog({
        width: 450,
        height: 280,
        icon: 'th-large'
    });
    $("#sancionUnificadaDialog").createDialog({
        width: 520,
        height: 420,
        icon: 'ban-circle'
    });

    $("#nuevoTipoSancionDialog").createDialog({
        width: 420,
        height: 200,
        icon: 'plus-sign'
    });

    $("#eventosDialog").createDialog({
        width: 820,
        height: 600,
        icon: 'calendar'
    });

    // Actualizar nivel de tipo de sanción cuando cambia el select
    $('#Tsa_Cod').on('change', function () {
        var nivel = $(this).find('option:selected').data('nivel');
        $('#nivel_tipo_sancion').text(nivel || '');
    });

    // Botón "+" para abrir modal de nuevo tipo de sanción
    window.abrirNuevoTipoSancion = function () {
        $('#nuevoTipoSancionForm')[0].reset();
        var emp = $('#nuevoEmp_Cod').val();
        if (!emp) {
            $('#nuevoEmp_Cod').val($('#Emp_Cod').val() || '');
        }
        $('#nuevoTipoSancionDialog').dialog('open');
    };

    // Guardar nuevo tipo de sanción vía AJAX
    window.guardarNuevoTipoSancion = function () {
        var des = $.trim($('#nuevoTsa_Des').val());
        var niv = $.trim($('#nuevoTsa_Niv').val());
        if (!des) {
            $.alert('Ingrese la descripción del tipo de sanción.');
            return;
        }
        if (!niv) {
            $.alert('Seleccione el nivel de riesgo (M, A o B).');
            return;
        }
        $.post('', {
            saveNuevoTipoSancionAjax: true,
            Tsa_Des: des,
            Tsa_Niv: niv
        }, function (r) {
            if (r && r.success) {
                $('#nuevoTipoSancionDialog').dialog('close');

                // 1) Agregar inmediatamente al select Tsa_Cod del modal de sanción
                var $sel = $('#Tsa_Cod');
                if ($sel.length && r.Tsa_Cod) {
                    // Crear opción si no existe
                    if ($sel.find('option[value="' + r.Tsa_Cod + '"]').length === 0) {
                        var $opt = $('<option></option>')
                            .attr('value', r.Tsa_Cod)
                            .attr('data-nivel', r.Tsa_Niv || '')
                            .text(r.Tsa_Des || des);
                        $sel.append($opt);
                    }
                    // Seleccionar el nuevo tipo y disparar cambio para actualizar el nivel
                    $sel.val(r.Tsa_Cod).trigger('change');
                }

                // 2) Actualizar también el catálogo general si aplica
                var pre = r.Tsa_Cod || '';
                cargarSelectTiposSancionLista(pre);
            } else {
                $.alert((r && r.message) || 'Error al guardar el tipo de sanción.');
            }
        }, 'json').fail(function () {
            $.alert('Error al guardar el tipo de sanción.');
        });
    };

    // Diálogo para mostrar QR del vehículo - sin botones
    $("#qrVehiculoDialog").dialog({
        autoOpen: false,
        width: 420,
        height: 'auto',
        modal: true,
        closeOnEscape: true
    });

    // Inicializar datepickers
    $('.datepicker').createDatePickers({
        checkAvailability: true,
        hideMsg: false
    });

    // Inicializar Chosen
    $('.chosen-select').chosen({
        width: '100%',
        no_results_text: 'No se encontró: '
    });

    // Crear grids
    // console.log("Antes de llamar createGridPlantas");
    createGridPlantas();
    // console.log("Después de llamar createGridPlantas");
    createGridEmpresasTransporte();
    createGridChoferes();
    createGridVehiculos();
    createGridCeldas();
    createGridSanciones();

    // Diálogos de búsqueda para Sanciones
    initSearchDialogsSanciones();

    cargarGeneralManifiesto();
    $('a[href="#tabGeneral"]').on('shown.bs.tab', function () {
        cargarGeneralManifiesto();
    });

    // Función para ajustar el ancho de todos los grids
    function ajustarAnchoGrids() {
        var grids = [
            { id: '#gridPlantas', tab: '#tabPlantas' },
            { id: '#gridEmpresasTransporte', tab: '#tabEmpresasTransporte' },
            { id: '#gridChoferes', tab: '#tabChoferes' },
            { id: '#gridVehiculos', tab: '#tabVehiculos' },
            { id: '#gridCeldas', tab: '#tabCeldas' },
            { id: '#gridSanciones', tab: '#tabSanciones' }
        ];

        grids.forEach(function (gridInfo) {
            var grid = $(gridInfo.id);
            var gridParam = grid.jqGrid('getGridParam');
            if (gridParam && gridParam.url !== undefined) {
                var tabWidth = $(gridInfo.tab).width();
                if (tabWidth > 0) {
                    grid.jqGrid('setGridWidth', tabWidth);
                }
            }
        });
    }

    // Evento de cambio de tab - ajustar grids
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href"); // activated tab
        setTimeout(function () {
            ajustarAnchoGrids();
        }, 100);
    });

    // Evento de resize de ventana - ajustar grids
    $(window).on('resize', function () {
        clearTimeout(window.resizeTimer);
        window.resizeTimer = setTimeout(function () {
            ajustarAnchoGrids();
        }, 250);
    });
});







// ==================== VALIDACIÓN DE IDENTIFICACIÓN ====================
// Valida cédula o RUC
function validaNoIdentif(number) {
    var digitos = number.split(''),
        dto = digitos.length,
        acu = 0,
        resp = {
            success: false,
            message: ''
        },
        coef = {
            'NA': [2, 1, 2, 1, 2, 1, 2, 1, 2],
            'PU': [3, 2, 7, 6, 5, 4, 3, 2, 0],
            'PR': [4, 3, 2, 7, 6, 5, 4, 3, 2]
        },
        modulo, acum = 0;
    if (dto === 0) resp['message'] = 'No has ingresado ningún dato!';
    else {
        for (var i = 0; i < dto; i++)
            if (!isNaN(digitos[i])) {
                digitos[i] = digitos[i] * 1;
                acu = acu + 1;
            }
        if (acu === dto) {
            var tipo = digitos[2];
            if (tipo === 7 || tipo === 8) resp['message'] = 'El tercer dígito ingresado es inválido';
            else {
                tipo = (tipo <= 5 ? 'NA' : (tipo === 6 ? 'PU' : (tipo === 9 ? 'PR' : '')));
                modulo = (tipo === 'NA' ? 10 : 11);
                resp['tipo_abrev'] = tipo;
                resp['tipo'] = (tipo === 'NA' ? 'Natural' : (tipo === 'PR' ? 'Privada' : (tipo === 'PU' ? 'Pública' : '')));
            }
            if (dto !== 10 && dto !== 13) {
                resp['message'] = 'La cantidad de dígitos deben ser 10 o 13';
                return resp;
            } else {
                resp['doc_abr'] = (dto === 10 ? 'C' : (dto === 13 ? 'R' : ''));
                resp['doc'] = (dto === 10 ? 'Cédula' : (dto === 13 ? 'R.U.C.' : ''));
            }
            if (number.substring(0, 2) * 1 > 24) resp['message'] = 'Los dos primeros dígitos no pueden ser mayores a 24.';
            if (dto === 13) {
                if (tipo === 'NA' && number.substring(10, 13) !== '001') resp['message'] = 'Los tres últimos dígitos deben ser 001 para RUC de persona natural.';
                if (tipo === 'PU' && number.substring(9, 13) !== '0001') resp['message'] = 'El R.U.C. de la empresa del sector público debe terminar con 0001';
            } else if ((tipo === 'PU' || tipo === 'PR')) resp['message'] = 'El R.U.C. de las empresas ' + resp['tipo'] + 's deben tener 13 dígitos!';
            if (resp['message'].length > 0) return resp;

            for (var a = 0; a < 9; a++) {
                var resul = digitos[a] * coef[tipo][a];
                acum += (resul - (tipo === 'NA' && resul >= 10 ? 9 : 0));
            }
            var residuo = acum % modulo,
                digitoVerificador = residuo === 0 ? 0 : modulo - residuo;
            if (digitos[(tipo === 'PU' ? 8 : 9)] !== digitoVerificador) resp['message'] = 'El número de ' + resp['doc'] + ' ingresado es inválido!';

            if (resp['message'].length === 0) resp['success'] = true;
        } else resp['message'] = 'ERROR: Solo debe contener dígitos!';
    }
    return resp;
}

// Función para validar placa de vehículo ecuatoriana
function validarPlacaVehiculo(placa) {
    if ($.isEmpty(placa)) {
        $("#Veh_Pla_Est").removeClass().css("color", "");
        return false;
    }

    // Formato: 3 letras seguidas de guion y 3-4 números
    // Ejemplos válidos: ABC-1234, XYZ-123, ABC-123
    var regex = /^[A-Z]{3}-[0-9]{3,4}$/;

    if (regex.test(placa)) {
        $("#Veh_Pla_Est").removeClass().addClass("fa fa-check").css("color", "green");
        return true;
    } else {
        $("#Veh_Pla_Est").removeClass().addClass("fa fa-close").css("color", "red");
        $.alert('El formato de la placa es inválido. Debe ser: ABC-1234 (3 letras, guion, 3-4 números). Ejemplo: ABC-1234');
        return false;
    }
}

// Función para validar identificación y mostrar feedback visual
function validarIdentificacion(campo, idIcono) {
    var valor = $(campo).val().trim();
    var icono = $('#' + idIcono);

    if ($.isEmpty(valor)) {
        icono.removeClass().css('color', '');
        return false;
    }

    if (valor.length < 10) {
        icono.removeClass().addClass('fa fa-close').css('color', 'orange');
        return false;
    }

    var resultado = validaNoIdentif(valor);
    if (resultado.success) {
        icono.removeClass().addClass('fa fa-check').css('color', 'green');
        return true;
    } else {
        icono.removeClass().addClass('fa fa-close').css('color', 'red');
        $.alert(resultado.message || 'Identificación inválida');
        return false;
    }
}

// Función para validar placa de vehículo ecuatoriana
function validarPlacaVehiculo(placa) {
    if ($.isEmpty(placa)) {
        $("#Veh_Pla_Est").removeClass().css("color", "");
        return false;
    }

    // Formato: 3 letras seguidas de guion y 3-4 números
    // Ejemplos válidos: ABC-1234, XYZ-123, ABC-123
    var regex = /^[A-Z]{3}-[0-9]{3,4}$/;

    if (regex.test(placa)) {
        $("#Veh_Pla_Est").removeClass().addClass("fa fa-check").css("color", "green");
        return true;
    } else {
        $("#Veh_Pla_Est").removeClass().addClass("fa fa-close").css("color", "red");
        $.alert('El formato de la placa es inválido. Debe ser: ABC-1234 (3 letras, guion, 3-4 números). Ejemplo: ABC-1234');
        return false;
    }
}

// Función para buscar persona en Admin Planta
function buscarPersonaAdminPlanta(cedula) {
    if ($.isEmpty(cedula) || cedula.length < 10) {
        $("#Prs_Ced_Est").removeClass().addClass("fa fa-close").css("color", "orange");
        return;
    }

    // Validar formato antes de buscar
    var resultado = validaNoIdentif(cedula);
    if (!resultado.success) {
        $("#Prs_Ced_Est").removeClass().addClass("fa fa-close").css("color", "red");
        $.alert(resultado.message || 'Identificación inválida');
        return;
    }

    $("#Prs_Ced_Est").removeClass().addClass("fa fa-spinner fa-spin").css("color", "#337ab7");
    $.post("", {
        buscarPersonaCedulaAjax: true,
        Prs_Ced: cedula
    }, function (r) {
        if (r.existe) {
            $('#Prs_Cod_Admin').val(r.persona.Prs_Cod);
            $('#Prs_Nom').val(r.persona.Prs_Nom || '');
            $('#Prs_Ape').val(r.persona.Prs_Ape || '');
            if (r.persona.Prs_Sex) $('#Prs_Sex').val(r.persona.Prs_Sex).trigger('chosen:updated');
            if (r.persona.Prs_Esc) $('#Pep_Esc').val(r.persona.Prs_Esc).trigger('chosen:updated');
            if (r.persona.Prs_Fec) $('#Prs_Fec').val(r.persona.Prs_Fec);
            if (r.persona.Prs_Tel) $('#Prs_Tel').val(r.persona.Prs_Tel);
            // Cod_Ciu_Nac y Cod_Ciu_Tra pueden no estar en la tabla persona, solo cargar si existen
            if (r.persona.Cod_Ciu_Nac) $('#Cod_Ciu_Nac').val(r.persona.Cod_Ciu_Nac).trigger('chosen:updated');
            if (r.persona.Cod_Ciu_Tra) $('#Cod_Ciu_Tra').val(r.persona.Cod_Ciu_Tra).trigger('chosen:updated');
            $("#Prs_Ced_Est").removeClass().addClass("fa fa-check").css("color", "green");
        } else {
            $('#Prs_Cod_Admin').val('');
            $("#Prs_Ced_Est").removeClass().addClass("fa fa-check").css("color", "#337ab7");
        }
    }, 'json');
}

// Función para buscar persona en Tributario
function buscarPersonaTributario(cedula) {
    if ($.isEmpty(cedula) || cedula.length < 10) {
        $("#Trb_Prs_Ced_Est").removeClass().addClass("fa fa-close").css("color", "orange");
        return;
    }

    // Validar formato antes de buscar
    var resultado = validaNoIdentif(cedula);
    if (!resultado.success) {
        $("#Trb_Prs_Ced_Est").removeClass().addClass("fa fa-close").css("color", "red");
        $.alert(resultado.message || 'Identificación inválida');
        return;
    }

    $("#Trb_Prs_Ced_Est").removeClass().addClass("fa fa-spinner fa-spin").css("color", "#337ab7");
    $.post("", {
        buscarPersonaCedulaAjax: true,
        Prs_Ced: cedula
    }, function (r) {
        if (r.existe) {
            $('#Prs_Cod_Trib').val(r.persona.Prs_Cod);
            $('#Trb_Prs_Nom').val(r.persona.Prs_Nom || '');
            $('#Trb_Prs_Ape').val(r.persona.Prs_Ape || '');
            if (r.persona.Prs_Sex) $('#Trb_Prs_Sex').val(r.persona.Prs_Sex).trigger('chosen:updated');
            if (r.persona.Prs_Esc) $('#Trb_Prs_Esc').val(r.persona.Prs_Esc).trigger('chosen:updated');
            if (r.persona.Prs_Fec) $('#Trb_Prs_Fec').val(r.persona.Prs_Fec);
            if (r.persona.Prs_Tel) $('#Trb_Prs_Tel').val(r.persona.Prs_Tel);
            // Cod_Ciu_Nac y Cod_Ciu_Tra pueden no estar en la tabla persona, solo cargar si existen
            if (r.persona.Cod_Ciu_Nac) $('#Trb_Cod_Ciu_Nac').val(r.persona.Cod_Ciu_Nac).trigger('chosen:updated');
            if (r.persona.Cod_Ciu_Tra) $('#Trb_Cod_Ciu_Tra').val(r.persona.Cod_Ciu_Tra).trigger('chosen:updated');
            $("#Trb_Prs_Ced_Est").removeClass().addClass("fa fa-check").css("color", "green");
        } else {
            $('#Prs_Cod_Trib').val('');
            $("#Trb_Prs_Ced_Est").removeClass().addClass("fa fa-check").css("color", "#337ab7");
        }
    }, 'json');
}

// Función para buscar persona en Ambiental
function buscarPersonaAmbiental(cedula) {
    if ($.isEmpty(cedula) || cedula.length < 10) {
        $("#Amb_Prs_Ced_Est").removeClass().addClass("fa fa-close").css("color", "orange");
        return;
    }

    // Validar formato antes de buscar
    var resultado = validaNoIdentif(cedula);
    if (!resultado.success) {
        $("#Amb_Prs_Ced_Est").removeClass().addClass("fa fa-close").css("color", "red");
        $.alert(resultado.message || 'Identificación inválida');
        return;
    }

    $("#Amb_Prs_Ced_Est").removeClass().addClass("fa fa-spinner fa-spin").css("color", "#337ab7");
    $.post("", {
        buscarPersonaCedulaAjax: true,
        Prs_Ced: cedula
    }, function (r) {
        if (r.existe) {
            $('#Prs_Cod_Amb').val(r.persona.Prs_Cod);
            $('#Amb_Prs_Nom').val(r.persona.Prs_Nom || '');
            $('#Amb_Prs_Ape').val(r.persona.Prs_Ape || '');
            if (r.persona.Prs_Sex) $('#Amb_Prs_Sex').val(r.persona.Prs_Sex).trigger('chosen:updated');
            if (r.persona.Prs_Esc) $('#Amb_Prs_Esc').val(r.persona.Prs_Esc).trigger('chosen:updated');
            if (r.persona.Prs_Fec) $('#Amb_Prs_Fec').val(r.persona.Prs_Fec);
            if (r.persona.Prs_Tel) $('#Amb_Prs_Tel').val(r.persona.Prs_Tel);
            // Cod_Ciu_Nac y Cod_Ciu_Tra pueden no estar en la tabla persona, solo cargar si existen
            if (r.persona.Cod_Ciu_Nac) $('#Amb_Cod_Ciu_Nac').val(r.persona.Cod_Ciu_Nac).trigger('chosen:updated');
            if (r.persona.Cod_Ciu_Tra) $('#Amb_Cod_Ciu_Tra').val(r.persona.Cod_Ciu_Tra).trigger('chosen:updated');
            $("#Amb_Prs_Ced_Est").removeClass().addClass("fa fa-check").css("color", "green");
        } else {
            $('#Prs_Cod_Amb').val('');
            $("#Amb_Prs_Ced_Est").removeClass().addClass("fa fa-check").css("color", "#337ab7");
        }
    }, 'json');
}

// ==================== SELECTOR DE CLIENTES PARA PLANTA ====================
function abrirDialogClientePlanta() {
    if ($('#gridClientePlanta').jqGrid('getGridParam', 'datatype') === undefined) {
        createGridClientePlanta();
    }
    $('#clientePlantaDialog').dialog('open');
}

function createGridClientePlanta() {
    $('#gridClientePlanta').createGrid({
        caption: 'Seleccionar Cliente',
        url: '',
        height: 350,
        colModel: [
            { label: 'Código', name: 'Cli_Cod', key: true, width: 50, align: "center", hidden: true },
            { label: 'Cédula/RUC', name: 'Prs_Ced', width: 100, align: "center" },
            { label: 'Nombre', name: 'Cliente', width: 200 },
            {
                label: '<center><i class="glyphicon glyphicon-ok"></i></center>',
                name: 'acciones',
                width: 60,
                align: 'center',
                formatter: function (cellvalue, options, o) {
                    return $.getGridButton('seleccionarClientePlanta', o, 'Seleccionar', 'ok', '', 'success');
                }
            }
        ],
        rowNum: 500,
        viewrecords: true,
        postData: {
            listClientesAjax: true
        }
    }, true, '#gridClientePlantaPager', {
        refresh: true,
        view: false
    });
}

function seleccionarClientePlanta(cliente) {
    $('#Cli_Cod').val(cliente.Cli_Cod);
    $('#Cli_Nom_Planta').val(cliente.Cliente || (cliente.Prs_Nom + ' ' + cliente.Prs_Ape));
    $('#Cli_Ced_Planta').val(cliente.Prs_Ced || cliente.Ruc);

    // Actualizar la variable global Cli_Cod
    Cli_Cod = cliente.Cli_Cod;

    // Actualizar el texto del cliente en el encabezado
    $('#nombreClienteHeader').text(cliente.Cliente || (cliente.Prs_Nom + ' ' + cliente.Prs_Ape) || 'N/A');
    $('#codigoClienteHeader').text(cliente.Cli_Cod || '');

    // Los grids de plantas muestran todas las plantas de la empresa (no filtran por cliente)
    // Solo actualizar choferes y vehículos si necesitan filtrar por cliente

    $('#clientePlantaDialog').dialog('close');
}

// ==================== TAB GENERAL (Pla_Smi) ====================
function cargarGeneralManifiesto() {
    var postUrl = (window.location.href || '').split('#')[0];
    $.post(postUrl, { getGeneralManifiestoAjax: 1 }, function (r) {
        if (r && r.success) {
            var sug = parseFloat(r.pla_smi_sugerido);
            if (isNaN(sug)) {
                sug = 0;
            }
            $('#cfg_pla_smi_general').val(sug.toFixed(2));
            
            // Cargar estado de la campaña (Select)
            $('#cfg_pla_act_general').val(r.pla_act_general === 'S' ? 'S' : 'N').trigger('change');
            
            // Cargar combo de eventos activos y preseleccionar los vigentes
            var evesVigentes = r.man_eves_vigentes || (r.man_eve_vigente ? [r.man_eve_vigente] : []);
            cargarComboEventosGeneral(evesVigentes);
        } else {
            $.alert((r && r.message) ? r.message : 'No se pudo cargar la configuración general.');
        }
    }, 'json').fail(function () {
        $.alert('Error de comunicación al cargar la configuración general.');
    });
}

function guardarGeneralManifiesto() {
    var raw = String($('#cfg_pla_smi_general').val() || '').replace(',', '.');
    var v = parseFloat(raw);
    if (isNaN(v) || v < 0) {
        $.alert('Ingrese un valor numérico mayor o igual a 0.');
        return;
    }
    var plaAct = $('#cfg_pla_act_general').val();
    var manEve = $('#cfg_man_eve_general').val() || [];
    
    var msg = 'Se modificara la configuracion general de manifiestos. ¿Desea continuar?';
    $.createDialogConfirm(msg, {
        saveGeneralManifiestoAjax: 1,
        Pla_Smi_general: v.toFixed(2),
        Pla_Act_general: plaAct,
        Man_Eve_general: manEve
    }, function (data) {
        $('#loader').show();
        var postUrl = (window.location.href || '').split('#')[0];
        var payload = $.extend({}, data);
        $.post(postUrl, payload, function (r) {
            $('#loader').fadeOut('slow');
            if (r && r.success) {
                $.alert(r.message || 'Guardado correctamente.');
                if ($('#gridPlantas').length && $('#gridPlantas').data('jqGrid')) {
                    $('#gridPlantas').trigger('reloadGrid');
                }
                cargarGeneralManifiesto();
            } else {
                $.alert((r && r.message) ? r.message : 'No se pudo guardar.');
            }
        }, 'json').fail(function () {
            $('#loader').fadeOut('slow');
            $.alert('Error de comunicación al guardar.');
        });
    });
}

// Inicializar colores del select de campaña y cargar combo de eventos
$(document).ready(function() {
    $(document).on('change', '#cfg_pla_act_general', function() {
        var val = $(this).val();
        if (val === 'S') {
            $(this).css({ 'color': '#10b981', 'border-color': '#10b981' });
        } else {
            $(this).css({ 'color': '#ef4444', 'border-color': '#ef4444' });
        }
    });

    // Cargar combo de eventos en tab general
    if ($('#cfg_man_eve_general').length) {
        cargarComboEventosGeneral();
    }

    // Restringir el año en campos de fecha a máximo 4 dígitos (AAAA)
    $(document).on('input blur change', '#evt_Man_EFei, #evt_Man_EFef, input[type="date"]', function () {
        var val = $(this).val();
        if (val) {
            var parts = val.split('-');
            if (parts[0] && parts[0].length > 4) {
                parts[0] = parts[0].substring(0, 4);
                $(this).val(parts.join('-'));
            }
        }
    });
});

// ==================== GESTIÓN DE EVENTOS (manifiesto_evento) ====================

function cargarComboEventosGeneral(selectedIds) {
    var postUrl = (window.location.href || '').split('#')[0];
    $.post(postUrl, { getComboEventosAjax: 1 }, function (r) {
        if (r && r.success) {
            var $sel = $('#cfg_man_eve_general');
            var currVals = selectedIds || $sel.val() || [];
            if (!Array.isArray(currVals)) {
                currVals = [currVals];
            }
            $sel.empty();
            if (r.data && r.data.length > 0) {
                $.each(r.data, function (i, evt) {
                    var label = evt.Man_ENom + ' (' + (evt.Man_EHor || 6) + 'h - ' + evt.Man_EFei + ' - ' + evt.Man_EFef + ')';
                    $sel.append('<option value="' + evt.Man_Eve + '">' + label + '</option>');
                });
            }
            $sel.val(currVals);
            if ($.fn.chosen) {
                $sel.trigger('chosen:updated');
            }
        }
    }, 'json');
}

window._historialEventosMap = {};

window.insertarTagEvento = function (campoId, tag) {
    var $el = $('#' + campoId);
    if (!$el.length) return;
    var el = $el[0];
    var start = el.selectionStart || 0;
    var end = el.selectionEnd || 0;
    var val = $el.val();
    $el.val(val.substring(0, start) + tag + val.substring(end));
    el.selectionStart = el.selectionEnd = start + tag.length;
    $el.focus();
};

window.restablecerTextoCertificado = function () {
    $('#evt_Man_Tcrf').val('Que el Sr(a). {nombre} asistió a la capacitación de {proyecto} el día {fecha} con una duración de {horas} horas con el tema "{evento}".');
};

window.restablecerMensajeWhatsApp = function () {
    $('#evt_Man_Wms').val(
        "¡Hola *{nombre}*! 👋\n\n" +
        "Te has registrado exitosamente en el evento *\"{evento}\"*.\n" +
        "Esperando que sea de su agrado y que disfrute al máximo.\n\n" +
        "🏢 Proyecto: *Relavera Comunitaria \"El Tablón\"* - ECOPARKMINING S.A.\n\n" +
        "¡Gracias por tu participación! ✨"
    );
};

window.restablecerMensajeMasivo = function () {
    $('#evt_Man_Mmsg').val(
        "¡Hola *{nombre}*! 📢\n\n" +
        "Te recordamos que el evento *\"{evento}\"* se llevará a cabo el día *{fecha}*.\n\n" +
        "🏢 Proyecto: *{proyecto}* - ECOPARKMINING S.A.\n\n" +
        "¡Te esperamos puntualmente! ✨"
    );
};

function abrirModalEventos() {
    limpiarFormEvento();
    $('#eventosDialog').dialog('open');
    cargarHistorialEventos();
}

function cargarHistorialEventos() {
    var postUrl = (window.location.href || '').split('#')[0];
    $('#bodyHistorialEventos').html('<tr><td colspan="8" class="text-center" style="color: #9ca3af; padding: 15px;"><i class="glyphicon glyphicon-refresh spin"></i> Cargando historial...</td></tr>');
    
    $.post(postUrl, { getHistorialEventosAjax: 1 }, function (r) {
        var $body = $('#bodyHistorialEventos');
        $body.empty();
        window._historialEventosMap = {};
        
        if (r && r.success && r.data && r.data.length > 0) {
            $.each(r.data, function (i, evt) {
                window._historialEventosMap[evt.Man_Eve] = evt;

                var isInactive = (evt.Man_EEst === 'I');
                var isVigente = (evt.Man_Vig === 'S');
                var rowStyle = isInactive ? ' style="background-color: #FADDDD !important;"' : '';

                var estBadge = (!isInactive) 
                    ? '<span class="label label-success" style="font-size: 10px;">ACTIVO</span>' 
                    : '<span class="label label-danger" style="font-size: 10px;">INACTIVO</span>';
                
                var vigBadge = isVigente 
                    ? '<span class="label label-info" style="font-size: 10px; background-color: #0284c7;">VIGENTE</span>' 
                    : '<span class="label label-default" style="font-size: 10px; background-color: #64748b;">NO VIGENTE</span>';

                var btnToggle = (!isInactive)
                    ? '<button type="button" class="btn btn-warning btn-xs" onclick="toggleEstadoEvento(' + evt.Man_Eve + ', \'I\');" title="Inactivar Evento" style="margin-right: 2px;"><i class="glyphicon glyphicon-eye-close"></i></button>'
                    : '<button type="button" class="btn btn-success btn-xs" onclick="toggleEstadoEvento(' + evt.Man_Eve + ', \'A\');" title="Activar Evento" style="margin-right: 2px;"><i class="glyphicon glyphicon-eye-open"></i></button>';

                var btnVig = isVigente
                    ? '<button type="button" class="btn btn-primary btn-xs" onclick="toggleVigenciaEvento(' + evt.Man_Eve + ', \'N\');" title="Quitar Vigencia" style="margin-right: 2px;"><i class="glyphicon glyphicon-star"></i></button>'
                    : '<button type="button" class="btn btn-default btn-xs" onclick="toggleVigenciaEvento(' + evt.Man_Eve + ', \'S\');" title="Marcar como Vigente" style="margin-right: 2px;"><i class="glyphicon glyphicon-star-empty"></i></button>';

                var isEditable = (evt.Hoy_YMD <= evt.Man_EFef);
                var btnEdit = isEditable
                    ? '<button type="button" class="btn btn-info btn-xs" onclick="editarEvento(' + evt.Man_Eve + ');" title="Editar Evento" style="margin-right: 2px;"><i class="glyphicon glyphicon-pencil"></i></button>'
                    : '<button type="button" class="btn btn-default btn-xs" disabled title="Solo editable hasta la fecha fin del evento" style="margin-right: 2px; opacity: 0.5;"><i class="glyphicon glyphicon-lock"></i></button>';

                var tr = '<tr' + rowStyle + '>' +
                    '<td class="text-center"><b>' + evt.Man_Eve + '</b></td>' +
                    '<td>' + escapeHtml(evt.Man_ENom) + '</td>' +
                    '<td class="text-center">' + (evt.Man_EHor || 6) + ' hrs</td>' +
                    '<td class="text-center">' + evt.Man_EFei_Fmt + '</td>' +
                    '<td class="text-center">' + evt.Man_EFef_Fmt + '</td>' +
                    '<td class="text-center">' + estBadge + '</td>' +
                    '<td class="text-center">' + vigBadge + '</td>' +
                    '<td class="text-center">' + btnEdit + btnVig + btnToggle + '</td>' +
                '</tr>';

                $body.append(tr);
            });
        } else {
            $body.html('<tr><td colspan="8" class="text-center" style="color: #6b7280; padding: 15px;">No se encontraron eventos registrados.</td></tr>');
        }
    }, 'json').fail(function () {
        $('#bodyHistorialEventos').html('<tr><td colspan="8" class="text-center text-danger" style="padding: 15px;">Error al cargar el historial.</td></tr>');
    });
}

function toggleVigenciaEvento(id, nuevaVig) {
    var postUrl = (window.location.href || '').split('#')[0];
    $.post(postUrl, { toggleVigenciaEventoAjax: 1, Man_Eve: id, Man_Vig: nuevaVig }, function (r) {
        if (r && r.success) {
            cargarHistorialEventos();
            cargarGeneralManifiesto();
        } else {
            $.alert((r && r.message) ? r.message : 'No se pudo cambiar la vigencia.');
        }
    }, 'json');
}

function limpiarFormEvento() {
    $('#evt_Man_Eve').val('0');
    $('#evt_Man_ENom').val('');
    $('#evt_Man_EHor').val('');
    $('#evt_Man_EFei').val('');
    $('#evt_Man_EFef').val('');
    $('#evt_Man_Teve').val('');
    $('#evt_Man_Afir').val('');
    $('#evt_Man_Tcrf').val('');
    $('#evt_Man_Wms').val('');
    $('#evt_Man_Mmsg').val('');
    $('#evt_Man_Mdel').val('10');
    $('#evt_Man_EEst').val('A');
}

function editarEvento(id) {
    var evt = window._historialEventosMap ? window._historialEventosMap[id] : null;
    if (!evt) return;

    if (evt.Hoy_YMD && evt.Hoy_YMD > evt.Man_EFef) {
        $.alert('No se puede editar este evento ya que su fecha fin ha expirado.');
        return;
    }
    $('#evt_Man_Eve').val(evt.Man_Eve);
    $('#evt_Man_ENom').val(evt.Man_ENom || '');
    $('#evt_Man_EHor').val(evt.Man_EHor || '6');
    $('#evt_Man_EFei').val(evt.Man_EFei || '');
    $('#evt_Man_EFef').val(evt.Man_EFef || '');
    $('#evt_Man_Teve').val(evt.Man_Teve || 'DE ASISTENCIA');
    $('#evt_Man_Afir').val(evt.Man_Afir || 'ÁREA DE CAPACITACIÓN');
    $('#evt_Man_Tcrf').val(evt.Man_Tcrf || '');
    $('#evt_Man_Wms').val(evt.Man_Wms || '');
    $('#evt_Man_Mmsg').val(evt.Man_Mmsg || '');
    $('#evt_Man_Mdel').val(evt.Man_Mdel || 10);
    $('#evt_Man_EEst').val(evt.Man_EEst || 'A');
}

function guardarEvento() {
    var id = $('#evt_Man_Eve').val();
    var nom = $.trim($('#evt_Man_ENom').val());
    var hor = $.trim($('#evt_Man_EHor').val()) || '6';
    var fei = $.trim($('#evt_Man_EFei').val());
    var fef = $.trim($('#evt_Man_EFef').val());
    var tip = $.trim($('#evt_Man_Teve').val()) || 'DE ASISTENCIA';
    var afir = $.trim($('#evt_Man_Afir').val()) || 'ÁREA DE CAPACITACIÓN';
    var tcrf = $.trim($('#evt_Man_Tcrf').val());
    var msg = $.trim($('#evt_Man_Wms').val());
    var mmsg = $.trim($('#evt_Man_Mmsg').val());
    var mdel = parseInt($('#evt_Man_Mdel').val(), 10);
    if (isNaN(mdel) || mdel < 10) {
        mdel = 10;
        $('#evt_Man_Mdel').val('10');
        $.alert('Por seguridad, el tiempo mínimo entre envíos es de 10 segundos para no saturar WhatsApp. Se ha ajustado automáticamente a 10 seg.');
        return;
    }
    var est = 'A';

    if (!nom) {
        $.alert('Por favor ingrese el nombre del evento.');
        return;
    }
    if (!hor) {
        $.alert('Por favor ingrese una duración válida.');
        return;
    }
    if (!fei || !fef) {
        $.alert('Por favor ingrese las fechas de inicio y fin.');
        return;
    }

    var feiYear = fei.split('-')[0];
    var fefYear = fef.split('-')[0];
    if (!feiYear || feiYear.length !== 4 || isNaN(feiYear)) {
        $.alert('El año en la fecha de inicio debe tener exactamente 4 dígitos (AAAA).');
        return;
    }
    if (!fefYear || fefYear.length !== 4 || isNaN(fefYear)) {
        $.alert('El año en la fecha de fin debe tener exactamente 4 dígitos (AAAA).');
        return;
    }

    var postUrl = (window.location.href || '').split('#')[0];
    var payload = {
        saveEventoAjax: 1,
        Man_Eve: id,
        Man_ENom: nom,
        Man_EHor: hor,
        Man_EFei: fei,
        Man_EFef: fef,
        Man_Teve: tip,
        Man_Afir: afir,
        Man_Tcrf: tcrf,
        Man_Wms: msg,
        Man_Mmsg: mmsg,
        Man_Mdel: mdel,
        Man_EEst: est
    };

    var $btn = $('#btnGuardarEvento');
    $btn.prop('disabled', true).html('<i class="glyphicon glyphicon-refresh spin"></i> Guardando...');
    $('#loader').show();

    $.post(postUrl, payload, function (r) {
        $btn.prop('disabled', false).html('<i class="glyphicon glyphicon-floppy-disk"></i> Guardar Evento');
        $('#loader').fadeOut('slow');
        if (r && r.success) {
            $.alert(r.message || 'Evento guardado correctamente.');
            limpiarFormEvento();
            cargarHistorialEventos();
            cargarComboEventosGeneral();
        } else {
            $.alert((r && r.message) ? r.message : 'Error al guardar evento.');
        }
    }, 'json').fail(function () {
        $btn.prop('disabled', false).html('<i class="glyphicon glyphicon-floppy-disk"></i> Guardar Evento');
        $('#loader').fadeOut('slow');
        $.alert('Error de comunicación al guardar el evento.');
    });
}

function toggleEstadoEvento(id, nuevoEstado) {
    var accion = (nuevoEstado === 'A') ? 'activar' : 'inactivar';
    var msg = '¿Está seguro de que desea ' + accion + ' este evento?';
    
    $.createDialogConfirm(msg, {
        toggleEstadoEventoAjax: 1,
        Man_Eve: id,
        Man_EEst: nuevoEstado
    }, function (data) {
        $('#loader').show();
        var postUrl = (window.location.href || '').split('#')[0];
        $.post(postUrl, data, function (r) {
            $('#loader').fadeOut('slow');
            if (r && r.success) {
                $.alert(r.message || 'Estado actualizado.');
                cargarHistorialEventos();
                cargarComboEventosGeneral();
            } else {
                $.alert((r && r.message) ? r.message : 'Error al cambiar estado.');
            }
        }, 'json').fail(function () {
            $('#loader').fadeOut('slow');
            $.alert('Error de comunicación.');
        });
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// ==================== GRID PLANTAS ====================
function createGridPlantas() {
    // console.log("Creando grid de plantas");
    $('#gridPlantas').createGrid({
        caption: 'Listado de Plantas',
        url: window.location.href,
        height: 400,
        colModel: [
            { label: 'Código', name: 'Pla_Cod', key: true, width: 50, align: "center" },
            { label: 'Cod_Cli', name: 'Cli_Cod', width: 80, align: "center", hidden: true },
            { label: 'Ced/RUC', name: 'Prs_Ced', width: 80, align: "center" },
            { label: 'Cliente', name: 'Cliente', width: 160, align: "left", hidden: false },

            { label: 'Nombre', name: 'Pla_Nom', width: 150 },
            { label: 'Ciudad', name: 'Ciu_Des', width: 90 },
            { label: 'Licencia', name: 'Pla_Lic', width: 100, align: "center" },
            { label: 'Cod.Desecho', name: 'Pla_Crd', width: 100, align: "center" },
            { label: 'Cod.Arcon', name: 'Pla_Car', width: 100, align: "center" },
            { label: 'Dirección', name: 'Pla_Dir', width: 150 },
            /*{
                label: 'Mín. anticipo',
                name: 'Pla_Smi',
                width: 88,
                align: 'right',
                formatter: function (v) {
                    var n = parseFloat(v);
                    return isNaN(n) ? '0.00' : n.toFixed(2);
                }
            },*/
            {
                label: 'Estado',
                name: 'Pla_Est',
                width: 60,
                align: "center",
                formatter: function (v) {
                    return v === 'A' ? '<span class="label label-success">Activo</span>' : '<span class="label label-danger">Inactivo</span>';
                }
            },
            {
                label: '<center><i class="glyphicon glyphicon-cog"></i></center>',
                name: 'acciones',
                width: 80,
                align: 'center',
                formatter: function (cellvalue, options, o) {
                    if (typeof esPerfilLectura !== 'undefined' && esPerfilLectura) return '';
                    return $.getGridButton('editarPlanta', o, 'Editar', 'pencil', '', 'success') + '&nbsp;' +
                        $.getGridButton('anularPlanta', o.Pla_Cod, 'Anular', 'trash', '', 'danger');
                }
            }
        ],
        rowNum: 500,
        viewrecords: true,
        postData: {
            listPlantasGridAjax: true,
            op_opciones: 'd',
            search: ''
        }
    }, false, '#gridPlantasPager', {
        refresh: true,
        view: false
    }).gridButtonsAdd([
        {
            caption: 'Exportar Excel',
            buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                $('#gridPlantas').jqGrid('exportGridExcel', {
                    nombre: 'Plantas',
                    hoja: 'HOJA 1',
                    footer: true,
                    removeHiddens: true,
                    removeCols: [1, 12] // Ocultar Cod_Cli (índice 1) y acciones
                });
            }
        },
        {
            caption: 'Exportar PDF',
            buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                imprimirPlantas();
            }
        }
    ]);
}

function imprimirPlantas() {
    $('#tablaReportePlantas').html($('#gridPlantas').jqGrid('exportGridInnerHTML', {
        footer: true,
        generated: false,
        removeHiddens: true,
        removeCols: [1, 12] // Ocultar Cod_Cli y acciones
    }));
    $('#imprimirPlantas').printElement();
}


function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}


function actualizarGridPlantas() {
    var op_opciones = $('input[name="op_opciones"]:checked', '#filtroPlantasForm').val() || 'd';
    var search = $('input[name="search"]', '#filtroPlantasForm').val() || '';
    $('#gridPlantas').jqGrid('setGridParam', {
        postData: {
            listPlantasGridAjax: true,
            op_opciones: op_opciones,
            search: search
        },
        page: 1
    }).trigger('reloadGrid');
}

function abrirModalPlanta() {
    // Ocultar mensajes de error
    $('#plantaErrorMessages').hide();
    $('#plantaErrorList').empty();

    // Limpiar formulario de planta
    $('#plantaForm')[0].reset();
    $('#Pla_Cod').val('');
    $('#Cli_Cod').val('');
    $('#Cli_Nom_Planta').val('');
    $('#Cli_Ced_Planta').val('');
    $('#Ciu_Cod').val('').trigger('chosen:updated');

    // Limpiar formulario de Administrador
    $('#adminPlantaForm')[0].reset();
    $('#Prs_Cod_Admin').val('');
    $('#Prs_Ced_Est').html('');

    // Limpiar formulario de Tributario
    $('#plantaTributarioForm')[0].reset();
    $('#Prs_Cod_Trib').val('');
    $('#Trb_Prs_Ced_Est').html('');

    // Limpiar formulario de Ambiental
    $('#plantaAmbientalForm')[0].reset();
    $('#Prs_Cod_Amb').val('');
    $('#Amb_Prs_Ced_Est').html('');

    $('#plantaDialog').dialog('open');
}

function editarPlanta(o) {
    // Ocultar mensajes de error
    $('#plantaErrorMessages').hide();
    $('#plantaErrorList').empty();

    // Primero limpiar todos los formularios del personal
    $('#adminPlantaForm')[0].reset();
    $('#Prs_Cod_Admin').val('');
    $('#Prs_Ced_Est').html('');
    $('#Prs_Sex').val('').trigger('chosen:updated');
    $('#Pep_Esc').val('').trigger('chosen:updated');
    $('#Cod_Ciu_Nac').val('').trigger('chosen:updated');
    $('#Cod_Ciu_Tra').val('').trigger('chosen:updated');

    $('#plantaTributarioForm')[0].reset();
    $('#Prs_Cod_Trib').val('');
    $('#Trb_Prs_Ced_Est').html('');
    $('#Trb_Prs_Sex').val('').trigger('chosen:updated');
    $('#Trb_Prs_Esc').val('').trigger('chosen:updated');
    $('#Trb_Cod_Ciu_Nac').val('').trigger('chosen:updated');
    $('#Trb_Cod_Ciu_Tra').val('').trigger('chosen:updated');

    $('#plantaAmbientalForm')[0].reset();
    $('#Prs_Cod_Amb').val('');
    $('#Amb_Prs_Ced_Est').html('');
    $('#Amb_Prs_Sex').val('').trigger('chosen:updated');
    $('#Amb_Prs_Esc').val('').trigger('chosen:updated');
    $('#Amb_Cod_Ciu_Nac').val('').trigger('chosen:updated');
    $('#Amb_Cod_Ciu_Tra').val('').trigger('chosen:updated');

    // Obtener datos completos de la planta incluyendo personal
    $.getDataJson('', {
        getPlantaCompletaAjax: true,
        Pla_Cod: o.Pla_Cod
    }, function (r) {
        if (r.success) {
            var planta = r.planta;

            // Cargar datos de la planta
            $('#plantaForm').setData(planta);
            $('#Ciu_Cod').val(planta.Ciu_Cod).trigger('chosen:updated');
            if (planta.Pla_Pfa) $('#Pla_Pfa').val(planta.Pla_Pfa);
            if (planta.Pla_Pfa) $('#Pla_Pfa').val(planta.Pla_Pfa);
            if (planta.Pla_Wat) $('#Pla_Wat').val(planta.Pla_Wat);

            // Cargar datos del cliente si existen
            if (r.cliente) {
                var clienteData = {
                    Cli_Cod: r.cliente.Cli_Cod,
                    Cliente: r.cliente.Cliente,
                    Prs_Ced: r.cliente.Prs_Ced
                };

                $('#plantaForm').setData($.extend(clienteData, {
                    op_opciones: 'c'
                }), 'name').find('.dialogSearch').addClass('x');

                $('input[name="Cli_Cod"]').val(r.cliente.Cli_Cod);
                $('span[name="Cliente"]').text(r.cliente.Cliente);
            } else {
                // Limpiar datos del cliente si no existe
                $('input[name="Cli_Cod"]').val('');
                $('span[name="Cliente"]').text('');
                $('input[data-name="Prs_Ced"]').val('').removeClass('x');
            }

            // Cargar datos del Administrador de Planta
            if (r.personalAdmin) {
                var admin = r.personalAdmin;
                $('#Prs_Ced').val(admin.Prs_Ced);
                $('#Prs_Nom').val(admin.Prs_Nom);
                $('#Prs_Ape').val(admin.Prs_Ape);
                if (admin.Prs_Sex) $('#Prs_Sex').val(admin.Prs_Sex).trigger('chosen:updated');
                if (admin.Pep_Esc) $('#Pep_Esc').val(admin.Pep_Esc).trigger('chosen:updated');
                if (admin.Prs_Fec) $('#Prs_Fec').val(admin.Prs_Fec);
                if (admin.Ciu_Cod_Nac) $('#Cod_Ciu_Nac').val(admin.Ciu_Cod_Nac).trigger('chosen:updated');
                if (admin.Ciu_Cod_Tra) $('#Cod_Ciu_Tra').val(admin.Ciu_Cod_Tra).trigger('chosen:updated');
                if (admin.Prs_Tel) $('#Prs_Tel').val(admin.Prs_Tel);
                $('#Pep_Tel').val(admin.Pep_Tel != null && admin.Pep_Tel !== undefined ? admin.Pep_Tel : '');
                if (admin.Pep_Cor) $('#Pep_Cor').val(admin.Pep_Cor);
                $('#Prs_Cod_Admin').val(admin.Prs_Cod);
                $('#Prs_Ced_Est').html('<i class="glyphicon glyphicon-ok text-success"></i>');
            }

            // Cargar datos del Tributario
            if (r.personalTrib) {
                var trib = r.personalTrib;
                $('#Trb_Prs_Ced').val(trib.Prs_Ced);
                $('#Trb_Prs_Nom').val(trib.Prs_Nom);
                $('#Trb_Prs_Ape').val(trib.Prs_Ape);
                if (trib.Prs_Sex) $('#Trb_Prs_Sex').val(trib.Prs_Sex).trigger('chosen:updated');
                if (trib.Pep_Esc) $('#Trb_Prs_Esc').val(trib.Pep_Esc).trigger('chosen:updated');
                if (trib.Prs_Fec) $('#Trb_Prs_Fec').val(trib.Prs_Fec);
                if (trib.Ciu_Cod_Nac) $('#Trb_Cod_Ciu_Nac').val(trib.Ciu_Cod_Nac).trigger('chosen:updated');
                if (trib.Ciu_Cod_Tra) $('#Trb_Cod_Ciu_Tra').val(trib.Ciu_Cod_Tra).trigger('chosen:updated');
                if (trib.Prs_Tel) $('#Trb_Prs_Tel').val(trib.Prs_Tel);
                $('#Trb_Pep_Tel').val(trib.Pep_Tel != null && trib.Pep_Tel !== undefined ? trib.Pep_Tel : '');
                if (trib.Pep_Cor) $('#Trb_Pep_Cor').val(trib.Pep_Cor);
                $('#Prs_Cod_Trib').val(trib.Prs_Cod);
                $('#Trb_Prs_Ced_Est').html('<i class="glyphicon glyphicon-ok text-success"></i>');
            }

            // Cargar datos del Ambiental
            if (r.personalAmb) {
                var amb = r.personalAmb;
                $('#Amb_Prs_Ced').val(amb.Prs_Ced);
                $('#Amb_Prs_Nom').val(amb.Prs_Nom);
                $('#Amb_Prs_Ape').val(amb.Prs_Ape);
                if (amb.Prs_Sex) $('#Amb_Prs_Sex').val(amb.Prs_Sex).trigger('chosen:updated');
                if (amb.Pep_Esc) $('#Amb_Prs_Esc').val(amb.Pep_Esc).trigger('chosen:updated');
                if (amb.Prs_Fec) $('#Amb_Prs_Fec').val(amb.Prs_Fec);
                if (amb.Ciu_Cod_Nac) $('#Amb_Cod_Ciu_Nac').val(amb.Ciu_Cod_Nac).trigger('chosen:updated');
                if (amb.Ciu_Cod_Tra) $('#Amb_Cod_Ciu_Tra').val(amb.Ciu_Cod_Tra).trigger('chosen:updated');
                if (amb.Prs_Tel) $('#Amb_Prs_Tel').val(amb.Prs_Tel);
                $('#Amb_Pep_Tel').val(amb.Pep_Tel != null && amb.Pep_Tel !== undefined ? amb.Pep_Tel : '');
                if (amb.Pep_Cor) $('#Amb_Pep_Cor').val(amb.Pep_Cor);
                $('#Prs_Cod_Amb').val(amb.Prs_Cod);
                $('#Amb_Prs_Ced_Est').html('<i class="glyphicon glyphicon-ok text-success"></i>');
            }

            $('#plantaDialog').dialog('open');
        } else {
            $.alert(r.message || 'Error al cargar los datos de la planta');
        }
    });
}

function guardarPlanta() {
    // Ocultar mensajes de error anteriores
    $('#plantaErrorMessages').hide();
    $('#plantaErrorList').empty();

    let errores = [];

    // Validar campos requeridos del formulario principal de la planta
    let plantaForm = $('#plantaForm')[0];
    let requiredFields = plantaForm.querySelectorAll('[required]');
    requiredFields.forEach(function (field) {
        if (!field.value || field.value.trim() === '') {
            let label = plantaForm.querySelector('label[for="' + field.id + '"]');
            if (!label) {
                // Intentar buscar por name o por clase
                label = $(field).closest('.form-group').find('label').first()[0];
            }
            let fieldName = label ? label.textContent.replace('*', '').replace(':', '').trim() : field.name;
            errores.push('El campo "' + fieldName + '" es obligatorio.');
        }
    });

    // Validar campos específicos adicionales (obligatorios pero que pueden no tener required)
    let data = $('#plantaForm').getData();
    if (!data.Pla_Nom || data.Pla_Nom.trim() === '') {
        if (errores.indexOf('El campo "Nombre Planta" es obligatorio.') === -1) {
            errores.push('El campo "Nombre Planta" es obligatorio.');
        }
    }
    if (!data.Pla_Lic || data.Pla_Lic.trim() === '') {
        if (errores.indexOf('El campo "Nro. Licencia Ambiental" es obligatorio.') === -1) {
            errores.push('El campo "Nro. Licencia Ambiental" es obligatorio.');
        }
    }
    if (!data.Pla_Dir || data.Pla_Dir.trim() === '') {
        if (errores.indexOf('El campo "Dirección" es obligatorio.') === -1) {
            errores.push('El campo "Dirección" es obligatorio.');
        }
    }
    if (!data.Ciu_Cod) {
        if (errores.indexOf('El campo "Ciudad" es obligatorio.') === -1) {
            errores.push('El campo "Ciudad" es obligatorio.');
        }
    }
    if (!data.Pla_Car || data.Pla_Car.trim() === '') {
        if (errores.indexOf('El campo "Cod.Arcon" es obligatorio.') === -1) {
            errores.push('El campo "Cod.Arcon" es obligatorio.');
        }
    }

    // Si hay errores, mostrarlos y detener el guardado
    if (errores.length > 0) {
        let errorHtml = '';
        errores.forEach(function (error) {
            errorHtml += '<li>' + error + '</li>';
        });
        $('#plantaErrorList').html(errorHtml);
        $('#plantaErrorMessages').show();

        // Hacer scroll al área de errores
        $('#plantaErrorMessages')[0].scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
        return;
    }

    // Recopilar datos de todos los formularios
    let dataAdmin = $('#adminPlantaForm').getData();
    let dataTributario = $('#plantaTributarioForm').getData();
    let dataAmbiental = $('#plantaAmbientalForm').getData();

    // Combinar todos los datos
    data = $.extend({}, data, dataAdmin, dataTributario, dataAmbiental);
    data.savePlantaAjax = true;

    $.createDialogConfirm('¿Está seguro que desea guardar los datos de la planta y el personal asociado?', data, function (d) {
        $.saveDataJson('', d, function (r) {
            if (r.success) {
                $('#plantaDialog').dialog('close');
                actualizarGridPlantas();
                //$.alert('Planta y personal guardados correctamente.');
            } else {
                // Mostrar error en el área de mensajes
                $('#plantaErrorList').html('<li>' + (r.message || 'Error al guardar') + '</li>');
                $('#plantaErrorMessages').show();
                $('#plantaErrorMessages')[0].scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        });
    });
}

function anularPlanta(Pla_Cod) {
    $.createDialogConfirm('¿Está seguro que desea anular esta planta?', {
        Pla_Cod: Pla_Cod
    }, function (d) {
        $.post('', {
            anularPlantaAjax: true,
            Pla_Cod: d.Pla_Cod
        }, function (r) {
            if (r.success) {
                actualizarGridPlantas();
                // $.alert('Planta anulada correctamente.');
            } else {
                $.alert(r.message || 'Error al anular');
            }
        }, 'json');
    });
}

// ==================== GRID EMPRESAS TRANSPORTE ====================
function createGridEmpresasTransporte() {
    $('#gridEmpresasTransporte').createGrid({
        caption: 'Listado de Empresas de Transporte',
        url: window.location.href,
        height: 250,
        colModel: [
            { label: 'Código', name: 'Mat_Cod', key: true, width: 50, align: "center" },
            { label: 'Nombre', name: 'Mat_Des', width: 200 },
            { label: 'Licencia MAE', name: 'Mat_Mae', width: 120, align: "center" },
            { label: 'Teléfono', name: 'Mat_Tel', width: 100, align: "center" },
            { label: 'Plan Contingencia', name: 'Mat_Pco', width: 130, align: "center" },
            { label: 'Dirección', name: 'Mat_Dir', width: 200 },
            {
                label: 'Estado',
                name: 'Mat_Est',
                width: 60,
                align: "center",
                formatter: function (v) {
                    return v === 'A' ? '<span class="label label-success">Activo</span>' : '<span class="label label-danger">Inactivo</span>';
                }
            },
            {
                label: '<center><i class="glyphicon glyphicon-cog"></i></center>',
                name: 'acciones',
                width: 80,
                align: 'center',
                formatter: function (cellvalue, options, o) {
                    if (typeof esPerfilLectura !== 'undefined' && esPerfilLectura) return '';
                    return $.getGridButton('editarEmpresaTransporte', o, 'Editar', 'pencil', '', 'success') + '&nbsp;' +
                        $.getGridButton('anularEmpresaTransporte', o.Mat_Cod, 'Anular', 'trash', '', 'danger');
                }
            }
        ],
        rowNum: 500,
        viewrecords: true,
        postData: {
            listEmpresasTransporteGridAjax: true,
            op_opciones: 'n',
            search: ''
        }
    }, false, '#gridEmpresasTransportePager', {
        refresh: true,
        view: false
    }).gridButtonsAdd([
        {
            caption: 'Exportar Excel',
            buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                $('#gridEmpresasTransporte').jqGrid('exportGridExcel', {
                    nombre: 'Empresas_Transporte',
                    hoja: 'HOJA 1',
                    footer: true,
                    removeHiddens: true,
                    removeCols: [7] // Ocultar acciones
                });
            }
        },
        {
            caption: 'Exportar PDF',
            buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                imprimirEmpresasTransporte();
            }
        }
    ]);
}

function imprimirEmpresasTransporte() {
    $('#tablaReporteEmpresasTransporte').html($('#gridEmpresasTransporte').jqGrid('exportGridInnerHTML', {
        footer: true,
        generated: false,
        removeHiddens: true,
        removeCols: [7] // Ocultar acciones
    }));
    $('#imprimirEmpresasTransporte').printElement();
}

function actualizarGridEmpresasTransporte() {
    var op_opciones = $('input[name="op_opciones"]:checked', '#filtroEmpresasTransporteForm').val() || 'n';
    var search = $('input[name="search"]', '#filtroEmpresasTransporteForm').val() || '';
    $('#gridEmpresasTransporte').jqGrid('setGridParam', {
        postData: {
            listEmpresasTransporteGridAjax: true,
            op_opciones: op_opciones,
            search: search
        },
        page: 1
    }).trigger('reloadGrid');
}

function abrirModalEmpresaTransporte() {
    $('#empresaTransporteForm')[0].reset();
    $('#Mat_Cod').val('');
    $('#empresaTransporteDialog').dialog('open');
}

function editarEmpresaTransporte(o) {
    $('#empresaTransporteForm').setData(o);
    $('#empresaTransporteDialog').dialog('open');
}

function guardarEmpresaTransporte() {
    let data = $('#empresaTransporteForm').getData();
    data.saveEmpresaTransporteAjax = true;
    $.createDialogConfirm('¿Está seguro que desea guardar los datos?', data, function (d) {
        $.saveDataJson('', d, function (r) {
            if (r.success) {
                $('#empresaTransporteDialog').dialog('close');
                actualizarGridEmpresasTransporte();
                //$.alert('Empresa de transporte guardada correctamente.');
            } else {
                $.alert(r.message || 'Error al guardar');
            }
        });
    });
}

function anularEmpresaTransporte(Mat_Cod) {
    $.createDialogConfirm('¿Está seguro que desea anular esta empresa de transporte?', {
        Mat_Cod: Mat_Cod
    }, function (d) {
        $.post('', {
            anularEmpresaTransporteAjax: true,
            Mat_Cod: d.Mat_Cod
        }, function (r) {
            if (r.success) {
                actualizarGridEmpresasTransporte();
                // $.alert('Empresa de transporte anulada correctamente.');
            } else {
                $.alert(r.message || 'Error al anular');
            }
        }, 'json');
    });
}

// ==================== GRID CHOFERES ====================
function createGridChoferes() {
    $('#gridChoferes').createGrid({
        caption: 'Listado de Choferes',
        url: window.location.href,
        postData: {
            listChoferesGridAjax: true
        },
        height: 250,
        colModel: [{
            label: 'Código',
            name: 'Cho_Cod',
            key: true,
            width: 50,
            align: "center"
        },
        {
            label: 'Cédula',
            name: 'Prs_Ced',
            width: 80,
            align: "center"
        },
        { label: 'Planta', name: 'planta', width: 150, align: "center", hidden: false },
        {
            label: 'Nombre',
            name: 'nombre',
            width: 150
        },
        {
            label: 'Licencia',
            name: 'Cho_Tli',
            width: 60,
            align: "center"
        },
        {
            label: 'Caducidad',
            name: 'Cho_Cli',
            width: 80,
            align: "center"
        },
        {
            label: 'Teléfono',
            name: 'Cho_Tel',
            width: 80,
            align: "center"
        },
        {
            label: 'Sangre',
            name: 'Cho_Tsa',
            width: 50,
            align: "center"
        },
        {
            label: '<center><i class="glyphicon glyphicon-cog"></i></center>',
            name: 'acciones',
            width: 60,
            align: 'center',
            formatter: function (cellvalue, options, o) {
                if (typeof esPerfilLectura !== 'undefined' && esPerfilLectura) return '';
                return $.getGridButton('editarChofer', o, 'Editar', 'pencil', '', 'success') + '&nbsp;' +
                    $.getGridButton('anularChoferGrid', o.Cho_Cod, 'Anular', 'trash', '', 'danger');
            }
        }
        ],
        rowNum: 500,
        viewrecords: true,
        jsonReader: {
            root: "rows",
            page: "page",
            total: "total",
            records: "records",
            repeatitems: false
        }
    }, false, '#gridChoferesPager', {
        refresh: true,
        view: false
    }).gridButtonsAdd([
        {
            caption: 'Exportar Excel',
            buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                $('#gridChoferes').jqGrid('exportGridExcel', {
                    nombre: 'Choferes',
                    hoja: 'HOJA 1',
                    footer: true,
                    removeHiddens: true,
                    removeCols: [8] // Ocultar acciones
                });
            }
        },
        {
            caption: 'Exportar PDF',
            buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                imprimirChoferes();
            }
        }
    ]);
}

function imprimirChoferes() {
    $('#tablaReporteChoferes').html($('#gridChoferes').jqGrid('exportGridInnerHTML', {
        footer: true,
        generated: false,
        removeHiddens: true,
        removeCols: [8] // Ocultar acciones
    }));
    $('#imprimirChoferes').printElement();
}

function actualizarGridChoferes() {
    var op_opciones = $('input[name="op_opciones"]:checked', '#filtroChoferesForm').val() || 'd';
    var search = $('input[name="search"]', '#filtroChoferesForm').val() || '';
    $('#gridChoferes').jqGrid('setGridParam', {
        postData: {
            listChoferesGridAjax: true,
            op_opciones: op_opciones,
            search: search
        },
        page: 1
    }).trigger('reloadGrid');
}

// Función para cargar plantas en dropdowns
function cargarPlantasDropdown(selectId, callback) {
    $.post('', {
        listPlantasSelectAjax: true
    }, function (r) {
        if (r.success && r.plantas) {
            var select = $('#' + selectId);
            var valorActual = select.val(); // Guardar el valor actual
            select.empty();
            select.append('<option value="">Seleccione...</option>');
            $.each(r.plantas, function (i, planta) {
                select.append('<option value="' + planta.Pla_Cod + '">' + planta.Pla_Nom + '</option>');
            });
            // Restaurar el valor si existe
            if (valorActual) {
                select.val(valorActual);
            }
            // Ejecutar callback si existe
            if (callback && typeof callback === 'function') {
                callback();
            }
        }
    }, 'json');
}

function abrirModalChofer() {
    $('#choferForm')[0].reset();
    $('#Cho_Cod').val('');
    $('#Prs_Cod').val('');
    $('#choferForm #Prs_Nom, #choferForm #Prs_Ape').prop('readonly', false).css('background-color', '');
    $('#choferForm #Cho_Ced').prop('readonly', false).css('background-color', '');
    // Cargar plantas
    cargarPlantasDropdown('choferForm #Pla_Cod');
    $('#choferDialog').dialog('open');
}

function editarChofer(o) {
    // Mapear los datos del grid al formulario
    var formData = {
        Cho_Cod: o.Cho_Cod,
        Pla_Cod: o.Pla_Cod, // Código de la planta
        Prs_Cod: o.Prs_Cod, // Código de la persona
        Cho_Ced: o.Prs_Ced, // Mapear Prs_Ced a Cho_Ced para el campo de cédula
        Prs_Nom: o.Prs_Nom || '', // Nombre
        Prs_Ape: o.Prs_Ape || '', // Apellido
        Cho_Tli: o.Cho_Tli || '', // Tipo de licencia
        Cho_Cli: o.Cho_Cli || '', // Caducidad (fecha)
        Cho_Tel: o.Cho_Tel || '', // Teléfono
        Cho_Tsa: o.Cho_Tsa || '', // Tipo de sangre
        Cho_Mae: '' // Campo oculto, siempre vacío
    };
    $('#choferForm').setData(formData);

    // Edición: permitir corregir nombres; la cédula identifica a la persona y no debe cambiarse
    $('#choferForm #Prs_Nom, #choferForm #Prs_Ape').prop('readonly', false).css('background-color', '');
    if (o.Cho_Cod) {
        $('#choferForm #Cho_Ced').prop('readonly', true).css('background-color', '#eee');
    } else {
        $('#choferForm #Cho_Ced').prop('readonly', false).css('background-color', '');
    }


    // Cargar plantas y luego seleccionar la planta
    cargarPlantasDropdown('choferForm #Pla_Cod', function () {
        if (o.Pla_Cod) {
            $('#choferForm #Pla_Cod').val(o.Pla_Cod);
        }
        // Inicializar datepicker para Cho_Cli si existe
        if (o.Cho_Cli) {
            $('#Cho_Cli').val(o.Cho_Cli);
        }
        // Actualizar chosen selects
        if (o.Cho_Tli) {
            $('#Cho_Tli').val(o.Cho_Tli).trigger('chosen:updated');
        }
        if (o.Cho_Tsa) {
            $('#Cho_Tsa').val(o.Cho_Tsa).trigger('chosen:updated');
        }
        // Marcar los campos de nombre y apellido como readonly si ya existe la persona
       if (o.Prs_Cod) {
            $("#Cho_Ced_Est").removeClass().addClass("fa fa-check").css("color", "green");
        }
    });
    $('#choferDialog').dialog('open');
}


function buscarPersonaPorCedula(cedula) {
    if ($.isEmpty(cedula) || cedula.length < 10) {
        $("#Cho_Ced_Est").removeClass().addClass("fa fa-close").css("color", "orange");
        return;
    }

    // Validar formato antes de buscar
    var resultado = validaNoIdentif(cedula);
    if (!resultado.success) {
        $("#Cho_Ced_Est").removeClass().addClass("fa fa-close").css("color", "red");
        $.alert(resultado.message || 'Identificación inválida');
        return;
    }

    var plaCod = $('#choferForm #Pla_Cod').val();
    if (!plaCod || plaCod === '' || plaCod === '0' || plaCod === null || plaCod === undefined) {
        $.alert('Por favor seleccione una planta primero');
        return;
    }

    $("#Cho_Ced_Est").removeClass().addClass("fa fa-spinner fa-spin").css("color", "#337ab7");
    $.post("", {
        buscarPersonaCedulaAjax: true,
        Prs_Ced: cedula,
        Pla_Cod: plaCod
    }, function (r) {
        if (r.choferExiste) {
            // El chofer ya existe en esta planta
            // console.log('Datos recibidos:', r);
            $('#Cho_Cod').val(r.chofer.Cho_Cod);
            $('#Prs_Cod').val(r.chofer.Prs_Cod || r.persona.Prs_Cod);

            var prsNom = r.chofer.Prs_Nom || r.persona.Prs_Nom || '';
            var prsApe = r.chofer.Prs_Ape || r.persona.Prs_Ape || '';
            $('#choferForm #Prs_Nom').val(prsNom).prop('readonly', false).css('background-color', '');
            $('#choferForm #Prs_Ape').val(prsApe).prop('readonly', false).css('background-color', '');
            $('#choferForm #Cho_Ced').prop('readonly', true).css('background-color', '#eee');

            if (r.chofer.Cho_Tel) $('#Cho_Tel').val(r.chofer.Cho_Tel);
            else if (r.persona.Prs_Tel) $('#Cho_Tel').val(r.persona.Prs_Tel);
            if (r.chofer.Cho_Tli) $('#Cho_Tli').val(r.chofer.Cho_Tli).trigger('chosen:updated');
            if (r.chofer.Cho_Cli) $('#Cho_Cli').val(r.chofer.Cho_Cli);
            if (r.chofer.Cho_Tsa) $('#Cho_Tsa').val(r.chofer.Cho_Tsa).trigger('chosen:updated');
            // Cho_Mae está oculto, no se carga
            $("#Cho_Ced_Est").removeClass().addClass("fa fa-exclamation-triangle").css("color", "orange");
            var plantaNombre = $('#choferForm #Pla_Cod option:selected').text();
            $.alert('El chofer ya existe en la planta "' + plantaNombre + '". Puede modificar la información si lo desea.');
        } else if (r.existe) {
            // La persona existe pero no es chofer en esta planta
            $('#Cho_Cod').val('');
            $('#Prs_Cod').val(r.persona.Prs_Cod);
            $('#choferForm #Prs_Nom').val(r.persona.Prs_Nom).prop('readonly', false).css('background-color', '');
            $('#choferForm #Prs_Ape').val(r.persona.Prs_Ape).prop('readonly', false).css('background-color', '');
            $('#choferForm #Cho_Ced').prop('readonly', true).css('background-color', '#eee');
            if (r.persona.Prs_Tel) $('#Cho_Tel').val(r.persona.Prs_Tel);
            $("#Cho_Ced_Est").removeClass().addClass("fa fa-check").css("color", "green");
        } else {
            // La persona no existe
            $('#Cho_Cod').val('');
            $('#Prs_Cod').val('');
            $('#choferForm #Prs_Nom, #choferForm #Prs_Ape').val('').prop('readonly', false).css('background-color', '');
            $('#choferForm #Cho_Ced').prop('readonly', false).css('background-color', '');
            $("#Cho_Ced_Est").removeClass().addClass("fa fa-check").css("color", "#337ab7");
        }
    }, 'json');
}

function guardarChofer() {
    let data = $('#choferForm').getData();
    data.saveChoferAjax = true;
    data.Cli_Cod = Cli_Cod;
    $.createDialogConfirm('¿Está seguro que desea guardar los datos?', data, function (d) {
        $.saveDataJson('', d, function (r) {
            if (r.success) {
                $('#choferDialog').dialog('close');
                actualizarGridChoferes();
                // $.alert('Chofer guardado correctamente.');
            } else {
                $.alert(r.message || 'Error al guardar');
            }
        });
    });
}


function anularChoferGrid(Cho_Cod) {
    $.createDialogConfirm('¿Está seguro que desea anular este chofer?', {
        Cho_Cod: Cho_Cod
    }, function (d) {
        $.post('', {
            anularChoferAjax: true,
            Cho_Cod: d.Cho_Cod
        }, function (r) {
            if (r.success) {
                actualizarGridChoferes();
                $.alert('Chofer anulado correctamente.');
            } else {
                $.alert(r.message || 'Error al anular');
            }
        }, 'json');
    });
}

// ==================== GRID VEHICULOS ====================
function createGridVehiculos() {
    $('#gridVehiculos').createGrid({
        caption: 'Listado de Vehículos',
        url: window.location.href,
        postData: {
            listVehiculosGridAjax: true
        },
        height: 250,
        colModel: [{
            label: 'Código',
            name: 'Veh_Cod',
            key: true,
            width: 50,
            align: "center"
        },
        {
            label: 'Placa',
            name: 'Veh_Pla',
            width: 80,
            align: "center"
        },
        {
            label: 'Marca',
            name: 'Veh_Mar',
            width: 100
        },
        {
            label: 'Color',
            name: 'Veh_Col',
            width: 70,
            align: "center"
        },
        {
            label: 'Capacidad (Kg)',
            name: 'Veh_Cap',
            width: 80,
            align: "right"
        },
        {
            label: 'Tipo',
            name: 'Veh_Tit',
            width: 80,
            align: "center",
            formatter: function (v) {
                let tipos = {
                    'V': 'Volqueta',
                    'D': 'Dumper',
                    'C': 'Camión'
                };
                return tipos[v] || v;
            }
        },
        {
            label: 'Planta',
            name: 'Pla_Nom',
            width: 120
        },
        {
            label: '<center><i class="glyphicon glyphicon-cog"></i></center>',
            name: 'acciones',
            width: 60,
            align: 'center',
            formatter: function (cellvalue, options, o) {
                if (typeof esPerfilLectura !== 'undefined' && esPerfilLectura) return '';
                return $.getGridButton('editarVehiculo', o, 'Editar', 'pencil', '', 'success') + '&nbsp;' +
                    $.getGridButton('anularVehiculoGrid', o.Veh_Cod, 'Anular', 'trash', '', 'danger');
            }
        }
        ],
        rowNum: 500,
        viewrecords: true,
        jsonReader: {
            root: "rows",
            page: "page",
            total: "total",
            records: "records",
            repeatitems: false
        }
    }, false, '#gridVehiculosPager', {
        refresh: true,
        view: false
    }).gridButtonsAdd([
        {
            caption: 'Exportar Excel',
            buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                $('#gridVehiculos').jqGrid('exportGridExcel', {
                    nombre: 'Vehiculos',
                    hoja: 'HOJA 1',
                    footer: true,
                    removeHiddens: true,
                    removeCols: [7] // Ocultar acciones
                });
            }
        },
        {
            caption: 'Exportar PDF',
            buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                imprimirVehiculos();
            }
        }
    ]);
}

function imprimirVehiculos() {
    $('#tablaReporteVehiculos').html($('#gridVehiculos').jqGrid('exportGridInnerHTML', {
        footer: true,
        generated: false,
        removeHiddens: true,
        removeCols: [7] // Ocultar acciones
    }));
    $('#imprimirVehiculos').printElement();
}

function actualizarGridVehiculos() {
    var op_opciones = $('input[name="op_opciones"]:checked', '#filtroVehiculosForm').val() || 'p';
    var search = $('input[name="search"]', '#filtroVehiculosForm').val() || '';
    $('#gridVehiculos').jqGrid('setGridParam', {
        postData: {
            listVehiculosGridAjax: true,
            op_opciones: op_opciones,
            search: search
        },
        page: 1
    }).trigger('reloadGrid');
}

function abrirModalVehiculo() {
    $('#vehiculoForm')[0].reset();
    $('#Veh_Cod').val('');
    // Cargar plantas
    cargarPlantasDropdown('vehiculoForm #Pla_Cod');
    $('#vehiculoDialog').dialog('open');
}

function editarVehiculo(o) {
    $('#vehiculoForm').setData(o);
    // Cargar plantas y luego seleccionar la planta
    cargarPlantasDropdown('vehiculoForm #Pla_Cod', function () {
        if (o.Pla_Cod) {
            $('#vehiculoForm #Pla_Cod').val(o.Pla_Cod);
        }
    });
    $('#vehiculoDialog').dialog('open');
}

function guardarVehiculo() {
    // Validar placa antes de guardar
    var placa = $('#Veh_Pla').val();
    if (!validarPlacaVehiculo(placa)) {
        $('#Veh_Pla').focus();
        return false;
    }

    var capacidadRaw = String($('#Veh_Cap').val() || '').trim().replace(',', '.');
    var capacidadNum = parseFloat(capacidadRaw);
    if (isNaN(capacidadNum)) {
        $.alert('Ingrese una capacidad válida en Kg.');
        $('#Veh_Cap').focus();
        return false;
    }
    if (capacidadNum > 20000) {
        $.alert('La capacidad del vehículo no puede superar los 20000 Kg.');
        $('#Veh_Cap').focus();
        return false;
    }

    let data = $('#vehiculoForm').getData();
    data.Veh_Cap = capacidadNum;
    data.saveVehiculoAjax = true;
    data.Cli_Cod = Cli_Cod;
    $.createDialogConfirm('¿Está seguro que desea guardar los datos?', data, function (d) {
        $.saveDataJson('', d, function (r) {
            if (r.success) {
                $('#vehiculoDialog').dialog('close');
                actualizarGridVehiculos();
                // $.alert('Vehículo guardado correctamente.');
            } else {
                $.alert(r.message || 'Error al guardar');
            }
        });
    });
}

function anularVehiculoGrid(Veh_Cod) {
    $.createDialogConfirm('¿Está seguro que desea anular este vehículo?', {
        Veh_Cod: Veh_Cod
    }, function (d) {
        $.post('', {
            anularVehiculoAjax: true,
            Veh_Cod: d.Veh_Cod
        }, function (r) {
            if (r.success) {
                actualizarGridVehiculos();
                $.alert('Vehículo anulado correctamente.');
            } else {
                $.alert(r.message || 'Error al anular');
            }
        }, 'json');
    });
}

// ==================== GRID CELDAS ====================
function createGridCeldas() {
    $('#gridCeldas').createGrid({
        caption: 'Listado de Celdas Agrupadas',
        url: window.location.href,
        postData: {
            listCeldasGridAjax: true
        },
        height: 400,
        subGrid: true,
        subGridOptions: {
            "plusicon": "ui-icon-triangle-1-e",
            "minusicon": "ui-icon-triangle-1-s",
            "openicon": "ui-icon-arrowreturn-1-e",
            "reloadOnExpand": true,
            "selectOnExpand": false
        },
        subGridRowExpanded: function (subgrid_id, row_id) {
            var subgrid_table_id = subgrid_id + "_t";
            $("#" + subgrid_id).html("<table id='" + subgrid_table_id + "' class='scroll'></table>");
            $("#" + subgrid_table_id).createGrid({
                url: window.location.href,
                postData: {
                    listCeldasDetalleAjax: true,
                    grupo_cod: row_id
                },
                datatype: "json",
                height: 'auto',
                colModel: [
                    {
                        label: 'Código',
                        name: 'Cel_Cod',
                        key: true,
                        width: 60,
                        align: "center",
                        hidden: true
                    },
                    {
                        label: 'Nombre',
                        name: 'Cel_Nom',
                        width: 200,
                        align: "center"
                    },
                    {
                        label: 'Ubicación',
                        name: 'Cel_Ubi',
                        width: 200,
                        align: "center"
                    },
                    {
                        label: 'Número/Código',
                        name: 'Cel_Num',
                        width: 120,
                        align: "center"
                    },
                    {
                        label: 'Tipo',
                        name: 'Cel_Tip',
                        width: 80,
                        align: "center",
                        formatter: function (v) {
                            return '<span class="label label-info">Detalle</span>';
                        }
                    },
                    {
                        label: 'Estado',
                        name: 'Cel_Est',
                        width: 70,
                        align: "center",
                        formatter: function (v) {
                            return v === 'A' ? '<span class="label label-success">Activo</span>' : '<span class="label label-danger">Inactivo</span>';
                        }
                    },
                    {
                        label: '<center><i class="glyphicon glyphicon-cog"></i></center>',
                        name: 'acciones',
                        width: 100,
                        align: 'center',
                        formatter: function (cellvalue, options, o) {
                            if (typeof esPerfilLectura !== 'undefined' && esPerfilLectura) return '';
                            var botones = $.getGridButton('editarCelda', o, 'Editar', 'pencil', '', 'success') + '&nbsp;';
                            if (o.Cel_Est === 'A') {
                                botones += $.getGridButton('anularCeldaGrid', o.Cel_Cod, 'Anular', 'remove', '', 'warning ') + '&nbsp;';
                                botones += $.getGridButton('eliminarCeldaGrid', o.Cel_Cod, 'Eliminar', 'trash', '', 'danger');
                            } else if (o.Cel_Est === 'I') {
                                botones += $.getGridButton('activarCeldaGrid', o.Cel_Cod, 'Activar', 'ok', '', 'primary') + '&nbsp;';
                                botones += $.getGridButton('eliminarCeldaGrid', o.Cel_Cod, 'Eliminar', 'trash', '', 'danger');
                            }
                            return botones;
                        }
                    }
                ],
                rowNum: 10000,
                viewrecords: true,
                jsonReader: {
                    root: "rows",
                    page: "page",
                    total: "total",
                    records: "records",
                    repeatitems: false
                },
                loadComplete: function (data) {
                    // Aplicar estilos a las filas de detalles (sub-registros)
                    var grid = $('#' + subgrid_table_id);
                    var rowIds = grid.jqGrid('getDataIDs');

                    $.each(rowIds, function (index, rowId) {
                        var $row = $('#' + rowId, grid);
                        $row.css({
                            'background-color': '#e6f3ff'

                        });
                        $row.find('td').css({
                            'background-color': '#e6f3ff'
                        });
                        $row.find('td:first').css({

                            'padding-left': '25px'
                        });
                        // Marcar inactivas usando data crudo (getRowData devuelve HTML formateado)
                        if (data && data.rows && data.rows[index] && data.rows[index].Cel_Est === 'I') {
                            $row.addClass('fila-inactiva');
                        }
                    });
                }
            }, false);
        },
        colModel: [
            {
                label: 'Código',
                name: 'Cel_Cod',
                key: true,
                width: 60,
                align: "center",
                hidden: true,
                formatter: function (cellvalue) {
                    return '<strong style="font-size: 1.05em;">' + cellvalue + '</strong>';
                }
            },
            {
                label: 'Nombre',
                name: 'Cel_Nom',
                width: 200,
                formatter: function (cellvalue) {
                    return '<strong style="color: #333; font-size: 1.05em;">' + cellvalue + '</strong>';
                }
            },
            {
                label: 'Número/Código',
                name: 'Cel_Num',
                width: 120,
                align: "center",
                formatter: function (cellvalue) {
                    return '<span style="color: #999;">-</span>';
                }
            },
            {
                label: 'Tipo',
                name: 'Cel_Tip',
                width: 80,
                align: "center",
                formatter: function (v) {
                    return '<span class="label label-primary">Grupo</span>';
                }
            },
            {
                label: 'Estado',
                name: 'Cel_Est',
                width: 70,
                align: "center",
                formatter: function (v) {
                    return v === 'A' ? '<span class="label label-success">Activo</span>' : '<span class="label label-danger">Inactivo</span>';
                }
            },
            {
                label: '<center><i class="glyphicon glyphicon-cog"></i></center>',
                name: 'acciones',
                width: 100,
                align: 'center',
                formatter: function (cellvalue, options, o) {
                    if (typeof esPerfilLectura !== 'undefined' && esPerfilLectura) return '';
                    var botones = $.getGridButton('editarCelda', o, 'Editar', 'pencil', '', 'success') + '&nbsp;';
                    if (o.Cel_Est === 'A') {
                        botones += $.getGridButton('anularCeldaGrid', o.Cel_Cod, 'Anular', 'remove', '', 'warning ') + '&nbsp;';
                        botones += $.getGridButton('eliminarCeldaGrid', o.Cel_Cod, 'Eliminar', 'trash   ', '', 'danger');
                    } else if (o.Cel_Est === 'I') {
                        botones += $.getGridButton('activarCeldaGrid', o.Cel_Cod, 'Activar', 'ok', '', 'primary') + '&nbsp;';
                        botones += $.getGridButton('eliminarCeldaGrid', o.Cel_Cod, 'Eliminar', 'trash', '', 'danger');
                    }
                    return botones;
                }
            }
        ],
        rowNum: 10000,
        viewrecords: true,
        jsonReader: {
            root: "rows",
            page: "page",
            total: "total",
            records: "records",
            repeatitems: false
        },
        loadComplete: function (data) {
            // Aplicar estilos a las filas de grupos y marcar inactivas
            var grid = $('#gridCeldas');
            var rowIds = grid.jqGrid('getDataIDs');

            $.each(rowIds, function (index, rowId) {
                var $row = $('#' + rowId, grid);
                $row.addClass('fila-grupo');
                // Marcar inactivas usando data crudo (getRowData devuelve HTML formateado)
                if (data && data.rows && data.rows[index] && data.rows[index].Cel_Est === 'I') {
                    $row.addClass('fila-inactiva');
                }
            });
        }
    }, false, '#gridCeldasPager', {
        refresh: true,
        view: false
    }).gridButtonsAdd([
        {
            caption: 'Exportar Excel',
            buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                $('#gridCeldas').jqGrid('exportGridExcel', {
                    nombre: 'Celdas',
                    hoja: 'HOJA 1',
                    footer: true,
                    removeHiddens: true,
                    removeCols: [0, 5] // Ocultar Código (hidden) y acciones
                });
            }
        },
        {
            caption: 'Exportar PDF',
            buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                imprimirCeldas();
            }
        }
    ]);
}

function imprimirCeldas() {
    $('#tablaReporteCeldas').html($('#gridCeldas').jqGrid('exportGridInnerHTML', {
        footer: true,
        generated: false,
        removeHiddens: true,
        removeCols: [0, 5] // Ocultar Código (hidden) y acciones
    }));
    $('#imprimirCeldas').printElement();
}


// Función eliminada - ya no se usan filtros de tipo

function actualizarGridCeldas() {
    var op_opciones = $('input[name="op_opciones"]:checked', '#filtroCeldasForm').val() || 'n';
    var search = $('input[name="search"]', '#filtroCeldasForm').val() || '';

    $('#gridCeldas').jqGrid('setGridParam', {
        postData: {
            listCeldasGridAjax: true,
            op_opciones: op_opciones,
            search: search
        },
        page: 1
    }).trigger('reloadGrid');
}

// Función para cambiar tipo de celda (Grupo/Detalle)
function cambiarTipoCelda(callback) {
    var tipo = $('#Cel_Tip').val();
    // Buscar el label que está en el mismo form-group que el input Cel_Nom
    var labelNombre = $('#Cel_Nom').closest('.form-group').find('label');

    if (tipo === 'G') {
        // Si es Grupo, ocultar campos de detalle
        $('.campos-detalle').hide();
        $('#Cel_Rec, #Cel_Num, #Cel_Ubi').removeAttr('required');
        $('#Cel_Rec').val('');
        $('#Cel_Num').val('');
        $('#Cel_Ubi').val('');
        // Cambiar label a "Nombre del Grupo"
        if (labelNombre.length > 0) {
            labelNombre.text('Nombre del Grupo:');
        }
        if (callback && typeof callback === 'function') {
            callback();
        }
    } else if (tipo === 'D') {
        // Si es Detalle, mostrar campos de detalle
        $('.campos-detalle').show();
        $('#Cel_Rec, #Cel_Num, #Cel_Ubi').attr('required', 'required');
        // Cambiar label a "Nombre de la Celda"
        if (labelNombre.length > 0) {
            labelNombre.text('Nombre de la Celda:');
        }
        // Cargar grupos disponibles
        cargarGruposCeldas(callback);
    }
}

// Función para cargar grupos disponibles
function cargarGruposCeldas(callback) {
    $.post('', {
        listGruposCeldasAjax: true
    }, function (r) {
        if (r.success && r.grupos) {
            var select = $('#Cel_Rec');
            select.empty();
            select.append('<option value="">Seleccione un grupo...</option>');
            $.each(r.grupos, function (i, grupo) {
                select.append('<option value="' + grupo.Cel_Cod + '">' + grupo.Cel_Nom + '</option>');
            });
            // Ejecutar callback si existe
            if (callback && typeof callback === 'function') {
                callback();
            }
        }
    }, 'json');
}

function abrirModalCelda() {
    $('#celdaForm')[0].reset();
    $('#Cel_Cod').val('');
    $('#Cel_Tip').val('G').trigger('change'); // Por defecto Grupo
    $('#Cel_Est').val('A').prop('disabled', true); // Por defecto Activo y deshabilitado
    cambiarTipoCelda();
    $('#celdaDialog').dialog('open');
}

function editarCelda(o) {
    $('#celdaForm').setData(o);
    // Asegurar que el estado no sea editable
    $('#Cel_Est').prop('disabled', true);

    // Si es detalle, cambiar tipo (que carga grupos) y luego seleccionar el grupo
    if (o.Cel_Tip === 'D') {
        cambiarTipoCelda(function () {
            // Después de cargar los grupos, seleccionar el grupo correspondiente
            if (o.Cel_Rec) {
                $('#Cel_Rec').val(o.Cel_Rec);
            }
        });
    } else {
        // Si es grupo, solo cambiar tipo
        cambiarTipoCelda();
    }

    $('#celdaDialog').dialog('open');
}

function guardarCelda() {
    var tipo = $('#Cel_Tip').val();

    // Validar que si es Detalle, tenga grupo seleccionado
    if (tipo === 'D') {
        var grupo = $('#Cel_Rec').val();
        if (!grupo || grupo === '') {
            $.alert('Debe seleccionar un grupo para el detalle.');
            $('#Cel_Rec').focus();
            return false;
        }
    }

    let data = $('#celdaForm').getData();
    data.saveCeldaAjax = true;
    $.createDialogConfirm('¿Está seguro que desea guardar los datos?', data, function (d) {
        $.saveDataJson('', d, function (r) {
            if (r.success) {
                $('#celdaDialog').dialog('close');
                actualizarGridCeldas();
                // $.alert('Celda guardada correctamente.');
            } else {
                $.alert(r.message || 'Error al guardar');
            }
        });
    });
}

function anularCeldaGrid(Cel_Cod) {
    $.createDialogConfirm('¿Está seguro que desea anular esta celda?', {
        Cel_Cod: Cel_Cod
    }, function (d) {
        $.post('', {
            anularCeldaAjax: true,
            Cel_Cod: d.Cel_Cod
        }, function (r) {
            if (r.success) {
                actualizarGridCeldas();
                $.alert('Celda anulada correctamente.');
            } else {
                $.alert(r.message || 'Error al anular');
            }
        }, 'json');
    });
}

function activarCeldaGrid(Cel_Cod) {
    $.createDialogConfirm('¿Está seguro que desea activar esta celda?', {
        Cel_Cod: Cel_Cod
    }, function (d) {
        $.post('', {
            activarCeldaAjax: true,
            Cel_Cod: d.Cel_Cod
        }, function (r) {
            if (r.success) {
                actualizarGridCeldas();
                $.alert('Celda activada correctamente.');
            } else {
                $.alert(r.message || 'Error al activar');
            }
        }, 'json');
    });
}

function eliminarCeldaGrid(Cel_Cod) {
    $.createDialogConfirm('¿Está seguro que desea ELIMINAR esta celda? Esta acción no se puede deshacer.', {
        Cel_Cod: Cel_Cod
    }, function (d) {
        $.post('', {
            eliminarCeldaAjax: true,
            Cel_Cod: d.Cel_Cod
        }, function (r) {
            if (r.success) {
                actualizarGridCeldas();
                $.alert('Celda eliminada correctamente.');
            } else {
                $.alert(r.message || 'Error al eliminar');
            }
        }, 'json');
    });
}

// ==================== GRID SANCIONES (UNIFICADO) ====================
function getPostDataSanciones() {
    var data = { listSancionesGridAjax: true };
    data.filtro_tipo = $('#filtro_tipo_sanciones').val();
    if ($('#filtro_vigentes_sanciones').is(':checked')) data.filtro_vigentes = '1'; else data.filtro_vigentes = '';
    data.op = $('input[name="op_opciones"]:checked', '#filtroSancionesForm').val();
    var search = ($('input[name="search"]', '#filtroSancionesForm').val() || '').trim();
    if (data.op === 'i') {
        data.filtro_nombres = ''; // Limpiar filtro de nombres
        data.filtro_identificacion = search;
    } else {
        data.filtro_nombres = search;
        data.filtro_identificacion = ''; // Limpiar filtro de identificación
    }

    return data;
}
function createGridSanciones() {
    $('#gridSanciones').createGrid({
        caption: 'Sanciones (Vehículos, Choferes, Plantas)',
        url: window.location.href,
        mtype: 'POST',
        postData: getPostDataSanciones(),
        serializeGridData: function (postData) {
            return $.extend(true, {}, postData, getPostDataSanciones());
        },
        height: 280,
        colModel: [
            { label: 'Cód. Int.', name: 'Msa_Cod', key: true, width: 20, align: 'center' },
            {
                label: 'Tipo', name: 'Msa_Tip', width: 40, align: 'center',
                formatter: formatterIconoTipoSancion
            },
            { label: 'Identificacion', name: 'Prs_Ced', width: 80 },
            { label: 'Sancionado', name: 'Identificador', width: 160 },
            { label: 'Veh_Cod', name: 'Veh_Cod', width: 60, hidden: true },
            { label: 'Cho_Cod', name: 'Cho_Cod', width: 60, hidden: true },
            { label: 'Pla_Cod', name: 'Pla_Cod', width: 60, hidden: true },
            { label: 'Prs_Ced', name: 'Prs_Ced', width: 60, hidden: true },
            { label: 'Prs_Nom', name: 'Prs_Nom', width: 100, hidden: true },
            { label: 'Tipo Sanción', name: 'Tsa_Des', width: 80 },
            { label: 'Nivel', name: 'Tsa_Niv', width: 50, align: 'center' },
            { label: 'Fecha/Hora Inicio', name: 'Msa_Fei', width: 70, align: 'center' },
            { label: 'Fecha/Hora Fin', name: 'Msa_Fef', width: 70, align: 'center' },
            { label: 'Observación', name: 'Msa_Obs', width: 150 },
            {
                label: 'Acciones', name: 'act', width: 90, align: 'center', sortable: false,
                formatter: function (cellvalue, options, o) {
                    if (typeof esPerfilLectura !== 'undefined' && esPerfilLectura) return '';
                    return $.getGridButton('editarSancionPorTipo', o, 'Editar', 'pencil', '', 'success') + '&nbsp;' +
                        $.getGridButton('suspenderSancionGrid', o.Msa_Cod, 'Suspender Sancion', 'minus-sign', '', 'info') + '&nbsp;' +
                        $.getGridButton('anularSancionGrid', o.Msa_Cod, 'Anular', 'trash', '', 'danger');
                }
            }
        ],
        rowNum: 100,
        viewrecords: true,
        jsonReader: { root: 'rows', page: 'page', total: 'total', records: 'records', repeatitems: false }
    }, false, '#gridSancionesPager', { refresh: true, view: false });
}

function actualizarGridSanciones() {
    $('#gridSanciones').jqGrid('setGridParam', { postData: getPostDataSanciones(), page: 1 }).trigger('reloadGrid');
}

function formatterIconoTipoSancion(cellvalue) {
    var icono = '', titulo = '';
    if (cellvalue === 'VE') { icono = 'fa fa-car'; titulo = 'Vehículo'; }
    else if (cellvalue === 'CH') { icono = 'fa fa-user'; titulo = 'Chofer'; }
    else if (cellvalue === 'PL') { icono = 'fa fa-industry'; titulo = 'Planta'; }
    else { return cellvalue || ''; }
    return '<i class="' + icono + '" title="' + titulo + '"></i>';
}

function initSearchDialogsSanciones() {
    // Vehículo: búsqueda directa por placa (Veh_Pla) - no usa diálogo de grid

    if ($('#choferSancionDialog').length === 0) return;
    $('#choferSancionDialog').createSearchDialog({
        url: window.location.href,
        postData: { listChoferesGridAjax: true },
        postExtra: { listChoferesGridAjax: true },
        colModel: [
            { label: 'Código', name: 'Cho_Cod', key: true, width: 60, align: 'center' },
            { label: 'Cédula', name: 'Prs_Ced', width: 90 },
            { label: 'Nombre', name: 'nombre', width: 140 },
            { label: '&nbsp;', name: 'act1', width: 30, align: 'center', formatter: 'gridButton', formatoptions: { action: selectChoferSancion } }
        ],
        rowNum: 20
    }, {
        options: [
            { label: ' Cédula ', value: 'c' },
            { label: ' Apellidos ', value: 'd' }
        ],
        label: 'Buscar chofer',
        title: 'Chofer'
    }, { width: 550, height: 420, title: 'Buscar Chofer' });

    if ($('#plantaSancionDialog').length === 0) return;
    $('#plantaSancionDialog').createSearchDialog({
        url: window.location.href,
        postData: { listPlantasGridAjax: true, op_opciones: 'n' },
        postExtra: { listPlantasGridAjax: true },
        colModel: [
            { label: 'Código', name: 'Pla_Cod', key: true, width: 55, align: 'center' },
            { label: 'Nombre planta', name: 'Pla_Nom', width: 140 },
            { label: 'Cliente', name: 'Cliente', width: 150 },
            { label: '&nbsp;', name: 'act1', width: 30, align: 'center', formatter: 'gridButton', formatoptions: { action: selectPlantaSancion } }
        ],
        rowNum: 20
    }, {
        options: [
            { label: ' Nombre de planta ', value: 'n' }
        ],
        label: 'Buscar por nombre',
        title: 'Planta'
    }, { width: 580, height: 420, title: 'Buscar Planta' });
}

function buscarVehiculoPorPlacaSancion() {
    var placa = $.trim($('#search_veh_sancion').val());
    var $status = $('#sancionVeh_Veh_Pla');
    if (!placa) {
        $.alert('Ingrese el número de placa.');
        return;
    }
    $status.html('Buscando...').css('color', '#666');
    $.post('', { busqVehiculoPorPlacaAjax: true, Veh_Pla: placa }, function (r) {
        if (r.success) {
            $('#sancionVeh_Veh_Cod').val(r.Veh_Cod);
            var placaMar = (r.Veh_Pla || placa) + (r.Veh_Mar ? ' - ' + r.Veh_Mar : '');
            var cant = parseInt(r.SancionesAnio, 10) || 0;
            var anio = r.Anio || new Date().getFullYear();
            $status.text(placaMar).css('color', 'green');
            if (cant > 0) {
                $('#sancionVeh_sancionesAnio').text(cant + ' sanci\u00f3n' + (cant !== 1 ? 'es' : '') + ' en ' + anio).css('color', '#666');
            } else {
                $('#sancionVeh_sancionesAnio').text('');
            }
        } else {
            $('#sancionVeh_Veh_Cod').val('');
            $status.text(r.message || 'Vehículo no encontrado.').css('color', 'red');
            $('#sancionVeh_sancionesAnio').text('');
        }
    }, 'json').fail(function () {
        $status.text('Error al buscar.').css('color', 'red');
        $('#sancionVeh_sancionesAnio').text('');
    });
}
function selectChoferSancion(row) {
    $('#sancionCho_Cho_Cod').val(row.Cho_Cod);
    $('#sancionCho_Prs_Ced').text(row.Prs_Ced || '');
    $('#sancionCho_Prs_Nom').text((row.nombre || row.Prs_Nom || ''));
    $('#search_cho_sancion').val((row.nombre || row.Prs_Nom || ''));
    $('#sancionCho_sancionesAnio').text('');
    $('#choferSancionDialog').dialog('close');
    if (row.Cho_Cod) {
        $.post('', { getCountSancionesChoferAjax: true, Cho_Cod: row.Cho_Cod }, function (r) {
            if (r.success && r.SancionesAnio > 0) {
                $('#sancionCho_sancionesAnio').text(r.SancionesAnio + ' sanci\u00f3n' + (r.SancionesAnio !== 1 ? 'es' : '') + ' en ' + r.Anio).css('color', '#666');
            }
        }, 'json');
    }
}
function selectPlantaSancion(row) {
    $('#sancionPla_Pla_Cod').val(row.Pla_Cod);
    $('#sancionPla_Prs_Ced').text(row.Prs_Ced || '');
    $('#sancionPla_Prs_Nom').text(row.Pla_Nom || '');
    $('#search_pla_sancion').val(row.Pla_Nom || '');
    $('#sancionPla_sancionesAnio').text('');
    $('#plantaSancionDialog').dialog('close');
    if (row.Pla_Cod) {
        $.post('', { getCountSancionesPlantaAjax: true, Pla_Cod: row.Pla_Cod }, function (r) {
            if (r.success && r.SancionesAnio > 0) {
                $('#sancionPla_sancionesAnio').text(r.SancionesAnio + ' sanci\u00f3n' + (r.SancionesAnio !== 1 ? 'es' : '') + ' en ' + r.Anio).css('color', '#666');
            }
        }, 'json');
    }
}

function abrirModalSancion() {
    // Abre el tab Sanciones si no está visible; el usuario puede usar los botones Agregar de cada grid
    if ($('#tabSanciones').length && !$('#tabSanciones').hasClass('active')) {
        $('a[href="#tabSanciones"]').tab('show');
    }
}

function resetFormSancionVehiculoCampos() {
    $('#sancionVehiculoForm')[0].reset();
    $('#sancionVeh_Msa_Cod').val('');
    $('#sancionVeh_Veh_Cod').val('');
    $('#search_veh_sancion').val('');
    $('#sancionVeh_Veh_Pla').text('');
    $('#sancionVeh_sancionesAnio').text('');
}
function resetFormSancionChoferCampos() {
    $('#sancionChoferForm')[0].reset();
    $('#sancionCho_Msa_Cod').val('');
    $('#sancionCho_Cho_Cod').val('');
    $('#sancionCho_Prs_Ced').text('');
    $('#search_cho_sancion').val('');
    $('#sancionCho_sancionesAnio').text('');
}
function resetFormSancionPlantaCampos() {
    $('#sancionPlantaForm')[0].reset();
    $('#sancionPla_Msa_Cod').val('');
    $('#sancionPla_Pla_Cod').val('');
    $('#sancionPla_Prs_Ced').text('');
    $('#search_pla_sancion').val('');
    $('#sancionPla_sancionesAnio').text('');
}

/** Catálogo manifiesto_sansiones_lista (por Emp_Cod): obligatorio si BD tiene Tsa_Cod + tabla lista. */
var _sancionRequiereTipoCatalogo = false;

function cargarSelectTiposSancionLista(preselect, onDone) {
    var pre = (preselect != null && preselect !== undefined) ? String(preselect) : '';
    var $sel = $('#Tsa_Cod');
    if (!$sel.length) {
        if (typeof onDone === 'function') {
            onDone();
        }
        return;
    }
    $sel.prop('disabled', true).html('<option value="">Cargando...</option>');
    $.post('', { listTipoSancionListaAjax: true }, function (r) {
        _sancionRequiereTipoCatalogo = !!(r && r.requiereSeleccion && r.rows && r.rows.length);
        $sel.prop('disabled', false).empty().append('<option value="">— Seleccione —</option>');
        if (r && r.rows && r.rows.length) {
            $.each(r.rows, function (i, row) {
                var cod = row.Tsa_Cod != null ? String(row.Tsa_Cod) : '';
                var nom = (row.Tsa_Nom != null && String(row.Tsa_Nom) !== '') ? String(row.Tsa_Nom) : cod;
                $sel.append($('<option></option>').attr('value', cod).text(nom));
            });
        }
        if (pre) {
            $sel.val(pre);
        }
        if (typeof onDone === 'function') {
            onDone();
        }
    }, 'json').fail(function () {
        _sancionRequiereTipoCatalogo = false;
        $sel.prop('disabled', false).html('<option value="">— Error al cargar —</option>');
        if (typeof onDone === 'function') {
            onDone();
        }
    });
}

function validarTipoSancionSiAplica() {
    var $tsa = $('#Tsa_Cod');
    if (!$tsa.length) {
        return true;
    }
    var v = $.trim($tsa.val());
    if (!v) {
        $.alert('Seleccione el tipo de sanción.');
        return false;
    }
    return true;
}

/** Nueva sanción: modal único con pestañas Vehículo / Chofer / Planta. tipo: VE | CH | PL */
function abrirModalSancionUnificada(tipo) {
    tipo = (tipo || 'VE').toString().toUpperCase();
    resetFormSancionVehiculoCampos();
    resetFormSancionChoferCampos();
    resetFormSancionPlantaCampos();
    var $tsa = $('#Tsa_Cod');
    if ($tsa.length) {
        $tsa.val('').trigger('change');
    }
    if (tipo === 'CH' || tipo === 'CHO' || tipo === 'CHOFER') {
        $('a[href="#tabPaneSancionCho"]').tab('show');
    } else if (tipo === 'PL' || tipo === 'PLANTA') {
        $('a[href="#tabPaneSancionPla"]').tab('show');
    } else {
        $('a[href="#tabPaneSancionVeh"]').tab('show');
    }
    $('#sancionUnificadaDialog').dialog('open');
}

function cerrarModalSancionUnificada() {
    $('#sancionUnificadaDialog').dialog('close');
}


function setLoadingModalSancionUnificada(isLoading, text) {
    var $dlg = $('#sancionUnificadaDialog');
    if (!$dlg.length) return;

    var $uiDialog = $dlg.closest('.ui-dialog');
    var $paneButtons = $uiDialog.find('.ui-dialog-buttonpane button');
    var $inputs = $dlg.find('input, select, textarea, button');

    var overlayId = 'sancionUnificadaLoadingOverlay';
    var $overlay = $dlg.find('#' + overlayId);
    if (!$overlay.length) {
        $dlg.css('position', 'relative').append(
            '<div id="' + overlayId + '" style="' +
            'display:none; position:absolute; inset:0; z-index:9999;' +
            'background: rgba(255,255,255,0.55);' +
            'align-items:center; justify-content:center; text-align:center;' +
            '">' +
            '<div style="min-width:260px; padding:16px 20px; background:#fff; border:1px solid #cfcfcf; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.25);">' +
            '<div class="spinner-border" style="width:2rem; height:2rem; border: .28em solid #d0d0d0; border-top-color:#111; border-radius:50%; display:inline-block; animation: sancionSpin 0.8s linear infinite;"></div>' +
            '<div id="' + overlayId + '_text" style="margin-top:12px; color:#111; font-size:15px; font-weight:600;"></div>' +
            '</div>' +
            '</div>'
        );
        if (!document.getElementById('sancionUnificadaSpinKeyframes')) {
            var style = document.createElement('style');
            style.id = 'sancionUnificadaSpinKeyframes';
            style.type = 'text/css';
            style.appendChild(document.createTextNode('@keyframes sancionSpin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}'));
            document.head.appendChild(style);
        }
        $overlay = $dlg.find('#' + overlayId);
    }

    if (isLoading) {
        $dlg.find('#' + overlayId + '_text').text(text || 'Registrando...');
        $overlay.css('display', 'flex');
        $inputs.prop('disabled', true);
        $paneButtons.prop('disabled', true);
    } else {
        $overlay.hide();
        $inputs.prop('disabled', false);
        $paneButtons.prop('disabled', false);
    }
}



function toDatetimeLocal(val) {
    if (!val) return '';
    return String(val).replace(' ', 'T').substring(0, 16);
}

function editarSancionPorTipo(o) {
    var tip = (o.Msa_Tip || '').toUpperCase();
    if (tip === 'VE') {
        editarSancionVehiculo(o);
    } else if (tip === 'CH') {
        editarSancionChofer(o);
    } else if (tip === 'PL') {
        editarSancionPlanta(o);
    } else {
        $.alert('Tipo de sanción no reconocido.');
    }
}

function editarSancionVehiculo(o) {
    resetFormSancionChoferCampos();
    resetFormSancionPlantaCampos();
    var placa = o.Veh_Pla || o.Prs_Ced || o.Identificador || '';
    var marca = o.Veh_Mar || o.Identificador || '';
    var placaMar = placa + (marca && marca !== placa ? ' - ' + marca : '');
    $('#sancionVeh_Msa_Cod').val(o.Msa_Cod || '');
    $('#sancionVeh_Veh_Cod').val(o.Veh_Cod || '');
    $('#search_veh_sancion').val(placa);
    $('#sancionVeh_Veh_Pla').text(placaMar).css('color', 'green');
    $('#sancionVeh_Msa_Fei').val(toDatetimeLocal(o.Msa_Fei));
    $('#sancionVeh_Msa_Fef').val(toDatetimeLocal(o.Msa_Fef));
    $('#sancionVeh_Msa_Obs').val(o.Msa_Obs || '');
    $('a[href="#tabPaneSancionVeh"]').tab('show');
    var tsaPre = (o.Tsa_Cod != null && o.Tsa_Cod !== '') ? o.Tsa_Cod : '';
    cargarSelectTiposSancionLista(tsaPre, function () {
        $('#sancionUnificadaDialog').dialog('open');
        if (o.Veh_Cod) {
            $.post('', { getCountSancionesVehiculoAjax: true, Veh_Cod: o.Veh_Cod }, function (r) {
                if (r.success && r.SancionesAnio > 0) {
                    $('#sancionVeh_sancionesAnio').text(r.SancionesAnio + ' sanci\u00f3n' + (r.SancionesAnio !== 1 ? 'es' : '') + ' en ' + r.Anio).css('color', '#666');
                } else {
                    $('#sancionVeh_sancionesAnio').text('');
                }
            }, 'json');
        } else {
            $('#sancionVeh_sancionesAnio').text('');
        }
    });
}
function editarSancionChofer(o) {
    resetFormSancionVehiculoCampos();
    resetFormSancionPlantaCampos();
    $('#sancionCho_Msa_Cod').val(o.Msa_Cod || '');
    $('#sancionCho_Cho_Cod').val(o.Cho_Cod || '');
    $('#sancionCho_Prs_Ced').text(o.Prs_Ced || '');
    $('#search_cho_sancion').val(o.Prs_Nom || o.Identificador || (o.nombre || ''));
    $('#sancionCho_Msa_Fei').val(toDatetimeLocal(o.Msa_Fei));
    $('#sancionCho_Msa_Fef').val(toDatetimeLocal(o.Msa_Fef));
    $('#sancionCho_Msa_Obs').val(o.Msa_Obs || '');
    $('#sancionCho_sancionesAnio').text('');
    $('a[href="#tabPaneSancionCho"]').tab('show');
    var tsaPreCho = (o.Tsa_Cod != null && o.Tsa_Cod !== '') ? o.Tsa_Cod : '';
    cargarSelectTiposSancionLista(tsaPreCho, function () {
        $('#sancionUnificadaDialog').dialog('open');
        if (o.Cho_Cod) {
            $.post('', { getCountSancionesChoferAjax: true, Cho_Cod: o.Cho_Cod }, function (r) {
                if (r.success && r.SancionesAnio > 0) {
                    $('#sancionCho_sancionesAnio').text(r.SancionesAnio + ' sanci\u00f3n' + (r.SancionesAnio !== 1 ? 'es' : '') + ' en ' + r.Anio).css('color', '#666');
                }
            }, 'json');
        }
    });
}
function editarSancionPlanta(o) {
    resetFormSancionVehiculoCampos();
    resetFormSancionChoferCampos();
    $('#sancionPla_Msa_Cod').val(o.Msa_Cod || '');
    $('#sancionPla_Pla_Cod').val(o.Pla_Cod || '');
    $('#sancionPla_Prs_Ced').text(o.Prs_Ced || '');
    $('#search_pla_sancion').val(o.Prs_Nom || o.Identificador || '');
    $('#sancionPla_Msa_Fei').val(toDatetimeLocal(o.Msa_Fei));
    $('#sancionPla_Msa_Fef').val(toDatetimeLocal(o.Msa_Fef));
    $('#sancionPla_Msa_Obs').val(o.Msa_Obs || '');
    $('#sancionPla_sancionesAnio').text('');
    $('a[href="#tabPaneSancionPla"]').tab('show');
    var tsaPrePla = (o.Tsa_Cod != null && o.Tsa_Cod !== '') ? o.Tsa_Cod : '';
    cargarSelectTiposSancionLista(tsaPrePla, function () {
        $('#sancionUnificadaDialog').dialog('open');
        if (o.Pla_Cod) {
            $.post('', { getCountSancionesPlantaAjax: true, Pla_Cod: o.Pla_Cod }, function (r) {
                if (r.success && r.SancionesAnio > 0) {
                    $('#sancionPla_sancionesAnio').text(r.SancionesAnio + ' sanci\u00f3n' + (r.SancionesAnio !== 1 ? 'es' : '') + ' en ' + r.Anio).css('color', '#666');
                }
            }, 'json');
        }
    });
}

function anularSancionGrid(Msa_Cod) {
    $.createDialogConfirm('¿Desea anular esta sanción?', { anularSancionAjax: true, Msa_Cod: Msa_Cod }, function (d) {
        $.post('', { anularSancionAjax: true, Msa_Cod: d.Msa_Cod }, function (r) {
            if (r.success) {
                actualizarGridSanciones();
            } else {
                $.alert(r.message || 'Error al anular');
            }
        }, 'json');
    });
}

/** Marca la sanción como suspendida (Msa_Est = S) en manifiesto_sanciones. */
function suspenderSancionGrid(Msa_Cod) {
    $.createDialogConfirm('¿Desea <b>suspender</b> esta sanci&oacute;n?', { suspenderSancionAjax: true, Msa_Cod: Msa_Cod }, function (d) {
        $.post('', { suspenderSancionAjax: true, Msa_Cod: d.Msa_Cod }, function (r) {
            if (r.success) {
                actualizarGridSanciones();
            } else {
                $.alert(r.message || 'Error al suspender la sanción');
            }
        }, 'json');
    });
}

function abrirBusquedaChoferSancion() {
    $('#choferSancionDialog').dialog('open');
}
function abrirBusquedaPlantaSancion() {
    $('#plantaSancionDialog').dialog('open');
}

function preGuardarSancionVehiculo() {
    if (!validarTipoSancionSiAplica()) {
        return;
    }
    var Veh_Cod = $.trim($('#sancionVeh_Veh_Cod').val());
    if (!Veh_Cod || Veh_Cod === '0') {
        $.alert('Debe seleccionar un vehículo. Ingrese la placa y pulse Buscar.');
        return;
    }
    var data = {
        saveSancionVehiculoAjax: true,
        Msa_Cod: $('#sancionVeh_Msa_Cod').val(),
        Veh_Cod: Veh_Cod,
        Msa_Cho: $('#sancionMsa_Cho').val(),
        Msa_Fei: $('#sancionVeh_Msa_Fei').val(),
        Msa_Fef: $('#sancionVeh_Msa_Fef').val(),
        Msa_Obs: $('#sancionVeh_Msa_Obs').val(),
        Tsa_Cod: $.trim($('#Tsa_Cod').val())
    };
    $.createDialogConfirm('Est&aacute; seguro que desea guardar los datos?', data, saveSancionVehiculo);
}
function saveSancionVehiculo(data) {
    setLoadingModalSancionUnificada(true, 'Guardando sanción...');
    $.post('', data, function (r) {
        if (r.success) {
            actualizarGridSanciones();
            cerrarModalSancionUnificada();
        } else {
            $.alert(r.message || 'Error al guardar');
        }
    }, 'json').fail(function () {
        $.alert('Error de conexión al guardar.');
    }).always(function () {
        setLoadingModalSancionUnificada(false);
    });
}
function guardarSancionChofer() {
    if (!validarTipoSancionSiAplica()) {
        return;
    }
    var Cho_Cod = $.trim($('#sancionCho_Cho_Cod').val());
    if (!Cho_Cod || Cho_Cod === '0') {
        $.alert('Debe seleccionar un chofer. Utilice el botón de búsqueda para elegir un chofer.');
        return;
    }
    var data = {
        saveSancionChoferAjax: true,
        Msa_Cod: $('#sancionCho_Msa_Cod').val(),
        Cho_Cod: Cho_Cod,
        Msa_Fei: $('#sancionCho_Msa_Fei').val(),
        Msa_Fef: $('#sancionCho_Msa_Fef').val(),
        Msa_Obs: $('#sancionCho_Msa_Obs').val(),
        Tsa_Cod: $.trim($('#Tsa_Cod').val())
    };
    setLoadingModalSancionUnificada(true, 'Guardando sanción...');
    $.post('', data, function (r) {
        if (r.success) {
            actualizarGridSanciones();
            cerrarModalSancionUnificada();
        } else {
            $.alert(r.message || 'Error al guardar');
        }
    }, 'json').fail(function () {
        $.alert('Error de conexión al guardar.');
    }).always(function () {
        setLoadingModalSancionUnificada(false);
    });
}
function guardarSancionPlanta() {
    if (!validarTipoSancionSiAplica()) {
        return;
    }
    var Pla_Cod = $.trim($('#sancionPla_Pla_Cod').val());
    if (!Pla_Cod || Pla_Cod === '0') {
        $.alert('Debe seleccionar una planta. Utilice el botón de búsqueda para elegir una planta.');
        return;
    }
    var data = {
        saveSancionPlantaAjax: true,
        Msa_Tel: $('#sancionPla_Msa_Tel').val(),
        Msa_Cod: $('#sancionPla_Msa_Cod').val(),
        Pla_Cod: Pla_Cod,
        Msa_Fei: $('#sancionPla_Msa_Fei').val(),
        Msa_Fef: $('#sancionPla_Msa_Fef').val(),
        Msa_Obs: $('#sancionPla_Msa_Obs').val(),
        Tsa_Cod: $.trim($('#Tsa_Cod').val())
    };
    setLoadingModalSancionUnificada(true, 'Guardando sanción...');
    $.post('', data, function (r) {
        if (r.success) {
            actualizarGridSanciones();
            cerrarModalSancionUnificada();
        } else {
            $.alert(r.message || 'Error al guardar');
        }
    }, 'json').fail(function () {
        $.alert('Error de conexión al guardar.');
    }).always(function () {
        setLoadingModalSancionUnificada(false);
    });
}

