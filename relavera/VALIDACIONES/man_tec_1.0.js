// Declaracion de variables globales para asignacion de grids
var man_tec = $('#man_tecGrid');
// Variables globales para el escáner QR
var html5QrcodeScanner = null;
var isScanning = false;

/**
 * Función auxiliar para crear el HTML de una tarjeta de información (Diseño Imagen 2)
 */
function createInfoCard(barClass, icon, label, value) {
    return  '<div class="info-card-item">' +
                '<div class="info-card-bar ' + barClass + '"></div>' +
                '<div class="info-card-icon"><i class="glyphicon glyphicon-' + icon + '"></i></div>' +
                '<div class="info-card-content">' +
                    '<div class="info-card-label">' + label + '</div>' +
                    '<div class="info-card-value">' + value + '</div>' +
                '</div>' +
            '</div>';
}

/**
 * Muestra la información del registro en formato de tarjetas (Diseño Imagen 2)
 */
function showInfoRegistro(rowObject) {
    var row = $.isArray(rowObject) ? rowObject[0] : rowObject;
    if (!row) return;

    var manUsu = row.Man_Usu;
    var events = [];
    try {
        events = typeof manUsu === 'string' ? JSON.parse(manUsu) : (manUsu || []);
    } catch (e) {
        // Fallback
    }

    // Identificar eventos de Entrada (GE) y Salida (GS)
    var eventGE = null;
    var eventGS = null;
    if ($.isArray(events)) {
        var filteredGE = $.grep(events, function(e) { return e.Man_Tip === 'GE'; });
        if (filteredGE.length > 0) eventGE = filteredGE[0];
        
        var filteredGS = $.grep(events, function(e) { return e.Man_Tip === 'GS'; });
        if (filteredGS.length > 0) eventGS = filteredGS[0];
    }

    // Recolectar códigos de usuario
    var userCodes = [];
    if (eventGE && eventGE.Usu_Cod) userCodes.push(eventGE.Usu_Cod);
    if (eventGS && eventGS.Usu_Cod) userCodes.push(eventGS.Usu_Cod);
    if (row.Usu_Cod) userCodes.push(row.Usu_Cod);

    // De-duplicación (ES5)
    var uniqueCodes = [];
    for (var i = 0; i < userCodes.length; i++) {
        var c = userCodes[i];
        if (c && $.inArray(c, uniqueCodes) === -1) uniqueCodes.push(c);
    }

    // Preparar UI inicial (Cargando...)
    var initialHtml = '<div class="info-view-container">';
    
    // Fieldset 1: Datos de Entrada
    initialHtml += '<fieldset><legend><i class="glyphicon glyphicon-log-in"></i> Datos de Entrada</legend>';
    if (eventGE) {
        var fGE = eventGE.Fecha || '';
        initialHtml += createInfoCard('bar-blue', 'user', 'Guardia:', '<span id="name_GE">Cargando...</span>');
        initialHtml += createInfoCard('bar-yellow', 'calendar', 'Fecha:', fGE.substring(0, 10));
        initialHtml += createInfoCard('bar-green', 'time', 'Hora:', fGE.substring(11));
    } else {
        initialHtml += '<p class="text-muted" style="padding: 10px;">Sin registro de entrada.</p>';
    }
    initialHtml += '</fieldset>';

    // Fieldset 2: Datos de Técnico
    initialHtml += '<fieldset><legend><i class="glyphicon glyphicon-wrench"></i> Datos de Técnico</legend>';
    var fGS = (eventGS && eventGS.Fecha) ? eventGS.Fecha : (row.Mat_Fde ? row.Mat_Fde : '');
    initialHtml += createInfoCard('bar-purple', 'user', 'Técnico:', '<span id="name_GS">Cargando...</span>');
    initialHtml += createInfoCard('bar-yellow', 'calendar', 'Fecha:', fGS.substring(0, 10));
    if (fGS.length > 10) initialHtml += createInfoCard('bar-green', 'time', 'Hora:', fGS.substring(11));
    initialHtml += '</fieldset>';

    initialHtml += '</div>';

    // Insertar y abrir
    if (!$('#infoRegistroDialog').hasClass('ui-dialog-content')) {
        $('#infoRegistroDialog').createDialog({ height: 480, width: 550, icon: 'info-sign' });
    }
    $('#infoRegistroContent').html(initialHtml);
    $('#infoRegistroDialog').dialog('open');

    // Cargar nombres vía AJAX
    if (uniqueCodes.length > 0) {
        $.ajax({
            url: '../FRONT/man_tec_1.0.php',
            method: 'POST',
            data: { getUsersNamesAjax: "true", codes: uniqueCodes.join(',') },
            dataType: 'json',
            success: function(users) {
                var map = {};
                if ($.isArray(users)) {
                    $.each(users, function(i, u) { map[u.Usu_Cod] = u.Usu_Nom; });
                }
                if (eventGE && map[eventGE.Usu_Cod]) $('#name_GE').text(map[eventGE.Usu_Cod]);
                else if (eventGE) $('#name_GE').text('Usuario ' + eventGE.Usu_Cod);

                var uGS = (eventGS && eventGS.Usu_Cod) ? eventGS.Usu_Cod : row.Usu_Cod;
                if (map[uGS]) $('#name_GS').text(map[uGS]);
                else $('#name_GS').text(row.usuario || 'Técnico');
            }
        });
    }
}

$(function(){
    // rango de fechas
    desbloquear();
    $.createDateRange('#Fec_IniM', '#Fec_FinM');
    // inicializa componentes de fecha en formulario
    $('.datepicker').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
    // grid de manifiestos técnicos AMBIENTE PRINCIPAL
    man_tec.createGrid({
        url: "../FRONT/man_tec_1.0.php",
        datatype: "json",
        mtype: "GET",
        postData: {
            LoadManifTecAjax: "true",
            Fec_IniM: function () {
                return $("#Fec_IniM").val();
            },
            Fec_FinM: function () {
                return $("#Fec_FinM").val();
            },
            ordenar: function () {
                return $("#ordenar").val();
            },
            search: function () {
                return $("#searchManifestoTec input[name='search']").val();
            },
            op_opciones: function () {
                return $("#searchManifestoTec input[name='op_opciones']:checked").val();
            }
        },
        caption: 'Manifiestos Técnicos <div class="pull-right"><b>Ordenar por:</b>&nbsp;<select id="ordenar" class="form-control input-xs" style="display:inline-block; width:105px; height:22px; padding:0 5px; text-align: center;" onchange="man_tec.trigger(\'reloadGrid\');"><option value="codigo_desc">Por defecto</option><option value="manifiesto">Nº Manifiesto</option><option value="fecha_asc">Fecha ASC</option><option value="fecha_desc">Fecha DESC</option><option value="placa">Placas</option></select>&nbsp;</div>',
        rowNum: 1000,
        height: 332,
        footerrow: true,
        colModel: [
            { label: 'Cod.Int.', name: 'Mat_Cod', width: 25, align: "center", key: true },
            { label: 'Manifiesto Nº', name: 'Man_Cod', width: 60, align: "center", key: true,
                formatter: function(cellvalue, options, rowObject) {
                    if (!cellvalue) return '';
                    var plaCod = rowObject.Pla_Cod || '';
                    var manNum = rowObject.Man_Num || cellvalue;
                    var manNumStr = '' + (parseInt(manNum) || 0);
                    
                    // Rellenar con ceros si tiene menos de 4 dígitos
                    while (manNumStr.length < 4) {
                        manNumStr = '0' + manNumStr;
                    }

                    // Retornar formato: M + Pla_Cod + "-" + Man_Num
                    return 'M' + plaCod + '-' + manNumStr;
                }
            },
            { label: 'Fecha', name: 'Mat_Fde', width: 35, align: "center",
                formatter: function(cellvalue) {
                    return cellvalue ? cellvalue.substring(0, 10) : '';
                }
            },
            { label: 'H. Aprobado', name: 'Man_Usu', width: 45, align: "center",
                formatter: function(cellvalue) {
                    if (!cellvalue) return '';
                    try {
                        var events = typeof cellvalue === 'string' ? JSON.parse(cellvalue) : cellvalue;
                        if (Array.isArray(events)) {
                            // Buscar el evento con Man_Tip 'GS'
                            var gsEvent = events.find(function(e) { return e.Man_Tip === 'GS'; });
                            if (gsEvent && gsEvent.Fecha) {
                                // Retornar solo la hora (HH:mm:ss)
                                return gsEvent.Fecha.substring(11);
                            }
                        }
                    } catch (e) {
                        console.error('Error al parsear Man_Usu:', e);
                    }
                    return '';
                }
            },
            { label: 'Nivel Humedad', name: 'Hum_Des', width: 40, align: "center" , hidden: true},
            { label: 'Vehiculo', name: 'Veh_Pla', width: 40, align: "center" },
            { label: 'No. Celda', name: 'Mat_Nce', width: 50, align: "center" },
            { label: 'Cod. Celda', name: 'Mat_Cce', width: 50, align: "center" },
            { label: 'Nomb. Plataforma', name: 'Mat_Dce', width: 60, align: "center" },
            { label: 'Estado Ambiental', name: 'Mat_Eae', width: 60, align: "center",  hidden: true,
                formatter: function(cellvalue) {
                    if (cellvalue === 'A') return 'Aceptado';
                    if (cellvalue === 'R') return 'Rechazado';
                    if (cellvalue === 'AC') return 'Aceptado con Condición';
                    return cellvalue || '';
                }
            },
            { label: 'Estado Acción', name: 'Mat_Ear', width: 80, align: "center",  hidden: false,
                formatter: function(cellvalue) {
                    if (cellvalue === 'TR') return 'Transporte';
                    if (cellvalue === 'AT') return 'Almacenamiento Temporal';
                    if (cellvalue === 'EL') return 'Eliminación';
                    if (cellvalue === 'DF') return 'Disposición Final';
                    if (cellvalue === 'CT') return 'Cierre Técnico';
                    return cellvalue || '';
                }
            },
            { label: 'Observación', name: 'Mat_Oce', width: 100, align: "left", hidden: true },
            { label: 'Tratamiento', name: 'Mat_Tra', width: 80, align: "center", hidden: true,
                formatter: function(cellvalue) {
                    if (cellvalue === 'AT') return 'Almacenamiento Temporal';
                    if (cellvalue === 'DF') return 'Disposición Final';
                    return cellvalue || '';
                }
            },
            { label: 'Usuario', name: 'usuario', width: 100, align: "left" },
            { label: '<center><i class="ui-icon ui-icon-gear"></i></center>', name: 'btns_tec', width: 22, align: 'center', viewable: false,
                formatter: function (cellvalue, options, rowObject) {
                    var botones = "";
                    var parm_edit = [rowObject, options.rowId];
                    var parm_view = [rowObject, options.rowId];
                    var parm_delete = [rowObject, options.rowId];

                    if (rowObject.Man_Tip === 'GS' || rowObject.Man_Tip === 'F') {
                        // Solo dejar el botón de info (azul) para registros finalizados
                        botones += $.getGridButton(showInfoRegistro, parm_view, 'Información del registro', 'info-sign', '', 'primary');
                    } else { 
                        // Para estados activos: Modificar, Info y Eliminar
                        botones += $.getGridButton(editManifTec, parm_edit, 'Modificar manifiesto técnico', 'pencil', '', 'success') + "&nbsp;";
                        botones += $.getGridButton(showInfoRegistro, parm_view, 'Información del registro', 'info-sign', '', 'primary') + "&nbsp;";
                        botones += $.getGridButton(deleteManifTec, parm_delete, 'Eliminar Manifiesto Técnico', 'trash', '', 'danger');
                    }
                    return botones;
                }
            }
        ]
    }, false, 'man_tecGridPager', { view: false, refresh: true }).gridButtonsAdd([
        { caption: 'Exportar Excel', buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                man_tec.jqGrid('exportGridExcel', {
                    nombre: 'Manifiesto_Tecnico',
                    hoja: 'HOJA 1',
                    footer: true
                });
            }
        }
    ]);

    // inicializa el dialog de registrar manifiesto técnico
    if ($('#manifTecDialog').length === 1) {
        // Función para ajustar el ancho del modal según la orientación
        function ajustarAnchoModal() {
            var esHorizontal = window.innerWidth > window.innerHeight;
            var anchoModal = esHorizontal ? 700 : 550;
            $('#manifTecDialog').dialog('option', 'width', anchoModal);
        }
        
        // Ancho inicial según orientación
        var anchoInicial = (window.innerWidth > window.innerHeight) ? 700 : 550;
        $('#manifTecDialog').createDialog({ height: 600, width: anchoInicial, icon: 'file' });
        
        // Ajustar cuando cambia la orientación o el tamaño de la ventana
        $(window).on('resize orientationchange', function() {
            ajustarAnchoModal();
        });
        
        // Restaurar campos al cerrar el diálogo
        $('#manifTecDialog').on('dialogclose', function() {
            limpiarFormulario();
        });
    }
    
    // Grid del modal oculto de Manifiestos 
    if ($('#manifiestosDialog').length === 1) {
        $.createSearchDialog('manifiestosDialog', [
            { label: 'Cod.Int.', name: 'Man_Cod', width: 30, align: "center", key: true },
            { label: 'Pla_Cod', name: 'Pla_Cod', width: 0, hidden: true }, // Columna oculta para Pla_Cod
            { label: 'No. Manifiesto', name: 'Man_Num', width: 50, align: "center",
                formatter: function(cellvalue, options, rowObject) {
                    // if (!cellvalue) return '';
                    if (!rowObject.Pla_Cod) return cellvalue || '';
                    var plaCod = rowObject.Pla_Cod || '';
                    // var manNum = parseInt(cellvalue) || 0;
                    var manNum = rowObject.Man_Num_Raw ? parseInt(rowObject.Man_Num_Raw) : (parseInt(cellvalue) || 0);
                    // Formatear Man_Num con 4 dígitos rellenados con ceros
                    var manNumFormateado = ('0000' + manNum).slice(-4);
                    // Retornar formato: M + Pla_Cod + "-" + Man_Num (4 dígitos)
                    return 'M' + plaCod + '-' + manNumFormateado;
                }
            },
            { label: 'Fecha', name: 'Man_Fec', width: 50, align: "center" },
            { label: 'Hora', name: 'Man_Fea_Hor', width: 40, align: "center" },
            { label: 'Estado', name: 'estado', width: 70, align: "center", 
                formatter: function(val, opts, row) {
                    if (val === 'PENDIENTE') {return '<span class="">PENDIENTE</span>';
                    }else if (val === 'APROBADO') {return '<span class="badge-activo">APROBADO</span>';
                    }else if (val === 'FACTURADO') {return '<span class="badge-facturado">FACTURADO</span>';
                    }else if (val === 'GARITA IN') {return '<span class="badge-garita-in">GARITA IN</span>';
                    }else if (val === 'GARITA OUT') {return '<span class="badge-garita-out">GARITA OUT</span>';
                    }else if (val === 'RECHAZADO') {return '<span class="badge-inactivo">RECHAZADO</span>';}                    
                }
            },
            { label: 'Conductor', name: 'cliente', width: 120 },
            { label: 'Placa', name: 'Veh_Pla', width: 60 },
            { label: '&nbsp;', name: 'act1', width: 20, align: 'center', viewable: false, formatter: 'gridButton',
                formatoptions: { 
                    action: selectManifiesto,
                    conditional: function(o) { 
                        // Solo permitir seleccionar si Man_Tip es 'GE' (GARITA IN)
                        return o.Man_Tip === 'GE';
                    },
                    caseFalse: function(o) { 
                        var mensaje = '';
                        if (o.Man_Tip === 'P'){
                            mensaje = 'No se puede seleccionar un manifiesto PENDIENTE';
                        } else if (o.Man_Tip === 'A'){
                            mensaje = 'No se puede seleccionar un manifiesto APROBADO';
                        } else if (o.Man_Tip === 'F'){
                            mensaje = 'Este manifiesto ya fue FACTURADO';
                        } else if (o.Man_Tip === 'GS'){
                            mensaje = 'No se puede seleccionar un manifiesto GARITA OUT';
                        } else if (o.Man_Tip === 'R'){
                            mensaje = 'No se puede seleccionar un manifiesto RECHAZADO';
                        } else {
                            mensaje = 'Este manifiesto no está disponible para selección';
                        }
                        return '<i class="glyphicon glyphicon-lock orange" title="' + mensaje + '"></i>'; 
                    }
                } 
            }
        ], null, null, null, { headertitles: true },
        { title: 'Manifiesto', text: 'searchMan',
            options: [{label:'&nbsp;&nbsp;Placa&nbsp;&nbsp;',value:'p'},
                    {label:'&nbsp;&nbsp;Codigo QR&nbsp;&nbsp;',value:'q'}
            ]
        });
        
        // Configurar scroll y agregar select de Man_Tip cuando se abra el modal
        $('#manifiestosDialog').on('dialogopen', function() {
            var $dialog = $(this);
            var $content = $dialog.find('.ui-dialog-content');
            // Asegurar que el contenido tenga scroll
            if ($content.length) {
                $content.css({
                    'overflow-y': 'auto',
                    'overflow-x': 'auto',
                    'max-height': '80vh'
                });
            }
            
            // Expandir el input de búsqueda a col-xs-10
            var $form = $('#manifiestosForm');
            var $searchFormGroup = $form.find('fieldset .form-group').has('.input-group').first();
            if ($searchFormGroup.length > 0) {
                var $inputGroupContainer = $searchFormGroup.find('.input-group').parent();
                var currentClass = $inputGroupContainer.attr('class') || '';
                // Remover todas las clases col-xs-* y agregar col-xs-10
                $inputGroupContainer.removeClass(function(index, className) {
                    return (className.match(/(^|\s)col-xs-\S+/g) || []).join(' ');
                }).addClass('col-xs-10');
            }
            
            // Agregar select de Man_Tip al formulario si no existe
            var $form = $('#manifiestosForm');
            if ($form.find('select[name="Man_Tip"]').length === 0) {
                // Buscar el primer form-group que contiene "Filtrar Por:" (el que tiene los radios)
                var $filtroFormGroup = $form.find('fieldset .form-group').has('.radioset').first();
                
                if ($filtroFormGroup.length > 0) {
                    // Obtener el div que contiene los radios (radioset) y el radioset mismo
                    var $radiosetContainer = $filtroFormGroup.find('.radioset').parent();
                    var $radioset = $filtroFormGroup.find('.radioset');
                    var currentClass = $radiosetContainer.attr('class') || '';
                    
                    // Reducir el ancho del contenedor de radios si es necesario para dejar espacio
                    if (currentClass.indexOf('col-xs-10') !== -1) {
                        $radiosetContainer.removeClass('col-xs-10').addClass('col-xs-5');
                    } else if (currentClass.indexOf('col-xs-9') !== -1) {
                        $radiosetContainer.removeClass('col-xs-9').addClass('col-xs-5');
                    } else if (currentClass.indexOf('col-xs-8') !== -1) {
                        $radiosetContainer.removeClass('col-xs-8').addClass('col-xs-4');
                    } else if (currentClass.indexOf('col-xs-7') !== -1) {
                        $radiosetContainer.removeClass('col-xs-7').addClass('col-xs-4');
                    } else if (currentClass.indexOf('col-xs-6') !== -1) {
                        $radiosetContainer.removeClass('col-xs-6').addClass('col-xs-4');
                    }
                    
                    // Crear el select de Estado y agregarlo directamente después del radioset, dentro del mismo contenedor
                    var $estadoSelect = $('<span style="display: inline-block; margin-left: 5px; vertical-align: middle;">' +
                        '<label class="control-label label-xs" style="margin-right: 5px; display: inline-block; margin-bottom: 0; vertical-align: middle;">Estado:</label>' +
                        '<select name="Man_Tip" id="Man_Tip" class="form-control input-xs" style="display: inline-block; width: 150px; vertical-align: middle;">' +
                        '<option value="T"><< Todos >></option>' +
                        '<option value="P">PENDIENTE</option>' +
                        '<option value="A">APROBADO</option>' +
                        '<option value="F">FACTURADO</option>' +
                        '<option value="GE" selected>GARITA IN</option>' +
                        '<option value="GS">GARITA OUT</option>' +
                        '<option value="R">RECHAZADO</option>' +
                        '</select>' +
                        '</span>');
                    
                    // Insertar después del radioset, dentro del mismo contenedor
                    $radioset.after($estadoSelect);
                } else {
                    // Fallback: buscar el primer form-group y agregarlo ahí
                    var $firstFormGroup = $form.find('fieldset .form-group').first();
                    if ($firstFormGroup.length > 0) {
                        var $estadoContainer = $('<div class="col-xs-4" style="padding-left: 15px; display: inline-block;">' +
                            '<label class="control-label label-xs" style="margin-right: 5px; display: inline-block; margin-bottom: 0;">Estado:</label>' +
                            '<select name="Man_Tip" id="Man_Tip" class="form-control input-xs" style="display: inline-block; width: 150px;">' +
                            '<option value="T"><< Todos >></option>' +
                            '<option value="P">PENDIENTE</option>' +
                            '<option value="A">APROBADO</option>' +
                            '<option value="F">FACTURADO</option>' +
                            '<option value="GE" selected>GARITA IN</option>' +
                            '<option value="GS">GARITA OUT</option>' +
                            '<option value="R">RECHAZADO</option>' +
                            '</select>' +
                            '</div>');
                        $firstFormGroup.append($estadoContainer);
                    }
                }
                
                // Agregar evento change para actualizar el grid cuando cambie el select
                $form.find('select[name="Man_Tip"]').on('change', function() {
                    if ($('#manifiestosGrid').length) {
                        $.Search('manifiestos');
                    }
                });
            }
        });
    }
    
    // Manejar cambio de opción en el modal manifiestosDialog (Placa/Código QR)
    $(document).on('change', '#manifiestosForm input[name="op_opciones"]', function(){
        var opcion = $(this).val();
        // console.log('Opción cambiada en modal manifiestosDialog:', opcion);
        
        if (opcion === 'q') {
            // Opción QR seleccionada: ocultar campo de búsqueda y grid, mostrar escáner QR
            var formSearchGroup = $('#manifiestosForm .form-group-search');
            formSearchGroup.hide();
            $('#manifiestosGrid').closest('.condensed').hide();
            
            // Crear o mostrar contenedor del escáner QR si no existe
            var qrContainer = $('#qr-reader-container-modal');
            if (qrContainer.length === 0) {
                var qrHtml = '<div class="form-group" id="qr-reader-container-modal" style="padding: 15px; text-align: center; margin-top: 20px;">' +
                    '<div id="qr-reader-modal" style="width: 100%; max-width: 500px; margin: 0 auto; padding: 10px; background-color: #f9f9f9; border: 2px solid #ddd; border-radius: 5px; min-height: 250px;"></div>' +
                    '<button type="button" id="btnRescanQRModal" class="btn btn-info btn-xs btn-rescan-qr-modal" style="margin-top: 10px; display: none;">' +
                    '<span class="glyphicon glyphicon-qrcode"></span> Escanear Nuevamente' +
                    '</button>' +
                    '</div>';
                $('#manifiestosForm').append(qrHtml);
            }
            $('#qr-reader-container-modal').show();
            
            // Iniciar el escáner QR
            setTimeout(function(){
                iniciarEscannerModal();
            }, 500);
        } else {
            // Opción Placa seleccionada: ocultar escáner QR, mostrar campo de búsqueda y grid
            // Detener el escáner primero y esperar a que se detenga completamente
            if (html5QrcodeScanner && isScanning) {
                detenerEscanner();
            }
            
            // Ocultar el contenedor QR completamente
            $('#qr-reader-container-modal').hide();
            
            // Limpiar el campo de búsqueda
            $('#manifiestosForm input[name="searchMan"]').val('');
            
            // Mostrar campo de búsqueda y grid con un pequeño delay para asegurar que el escáner se detuvo
            setTimeout(function(){
                $('#manifiestosForm .form-group-search').show();
                var gridContainer = $('#manifiestosGrid').closest('.condensed');
                gridContainer.show();
                
                // Asegurarse de que el grid se muestre correctamente
                if ($('#manifiestosGrid').length) {
                    // Forzar un redibujado del grid si es necesario
                    try {
                        $('#manifiestosGrid').jqGrid('setGridWidth', gridContainer.width() - 32, true);
                    } catch(e) {
                        // Si hay error, no hacer nada
                    }
                }
                
                // Enfocar el campo de búsqueda
                $('#manifiestosForm input[name="searchMan"]').focus();
            }, 300);
        }
    });
    
    // Botón para escanear nuevamente en el modal
    $(document).on('click', '#btnRescanQRModal', function(){
        iniciarEscannerModal();
    });
            
    // Cargar niveles de humedad al iniciar
    cargarNivelesHumedad();
});

/* Imprimir comprobante */
function ImpCom(comprobante) {
    $.getDataJson('', { 'cargarReportes': true }, function (res) {
        var reportes = res['reportes'];
        $.varValid(reportes[2]) ? $.imprimirUrl(reportes[2] + '?codigo=' + comprobante.Com_Cod) : $.alert('Sin Reportes Asociados');
    }, function (err) {
        console.log(err['message']);
    });
}

// Función para ajustar las fechas según el periodo seleccionado
function desbloquear() {
    // Obtener el option seleccionado
    var seleccionado = $('#Pec_Cod option:selected');
    var valor = (seleccionado.val() || '').toString();

    // Caso "T" (Todos) o valor vacio: establecer desde el año mínimo hasta el máximo y deshabilitar edición
    if (valor === 'T' || valor === '') {
        var years = [];
        $('#Pec_Cod option').each(function() {
            var y = $(this).attr('data--year') || $(this).data('year') || $(this).data('--year');
            if (y == null) return;
            var yi = parseInt(y, 10);
            if (!isNaN(yi)) years.push(yi);
        });

        if (years.length > 0) {
            var minYear = Math.min.apply(null, years);
            var maxYear = Math.max.apply(null, years);
            $('#Fec_IniM').val(minYear + '-01-01').prop('disabled', true);
            $('#Fec_FinM').val(maxYear + '-12-31').prop('disabled', true);
        } else {
            $('#Fec_IniM, #Fec_FinM').prop('disabled', false).val('');
        }
        return;
    }

    // Caso "PF": rango del mes actual pero editable
    if (valor === 'PF') {
        var now = new Date();
        var year = now.getFullYear();
        var month = now.getMonth();
        var first = new Date(year, month, 1);
        var last = new Date(year, month + 1, 0);
        var fmt = function(d) { return d.toISOString().split('T')[0]; };
        $('#Fec_IniM').val(fmt(first)).prop('disabled', false);
        $('#Fec_FinM').val(fmt(last)).prop('disabled', false);
       // Re-inicializar datepickers y rango de fechas cuando se habilitan
        $.createDateRange('#Fec_IniM', '#Fec_FinM');
        $('#Fec_IniM, #Fec_FinM').createDatePickers({ checkAvailability: true, hideMsg: false }).mask('9999-99-99', { placeholder: '_' });
        return;
    }

    // Caso específico: usar data-inicio / data-fin del option
    var inicio = seleccionado.data('inicio') || seleccionado.attr('data-inicio') || '';
    var fin = seleccionado.data('fin') || seleccionado.attr('data-fin') || '';
    $('#Fec_IniM').val(inicio).prop('disabled', false);
    $('#Fec_FinM').val(fin).prop('disabled', false);
}

// Cargar niveles de humedad en el select
function cargarNivelesHumedad(callback) {
    $.ajax({
        url: '../FRONT/man_tec_1.0.php',
        type: 'POST',
        data: { loadHumedadAjax: true },
        dataType: 'json',
        success: function(resp) {
            if (resp && resp.length > 0) {
                $('#Hum_Cod').empty();
                $('#Hum_Cod').append('<option value="">-- Seleccione --</option>');
                $.each(resp, function(index, item) {
                    $('#Hum_Cod').append('<option value="' + item.Hum_Cod + '">' + item.Hum_Des + ' - ' + item.Hum_Rie + '</option>');
                });
            }
            // Ejecutar callback si existe
            if (typeof callback === 'function') {
                callback();
            }
        },
        error: function() {
            $.alert('Error al cargar los niveles de humedad');
            // Ejecutar callback incluso si hay error
            if (typeof callback === 'function') {
                callback();
            }
        }
    });
}

// Cargar el siguiente código de manifiesto
function cargarSiguienteManifiesto() {
    $.ajax({
        url: '../FRONT/man_tec_1.0.php',
        type: 'POST',
        data: { getLastManAjax: true },
        dataType: 'json',
        success: function(resp) {
            if (resp && resp.success) {
                $('#Man_Cod').val(resp.data);
            }
        },
        error: function() {
            console.log('Error al cargar el siguiente código de manifiesto');
        }
    });
}

// Limpiar formulario
function limpiarFormulario() {
    $('#manifTecForm')[0].reset();
    $('#manifTecForm #Mat_Cod').val('');
    $('#manifTecForm #Man_Cod').val('');

    // Restaurar todos los campos (quitar readonly y habilitar)
    $("#manifTecForm input, #manifTecForm textarea").prop('readonly', false);
    $("#manifTecForm select").prop('disabled', false);
    $("#manifTecForm #btnBusMan").show(); // Mostrar botón de búsqueda
    // Mostrar botón de guardar
    $("#manifTecForm").find('a[onclick*="GuardarManifTec"]').show();
    // Restaurar título del diálogo
    $('#manifTecDialog').dialog('option', 'title', 'Manifiesto Técnico');

    cargarNivelesHumedad();
}

// Abrir modal para nuevo registro
function abrirModalManifTec() {
    limpiarFormulario();
    
    // Establecer fecha actual
    var hoy = new Date();
    var fechaFormateada = hoy.getFullYear() + '-' + 
                          String(hoy.getMonth() + 1).padStart(2, '0') + '-' + 
                          String(hoy.getDate()).padStart(2, '0');
    $('#Mat_Fde').val(fechaFormateada);
    
    $('#manifTecDialog').dialog('open');
}

// Guardar manifiesto técnico
function GuardarManifTec() {
    // Validar campos requeridos
    if (!$('#Hum_Cod').val() || $('#Hum_Cod').val() === '') {
        $.alert('Debe seleccionar un Nivel de Humedad');
        return;
    }
    
    if (!$('#Mat_Eae').val() || $('#Mat_Eae').val() === '') {
        $.alert('Debe seleccionar un Estado Ambiental');
        return;
    }
    
    if (!$('#Mat_Ear').val() || $('#Mat_Ear').val() === '') {
        $.alert('Debe seleccionar un Estado de Acción');
        return;
    }
    
    var amaCod = $("#manifTecForm #Mat_Cod").val();
    var esEdicion = amaCod && amaCod.trim() !== '';
    
    // Recolectar datos desde el formulario
    var data = {
        saveManiTecAjax: true,
        Mat_Cod: amaCod,
        Man_Cod: $("#manifTecForm #Man_Cod").val(),
        Hum_Cod: $("#manifTecForm #Hum_Cod").val(),
        Mat_Rso: $("#manifTecForm #Mat_Rso").val(),
        Mat_Dna: $("#manifTecForm #Mat_Dna").val(),
        Mat_Fde: $("#manifTecForm #Mat_Fde").val(),
        Mat_Eae: $("#manifTecForm #Mat_Eae").val(),
        Mat_Ear: $("#manifTecForm #Mat_Ear").val(),
        Mat_Oce: $("#manifTecForm #Mat_Oce").val(),
        Mat_Tra: $("#manifTecForm #Mat_Tra").val()
    };
    
    var mensajeConfirmacion = esEdicion 
        ? '¿Está seguro que desea actualizar el manifiesto técnico?' 
        : '¿Está seguro que desea registrar el manifiesto técnico?';
    
    var mensajeExito = esEdicion 
        ? '¡Manifiesto técnico actualizado correctamente!' 
        : '¡Manifiesto técnico registrado correctamente!';
    
    $.createDialogConfirm(mensajeConfirmacion, data, function (d) {
        $.ajax({
            url: '../FRONT/man_tec_1.0.php',
            method: 'POST',
            data: d,
            dataType: 'json',
            success: function(response) {
                if(response.success){
                    $.alert(mensajeExito);
                    limpiarFormulario();
                    $("#manifTecDialog").dialog('close');
                    setTimeout(function() {
                        $('#man_tecGrid').jqGrid('setGridParam', {page: 1}).trigger('reloadGrid');
                    }, 100);
                } else {
                    $.alert(response.message || 'Error al guardar');
                }
            },
            error: function() {
                $.alert("Ocurrió un error en la petición");
            }
        });
    });
}

// Editar manifiesto técnico
function editManifTec(rowObject) {
    var row = Array.isArray(rowObject) ? rowObject[0] : rowObject;
    
    if (!row || !row.Mat_Cod) {
        $.alert("No se encuentra el código del manifiesto técnico.");
        return;
    }
    
    // Limpia el formulario
    $('#manifTecForm')[0].reset();
    
    // Hacer AJAX para obtener los datos completos
    $.ajax({
        url: '../FRONT/man_tec_1.0.php',
        method: 'GET',
        data: { getManifTecAjax: true, Mat_Cod: row.Mat_Cod },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                var data = response.data;
                
                // Guardar el código
                $("#manifTecForm #Mat_Cod").val(data.Mat_Cod || '');
                
                // Llenar campos (excepto Hum_Cod que se establecerá después de cargar las opciones)
                $("#manifTecForm #Man_Cod").val(data.Man_Cod || '');
                // $("#manifTecForm #Hum_Cod").val(data.Hum_Cod || '');
                $("#manifTecForm #Mat_Rso").val(data.Mat_Rso || '');
                $("#manifTecForm #Mat_Dna").val(data.Mat_Dna || '');
                $("#manifTecForm #Mat_Fde").val(data.Mat_Fde || '');
                // Mapear campos de celdas desde la consulta SQL
                $("#manifTecForm #Mat_Nce").val(data.Cel_Num || '');
                $("#manifTecForm #Mat_Cce").val(data.Cel_Nom || '');
                $("#manifTecForm #Mat_Dce").val(data.Cel_Nom_Grupo || '');
                $("#manifTecForm #Mat_Eae").val(data.Mat_Eae || '');
                $("#manifTecForm #Mat_Ear").val(data.Mat_Ear || '');
                $("#manifTecForm #Mat_Oce").val(data.Mat_Oce || '');
                $("#manifTecForm #Mat_Tra").val(data.Mat_Tra || '');
                
                // Guardar el Hum_Cod para establecerlo después
                var humCodValue = data.Hum_Cod || '';
                
                // Recargar niveles de humedad y establecer el valor cuando termine
                cargarNivelesHumedad(function() {
                    // Establecer el valor de Hum_Cod después de que se carguen las opciones
                    if (humCodValue) {
                        $("#manifTecForm #Hum_Cod").val(humCodValue);
                    }
                });

                // Esperar a que se cargue el select y luego seleccionar los valores correctos
                // setTimeout(function() {
                //     $("#manifTecForm #Hum_Cod").val(data.Hum_Cod || '');
                //     $("#manifTecForm #Man_Cod").val(data.Man_Cod || '');
                //     $("#manifTecForm #Mat_Tra").val(data.Mat_Tra || '');
                // }, 300);
                
                // Mostrar el modal
                $('#manifTecDialog').dialog('open');
            } else {
                $.alert(response.message || 'No se pudieron cargar los datos del manifiesto técnico');
            }
        },
        error: function() {
            $.alert('Ocurrió un error al cargar los datos del manifiesto técnico');
        }
    });
}

// Ver manifiesto técnico (solo lectura)
function viewManifTec(rowObject) {
    var row = Array.isArray(rowObject) ? rowObject[0] : rowObject;
    
    if (!row || !row.Mat_Cod) {
        $.alert("No se encuentra el código del manifiesto técnico.");
        return;
    }
    
    // Limpia el formulario
    $('#manifTecForm')[0].reset();
    
    // Hacer AJAX para obtener los datos completos
    $.ajax({
        url: '../FRONT/man_tec_1.0.php',
        method: 'GET',
        data: { getManifTecAjax: true, Mat_Cod: row.Mat_Cod },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                var data = response.data;
                
                // Guardar el código
                $("#manifTecForm #Mat_Cod").val(data.Mat_Cod || '');
                
                // Llenar campos (excepto Hum_Cod que se establecerá después de cargar las opciones)
                $("#manifTecForm #Man_Cod").val(data.Man_Cod || '');
                $("#manifTecForm #Mat_Rso").val(data.Mat_Rso || '');
                $("#manifTecForm #Mat_Dna").val(data.Mat_Dna || '');
                $("#manifTecForm #Mat_Fde").val(data.Mat_Fde || '');
                // Mapear campos de celdas desde la consulta SQL
                $("#manifTecForm #Mat_Nce").val(data.Cel_Num || '');
                $("#manifTecForm #Mat_Cce").val(data.Cel_Nom || '');
                $("#manifTecForm #Mat_Dce").val(data.Cel_Nom_Grupo || '');
                $("#manifTecForm #Mat_Eae").val(data.Mat_Eae || '');
                $("#manifTecForm #Mat_Ear").val(data.Mat_Ear || '');
                $("#manifTecForm #Mat_Oce").val(data.Mat_Oce || '');
                $("#manifTecForm #Mat_Tra").val(data.Mat_Tra || '');
                
                // Guardar el Hum_Cod para establecerlo después
                var humCodValue = data.Hum_Cod || '';
                
                // Recargar niveles de humedad y establecer el valor cuando termine
                cargarNivelesHumedad(function() {
                    // Establecer el valor de Hum_Cod después de que se carguen las opciones
                    if (humCodValue) {
                        $("#manifTecForm #Hum_Cod").val(humCodValue);
                    }
                });
                
                // Establecer todos los campos como readonly (ya tienen readonly en el HTML, no necesita disabled)
                $("#manifTecForm input, #manifTecForm select, #manifTecForm textarea").prop('readonly', true);
                $("#manifTecForm select").prop('disabled', true); // Los select necesitan disabled porque readonly no funciona en ellos
                $("#manifTecForm #btnBusMan").hide(); // Ocultar botón de búsqueda en lugar de deshabilitarlo
                
                // Ocultar botón de guardar y mostrar solo botón de cerrar
                $("#manifTecForm").find('a[onclick*="GuardarManifTec"]').hide();
                
                // Cambiar el título del diálogo
                $('#manifTecDialog').dialog('option', 'title', 'Vista Previa - Manifiesto Técnico');
                
                // Mostrar el modal
                $('#manifTecDialog').dialog('open');
            } else {
                $.alert(response.message || 'No se pudieron cargar los datos del manifiesto técnico');
            }
        },
        error: function() {
            $.alert('Ocurrió un error al cargar los datos del manifiesto técnico');
        }
    });
}

// Seleccionar manifiesto del modal de búsqueda
function selectManifiesto(manifiesto) {
    // Si el parámetro viene como array, obtener el primer elemento (objeto de la fila)
    var rowData = Array.isArray(manifiesto) ? manifiesto[0] : manifiesto;

    // Validar que el estado no sea 'P' (PENDIENTE)
    var manTip = rowData.Man_Tip || '';
    if (manTip === 'P') {
        $.alert('No se puede seleccionar un manifiesto con estado PENDIENTE. Solo se permiten manifiestos aprobados, facturados o con garita.');
        return;
    }

    if (manTip === 'F') {
        $.alert('Este manifiesto ya fue facturado y no se puede seleccionar.');
        return;
    }

    if (manTip === 'APROBADO'){
        $.alert('No se puede seleccionar un manifiesto con estado APROBADO.');
        return false;
    }
    
    // Llenar el campo Man_Cod con el código seleccionado
    $("#manifTecForm #Man_Cod").val(rowData.Man_Cod || '');

    // Cargar datos de la celda automáticamente
    // Cel_Num -> Mat_Nce (No. Celda)
    $("#manifTecForm #Mat_Nce").val(rowData.Cel_Nom || '');
    
    // Cel_Nom -> Mat_Cce (Código Celda)
    $("#manifTecForm #Mat_Cce").val(rowData.Cel_Num || '');
    
    // Cel_Nom_Grupo (del registro padre referenciado por Cel_Rec) -> Mat_Dce (Descripción Celda)
    $("#manifTecForm #Mat_Dce").val(rowData.Cel_Nom_Grupo || '');
    
    // Cerrar el diálogo de búsqueda
    $('#manifiestosDialog').dialog('close');
}

// Eliminar manifiesto técnico
function deleteManifTec(row) {
    var rowObject = Array.isArray(row) ? row[0] : row;
    
    if (!rowObject || !rowObject.Mat_Cod) {
        $.alert('No se encuentra el código del manifiesto técnico.');
        return;
    }
    
    $.createDialogConfirm(
        '¿Está seguro que desea eliminar este manifiesto técnico? Esta acción no se puede deshacer.',
        rowObject,
        function(confirmedRow) {
            $.ajax({
                url: '../FRONT/man_tec_1.0.php',
                method: 'POST',
                data: {
                    deleteManiTecAjax: true,
                    Mat_Cod: confirmedRow.Mat_Cod
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $.alert('¡Manifiesto técnico eliminado correctamente!');
                        $('#man_tecGrid').trigger('reloadGrid');
                    } else {
                        $.alert(response.message || 'Error al eliminar');
                    }
                },
                error: function() {
                    $.alert('Ocurrió un error al eliminar el manifiesto técnico');
                }
            });
        }
    );
}

/* Inicia el escáner QR en el modal */
function iniciarEscannerModal() {
    // console.log('iniciarEscannerModal llamado');
    
    // Verificar que la librería Html5Qrcode esté disponible
    if (typeof Html5Qrcode === 'undefined') {
        console.error('La librería Html5Qrcode no está cargada');
        var alertMsg = 'Error: La librería de escáner QR no está disponible. Por favor, recarga la página.';
        if (typeof $.alert !== 'undefined') {
            $.alert(alertMsg);
        } else {
            alert(alertMsg);
        }
        return;
    }
    
    // Verificar que el contenedor exista
    if ($('#qr-reader-modal').length === 0) {
        console.error('El contenedor #qr-reader-modal no existe');
        return;
    }
    
    // Detener cualquier escáner previo
    if (html5QrcodeScanner && isScanning) {
        // console.log('Deteniendo escáner previo...');
        detenerEscanner();
    }
    
    // Limpiar el contenedor antes de inicializar
    $('#qr-reader-modal').empty();
    
    // Inicializar el scanner
    try {
        if (!html5QrcodeScanner) {
            // console.log('Inicializando nuevo scanner...');
            html5QrcodeScanner = new Html5Qrcode("qr-reader-modal");
        }
        
        // Iniciar el escaneo
        if (!isScanning) {
            // console.log('Iniciando escaneo en modal...');
            html5QrcodeScanner.start(
                { facingMode: "environment" }, // Cámara trasera
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                },
                function(decodedText, decodedResult) {
                    // Cuando se detecta un código QR
                    // console.log('QR ESCANEADO:', decodedText);
                    
                    detenerEscanner();
                    
                    // Ocultar el botón de escanear nuevamente
                    $('#btnRescanQRModal').hide();
                    
                    // Buscar el manifiesto automáticamente con el código escaneado
                    buscarManifiestoPorQR(decodedText);
                },
                function(errorMessage) {
                    // Error al escanear (se puede ignorar para no saturar la consola)
                    // console.log(errorMessage);
                }
            ).then(function() {
                // console.log('Escáner iniciado correctamente en modal');
                isScanning = true;
            }).catch(function(err) {
                console.error('Error al iniciar el escáner:', err);
                var errorMsg = 'Error al iniciar la cámara: ' + err;
                if (typeof $.alert !== 'undefined') {
                    $.alert(errorMsg);
                } else {
                    alert(errorMsg);
                }
                detenerEscanner();
            });
        }
    } catch (error) {
        console.error('Error al inicializar el escáner:', error);
        var errorMsg = 'Error al inicializar el escáner QR: ' + error.message;
        if (typeof $.alert !== 'undefined') {
            $.alert(errorMsg);
        } else {
            alert(errorMsg);
        }
    }
}

/* Detiene el escáner QR */
function detenerEscanner() {
    if (html5QrcodeScanner && isScanning) {
        try {
            html5QrcodeScanner.stop().then(function() {
                isScanning = false;
                if ($('#qr-reader-modal').length) {
                    $('#qr-reader-modal').empty();
                }
                // Limpiar también el scanner para evitar problemas
                html5QrcodeScanner = null;
            }).catch(function(err) {
                // console.log('Error al detener el escáner: ' + err);
                isScanning = false;
                if ($('#qr-reader-modal').length) {
                    $('#qr-reader-modal').empty();
                }
                // Limpiar el scanner incluso si hay error
                html5QrcodeScanner = null;
            });
        } catch(e) {
            // Si hay un error al intentar detener, forzar el estado
            isScanning = false;
            if ($('#qr-reader-modal').length) {
                $('#qr-reader-modal').empty();
            }
            html5QrcodeScanner = null;
        }
    } else {
        if ($('#qr-reader-modal').length) {
            $('#qr-reader-modal').empty();
        }
        // Asegurarse de que el estado esté limpio
        isScanning = false;
        html5QrcodeScanner = null;
    }
}

/* Busca un manifiesto por código QR desde el modal */
function buscarManifiestoPorQR(codigo_qr) {
    $.ajax({
        url: '../FRONT/man_tec_1.0.php',
        type: 'GET',
        data: {
            buscarPorQRAjax: true,
            codigo_qr: codigo_qr
        },
        dataType: 'json',
        success: function(resp) {
            if (resp.success && resp.data) {
                // Si el manifiesto se encuentra, seleccionarlo automáticamente
                var manCod = resp.data.Man_Cod;
                
                if (manCod) {
                    // Verificar que el estado permita seleccionar
                    if (resp.data.Man_Tip === 'P') {
                        $.alert('No se puede seleccionar un manifiesto con estado PENDIENTE. Solo se permiten manifiestos aprobados, facturados o con garita.');
                        $('#btnRescanQRModal').show();
                        return;
                    }

                    if (resp.data.Man_Tip === 'F') {
                        $.alert('Este manifiesto ya fue facturado y no se puede seleccionar.');
                        $('#btnRescanQRModal').show();
                        return;
                    }
                    
                    // Seleccionar el manifiesto automáticamente usando la función existente
                    selectManifiesto(resp.data);
                } else {
                    $.alert('No se encontró el código del manifiesto en el QR escaneado');
                    $('#btnRescanQRModal').show();
                }
            } else {
                $.alert(resp.message || 'No existe el manifiesto buscado');
                // Mostrar botón para volver a escanear
                $('#btnRescanQRModal').show();
            }
        },
        error: function(xhr, status, error) {
            $.alert('Error al realizar la búsqueda: ' + (error || 'Error desconocido'));
            // Mostrar botón para volver a escanear
            $('#btnRescanQRModal').show();
        }
    });
}

// Evento para cambiar el placeholder según el filtro seleccionado
$(function() {
    // Al cambiar la opción de radio, cambiar el placeholder del input de búsqueda
    // Solo aplicar a la sección de búsqueda principal (opt_search)
    $(document).on('change', '.opt_search input[name="op_opciones"]', function() {
        var op = $(this).val();
        var placeholder = 'Ingrese búsqueda...';
        if (op === 'u') {
            placeholder = 'Buscar por nombre de usuario...';
        } else if (op === 'n') {
            placeholder = 'Ej: M12-0012';
        } else if (op === 'p') {
            placeholder = 'Buscar por placa...';
        }
        $('input[name="search"]').attr('placeholder', placeholder);
    });
    
    // Disparar el cambio inicial específicamente para la opción u (Usuario) marcada por defecto
    setTimeout(function(){
        $('.opt_search input[name="op_opciones"]:checked').trigger('change');
    }, 300);
});