// Variables globales
var html5QrcodeScanner = null;
var isScanning = false;

$(function(){
    // Sincronizar campos móviles con desktop
    function sincronizarCampos() {
        // $('#searchMesMobile').val($('#searchMes').val());
        $('#searchPlaCodMobile').val($('#searchPlaCod').val());
        $('#searchInputManifiestoMobile').val($('#searchInputManifiesto').val());
    }
    
    function sincronizarCamposInverso() {
        // $('#searchMes').val($('#searchMesMobile').val());
        $('#searchPlaCod').val($('#searchPlaCodMobile').val());
        $('#searchInputManifiesto').val($('#searchInputManifiestoMobile').val());
    }
    
    // Sincronizar desde desktop a móvil
    $('#searchPlaCod, #searchInputManifiesto').on('change keyup', sincronizarCampos);
    
    // Sincronizar desde móvil a desktop
    $('#searchPlaCodMobile, #searchInputManifiestoMobile').on('change keyup', sincronizarCamposInverso);
    
    // Validar que solo se ingresen números en los campos de código de planta y número de manifiesto
    function validarSoloNumeros(input) {
        $(input).on('input', function() {
            var value = $(this).val();
            // Remover cualquier carácter que no sea número
            value = value.replace(/[^0-9]/g, '');
            $(this).val(value);
        });
    }
    
    // Aplicar validación a todos los campos numéricos
    validarSoloNumeros('#searchPlaCod');
    validarSoloNumeros('#searchPlaCodMobile');
    validarSoloNumeros('#searchInputManifiesto');
    validarSoloNumeros('#searchInputManifiestoMobile');
    
    // Función para actualizar el display del número de manifiesto formateado
    function actualizarDisplayManifiesto(inputId, displayId) {
        $(inputId).on('input keyup', function() {
            var value = $(this).val();
            var display = $(displayId);
            
            if (value && value.length > 0) {
                // Convertir a número y rellenar con ceros a la izquierda
                var numeroInt = parseInt(value, 10);
                if (!isNaN(numeroInt) && numeroInt > 0) {
                    var numeroFormateado = String(numeroInt).padStart(4, '0');
                    display.text(numeroFormateado).css('display', 'table-cell');
                } else {
                    display.hide();
                }
            } else {
                display.hide();
            }
        });
        
        // También actualizar cuando se pierde el foco
        $(inputId).on('blur', function() {
            var value = $(this).val();
            var display = $(displayId);
            
            if (value && value.length > 0) {
                var numeroInt = parseInt(value, 10);
                if (!isNaN(numeroInt) && numeroInt > 0) {
                    var numeroFormateado = String(numeroInt).padStart(4, '0');
                    display.text(numeroFormateado).css('display', 'table-cell');
                } else {
                    display.hide();
                }
            } else {
                display.hide();
            }
        });
    }
    
    // Aplicar actualización de display a ambos campos (desktop y móvil)
    actualizarDisplayManifiesto('#searchInputManifiesto', '#manifiestoDisplay');
    actualizarDisplayManifiesto('#searchInputManifiestoMobile', '#manifiestoDisplayMobile');

    // Cambio de tipo de búsqueda
    $('input[name="tipo_busqueda"]').on('change', function(){
        var tipo = $(this).val();
        
        if (tipo === 'qr') {
            // Ocultar campos de manifiesto, mostrar escáner QR integrado
            $('#searchManifiestoGroup').hide();
            $('#searchPlacaGroup').hide();
            $('#searchQRGroup').show();
            
            // Ocultar botón de rescanear desde resultados
            $('#rescanButtonGroup').hide();
            
            // Iniciar el escáner QR integrado
            setTimeout(function(){
                iniciarEscannerIntegrado();
            }, 300);
        } else if (tipo === 'placa') {
            // Detener el escáner QR si está activo
            if (html5QrcodeScanner && isScanning) {
                detenerEscanner();
            }
            
            // Limpiar el contenedor QR
            $('#qr-reader').empty();
            html5QrcodeScanner = null;
            isScanning = false;
            
            // Ocultar botón de rescanear
            $('#rescanButtonGroup').hide();
            $('#btnRescanQR').hide();
            
            // Ocultar campos de manifiesto y QR, mostrar campo de placa
            $('#searchManifiestoGroup').hide();
            $('#searchQRGroup').hide();
            $('#searchPlacaGroup').show();
            
            // Enfocar el campo de placa
            setTimeout(function(){
                $('#searchPlaca').focus();
            }, 100);
        } else {
            // Detener el escáner QR si está activo
            if (html5QrcodeScanner && isScanning) {
                detenerEscanner();
            }

            // Limpiar el contenedor QR
            $('#qr-reader').empty();
            html5QrcodeScanner = null;
            isScanning = false;
            
            // Ocultar botón de rescanear
            $('#rescanButtonGroup').hide();
            $('#btnRescanQR').hide();
            
            // Mostrar campos de manifiesto, ocultar escáner QR
            $('#searchQRGroup').hide();
            $('#searchPlacaGroup').hide();
            $('#searchManifiestoGroup').show();
            // Enfocar el campo apropiado según el tamaño de pantalla
            setTimeout(function(){
                if ($(window).width() <= 768) {
                    $('#searchPlaCodMobile').focus();
                } else {
                    $('#searchPlaCod').focus();
                }
            }, 100);
        }
        
        // Limpiar resultados anteriores
        limpiarResultados();
    });
    
    // Botón de búsqueda por manifiesto (desktop)
    $('#btnSearchManifiesto').on('click', function(){
        buscarDocumento();
    });
    
    // Botón de búsqueda por manifiesto (móvil)
    $('#btnSearchManifiestoMobile').on('click', function(){
        sincronizarCamposInverso();
        buscarDocumento();
    });
    
    // Botón de búsqueda por placa
    $('#btnSearchPlaca').on('click', function(){
        buscarDocumento();
    });
    
    // Enter en el input de placa
    $('#searchPlaca').on('keypress', function(e){
        if (e.which === 13) {
            e.preventDefault();
            buscarDocumento();
        }
    });
    
    // Enter en el input de manifiesto (desktop)
    $('#searchInputManifiesto').on('keypress', function(e){
        if (e.which === 13) {
            e.preventDefault();
            buscarDocumento();
        }
    });
    
    // Enter en el input de manifiesto (móvil)
    $('#searchInputManifiestoMobile').on('keypress', function(e){
        if (e.which === 13) {
            e.preventDefault();
            sincronizarCamposInverso();
            buscarDocumento();
        }
    });
    
    // Enter en el código de planta (desktop) - pasar al siguiente campo
    $('#searchPlaCod').on('keypress', function(e){
        if (e.which === 13) {
            e.preventDefault();
            $('#searchInputManifiesto').focus();
        }
    });
    
    // Enter en el código de planta (móvil) - pasar al siguiente campo
    // $('#searchPlaCodMobile').on('keypress', function(e){
    //     if (e.which === 13) {
    //         e.preventDefault();
    //         $('#searchInputManifiestoMobile').focus();
    //     }
    // });
    
    // Establecer año actual por defecto si está vacío
    var anioActual = new Date().getFullYear();
    if (!$('#searchAnio').val()) {
        $('#searchAnio').val(anioActual);
    }

    if (!$('#searchAnioMobile').val()) {
        $('#searchAnioMobile').val(anioActual);
    }
    
    // Botón para escanear nuevamente desde el contenedor QR
    $('#btnRescanQR').on('click', function(){
        iniciarEscannerIntegrado();
    });
    
    // Botón para escanear nuevamente desde los resultados
    $('#btnRescanFromResult').on('click', function(){
        // Ocultar resultados
        limpiarResultados();
        // Ocultar botón de rescanear
        $('#rescanButtonGroup').hide();
        // Cambiar a la opción QR y activar el escáner
        $('#radio_qr').prop('checked', true).trigger('change');
    });
    
    // Botón para aprobar entrada/salida
    $('#btnAprobarManifiesto').on('click', function(){
        var man_cod = $('#hiddenManCod').val();
        var estado_actual = $(this).data('estado-actual');
        
        if (!man_cod) {
            alert('No se encontró el código del manifiesto');
            return;
        }
        
        if (estado_actual != 'P' && estado_actual != 'A') {
            alert('El manifiesto no está en un estado válido para aprobar');
            return;
        }
        
        // Llamar directamente a aprobar sin confirmación
        aprobarManifiesto(man_cod, estado_actual);
    });
});

/**
 * Realiza la búsqueda del documento
 */
function buscarDocumento() {
    var tipo = $('input[name="tipo_busqueda"]:checked').val();
    var params = {};
    
    // La búsqueda por QR se maneja automáticamente cuando se escanea
    // No se necesita búsqueda manual por QR
    if (tipo === 'qr') {
        // Si se intenta buscar manualmente con QR, abrir el escáner
        abrirLectorQR();
        return;
    } else if (tipo === 'placa') {
        // Búsqueda por placa
        var vehPla = $('#searchPlaca').val().trim();
        
        if (!vehPla) {
            alert('Por favor ingrese el número de placa');
            $('#searchPlaca').focus();
            return;
        }
        
        params = {
            buscarPorPlacaAjax: true,
            veh_pla: vehPla
        };
    } else {
        // Búsqueda por número de manifiesto
        // Búsqueda por número de manifiesto con formato M15-0001
        var plaCod = $('#searchPlaCod').val().trim();
        var numero = $('#searchInputManifiesto').val().trim();

        if (!plaCod) {
            alert('Por favor ingrese el código de planta');
            $('#searchPlaCod').focus();
            return;
        }
        
        if (!numero) {
            alert('Por favor ingrese el número de manifiesto');
            return;
        }
        
        // Convertir el número a entero y luego rellenar con ceros a la izquierda hasta 4 dígitos
        var numeroInt = parseInt(numero, 10);
        if (isNaN(numeroInt) || numeroInt < 1) {
            alert('El número de manifiesto debe ser un número válido mayor a 0');
            $('#searchInputManifiesto').focus();
            return;
        }

        // Rellenar con ceros a la izquierda hasta 4 dígitos
        var numeroFormateado = String(numeroInt).padStart(4, '0');
        
        params = {
            buscarPorManifiestoAjax: true,
            pla_cod: plaCod,
            numero_manifiesto: numeroFormateado
        };
    }
    
    // Mostrar loading
    mostrarLoading();
    
    // Usar $.get directamente ya que getDataJson muestra alertas automáticas
    $.get('', params, function(resp) {
        // Si resp es un string, intentar parsearlo
        if (typeof resp === 'string') {
            try {
                resp = JSON.parse(resp);
            } catch(e) {
                mostrarResultadoError('Error al procesar la respuesta del servidor');
                return;
            }
        }
        
        var tipoMensaje = resp.tipo_mensaje || (resp.success ? 'success' : 'error');
        
        if (resp.success && resp.data) {
            mostrarResultadoExito(resp.data, tipoMensaje);
        } else if (resp.data && (resp.tipo_mensaje == 'info' || resp.tipo_mensaje == 'error')) {
            // Documento encontrado pero con estado F o R
            mostrarResultadoEspecial(resp.data, resp.message, resp.tipo_mensaje);
        } else {
            mostrarResultadoError(resp.message || 'No existe el documento buscado', tipoMensaje);
        }
    }, 'json').fail(function(xhr, status, error) {
        mostrarResultadoError('Error al realizar la búsqueda: ' + (error || 'Error desconocido'), 'error');
    });
}

/**
 * Muestra el resultado exitoso con animación
 */
function mostrarResultadoExito(data, tipoMensaje) {
    tipoMensaje = tipoMensaje || 'success';
    var container = $('#resultContainer');
    var icon = $('#resultIcon');
    var message = $('#resultMessage');
    var dataContainer = $('#resultData');
    var btnAprobar = $('#btnAprobarManifiesto');
    var btnAprobarTexto = $('#btnAprobarTexto');
    
    // Ocultar loading
    ocultarLoading();

    // Validar fecha anterior
    var isExpired = false;
    var fechaRaw = data.Man_Fec || '';
    if (fechaRaw) {
        var fechaSolo = fechaRaw.split(' ')[0]; // YYYY-MM-DD
        if (fechaSolo && fechaSolo.includes('-')) {
            var parts = fechaSolo.split('-');
             // Javascript Date: Year, Month (0-11), Day
            var manDate = new Date(parts[0], parts[1]-1, parts[2]);
            var today = new Date();
            today.setHours(0,0,0,0);
            manDate.setHours(0,0,0,0);
            
            if (manDate < today) {
                isExpired = true;
            }
        }
    }
    
    // Configurar el contenedor
    if (isExpired) {
        container.removeClass('success error info warning').addClass('warning show');
        
        // Mostrar ícono de warning
        icon.removeClass('success error info warning animate').addClass('warning animate');
        icon.html('<span class="glyphicon glyphicon-warning-sign"></span>');
        
        // Mensaje
        message.text('Documento Expirado');
        message.css('color', '#ffc107');
    } else {
        container.removeClass('error info warning').addClass('success show');
        
        // Mostrar ícono de éxito con animación
        icon.removeClass('error info warning animate').addClass('success animate');
        icon.html('<span class="glyphicon glyphicon-ok-circle"></span>');
        
        // Mensaje
        message.text('Documento Encontrado');
        message.css('color', '#28a745');
    }
    
    // Guardar Man_Cod en el input oculto
    $('#hiddenManCod').val(data.Man_Cod || '');
    
    // Determinar el texto del botón según el estado
    var estado = data.Man_Tip || '';
    
    // Llenar datos para todos los casos
    $('#choferValue').text(data.chofer || 'N/A');
    $('#placaValue').text(data.Veh_Pla || 'N/A');
    
    // Formatear manifiestoValue como: M + Pla_Cod + Man_Num (con Man_Num de 4 dígitos rellenado con ceros)
    var manifiestoTexto = 'N/A';
    if (data.Pla_Cod && data.Man_Num) {
        // Rellenar Man_Num con ceros a la izquierda hasta 4 dígitos
        var manNumFormateado = String(data.Man_Num).padStart(4, '0');
        manifiestoTexto = 'M' + data.Pla_Cod + "-"+ manNumFormateado;
    } else if (data.Man_Num) {
        // Si no hay Pla_Cod, solo formatear Man_Num
        var manNumFormateado = String(data.Man_Num).padStart(4, '0');
        manifiestoTexto = 'M' + "-" + manNumFormateado;
    } else if (data.Man_Cod) {
        manifiestoTexto = data.Man_Cod;
    }
    $('#manifiestoValue').text(manifiestoTexto);
    
    // Formatear y mostrar la fecha
    // var fecha = data.Man_Fec || '';
    if (fechaRaw) {
        // Si la fecha viene en formato datetime, extraer solo la fecha
        var fechaFormateada = fechaRaw.split(' ')[0];
        // Convertir formato YYYY-MM-DD a DD/MM/YYYY
        if (fechaFormateada && fechaFormateada.includes('-')) {
            var partes = fechaFormateada.split('-');
            if (partes.length === 3) {
                fechaFormateada = partes[2] + '/' + partes[1] + '/' + partes[0];
            }
        }
        $('#fechaValue').text(fechaFormateada || fechaRaw);
    } else {
        $('#fechaValue').text('N/A');
    }
    
    // Ocultar nota por defecto
    $('#notaProceso').hide();
    $('#notaTexto').text('');
    
    // Mostrar datos y botón cuando el estado sea 'P' (Aprobar Entrada)
    if (isExpired) {
        // Mostrar datos con nota de expirado
        dataContainer.show();
        $('#notaTexto').text('Este manifiesto fue generado en fechas anteriores');
        $('#notaProceso').show();
        btnAprobar.hide();
    } else {
        // Mostrar datos y botón cuando el estado sea 'P' (Aprobar Entrada)
        if (estado == 'P') {
            dataContainer.show();
            
            // Actualizar todo el contenido del botón (icono + texto)
            btnAprobar.html('<span class="glyphicon glyphicon-ok"></span> <span id="btnAprobarTexto">Aprobar Entrada</span>');
            btnAprobar.data('estado-actual', 'P');
            btnAprobar.show();
        } else if (estado == 'A') {
            // Mostrar datos y botón cuando el estado sea 'A' (Aprobar Salida)
            dataContainer.show();
            // Actualizar todo el contenido del botón (icono + texto)
            btnAprobar.html('<span class="glyphicon glyphicon-ok"></span> <span id="btnAprobarTexto">Aprobar Salida</span>');
            btnAprobar.data('estado-actual', 'A');
            btnAprobar.show();
        } else if (estado == 'GE') {
            // Mostrar datos con nota cuando el estado sea 'GE'
            dataContainer.show();
            $('#notaTexto').text('** Vehículo en proceso entrada a descargar **');
            $('#notaProceso').show();
            btnAprobar.hide();
        } else if (estado == 'GS') {
            // Mostrar datos con nota cuando el estado sea 'GS'
            dataContainer.show();
            $('#notaTexto').text('** Vehículo en proceso de salida **');
            $('#notaProceso').show();
            btnAprobar.hide();
        } else {
            // No mostrar datos ni botón para otros estados (F, R, etc.)
            dataContainer.hide();
            btnAprobar.hide();
        }
    }
    
    // Hacer scroll hacia los resultados para que sean visibles (especialmente útil en móvil)
    setTimeout(function(){
        var containerTop = container.offset().top;
        var windowHeight = $(window).height();
        var scrollPosition = containerTop - (windowHeight / 2) + (container.outerHeight() / 2);
        
        $('html, body').animate({
            scrollTop: Math.max(0, scrollPosition - 50)
        }, 600);
    }, 100);
    
    // Reiniciar animación después de un tiempo
    setTimeout(function(){
        icon.removeClass('animate');
    }, 600);
}

/**
 * Muestra el resultado de error con animación
 */
function mostrarResultadoError(mensaje, tipoMensaje) {
    tipoMensaje = tipoMensaje || 'error';
    var container = $('#resultContainer');
    var icon = $('#resultIcon');
    var message = $('#resultMessage');
    var dataContainer = $('#resultData');
    var btnAprobar = $('#btnAprobarManifiesto');
    
    // Ocultar loading
    ocultarLoading();
    
    // Configurar el contenedor según el tipo de mensaje
    container.removeClass('success error info warning').addClass(tipoMensaje + ' show');
    
    // Mostrar ícono según el tipo de mensaje
    icon.removeClass('success error info warning animate').addClass(tipoMensaje + ' animate');
    
    if (tipoMensaje == 'warning') {
        icon.html('<span class="glyphicon glyphicon-warning-sign"></span>');
        message.css('color', '#ffc107');
    } else {
        icon.html('<span class="glyphicon glyphicon-remove-circle"></span>');
        message.css('color', '#dc3545');
    }
    
    // Mensaje
    message.text(mensaje || 'No existe el documento buscado');
    
    // Ocultar datos y botón
    dataContainer.hide();
    btnAprobar.hide();
    
    // Hacer scroll hacia los resultados para que sean visibles (especialmente útil en móvil)
    setTimeout(function(){
        var containerTop = container.offset().top;
        var windowHeight = $(window).height();
        var scrollPosition = containerTop - (windowHeight / 2) + (container.outerHeight() / 2);
        
        $('html, body').animate({
            scrollTop: Math.max(0, scrollPosition - 50)
        }, 600);
    }, 100);
    
    // Reiniciar animación después de un tiempo
    setTimeout(function(){
        icon.removeClass('animate');
    }, 600);
}

/**
 * Muestra resultado especial para documentos con estado F o R
 */
function mostrarResultadoEspecial(data, mensaje, tipoMensaje) {
    tipoMensaje = tipoMensaje || 'info';
    var container = $('#resultContainer');
    var icon = $('#resultIcon');
    var message = $('#resultMessage');
    var dataContainer = $('#resultData');
    var btnAprobar = $('#btnAprobarManifiesto');
    
    // Ocultar loading
    ocultarLoading();
    
    // Configurar el contenedor según el tipo de mensaje
    container.removeClass('success error info warning').addClass(tipoMensaje + ' show');
    
    // Mostrar ícono según el tipo de mensaje
    icon.removeClass('success error info warning animate').addClass(tipoMensaje + ' animate');
    
    if (tipoMensaje == 'info') {
        icon.html('<span class="glyphicon glyphicon-info-sign"></span>');
        message.css('color', '#17a2b8');
    } else {
        icon.html('<span class="glyphicon glyphicon-remove-circle"></span>');
        message.css('color', '#dc3545');
    }
    
    // Mensaje
    message.text(mensaje || '');
    
    // No mostrar datos para estados F o R
    dataContainer.hide();
    
    // Ocultar botón de aprobar
    btnAprobar.hide();
    
    // Hacer scroll hacia los resultados
    setTimeout(function(){
        var containerTop = container.offset().top;
        var windowHeight = $(window).height();
        var scrollPosition = containerTop - (windowHeight / 2) + (container.outerHeight() / 2);
        
        $('html, body').animate({
            scrollTop: Math.max(0, scrollPosition - 50)
        }, 600);
    }, 100);
    
    // Reiniciar animación después de un tiempo
    setTimeout(function(){
        icon.removeClass('animate');
    }, 600);
}

/**
 * Limpia los resultados mostrados
 */
function limpiarResultados() {
    $('#resultContainer').removeClass('show success error info warning');
    $('#resultIcon').removeClass('success error info warning animate').html('');
    $('#resultMessage').text('').css('color', '');
    $('#resultData').hide();
    // Ocultar y resetear el botón
    var btn = $('#btnAprobarManifiesto');
    btn.hide();
    btn.prop('disabled', false);
    btn.html('<span class="glyphicon glyphicon-ok"></span> <span id="btnAprobarTexto">Aprobar Entrada</span>');
    $('#hiddenManCod').val(''); // Vaciar el Man_Cod en cada búsqueda
    $('#searchInputManifiesto').val('');
    // Limpiar campo de placa
    $('#searchPlaca').val('');
}

/**
 * Inicia el escáner QR integrado en el área de búsqueda
 */
function iniciarEscannerIntegrado() {
    // Detener cualquier escáner previo
    if (html5QrcodeScanner && isScanning) {
        detenerEscanner();
    }
    
    // Limpiar el contenedor antes de inicializar
    $('#qr-reader').empty();
    try{
        // Inicializar el scanner
        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5Qrcode("qr-reader");
        }
    } catch(err) {
        console.error('Error al crear el escáner QR:', err);
        detenerEscanner();
        
        // Cambiar automáticamente a la opción de Manifiesto si falla la inicialización
        $('#radio_manifiesto').prop('checked', true).trigger('change');
        
        // Mostrar mensaje informativo
        mostrarResultadoError('No se pudo inicializar el escáner QR. Se ha cambiado automáticamente a búsqueda por Manifiesto.', 'warning');
        return;
    }
    
    // Iniciar el escaneo
    if (!isScanning) {
        html5QrcodeScanner.start(
            { facingMode: "environment" }, // Cámara trasera
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            function(decodedText, decodedResult) {
                // Cuando se detecta un código QR
                console.log('========================================');
                console.log('QR ESCANEADO - Información completa:');
                console.log('========================================');
                console.log('Texto decodificado (decodedText):', decodedText);
                console.log('Tipo de dato:', typeof decodedText);
                console.log('Longitud:', decodedText.length);
                console.log('Resultado completo (decodedResult):', decodedResult);
                console.log('Estructura del resultado:', JSON.stringify(decodedResult, null, 2));
                console.log('========================================');
                
                detenerEscanner();
                
                // Ocultar el botón de escanear nuevamente del contenedor
                $('#btnRescanQR').hide();
                
                // Buscar automáticamente con el código escaneado
                var params = {
                    buscarPorQRAjax: true,
                    codigo_qr: decodedText
                };
                
                console.log('Parámetros enviados al servidor:', params);
                
                mostrarLoading();
                
                $.get('', params, function(resp) {
                    console.log('Respuesta del servidor (raw):', resp);
                    
                    if (typeof resp === 'string') {
                        try {
                            resp = JSON.parse(resp);
                        } catch(e) {
                            console.error('Error al parsear respuesta:', e);
                            mostrarResultadoError('Error al procesar la respuesta del servidor', 'error');
                            return;
                        }
                    }
                    
                    console.log('Respuesta del servidor (parsed):', resp);
                    
                    var tipoMensaje = resp.tipo_mensaje || (resp.success ? 'success' : 'error');
                    
                    if (resp.success && resp.data) {
                        console.log('Datos encontrados:', resp.data);
                        mostrarResultadoExito(resp.data, tipoMensaje);
                        // Ocultar el escáner después de escanear exitosamente
                        $('#searchQRGroup').hide();
                        // Mostrar botón para escanear nuevamente
                        setTimeout(function(){
                            $('#rescanButtonGroup').show();
                        }, 800);
                    } else if (resp.data && (resp.tipo_mensaje == 'info' || resp.tipo_mensaje == 'error')) {
                        // Documento encontrado pero con estado F o R
                        mostrarResultadoEspecial(resp.data, resp.message, resp.tipo_mensaje);
                        // Ocultar el escáner después de escanear
                        $('#searchQRGroup').hide();
                        // Mostrar botón para volver a escanear
                        setTimeout(function(){
                            $('#rescanButtonGroup').show();
                        }, 800);
                    } else {
                        mostrarResultadoError(resp.message || 'No existe el documento buscado', tipoMensaje);
                        // Ocultar el escáner después de escanear
                        $('#searchQRGroup').hide();
                        // Mostrar botón para volver a escanear en caso de error
                        setTimeout(function(){
                            $('#rescanButtonGroup').show();
                        }, 800);
                    }
                }, 'json').fail(function(xhr, status, error) {
                    mostrarResultadoError('Error al realizar la búsqueda: ' + (error || 'Error desconocido'));
                    // Ocultar el escáner después de error
                    $('#searchQRGroup').hide();
                    // Mostrar botón para volver a escanear en caso de error
                    setTimeout(function(){
                        $('#rescanButtonGroup').show();
                    }, 800);
                });
            },
            function(errorMessage) {
                // Error al escanear (se puede ignorar para no saturar la consola)
                // console.log(errorMessage);
            }
        ).then(function() {
            // El escáner se inició correctamente
            isScanning = true;
        }        ).catch(function(err) {
            console.error('Error al iniciar la cámara:', err);
            
            // Detectar el tipo de error
            var errorMessage = '';
            var errorName = err.name || err.toString() || '';
            
            // Verificar si es un error de permiso denegado
            if (errorName.includes('NotAllowedError') || 
                errorName.includes('PermissionDeniedError') || 
                errorName.includes('NotAllowed') ||
                err.toString().includes('NotAllowedError') ||
                err.toString().includes('Permission denied') ||
                err.toString().includes('permission denied')) {
                errorMessage = 'Permiso de cámara denegado. Se ha cambiado automáticamente a búsqueda por Manifiesto.';
            } else if (errorName.includes('NotFoundError') || 
                        errorName.includes('NotFound') ||
                        err.toString().includes('NotFoundError') ||
                        err.toString().includes('device not found')) {
                errorMessage = 'No se encontró la cámara. Se ha cambiado automáticamente a búsqueda por Manifiesto.';
            } else {
                errorMessage = 'No se pudo acceder a la cámara. Se ha cambiado automáticamente a búsqueda por Manifiesto.';
            }
            
            // Limpiar sin intentar detener (porque nunca se inició)
            isScanning = false;
            $('#qr-reader').empty();
            if (html5QrcodeScanner) {
                try {
                    html5QrcodeScanner.clear();
                } catch(e) {
                    // Ignorar errores al limpiar
                }
                html5QrcodeScanner = null;
            }
            
            // Cambiar inmediatamente a la opción de Manifiesto si falla la cámara o se niega el permiso
            setTimeout(function() {
                $('#radio_manifiesto').prop('checked', true).trigger('change');
                
                // Mostrar mensaje informativo
                mostrarResultadoError(errorMessage, 'warning');
            }, 100);
        });
        
        // isScanning = true;
        // Solo marcar como escaneando si se inició correctamente
        // (se moverá dentro del start si es exitoso)
    }
}

/**
 * Detiene el escáner QR
 */
function detenerEscanner() {
    if (html5QrcodeScanner && isScanning) {
        html5QrcodeScanner.stop().then(function() {
            isScanning = false;
            $('#qr-reader').empty();
            // Limpiar completamente el escáner
            if (html5QrcodeScanner) {
                try {
                    html5QrcodeScanner.clear();
                } catch(e) {
                    // Ignorar errores al limpiar
                }
            }
            html5QrcodeScanner = null;
        }).catch(function(err) {
            console.log('El escáner no estaba corriendo, limpiando...');
            isScanning = false;
            $('#qr-reader').empty();
            if (html5QrcodeScanner) {
                try {
                    html5QrcodeScanner.clear();
                } catch(e) {
                    // Ignorar errores al limpiar
                }
            }
            html5QrcodeScanner = null;
        });
    } else {
        // Si no hay escáner o no está escaneando, solo limpiar
        if ($('#qr-reader').length) {
            $('#qr-reader').empty();
        }
        isScanning = false;
        if (html5QrcodeScanner) {
            try {
                html5QrcodeScanner.clear();
            } catch(e) {
                // Ignorar errores al limpiar
            }
        }
        html5QrcodeScanner = null;
    }
}

/**
 * Muestra el indicador de carga
 */
function mostrarLoading() {
    var container = $('#resultContainer');
    var icon = $('#resultIcon');
    var message = $('#resultMessage');
    
    container.removeClass('success error info warning').addClass('show');
    icon.removeClass('success error info warning animate').html('<span class="glyphicon glyphicon-refresh glyphicon-spin"></span>');
    message.text('Buscando...');
    $('#resultData').hide();
    $('#btnAprobarManifiesto').hide(); // Ocultar botón al iniciar nueva búsqueda
}

/**
 * Oculta el indicador de carga
 */
function ocultarLoading() {
    // La función mostrarResultadoExito o mostrarResultadoError se encargará de esto
}

/**
 * Aprobar entrada o salida del manifiesto
 */
function aprobarManifiesto(man_cod, estado_actual) {
    var btn = $('#btnAprobarManifiesto');
    
    // Deshabilitar botón mientras se procesa
    btn.prop('disabled', true);
    // Actualizar todo el contenido del botón
    btn.html('<span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Procesando...');
    
    // Realizar petición AJAX
    $.get('', {
        aprobarManifiestoAjax: true,
        man_cod: man_cod
    }, function(resp) {
        if (typeof resp === 'string') {
            try {
                resp = JSON.parse(resp);
            } catch(e) {
                alert('Error al procesar la respuesta del servidor');
                btn.prop('disabled', false);
                var textoOriginal = estado_actual == 'P' ? 'Aprobar Entrada' : 'Aprobar Salida';
                btn.html('<span class="glyphicon glyphicon-ok"></span> <span id="btnAprobarTexto">' + textoOriginal + '</span>');
                return;
            }
        }
        
        if (resp.success) {
            // Actualizar el estado en el botón
            var nuevo_estado = resp.nuevo_estado;
            
            if (nuevo_estado == 'GE') {
                // Se aprobó la entrada, mostrar "Listo" y luego ocultar el botón
                btn.html('<span class="glyphicon glyphicon-ok"></span> Listo');
                btn.prop('disabled', false);
                
                // Después de 2 segundos, ocultar el botón
                setTimeout(function(){
                    btn.hide();
                }, 500);
            } else if (nuevo_estado == 'GS') {
                // Se aprobó la salida, mostrar "Listo" y luego ocultar el botón
                btn.html('<span class="glyphicon glyphicon-ok"></span> Listo');
                btn.prop('disabled', false);
                
                // Después de 2 segundos, ocultar el botón
                setTimeout(function(){
                    btn.hide();
                }, 500);
            }
        } else {
            alert('Error: ' + (resp.message || 'No se pudo aprobar el manifiesto'));
            btn.prop('disabled', false);
            var textoOriginal = estado_actual == 'P' ? 'Aprobar Entrada' : 'Aprobar Salida';
            btn.html('<span class="glyphicon glyphicon-ok"></span> <span id="btnAprobarTexto">' + textoOriginal + '</span>');
        }
    }, 'json').fail(function(xhr, status, error) {
        alert('Error al aprobar el manifiesto: ' + (error || 'Error desconocido'));
        btn.prop('disabled', false);
        var textoOriginal = estado_actual == 'P' ? 'Aprobar Entrada' : 'Aprobar Salida';
        btn.html('<span class="glyphicon glyphicon-ok"></span> <span id="btnAprobarTexto">' + textoOriginal + '</span>');
    });
}
