var html5QrcodeScanner = null;
var isScanning = false;

/* Función para cargar manifiestos técnicos en formato HTML (Cards) */
function loadManifiestosTecnicosHTML() {
    var formData = $('#searchManifestoTec').serializeArray();
    
    // Check if filter is 'm' (Manifiesto)
    var filtro = $('input[name="op_opciones"]:checked').val();
    
    if (filtro === 'm') {
        var plaCod = $('#searchPlaCod').val();
        var manNum = $('#searchManNum').val();
        
        // Add specific parameters
        formData.push({name: 'Pla_Cod', value: plaCod});
        formData.push({name: 'Man_Num', value: manNum});
    } else if (filtro === 'q') {
        var searchQR = $('#searchQR').val();
        // Override search param with QR code
        for (var i = 0; i < formData.length; i++) {
            if (formData[i].name === 'search') {
                formData[i].value = searchQR;
                break;
            }
        }
    }

    var dataStr = $.param(formData);
    dataStr += '&LoadManifTecAjax=true';
    
    $.ajax({
        url: 'man_tec_camp_1.0.php',
        data: dataStr,
        dataType: 'json',
        success: function(response) {
            var container = $('#man_tec_container');
            container.empty();
            
            if (response && response.length > 0) {
                $.each(response, function(i, item) {
                    var manCod = parseInt(item.Man_Num || item.Man_Cod || 0);
                    var manCodFormateado = String(manCod).length < 4 ? String(manCod).padStart(6, '0') : String(manCod);
                    var plaCod = item.Pla_Cod || '';
                    var fullCode = 'M' + plaCod + '-' + manCodFormateado;
                    
                    var estado = item.Man_Tip || '';
                    var estadoLabel = estado;
                    var badgeClass = 'label-default';
                    
                    if (estado === 'P') { estadoLabel = 'PENDIENTE'; badgeClass = 'label-warning'; }
                    else if (estado === 'A') { estadoLabel = 'APROBADO'; badgeClass = 'label-success'; }
                    else if (estado === 'F') { estadoLabel = 'FACTURADO'; badgeClass = 'label-primary'; }
                    else if (estado === 'GE') { estadoLabel = 'GARITA IN'; badgeClass = 'label-info'; }
                    else if (estado === 'GS') { estadoLabel = 'GARITA OUT'; badgeClass = 'label-danger'; }
                    else if (estado === 'R') { estadoLabel = 'RECHAZADO'; badgeClass = 'label-danger'; }
                    
                    var cardId = 'man_card_' + i;
                    var index = i + 1;
                    
                    var card = $('<div class="col-xs-12 col-sm-6 col-md-3">' +
                        '<div class="panel panel-default" id="' + cardId + '" style="margin-bottom: 10px;">' +
                            '<div class="panel-heading" style="padding: 8px 10px;">' +
                                '<div class="row" style="margin:0;">' +
                                    '<div class="col-xs-6" style="padding:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="N° ' + index + ' | Manifiesto: ' + fullCode + '">' +
                                        '<span style="font-size: 16px; font-weight: bold;">N° ' + index + ' | ' + fullCode + '</span>' +
                                    '</div>' +
                                    '<div class="col-xs-6" style="padding:0; text-align:right;">' +
                                        '<span class="label ' + badgeClass + '" style="font-size: 16px !important;">' + estadoLabel + '</span>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="panel-body" style="padding: 5px;">' +
                                '<div class="row" style="margin: 0; display: flex; align-items: center;">' +
                                    '<div class="col-xs-10" style="padding-left: 5px;">' +
                                        '<p style="margin-bottom: 2px; font-size: 16px;"><strong>Chofer:</strong> ' + (item.chofer_nombre || '') + '</p>' +
                                        '<p style="margin-bottom: 2px; font-size: 16px;"><strong>Placa:</strong> ' + (item.Veh_Pla || '') + '</p>' +
                                        '<p style="margin-bottom: 0px; font-size: 16px;"><strong>Fecha:</strong> ' + ((item.Mat_Fde || item.Man_Fec || '').substring(0, 10)) + '</p>' +
                                    '</div>' +
                                    '<div class="col-xs-2 btn-group-action mobile-actions" style="padding: 0; padding-right: 5px; text-align: center;">' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' + 
                    '<style>' +
                    '@media (max-width: 768px) {' +
                    '   .mobile-actions .btn { font-size: 12px; padding: 5px; }' +
                    '}' +
                    '</style>');
                    
                    card.data('row', item);
                    
                    var btnGroup = card.find('.btn-group-action');
                    
                    // Botón Ver (Solo para A, GS, R, F)
                    if (estado === 'A' || estado === 'GS' || estado === 'R' || estado === 'F') {
                        var btnView = $('<button type="button" class="btn btn-info btn-sm" title="Ver" style="margin-bottom: 5px; padding: 4px 8px;"><span class="glyphicon glyphicon-eye-open"></span></button>');
                        btnView.click(function() { viewManifTec(item, item.Mat_Cod); });
                        btnGroup.append(btnView);
                    }

                    // Botón Editar (Solo para GE)
                    if (estado === 'GE') {
                        var btnEdit = $('<button type="button" class="btn btn-success btn-sm" title="Editar" style="margin-bottom: 5px; padding: 4px 8px;"><span class="glyphicon glyphicon-pencil"></span></button>');
                        btnEdit.click(function() { editManifTec(item, item.Mat_Cod); });
                        btnGroup.append(btnEdit);
                    }
                    
                    container.append(card);
                });
            } else {
                container.html('<div class="col-xs-12"><div class="alert alert-info">No se encontraron resultados</div></div>');
            }
        },
        error: function() {
            $('#man_tec_container').html('<div class="col-xs-12"><div class="alert alert-danger">Error al cargar datos</div></div>');
        }
    });
}

function viewManifTec(item, matCod) {
    $('#documentoSearch').hide();
    $('#loader').show();
    $('#manifTecForm')[0].reset();
    
    function setReadOnlyMode() {
        $('#manifTecForm input, #manifTecForm textarea').prop('readonly', true);
        $('#manifTecForm select').prop('disabled', true);
        $('#manifTecForm a[onclick*="Guardar"]').hide();
        $('#manifTecForm a[onclick*="close"]').attr('onclick', 'cancelarEdicion()');
    }

    function populateFromItem() {
        var manNum = parseInt(item.Man_Num || 0);
        var manNumFormatted = String(manNum).length < 4 ? String(manNum).padStart(6, '0') : String(manNum);
        var plaCod = item.Pla_Cod;
        $('#Man_Cod').val('M' + plaCod + '-' + manNumFormatted);
        
        $('#Mat_Cce').val(item.Cel_Num);
        $('#Mat_Nce').val(item.Cel_Nom);
        $('#Mat_Dce').val(item.Cel_Nom_Grupo || item.Cel_Nom);
        
        var today = new Date().toISOString().split('T')[0];
        $('#Mat_Fde').val(today);
        
        setReadOnlyMode();
    }

    if (matCod) {
        $.ajax({
            url: 'man_tec_camp_1.0.php',
            data: { getManifTecAjax: true, Mat_Cod: matCod },
            dataType: 'json',
            success: function(response) {
                $('#loader').fadeOut("slow");
                $('#manifTecDialog').show().removeClass('hidden');
                
                if (response.success) {
                    var data = response.data;
                    populateForm(data);
                    
                    if (data.Mat_Fde) {
                        $('#Mat_Fde').val(data.Mat_Fde.substring(0, 10));
                    }

                    var manNum = parseInt(data.Man_Num || item.Man_Num || 0);
                    var manNumFormatted = String(manNum).length < 4 ? String(manNum).padStart(6, '0') : String(manNum);
                    var plaCod = data.Pla_Cod || item.Pla_Cod;
                    $('#Man_Cod').val('M' + plaCod + '-' + manNumFormatted);
                    
                    if (!$('#Mat_Cce').val()) $('#Mat_Cce').val(item.Cel_Num);
                    if (!$('#Mat_Nce').val()) $('#Mat_Nce').val(item.Cel_Nom);
                    if (!$('#Mat_Dce').val()) $('#Mat_Dce').val(item.Cel_Nom_Grupo || item.Cel_Nom);
                    
                    setReadOnlyMode();
                } else {
                    populateFromItem();
                }
            },
            error: function() {
                $('#loader').fadeOut("slow");
                $('#manifTecDialog').show().removeClass('hidden');
                populateFromItem();
            }
        });
    } else {
        $('#loader').fadeOut("slow");
        $('#manifTecDialog').show().removeClass('hidden');
        populateFromItem();
    }
}

function editManifTec(item, matCod) {
    $('#documentoSearch').hide();
    $('#loader').show();
    $('#manifTecForm')[0].reset();
    
    // Enable fields and remove readonly
    $('#manifTecForm input, #manifTecForm select, #manifTecForm textarea').prop('disabled', false);
    $('#manifTecForm input, #manifTecForm textarea').prop('readonly', false);
    
    // Some fields are always readonly
    $('#Man_Cod, #Usu_Nom, #Mat_Nce, #Mat_Cce, #Mat_Dce').prop('readonly', true);
    
    // Store raw Man_Cod for saving
    $('#manifTecForm').data('rawManCod', item.Man_Cod);
    $('#manifTecForm').data('rawMatCod', matCod);
    
    // Helper to populate from item
    function populateFromItem() {
        var manNum = parseInt(item.Man_Num || 0);
        var manNumFormatted = String(manNum).length < 4 ? String(manNum).padStart(6, '0') : String(manNum);
        var plaCod = item.Pla_Cod;
        $('#Man_Cod').val('M' + plaCod + '-' + manNumFormatted);
        
        $('#Mat_Cce').val(item.Cel_Num);
        $('#Mat_Nce').val(item.Cel_Nom);
        $('#Mat_Dce').val(item.Cel_Nom_Grupo || item.Cel_Nom);
        
        // Set Date to Today
        var today = new Date().toISOString().split('T')[0];
        $('#Mat_Fde').val(today);
    }

    if (matCod) {
        // Fetch full details
        $.ajax({
            url: 'man_tec_camp_1.0.php',
            data: { getManifTecAjax: true, Mat_Cod: matCod },
            dataType: 'json',
            success: function(response) {
                $('#loader').fadeOut("slow");
                $('#manifTecDialog').show().removeClass('hidden');
                
                if (response.success) {
                    var data = response.data;
                    populateForm(data);
                    
                    // Format Mat_Fde to YYYY-MM-DD
                    if (data.Mat_Fde) {
                        $('#Mat_Fde').val(data.Mat_Fde.substring(0, 10));
                    }
                    
                    // Format Man_Cod
                    var manNum = parseInt(data.Man_Num || item.Man_Num || 0);
                    var manNumFormatted = String(manNum).length < 4 ? String(manNum).padStart(6, '0') : String(manNum);
                    var plaCod = data.Pla_Cod || item.Pla_Cod;
                    $('#Man_Cod').val('M' + plaCod + '-' + manNumFormatted);
                    
                    // Ensure Celda fields are populated
                    if (!$('#Mat_Cce').val()) $('#Mat_Cce').val(item.Cel_Num);
                    if (!$('#Mat_Nce').val()) $('#Mat_Nce').val(item.Cel_Nom);
                    if (!$('#Mat_Dce').val()) $('#Mat_Dce').val(item.Cel_Nom_Grupo || item.Cel_Nom);
                    
                } else {
                    populateFromItem();
                }
            },
            error: function() {
                $('#loader').fadeOut("slow");
                $('#manifTecDialog').show().removeClass('hidden');
                populateFromItem();
            }
        });
    } else {
        $('#loader').fadeOut("slow");
        $('#manifTecDialog').show().removeClass('hidden');
        populateFromItem();
    }
    
    // Show Save
    $('#manifTecForm a[onclick*="Guardar"]').show();
    
    // Update Cancel button action
    $('#manifTecForm a[onclick*="close"]').attr('onclick', 'cancelarEdicion()');
}

function cancelarEdicion() {
    $('#manifTecDialog').hide();
    limpiarFormulario();
    $('#loader').show();
    
    // Pequeño delay para simular transición y mostrar el loader
    setTimeout(function() {
        $('#loader').fadeOut("slow");
        $('#documentoSearch').show();
    }, 500);
}

function limpiarFormulario() {
    $('#manifTecForm')[0].reset();
    $('#manifTecForm').removeData('rawManCod');
    $('#manifTecForm').removeData('rawMatCod');
}

function GuardarManifTec() {
    var formData = $('#manifTecForm').serializeArray();
    // Fix Man_Cod to be the raw ID
    var rawManCod = $('#manifTecForm').data('rawManCod');
    var rawMatCod = $('#manifTecForm').data('rawMatCod');
    
    // Replace Man_Cod with raw value
    var foundMan = false;
    for (var i = 0; i < formData.length; i++) {
        if (formData[i].name === 'Man_Cod') {
            formData[i].value = rawManCod;
            foundMan = true;
        }
        if (formData[i].name === 'Mat_Cod' && rawMatCod) {
            formData[i].value = rawMatCod;
        }
    }
    if (!foundMan && rawManCod) {
        formData.push({name: 'Man_Cod', value: rawManCod});
    }
    
    // Add ajax flag
    formData.push({name: 'saveManiTecAjax', value: true});
    
    // Determine if it is edition or new
    var esEdicion = rawMatCod && rawMatCod.toString().trim() !== '';

    var mensajeConfirmacion = '¿Está seguro que desea registrar el manifiesto técnico?';
    if (esEdicion) {
        mensajeConfirmacion = '¿Está seguro que desea actualizar el manifiesto técnico?';
    }

    $.createDialogConfirm(mensajeConfirmacion, formData, function(d) {
        // Show loader
        $('#loader').show();

        $.ajax({
            url: 'man_tec_camp_1.0.php',
            type: 'POST',
            data: d,
            dataType: 'json',
            success: function(response) {
                $('#loader').hide();
                if (response.success) {
                    $.alert(response.message);
                    $('#manifTecDialog').hide();
                    limpiarFormulario();
                    $('#documentoSearch').show();
                    // Reload grid
                    loadManifiestosTecnicosHTML();
                } else {
                    $.alert(response.message);
                }
            },
            error: function() {
                $('#loader').hide();
                $.alert('Error al guardar el manifiesto técnico');
            }
        });
    });
}

function populateForm(data) {
    $.each(data, function(key, value) {
        var ctrl = $('[name='+key+']', '#manifTecForm');
        if (ctrl.length > 0) {
            if (ctrl.is('select')) {
                ctrl.val(value).trigger('change');
            } else if (ctrl.is(':checkbox')) {
                ctrl.prop('checked', value == 1 || value == 'true');
            } else {
                ctrl.val(value);
            }
        }
    });
}

function iniciarEscannerMain() {
    // Verificar que la librería Html5Qrcode esté disponible
    if (typeof Html5Qrcode === 'undefined') {
        console.error('La librería Html5Qrcode no está cargada');
        $('#qr-reader-main').html('<div class="alert alert-danger">Error: La librería de escáner QR no se ha cargado correctamente. Por favor recarga la página.</div>');
        return;
    }

    // Verificar que el contenedor exista
    if ($('#qr-reader-main').length === 0) {
        console.error('El contenedor #qr-reader-main no existe');
        return;
    }

    // Función auxiliar para iniciar el escáner
    var startScanner = function() {
        // Limpiar el contenedor antes de inicializar
        $('#qr-reader-main').empty();

        // Configuración del escáner
        var config = { 
            fps: 30, // Mayor FPS para escaneo más rápido
            qrbox: { width: 180, height: 180 }, // Recuadro más pequeño para enfocar mejor
            aspectRatio: 1.0,
            experimentalFeatures: {
                useBarCodeDetectorIfSupported: true
            }
        };
        
        // Preferir cámara trasera
        // NOTA: La librería es estricta y solo acepta una llave (facingMode o deviceId)
        var cameraConfig = { 
            facingMode: "environment"
        };
        
        try {
            html5QrcodeScanner = new Html5Qrcode("qr-reader-main");
            
            html5QrcodeScanner.start(
                cameraConfig, 
                config,
                onScanSuccessMain,
                onScanFailureMain
            ).then(function() {
                isScanning = true;
                $('#btnRescanQRMain').hide();
            }).catch(err => {
                console.error("Error iniciando escáner", err);
                $('#qr-reader-main').html('<div class="alert alert-danger">No se pudo iniciar la cámara. Asegúrese de dar permisos y usar HTTPS.<br>Error: ' + err + '</div>');
                // Intentar limpiar si falló el inicio
                detenerEscannerMain();
            });
        } catch (e) {
            console.error("Excepción al crear instancia de escáner", e);
            $('#qr-reader-main').html('<div class="alert alert-danger">Error al inicializar el componente de escáner: ' + e + '</div>');
        }
    };

    if (html5QrcodeScanner && isScanning) {
        // Ya está iniciado, detenerlo primero y esperar promesa
        detenerEscannerMain().then(function() {
            startScanner();
        });
    } else {
        // Si existe instancia pero no escaneando, o si está null
        if (html5QrcodeScanner) {
            try {
                html5QrcodeScanner.clear().catch(e => console.log(e));
            } catch (e) {}
            html5QrcodeScanner = null;
        }
        startScanner();
    }
}

function detenerEscannerMain() {
    return new Promise((resolve, reject) => {
        if (html5QrcodeScanner && isScanning) {
            try {
                html5QrcodeScanner.stop().then((ignore) => {
                    // Stopped
                    isScanning = false;
                    if ($('#qr-reader-main').length) {
                        $('#qr-reader-main').empty();
                    }
                    try {
                        var p = html5QrcodeScanner.clear();
                        if (p && typeof p.catch === 'function') {
                            p.catch(e => console.log("Clear warning", e));
                        }
                    } catch(e) {
                        console.error("Error clearing scanner", e);
                    }
                    html5QrcodeScanner = null;
                    resolve();
                }).catch((err) => {
                    // Stop failed, handle it.
                    console.error("Error deteniendo escáner", err);
                    isScanning = false;
                    if ($('#qr-reader-main').length) {
                        $('#qr-reader-main').empty();
                    }
                    html5QrcodeScanner = null;
                    resolve(); // Resolvemos de todos modos para permitir reintentos
                });
            } catch (e) {
                console.error("Excepción al detener escáner", e);
                isScanning = false;
                if ($('#qr-reader-main').length) {
                    $('#qr-reader-main').empty();
                }
                html5QrcodeScanner = null;
                resolve();
            }
        } else {
            // Asegurar limpieza aunque no esté escaneando
            if (html5QrcodeScanner) {
                try {
                    html5QrcodeScanner.clear().catch(e => console.log(e));
                } catch(e) {}
                html5QrcodeScanner = null;
            }
            if ($('#qr-reader-main').length) {
                $('#qr-reader-main').empty();
            }
            isScanning = false;
            resolve();
        }
    });
}

function onScanSuccessMain(decodedText, decodedResult) {
    // Detener escaneo
    detenerEscannerMain();
    
    // Mostrar botón para escanear de nuevo
    $('#btnRescanQRMain').show();
    
    // Intentar extraer Man_Cod (para mostrar en input, aunque ajax buscará con decodedText)
    var manCod = decodedText;
    try {
        var json = JSON.parse(decodedText);
        if (json && json.Man_Cod) {
            manCod = json.Man_Cod;
        }
    } catch (e) {
        // No es JSON, intentar regex
        var match = decodedText.match(/CODIGO:\s*(\d+)/i);
        if (match) {
            manCod = match[1];
        }
    }
    
    // Setear valor
    $('#searchQR').val(manCod);
    
    // Buscar datos y abrir editor
    $('#loader').show();
    $.ajax({
        url: 'man_tec_camp_1.0.php',
        type: 'GET',
        data: { 
            buscarPorQRAjax: true,
            codigo_qr: decodedText
        },
        dataType: 'json',
        success: function(response) {
            $('#loader').fadeOut("slow");
            if (response.success && response.data) {
                var item = response.data;
                
                // Validación de estado similar a man_tec_1.0.js pero adaptado
                if (item.Man_Tip === 'P') {
                    $.alert('No se puede procesar un manifiesto con estado PENDIENTE.');
                    return;
                }
                
                // Determinar acción según estado
                // GE -> Editar (Registrar Técnico)
                // A, GS, R, F -> Ver
                
                if (item.Man_Tip === 'GE') {
                    // Editar / Registrar
                    editManifTec(item, item.Mat_Cod);
                } else if (['A', 'GS', 'R', 'F'].indexOf(item.Man_Tip) !== -1) {
                    // Solo ver
                    viewManifTec(item, item.Mat_Cod);
                    $.alert('El manifiesto está en estado ' + item.Man_Tip + ', se abrirá en modo lectura.');
                } else {
                    $.alert('El manifiesto tiene un estado no válido para esta operación: ' + item.Man_Tip);
                }
            } else {
                $.alert(response.message || 'No se encontró el manifiesto con código: ' + manCod);
            }
        },
        error: function(xhr, status, error) {
            $('#loader').fadeOut("slow");
            $.alert('Error al buscar el manifiesto: ' + (error || 'Error desconocido'));
        }
    });
}

function onScanFailureMain(error) {
    // handle scan failure, usually better to ignore and keep scanning.
}

$(document).ready(function() {
    // Toggle search inputs based on filter
    $(document).on('change', 'input[name="op_opciones"]', function() {
        var opcion = $(this).val();
        if (opcion === 'm') {
            $('#divSearchNormal').hide();
            $('#divSearchManifiesto').show();
            $('#divSearchQR').hide();
            // Disable status select
            $('#Man_Tip').prop('disabled', true);
            detenerEscannerMain();
        } else if (opcion === 'q') {
            $('#divSearchNormal').hide();
            $('#divSearchManifiesto').hide();
            $('#divSearchQR').show();
            // Hide grid container
            $('#man_tec_container').hide();
            
            // Disable status select
            $('#Man_Tip').prop('disabled', true);
            // Iniciar escáner con un pequeño delay para asegurar que el div es visible
            setTimeout(function() {
                iniciarEscannerMain();
            }, 200);
        } else {
            $('#divSearchNormal').show();
            $('#divSearchManifiesto').hide();
            $('#divSearchQR').hide();
            // Show grid container
            $('#man_tec_container').show();
            
            // Enable status select
            $('#Man_Tip').prop('disabled', false);
            detenerEscannerMain();
        }
    });

    // Botón para escanear nuevamente
    $(document).on('click', '#btnRescanQRMain', function(){
        iniciarEscannerMain();
    });
});
