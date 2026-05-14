// Variables globales
var html5QrcodeScanner = null;
var isScanning = false;

$(function(){
    // Sincronizar campos móviles con desktop
    function sincronizarCampos() {
        $('#searchPlaCodMobile').val($('#searchPlaCod').val());
        $('#searchInputManifiestoMobile').val($('#searchInputManifiesto').val());
    }
    
    function sincronizarCamposInverso() {
        $('#searchPlaCod').val($('#searchPlaCodMobile').val());
        $('#searchInputManifiesto').val($('#searchInputManifiestoMobile').val());
    }
    
    // Sincronizar desde desktop a móvil
    $('#searchPlaCod, #searchInputManifiesto').on('change keyup', sincronizarCampos);
    
    // Sincronizar desde móvil a desktop
    $('#searchPlaCodMobile, #searchInputManifiestoMobile').on('change keyup', sincronizarCamposInverso);
    
    // Validar que solo se ingresen números
    function validarSoloNumeros(input) {
        $(input).on('input', function() {
            var value = $(this).val();
            value = value.replace(/[^0-9]/g, '');
            $(this).val(value);
        });
    }
    
    validarSoloNumeros('#searchPlaCod');
    validarSoloNumeros('#searchPlaCodMobile');
    validarSoloNumeros('#searchInputManifiesto');
    validarSoloNumeros('#searchInputManifiestoMobile');
    
    // Cambio de tipo de búsqueda
    $('input[name="tipo_busqueda"]').on('change', function(){
        var tipo = $(this).val();
        
        // Mostrar/Ocultar selector de Estado
        if (tipo === 'qr') {
            $('#Man_Tip').closest('.row').hide();
        } else {
            $('#Man_Tip').closest('.row').show();
        }
        
        if (tipo === 'qr') {
            $('#searchManifiestoGroup').hide();
            $('#searchPlacaGroup').hide();
            $('#searchQRGroup').show();
            $('#rescanButtonGroup').hide();
            setTimeout(function(){ iniciarEscannerIntegrado(); }, 300);
        } else if (tipo === 'placa') {
            if (html5QrcodeScanner && isScanning) detenerEscanner();
            $('#searchManifiestoGroup').hide();
            $('#searchQRGroup').hide();
            $('#searchPlacaGroup').show();
            setTimeout(function(){ $('#searchPlaca').focus(); }, 100);
        } else {
            if (html5QrcodeScanner && isScanning) detenerEscanner();
            $('#searchQRGroup').hide();
            $('#searchPlacaGroup').hide();
            $('#searchManifiestoGroup').show();
            setTimeout(function(){
                if ($(window).width() <= 768) {
                    $('#searchPlaCodMobile').focus();
                } else {
                    $('#searchPlaCod').focus();
                }
            }, 100);
        }
        limpiarResultados();
    });
    
    // Inyectar estilos CSS personalizados para el botón responsivo si no existen
    if ($('#estilos-boton-verificar').length === 0) {
        $('head').append('<style id="estilos-boton-verificar">' +
            '.btn-verificar-responsive { white-space: normal; font-weight: bold; width: auto; min-width: 50%; display: inline-block; }' +
            '@media (min-width: 992px) { .btn-verificar-responsive { width: 100%; display: block; } }' +
        '</style>');
    }

    // Eventos de botones y teclas
    $('#btnSearchManifiesto, #btnSearchManifiestoMobile').on('click', function(){
        if(this.id.includes('Mobile')) sincronizarCamposInverso();
        buscarDocumento();
    });
    
    $('#btnSearchPlaca').on('click', function(){
        buscarDocumento();
    });
    
    $('#searchPlaca').on('keypress', function(e){
        if (e.which === 13) { e.preventDefault(); buscarDocumento(); }
    });
    
    $('#searchInputManifiesto, #searchInputManifiestoMobile').on('keypress', function(e){
        if (e.which === 13) {
            e.preventDefault();
            if(this.id.includes('Mobile')) sincronizarCamposInverso();
            buscarDocumento();
        }
    });
    
    $('#searchPlaCod').on('keypress', function(e){
        if (e.which === 13) { e.preventDefault(); $('#searchInputManifiesto').focus(); }
    });
    
    // Año actual por defecto
    var anioActual = new Date().getFullYear();
    if (!$('#searchAnio').val()) $('#searchAnio').val(anioActual);
    if (!$('#searchAnioMobile').val()) $('#searchAnioMobile').val(anioActual);
    
    // Botones de rescanear
    $('#btnRescanQR').on('click', function(){ iniciarEscannerIntegrado(); });
    $('#btnRescanFromResult').on('click', function(){
        limpiarResultados();
        $('#rescanButtonGroup').hide();
        $('#radio_qr').prop('checked', true).trigger('change');
    });

    // Botones de Limpiar
    $('#btnCleanSearchManifiesto, #btnCleanSearchManifiestoMobile, #btnCleanSearchPlaca').on('click', function(){
        // Limpiar inputs
        $('#searchPlaCod, #searchPlaCodMobile').val('');
        $('#searchInputManifiesto, #searchInputManifiestoMobile').val('');
        $('#searchPlaca').val('');
        
        // Habilitar select de estado
        $('#Man_Tip').prop('disabled', false);
        
        // Limpiar resultados y volver a la lista por defecto (si hay estado seleccionado)
        limpiarResultados();
        
        // Si el estado seleccionado es P, recargar la lista de pendientes
        if ($('#Man_Tip').val() === 'P') {
            listarManifiestos();
        }
    });
    
    // Evento change en Select Estado
    $('#Man_Tip').on('change', function(){
        listarManifiestos();
    });

    // Cargar manifiestos pendientes al inicio si es el seleccionado
    if ($('#Man_Tip').val() === 'P') {
        listarManifiestos();
    }
    
    // Bloquear select de estado si hay texto en los campos de búsqueda
    /*
    var inputsBusqueda = '#searchPlaCod, #searchInputManifiesto, #searchPlaca, #searchPlaCodMobile, #searchInputManifiestoMobile';
    $(inputsBusqueda).on('input keyup change', function() {
        var tieneValor = false;
        $(inputsBusqueda).each(function() {
            if ($(this).val().trim() !== '') {
                tieneValor = true;
                return false; // break
            }
        });
        $('#Man_Tip').prop('disabled', tieneValor);
    });
    */
});

/**
 * Lista manifiestos según el estado seleccionado
 */
function listarManifiestos() {
    var manTip = $('#Man_Tip').val();
    
    mostrarLoading();
    
    var params = {
        listarManifiestosPorEstadoAjax: true,
        Man_Tip: manTip
    };
    
    $.get('', params, function(resp) {
        if (typeof resp === 'string') {
            try { resp = JSON.parse(resp); } catch(e) {
                mostrarResultadoError('Error al procesar la respuesta del servidor');
                return;
            }
        }
        
        if (resp.success) {
            var datos = resp.data || [];
            if (!Array.isArray(datos)) datos = [datos];
            renderGrid(datos);
        } else {
            $('#gridContainer').empty();
            if (resp.tipo_mensaje == 'info') {
                // Mensaje informativo (ej. no hay resultados)
                mostrarResultadoError(resp.message || 'No se encontraron documentos', 'info');
            } else {
                mostrarResultadoError(resp.message || 'Error al consultar', resp.tipo_mensaje || 'error');
            }
        }
    }, 'json').fail(function(xhr, status, error) {
        mostrarResultadoError('Error al realizar la búsqueda: ' + (error || 'Error desconocido'), 'error');
    });
}

/**
 * Realiza la búsqueda del documento
 */
function buscarDocumento() {
    var tipo = $('input[name="tipo_busqueda"]:checked').val();
    var params = {};
    
    if (tipo === 'qr') {
        abrirLectorQR();
        return;
    } else if (tipo === 'placa') {
        var vehPla = $('#searchPlaca').val().trim();
        
        // Si el campo está vacío, llamar a listarManifiestos (comportamiento similar a Limpiar)
        if (!vehPla) {
            listarManifiestos();
            return;
        }
        
        params = { buscarPorPlacaAjax: true, veh_pla: vehPla };
    } else {
        var plaCod = $('#searchPlaCod').val().trim();
        var numero = $('#searchInputManifiesto').val().trim();

        // Si ambos campos están vacíos, llamar a listarManifiestos
        if (!plaCod && !numero) {
            listarManifiestos();
            return;
        }

        if (!plaCod) {
            alert('Por favor ingrese el código de planta');
            $('#searchPlaCod').focus();
            return;
        }
        if (!numero) {
            alert('Por favor ingrese el número de manifiesto');
            return;
        }
        
        var numeroInt = parseInt(numero, 10);
        if (isNaN(numeroInt) || numeroInt < 1) {
            alert('El número de manifiesto debe ser un número válido mayor a 0');
            return;
        }

        var numeroFormateado = String(numeroInt).length < 4 ? String(numeroInt).padStart(6, '0') : String(numeroInt);
        params = {
            buscarPorManifiestoAjax: true,
            pla_cod: plaCod,
            numero_manifiesto: numeroFormateado
        };
    }
    
    // El usuario solicitó habilitar el estado en la búsqueda
    var manTip = $('#Man_Tip').val();
    if (manTip) {
        params.Man_Tip = manTip;
    } 
    
    mostrarLoading();
    
    $.get('', params, function(resp) {
        if (typeof resp === 'string') {
            try { resp = JSON.parse(resp); } catch(e) {
                mostrarResultadoError('Error al procesar la respuesta del servidor');
                return;
            }
        }
        
        var tipoMensaje = resp.tipo_mensaje || (resp.success ? 'success' : 'error');
        
        if (resp.success && resp.data) {
            // Si es éxito, renderizamos Grid o Vista Especial según corresponda
            var datos = Array.isArray(resp.data) ? resp.data : [resp.data];
            
            // Si la búsqueda es manual (Manifiesto o Placa) y hay un solo resultado,
            // mostramos directamente la vista de detalle (Legacy)
            if (datos.length === 1 && tipo !== 'qr') {
                $('#gridContainer').empty(); // Limpiar grid por si acaso
                mostrarResultadoEspecial(datos[0], resp.message, 'success');
            } else {
                renderGrid(datos);
            }
        } else if (resp.data && (resp.tipo_mensaje == 'info' || resp.tipo_mensaje == 'error' || resp.tipo_mensaje == 'warning')) {
            // Si hay datos pero con warning/error (ej. expirado, rechazado), mostramos alerta especial
            // Ocultamos el grid si hubiera
            $('#gridContainer').empty();
            mostrarResultadoEspecial(resp.data, resp.message, resp.tipo_mensaje);
        } else {
            mostrarResultadoError(resp.message || 'No existe el documento buscado', tipoMensaje);
        }
    }, 'json').fail(function(xhr, status, error) {
        mostrarResultadoError('Error al realizar la búsqueda: ' + (error || 'Error desconocido'), 'error');
    });
}

/**
 * Renderiza los resultados en formato Grid (Cards)
 * Basado en man_tec_camp_1.0.js
 */
function renderGrid(data, extraMessage) {
    var container = $('#gridContainer');
    container.empty();
    
    // Ocultar contenedor legacy
    $('#resultContainer').removeClass('show').hide();
    
    // Ocultar loading
    ocultarLoading();
    
    if (extraMessage) {
        container.append('<div class="col-xs-12"><div class="alert alert-info">' + extraMessage + '</div></div>');
    }
    
    if (data && data.length > 0) {
        $.each(data, function(i, item) {
            var manCod = parseInt(item.Man_Num || item.Man_Cod || 0);
            var manCodFormateado = String(manCod).length < 4 ? String(manCod).padStart(6, '0') : String(manCod);
            var plaCod = item.Pla_Cod || '';
            // Si viene el código completo Man_Cod usémoslo, si no construyámoslo
            var fullCode = item.Man_Cod ? item.Man_Cod : ('M' + plaCod + '-' + manCodFormateado);
            // Ajuste visual si el Man_Cod ya viene formateado
            if(item.Man_Cod && item.Man_Cod.indexOf('M') === 0) {
                fullCode = item.Man_Cod;
            } else if (plaCod && manCod) {
                fullCode = 'M' + plaCod + '-' + manCodFormateado;
            }
            
            var estado = item.Man_Tip || '';
            var estadoLabel = estado;
            var badgeClass = 'label-default';
            
            // Lógica de badges igual a man_tec_camp_1.0.js
            if (estado === 'P') { estadoLabel = 'PENDIENTE'; badgeClass = 'badge-activo'; } // Usando clases CSS definidas en PHP
            else if (estado === 'A') { estadoLabel = 'APROBADO'; badgeClass = 'badge-activo'; } // Ajustado a visualización requerida
            else if (estado === 'F') { estadoLabel = 'FACTURADO'; badgeClass = 'badge-facturado'; }
            else if (estado === 'GE') { estadoLabel = 'GARITA IN'; badgeClass = 'badge-garita-in'; }
            else if (estado === 'GS') { estadoLabel = 'GARITA OUT'; badgeClass = 'badge-garita-out'; }
            else if (estado === 'R') { estadoLabel = 'RECHAZADO'; badgeClass = 'badge-inactivo'; }
            
            // Bootstrap label classes fallback
            var bootstrapLabelClass = 'label-default';
            if (estado === 'P') bootstrapLabelClass = 'label-warning';
            else if (estado === 'A') bootstrapLabelClass = 'label-success';
            else if (estado === 'F') bootstrapLabelClass = 'label-primary';
            else if (estado === 'GE') bootstrapLabelClass = 'label-info';
            else if (estado === 'GS') bootstrapLabelClass = 'label-danger';
            else if (estado === 'R') bootstrapLabelClass = 'label-danger';
            
            var cardId = 'man_card_' + i;
            var index = i + 1;
            
            // Construcción de la tarjeta
            var cardHtml = '<div class="col-xs-12 col-sm-6 col-md-3">' +
                '<div class="panel panel-default" id="' + cardId + '" style="margin-bottom: 10px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">' +
                    '<div class="panel-heading" style="padding: 10px 15px; background-color: #f5f5f5; border-top-left-radius: 8px; border-top-right-radius: 8px;">' +
                        '<div class="row" style="margin:0;">' +
                            '<div class="col-xs-7" style="padding:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="Manifiesto: ' + fullCode + '">' +
                                '<span style="font-size: 16px; font-weight: bold; color: #333;">N° ' + index + ' | ' + fullCode + '</span>' +
                            '</div>' +
                            '<div class="col-xs-5" style="padding:0; text-align:right;">' +
                                '<span class="label ' + bootstrapLabelClass + '" style="font-size: 12px !important; padding: 4px 8px;">' + estadoLabel + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="panel-body" style="padding: 15px;">' +
                        '<div class="row" style="margin: 0;">' +
                            '<div class="col-xs-12" style="padding: 0; margin-bottom: 10px;">' +
                                '<p style="margin-bottom: 5px; font-size: 14px; color: #555;"><strong>Chofer:</strong> <span style="color: #000;">' + (item.chofer || item.chofer_nombre || 'N/A') + '</span></p>' +
                                '<p style="margin-bottom: 5px; font-size: 14px; color: #555;"><strong>Placa:</strong> <span style="color: #000;">' + (item.Veh_Pla || 'N/A') + '</span></p>' +
                                '<p style="margin-bottom: 0px; font-size: 14px; color: #555;"><strong>Fecha:</strong> <span style="color: #000;">' + ((item.Man_Fec || '').substring(0, 10)) + '</span></p>' +
                                '<p style="margin-bottom: 0px; font-size: 14px; color: #555;"><strong>Hora de Arribo:</strong> <span style="color: #000;">' + ((item.Man_Fea || '').substring(11, 19)) + '</span></p>' +
                            '</div>' +
                            '<div class="col-xs-12 btn-group-action" style="padding: 0; text-align: center; margin-top: 5px;">';
                            
            // Botones de Acción
            // Se cambia el botón a "Verificar Documento" para todos los estados que antes mostraban "Aprobar"
            // Esto permite abrir la vista de detalle y validar fecha/estado antes de aprobar.
            if (estado === 'P' || estado === 'A') {
                cardHtml += '<button type="button" class="btn btn-success btn-verificar btn-verificar-responsive" data-man-cod="' + (item.Man_Cod || '') + '" data-pla-cod="' + (item.Pla_Cod || '') + '" data-man-num="' + (item.Man_Num || '') + '"><span class="glyphicon glyphicon-search"></span> Verificar Documento</button>';
            } else if (estado === 'GE') {
                cardHtml += '<div class="alert alert-warning" style="margin-bottom: 0; padding: 5px; font-size: 12px;"><strong>En descarga</strong></div>';
            } else if (estado === 'GS') {
                cardHtml += '<div class="alert alert-info" style="margin-bottom: 0; padding: 5px; font-size: 12px;"><strong>En salida</strong></div>';
            }
            
            cardHtml +=     '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
            
            var card = $(cardHtml);
            
            // Evento click para botones generados dinámicamente
            card.find('.btn-verificar').on('click', function() {
                var manCod = $(this).data('man-cod');
                var plaCod = $(this).data('pla-cod');
                var manNum = $(this).data('man-num');
                verificarManifiesto(manCod, plaCod, manNum);
            });
            
            container.append(card);
        });
        
        // Scroll al grid si el contenedor existe
        if (container.length > 0 && container.offset()) {
            $('html, body').animate({
                scrollTop: container.offset().top - 50
            }, 600);
        }
        
    } else {
        container.html('<div class="col-xs-12"><div class="alert alert-info">No se encontraron resultados</div></div>');
    }
}

/**
 * Muestra resultados especiales (warnings, info, error) usando el contenedor legacy
 * para mantener la fidelidad visual solicitada por el usuario.
 */
function mostrarResultadoEspecial(data, mensaje, tipoMensaje) {
    // Asegurar que data sea un objeto único
    if (Array.isArray(data)) {
        data = data[0];
    }

    var container = $('#resultContainer');
    var icon = $('#resultIcon');
    var message = $('#resultMessage');
    var dataContainer = $('#resultData');
    var btnAprobar = $('#btnAprobarManifiesto');
    var btnAprobarTexto = $('#btnAprobarTexto');
    
    // Asegurarse de que el contenedor grid esté vacío
    $('#gridContainer').empty();
    
    ocultarLoading();
    
    // Resetear clases
    container.removeClass('success error info warning show');
    icon.removeClass('success error info warning animate');
    message.removeClass('result-message-found');
    message.css('color', ''); // Reset color inline
    
    // Lógica específica por estado/tipo
    var estado = data ? (data.Man_Tip || '') : '';
    
    // Validar fecha expirada para pendientes
    var esExpirado = false;
    if (data && data.Man_Fec && estado === 'P') {
        // var fechaMan = data.Man_Fec.substring(0, 10);
        
        // Obtener fecha y hora actual en formato YYYY-MM-DD HH:mm:ss local
        var hoy = new Date();
        // var d = String(hoy.getDate()).padStart(2, '0');
        // var m = String(hoy.getMonth() + 1).padStart(2, '0');
        // var y = hoy.getFullYear();
        // var fechaActual = y + '-' + m + '-' + d;
        
        // if (fechaMan < fechaActual) {
        //     esExpirado = true;
        // }
        var yCurrent = hoy.getFullYear();
        var mCurrent = String(hoy.getMonth() + 1).padStart(2, '0');
        var dCurrent = String(hoy.getDate()).padStart(2, '0');
        var hhCurrent = String(hoy.getHours()).padStart(2, '0');
        var mmCurrent = String(hoy.getMinutes()).padStart(2, '0');
        var ssCurrent = String(hoy.getSeconds()).padStart(2, '0');

        var fechaActual = yCurrent + '-' + mCurrent + '-' + dCurrent;
        var fechaHoraActual = fechaActual + ' ' + hhCurrent + ':' + mmCurrent + ':' + ssCurrent;

        var manFecDate = data.Man_Fec.substring(0, 10);
        var fullManFea = data.Man_Fea || '';

        var manFecDate = data.Man_Fec.substring(0, 10);
        var fullManFea = data.Man_Fea || '';

        // Validar contra FECHA de manifiesto (Man_Fec)
        if (manFecDate < fechaActual) {
            esExpirado = true;
        }
        
        // Validar contra FECHA/HORA de llegada/atención (Man_Fea)
        if (!esExpirado && fullManFea) {
            // Documento Expirado: Solo si la hora ya PASÓ en relación al sistema
            if (fullManFea < fechaHoraActual) {
                esExpirado = true;
            }
        }

        // Validar que el día de Man_Fea no sea posterior al día de Man_Fec
        if (!esExpirado && manFecDate && manFecDate !== fechaActual) {
            esExpirado = true;
        }
    }
    
    if (estado === 'F') {
        // FACTURADO: Mensaje informativo, icono info, SIN datos
        tipoMensaje = 'info';
        mensaje = 'Este manifiesto ya fue Facturado';
        container.addClass('info show');
        icon.addClass('info animate').html('<span class="glyphicon glyphicon-info-sign"></span>');
        message.css('color', '#17a2b8');
        dataContainer.hide();
        btnAprobar.hide();
        
    } else if (estado === 'R') {
        // RECHAZADO: Mensaje error, icono X, SIN datos
        tipoMensaje = 'error';
        mensaje = 'Este manifiesto fue Rechazado';
        container.addClass('error show');
        icon.addClass('error animate').html('<span class="glyphicon glyphicon-remove-circle"></span>');
        message.css('color', '#dc3545');
        dataContainer.hide();
        btnAprobar.hide();
        
    } else if (estado === 'GE') {
        // GARITA ENTRADA (En descarga): Mensaje informativo
        tipoMensaje = 'info';
        mensaje = 'Vehiculo en camino a descargar';
        container.addClass('info show');
        icon.addClass('info animate').html('<span class="glyphicon glyphicon-info-sign"></span>');
        message.css('color', '#17a2b8');
        
        // Mostrar datos para referencia
        if (data) {
            dataContainer.show();
            $('#choferValue').text(data.chofer || data.chofer_nombre || 'N/A');
            $('#placaValue').text(data.Veh_Pla || 'N/A');
            var fullCode = data.Man_Cod || 'N/A';
            if (data.Pla_Cod && data.Man_Num) {
                fullCode = 'M' + data.Pla_Cod + "-" + (String(data.Man_Num).length < 4 ? String(data.Man_Num).padStart(6, '0') : String(data.Man_Num));
            }
            $('#manifiestoValue').text(fullCode);
            var fecha = data.Man_Fec || '';
            $('#fechaValue').text(fecha.substring(0, 10));
            var fechaArribo = data.Man_Fea || '';
            // $('#fechaArriboValue').text(fechaArribo.substring(0, 10));
            $('#fechaArriboValue').text(fechaArribo.substring(11, 19) || 'N/A');
            $('#notaProceso').hide();
        }
        btnAprobar.hide();

    } else if (estado === 'GS') {
        // GARITA SALIDA (Listo para facturación): Mensaje informativo
        tipoMensaje = 'info';
        mensaje = 'Documento listo a proceso de facturacion';
        container.addClass('info show');
        icon.addClass('info animate').html('<span class="glyphicon glyphicon-info-sign"></span>');
        message.css('color', '#17a2b8');
        
        if (data) {
            dataContainer.show();
            $('#choferValue').text(data.chofer || data.chofer_nombre || 'N/A');
            $('#placaValue').text(data.Veh_Pla || 'N/A');
            var fullCode = data.Man_Cod || 'N/A';
            if (data.Pla_Cod && data.Man_Num) {
                fullCode = 'M' + data.Pla_Cod + "-" + (String(data.Man_Num).length < 4 ? String(data.Man_Num).padStart(6, '0') : String(data.Man_Num));
            }
            $('#manifiestoValue').text(fullCode);
            var fecha = data.Man_Fec || '';
            $('#fechaValue').text(fecha.substring(0, 10));
            var fechaArribo = data.Man_Fea || '';
            // $('#fechaArriboValue').text(fechaArribo.substring(0, 10));
            $('#fechaArriboValue').text(fechaArribo.substring(11, 19) || 'N/A');
            $('#notaProceso').hide();
        }
        btnAprobar.hide();

    } else if (esExpirado) {
        // EXPIRADO: Mensaje warning, icono warning, CON datos, SIN botón
        tipoMensaje = 'warning';
        mensaje = 'Documento Expirado';
        container.addClass('warning show');
        icon.addClass('warning animate').html('<span class="glyphicon glyphicon-warning-sign"></span>');
        message.css('color', '#ffc107'); // Naranja warning
        
        if (data) {
            dataContainer.show();
            $('#choferValue').text(data.chofer || data.chofer_nombre || 'N/A');
            $('#placaValue').text(data.Veh_Pla || 'N/A');
            
            var fullCode = data.Man_Cod || 'N/A';
            if (data.Pla_Cod && data.Man_Num) {
                fullCode = 'M' + data.Pla_Cod + "-" + (String(data.Man_Num).length < 4 ? String(data.Man_Num).padStart(6, '0') : String(data.Man_Num));
            }
            $('#manifiestoValue').text(fullCode);
            
            var fecha = data.Man_Fec || '';
            $('#fechaValue').text(fecha.substring(0, 10));
            var fechaArribo = data.Man_Fea || '';
            // $('#fechaArriboValue').text(fechaArribo.substring(0, 10));
            $('#fechaArriboValue').text(fechaArribo.substring(11, 19) || 'N/A');
            
            $('#notaProceso').hide();
        }
        btnAprobar.hide();

    } else if (estado === 'P' || estado === 'A' || tipoMensaje === 'success') {
        // PENDIENTE o APROBADO (éxito): Verde, Texto negro, Icono check, CON datos
        container.addClass('success show');
        icon.addClass('success animate').html('<span class="glyphicon glyphicon-ok-circle"></span>');
        
        // "Documento Encontrado" siempre en negro
        mensaje = 'Documento Encontrado';
        message.addClass('result-message-found');
        
        // Mostrar datos
        if (data) {
            dataContainer.show();
            $('#choferValue').text(data.chofer || data.chofer_nombre || 'N/A');
            $('#placaValue').text(data.Veh_Pla || 'N/A');
            
            var fullCode = data.Man_Cod || 'N/A';
            if (data.Pla_Cod && data.Man_Num) {
                fullCode = 'M' + data.Pla_Cod + "-" + (String(data.Man_Num).length < 4 ? String(data.Man_Num).padStart(6, '0') : String(data.Man_Num));
            }
            $('#manifiestoValue').text(fullCode);
            
            var fecha = data.Man_Fec || '';
            $('#fechaValue').text(fecha.substring(0, 10));
            var fechaArribo = data.Man_Fea || '';
            // $('#fechaArriboValue').text(fechaArribo.substring(0, 10));
            $('#fechaArriboValue').text(fechaArribo.substring(11, 19) || 'N/A');
            
            // Ocultar nota si es éxito limpio
            $('#notaProceso').hide();
        }
        
        // Botón Aprobar Acceso
        btnAprobar.show();
        
        // Cambiar texto según estado
        if (estado === 'A') {
            btnAprobarTexto.text('Aprobar Salida');
        } else {
            btnAprobarTexto.text('Aprobar Acceso');
        }
        
        // Guardar datos en el botón para la acción
        btnAprobar.data('man-cod', data.Man_Cod);
        btnAprobar.data('estado', estado);
        
        // Evento click del botón grande
        btnAprobar.off('click').on('click', function() {
            aprobarManifiesto(data.Man_Cod, estado, $(this));
        });

    } else {
        // Otros casos (Warning, Error genérico)
        container.addClass(tipoMensaje + ' show');
        icon.addClass(tipoMensaje + ' animate');
        
        if (tipoMensaje == 'warning') {
            icon.html('<span class="glyphicon glyphicon-warning-sign"></span>');
            message.css('color', '#ffc107');
        } else {
            icon.html('<span class="glyphicon glyphicon-remove-circle"></span>');
            message.css('color', '#dc3545');
        }
        
        if (data) {
            dataContainer.show();
            // Llenar datos...
            $('#choferValue').text(data.chofer || data.chofer_nombre || 'N/A');
            $('#placaValue').text(data.Veh_Pla || 'N/A');
            // ... resto de campos
             var fullCode = data.Man_Cod || 'N/A';
            if (data.Pla_Cod && data.Man_Num) {
                fullCode = 'M' + data.Pla_Cod + "-" + (String(data.Man_Num).length < 4 ? String(data.Man_Num).padStart(6, '0') : String(data.Man_Num));
            }
            $('#manifiestoValue').text(fullCode);
            var fecha = data.Man_Fec || '';
            $('#fechaValue').text(fecha.substring(0, 10));
            var fechaArribo = data.Man_Fea || '';
            // $('#fechaArriboValue').text(fechaArribo.substring(0, 10));
            $('#fechaArriboValue').text(fechaArribo.substring(11, 19) || 'N/A');
            
            $('#notaProceso').show();
            $('#notaTexto').text(mensaje);
        } else {
            dataContainer.hide();
        }
        btnAprobar.hide();
    }
    
    message.text(mensaje || '');
    
    // Forzar display block
    container.attr('style', 'display: block !important;');
    
    $('html, body').animate({
        scrollTop: container.offset().top - 50
    }, 600);
}

function mostrarResultadoError(mensaje, tipoMensaje) {
    // Reutilizamos la lógica especial pero sin datos
    mostrarResultadoEspecial(null, mensaje, tipoMensaje || 'error');
}

function limpiarResultados() {
    $('#resultContainer').hide().attr('style', 'display: none !important;');
    $('#gridContainer').empty();
    $('#searchInputManifiesto').val('');
    $('#searchPlaca').val('');
    $('#hiddenManCod').val('');
}

function mostrarLoading() {
    $('#gridContainer').empty();
    var container = $('#resultContainer');
    container.attr('style', 'display: block !important;');
    container.removeClass('success error info warning').addClass('show');
    $('#resultIcon').html('<span class="glyphicon glyphicon-refresh glyphicon-spin"></span>');
    $('#resultMessage').text('Buscando...');
    $('#resultData').hide();
    $('#btnAprobarManifiesto').hide();
}

function ocultarLoading() {
    // Helper
}

/**
 * Carga el detalle del manifiesto (Vista Legacy) para validación antes de aprobar
 */
function verificarManifiesto(manCod, plaCod, manNum) {
    // Si tenemos los datos completos (plaCod y manNum), usamos la búsqueda por manifiesto
    // Si solo tenemos manCod (por ejemplo de un QR o lista simple), podríamos necesitar lógica adicional,
    // pero el Grid suele tener toda la info.
    
    if (plaCod && manNum) {
        // Rellenar los inputs para simular una búsqueda manual (opcional, pero visualmente consistente)
        $('#searchPlaCod').val(plaCod);
        $('#searchInputManifiesto').val(manNum);
        
        // Ejecutar búsqueda por manifiesto
        // Esto reutiliza toda la lógica de validación del backend (fechas, estado, etc.)
        // y muestra el resultado en la vista "Especial" (Legacy) si hay warnings o es un solo resultado.
        
        // IMPORTANTE: Forzamos que la respuesta se muestre SIEMPRE en la vista legacy (detalle)
        // para que el usuario pueda ver el botón de aprobar grande.
        
        var numeroInt = parseInt(manNum, 10);
        var numeroFormateado = String(numeroInt).length < 4 ? String(numeroInt).padStart(6, '0') : String(numeroInt);
        
        var params = {
            buscarPorManifiestoAjax: true,
            pla_cod: plaCod,
            numero_manifiesto: numeroFormateado
        };
        
        mostrarLoading();
        
        $.get('', params, function(resp) {
            if (typeof resp === 'string') { try { resp = JSON.parse(resp); } catch(e) {} }
            
            // Aquí forzamos mostrarResultadoEspecial incluso si es success
            // Porque "Verificar Documento" debe llevar a la vista de detalle.
            if (resp.success && resp.data) {
                $('#gridContainer').empty(); // Limpiar grid
                // Asegurar que pasamos un objeto, no un array
                var item = Array.isArray(resp.data) ? resp.data[0] : resp.data;
                mostrarResultadoEspecial(item, resp.message, resp.tipo_mensaje || 'success');
            } else if (resp.data) {
                $('#gridContainer').empty();
                var item = Array.isArray(resp.data) ? resp.data[0] : resp.data;
                mostrarResultadoEspecial(item, resp.message, resp.tipo_mensaje || 'error');
            } else {
                mostrarResultadoError(resp.message || 'Error al verificar documento', 'error');
            }
        }, 'json').fail(function() {
            mostrarResultadoError('Error de conexión al verificar', 'error');
        });
        
    } else {
        alert('No se pudo obtener la información completa del manifiesto para verificar.');
    }
}

/**
 * Aprobar manifiesto desde el Grid
 */
function aprobarManifiesto(man_cod, estado_actual, btnElement) {
    var nuevo_estado = '';
    
    // Determinar siguiente estado
    if (estado_actual === 'P') {
        nuevo_estado = 'GE'; // De Pendiente a Garita Entrada
    } else if (estado_actual === 'A') {
        nuevo_estado = 'GS'; // De Aprobado (cargado/descargado) a Garita Salida
    } else {
        alert('Estado no válido para aprobación directa.');
        return;
    }
    
    /*
    if (!confirm('¿Está seguro de aprobar el acceso/salida para este manifiesto?')) {
        return;
    }
    */
    
    // Usar el botón pasado o buscarlo si es null
    var btn = btnElement || $('#btnAprobarManifiesto');
    var originalText = btn.text();
    // Loader en el botón
    btn.prop('disabled', true).html('<span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Procesando...');
    
    // Parámetros para Case 3 (SQL Update)
    // El SQL Case 3 espera: man_cod, nuevo_estado, estado_actual
    var params = {
        aprobarManifiestoAjax: true,
        man_cod: man_cod,
        nuevo_estado: nuevo_estado,
        estado_actual: estado_actual
    };
    
    $.post('', params, function(resp) {
        if (typeof resp === 'string') { try { resp = JSON.parse(resp); } catch(e) {} }
        
        if (resp.success) {
            // "solo aparezca un label diciendo Listo y alado un icono de check"
            btn.removeClass('btn-primary btn-default btn-danger').addClass('btn-success');
            btn.html('<span class="glyphicon glyphicon-ok"></span> Listo');
            
            // Refrescar después de un breve momento para que el usuario vea el "Listo"
            setTimeout(function() {
                 $('#resultContainer').removeClass('show');
                 if ($('#Man_Tip').val() === 'P') {
                     listarManifiestos();
                 } else {
                     limpiarResultados();
                 }
            }, 1500);
            
        } else {
            mostrarResultadoError(resp.message || 'Error al aprobar', 'error');
            btn.prop('disabled', false).text(originalText);
        }
    }, 'json').fail(function() {
        mostrarResultadoError('Error de conexión', 'error');
        btn.prop('disabled', false).text(originalText);
    });
}

// Funciones del escáner QR (simplificadas e integradas)
function iniciarEscannerIntegrado() {
    if (html5QrcodeScanner && isScanning) detenerEscanner();
    $('#qr-reader').empty();
    
    try {
        html5QrcodeScanner = new Html5Qrcode("qr-reader");
        html5QrcodeScanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            function(decodedText) {
                detenerEscanner();
                $('#btnRescanQR').hide();
                // Buscar por QR
                mostrarLoading();
                $.get('', { buscarPorQRAjax: true, codigo_qr: decodedText }, function(resp) {
                    if (typeof resp === 'string') { try { resp = JSON.parse(resp); } catch(e) {} }
                    
                    if (resp.success && resp.data) {
                        var datos = Array.isArray(resp.data) ? resp.data : [resp.data];
                        renderGrid(datos);
                    } else if (resp.data) {
                        mostrarResultadoEspecial(resp.data, resp.message, resp.tipo_mensaje);
                    } else {
                        mostrarResultadoError(resp.message || 'No encontrado', 'error');
                    }
                    
                    $('#searchQRGroup').hide();
                    setTimeout(function(){ $('#rescanButtonGroup').show(); }, 800);
                }, 'json');
            },
            function(err) {}
        ).then(function(){ isScanning = true; })
        .catch(function(err){ 
            mostrarResultadoError('Error al iniciar cámara: ' + err, 'error'); 
        });
    } catch(e) {
        mostrarResultadoError('Error al crear escáner', 'error');
    }
}

function detenerEscanner() {
    if (html5QrcodeScanner && isScanning) {
        html5QrcodeScanner.stop().then(function(){
            html5QrcodeScanner.clear();
            isScanning = false;
        }).catch(function(){ isScanning = false; });
    }
}

function abrirLectorQR() {
    // Wrapper por compatibilidad
    iniciarEscannerIntegrado();
}
