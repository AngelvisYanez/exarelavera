/**
 * @fileoverview Librería con funciones de validaciones para el control de IMEI
 *
 * @author Exa Contable
 * @version 1.0
 * Fecha de creación: 2026-01-19
 */

var gridImei;
var gridProductos;
var listaImei = []; // Lista de IMEI agregados en la sesión actual
var mapaImeiCod = {}; // Mapa de Ime_Num -> Ime_Cod para actualizaciones
var imeiOriginales = []; // Lista de IMEI originales para comparar cambios
var mapaImeiCodPorIndice = {}; // Mapa de índice -> Ime_Cod para obtener Ime_Cod por posición
var modoEdicion = false; // Indica si estamos editando IMEI existentes
var cargarImeiAlAbrir = false; // Flag para indicar si se deben cargar IMEI al abrir el modal
var proCodParaCargar = null; // Código de producto a cargar cuando se abra el modal


$(document).ready(function() {
    // Inicializar componentes
    inicializarModalBusqueda();
    inicializarGridProductos();
    inicializarGridImeiPreview();
    inicializarModalVistaPrevia();
    inicializarModalImei();
    
    // Evento para el formulario de importación
    $('#formImport').on('submit', function(e) {
        e.preventDefault();
        importarExcelDesdeModal();
    });
    
});


/* Inicializar grid de IMEI */
function inicializarGridImei() {
    gridImei = $('#gridImei').jqGrid({
        url: '',
        postData: {
            listImei: true
        },
        datatype: 'json',
        mtype: 'POST',
        colNames: ['Código', 'Producto', 'Marca', 'Stock', 'IMEI', 'Tipo', 'Estado', 'Fecha', 'Acciones'],
        colModel: [
            { label: 'Cod.Int.', name: 'Ime_Cod', index: 'Ime_Cod', width: 80, key: true, hidden: true },
            { label: 'Producto', name: 'Pro_Nom', index: 'Pro_Nom', width: 300 },
            { label: 'Marca', name: 'Mar_Des', index: 'Mar_Des', width: 150 },
            { label: 'Stock', name: 'Stk_Can', index: 'Stk_Can', width: 100, align: 'right' },
            { label: 'IMEI', name: 'Ime_Num', index: 'Ime_Num', width: 180 },
            { label: 'Tipo', name: 'Ime_Tip_Des', index: 'Ime_Tip_Des', width: 130,
                formatter: function(cellvalue) {
                    var colores = {
                        'Pendiente': '#FFA500',
                        'Vendido': '#28a745',
                        'Con Novedad': '#dc3545',
                        'Rechazado': '#6c757d'
                    };
                    var color = colores[cellvalue] || '#000';
                    return '<span style="color: ' + color + '; font-weight: bold;">' + (cellvalue || 'Pendiente') + '</span>';
                }
            },
            { label: '&nbsp;', name: 'actions', index: 'actions', width: 120, sortable: false,
                formatter: function(cellvalue, options, rowObject) {
                    var editBtn = '<button class="btn btn-xs btn-primary" onclick="editarImei(' + rowObject.Ime_Cod + ')" title="Editar">' +
                                '<span class="glyphicon glyphicon-edit"></span></button> ';
                    var deleteBtn = '<button class="btn btn-xs btn-danger" onclick="eliminarImei(' + rowObject.Ime_Cod + ')" title="Eliminar">' +
                                    '<span class="glyphicon glyphicon-trash"></span></button>';
                    return editBtn + deleteBtn;
                }
            }
        ],
        rowNum: 25,
        rowList: [10, 25, 50, 100, 200],
        pager: '#pagerImei',
        sortname: 'Ime_Sys',
        sortorder: 'desc',
        viewrecords: true,
        caption: 'Lista de IMEI',
        height: 500,
        autowidth: true,
        shrinkToFit: false,
        loadComplete: function() {
            $(this).setGridWidth($('.imei-grid').width(), true);
        }
    });
    
    gridImei.jqGrid('navGrid', '#pagerImei', {
        edit: false,
        add: false,
        del: false,
        search: true,
        refresh: true
    });
}


/* Inicializar modal de IMEI */
function inicializarModalImei() {
    // Inicializar tabs de jQuery UI
    $('#imeiTabs').tabs();
    
    $('#imeiModal').dialog({
        width: 800,
        height: 650,
        modal: true,
        resizable: true,
        autoOpen: false,
        open: function() {
            // Activar el primer tab
            $('#imeiTabs').tabs('option', 'active', 0);
            
            // Solo limpiar si no estamos cargando IMEI de un producto
            if (!cargarImeiAlAbrir) {
                modoEdicion = false;
                limpiarListaImei();
                actualizarListaImei();
                $('#imeiManualInput').val('');
                // Restaurar título y botones a modo inserción
                $('#imeiModal').dialog('option', 'title', 'Agregar IMEI');
                $('#btnAgregarImei').html('<span class="glyphicon glyphicon-ok"></span> Agregar IMEI');
                $('#btnGuardarImei').html('<span class="glyphicon glyphicon-floppy-disk"></span> Guardar Todos los IMEI');
            } else {
                // Cargar IMEI del producto después de abrir el modal
                if (proCodParaCargar) {
                    setTimeout(function() {
                        cargarImeiEnModal(proCodParaCargar);
                        cargarImeiAlAbrir = false;
                        proCodParaCargar = null;
                    }, 150);
                }
            }
        }
    });
}

/* Procesar IMEI ingresados manualmente */
function procesarImeiManual() {
    var input = $('#imeiManualInput').val().trim();
    if (!input) {
        $.alert('Debe ingresar al menos un IMEI', null, 'remove');
        return;
    }
    
    // Separar por líneas o comas y limpiar
    var imeis = input.split(/[\n,;]+/).map(function(imei) {
        return imei.trim().replace(/\D/g, '');
    }).filter(function(imei) {
        return imei.length === 15;
    });
    
    // Si estamos en modo edición, actualizar la lista y el mapeo basándose en la posición
    if (modoEdicion) {
        // Verificar que la cantidad de IMEI coincida con los originales
        if (imeis.length !== imeiOriginales.length) {
            $.alert('La cantidad de IMEI debe coincidir con los originales. No se pueden agregar o eliminar IMEI en modo edición.', null, 'remove');
            return;
        }
        
        // Actualizar listaImei con los IMEI del textarea (manteniendo el orden)
        listaImei = [];
        var nuevoMapaImeiCod = {};
        
        imeis.forEach(function(imeiNuevo, index) {
            if (index < imeiOriginales.length) {
                // Hay un IMEI original en esta posición
                var imeiOriginal = imeiOriginales[index];
                
                // Obtener el Ime_Cod del original usando el índice (más confiable)
                var Ime_Cod = mapaImeiCodPorIndice[index];
                
                // Si no está en el mapeo por índice, intentar obtener del mapeo por IMEI
                if (!Ime_Cod && imeiOriginal && mapaImeiCod[imeiOriginal]) {
                    Ime_Cod = mapaImeiCod[imeiOriginal];
                    // Actualizar el mapeo por índice para futuras referencias
                    mapaImeiCodPorIndice[index] = Ime_Cod;
                }
                
                // Actualizar el mapeo por IMEI nuevo con el Ime_Cod del original
                if (Ime_Cod) {
                    nuevoMapaImeiCod[imeiNuevo] = Ime_Cod;
                    // Mantener el mapeo por índice actualizado
                    mapaImeiCodPorIndice[index] = Ime_Cod;
                }
            }
            listaImei.push(imeiNuevo);
        });
        
        // Actualizar el mapa
        mapaImeiCod = nuevoMapaImeiCod;
        
        // Actualizar la lista visual
        actualizarListaImei();
        actualizarDisplayImei();
        
        $.alert('Lista de IMEI actualizada. Haga clic en "Actualizar IMEI" para guardar los cambios en la base de datos.', null, 'success');
        return;
    }
    
    // Modo inserción normal
    var imeisValidos = [];
    var imeisInvalidos = [];
    var imeisDuplicados = [];
    var imeisExistentes = [];
    var imeisParaValidar = [];
    
    // Primero validar formato y duplicados en la lista actual
    imeis.forEach(function(imei) {
        if (imei.length === 15) {
            // Verificar si ya está en la lista actual
            if (listaImei.indexOf(imei) === -1) {
                imeisParaValidar.push(imei);
            } else {
                imeisDuplicados.push(imei);
            }
        } else if (imei.length > 0) {
            imeisInvalidos.push(imei);
        }
    });
    
    if (imeisInvalidos.length > 0) {
        $.alert('Algunos IMEI no son válidos (deben tener 15 dígitos):\n' + imeisInvalidos.join(', '), null, 'remove');
    }
    
    if (imeisDuplicados.length > 0) {
        $.alert('Algunos IMEI ya están en la lista actual:\n' + imeisDuplicados.join(', '), null, 'remove');
    }
    
    if (imeisParaValidar.length === 0) {
        if (imeisInvalidos.length === 0 && imeisDuplicados.length === 0) {
            $.alert('No se agregaron nuevos IMEI. Verifique que los IMEI tengan 15 dígitos y no estén duplicados.', null, 'remove');
        }
        return;
    }
    
    // Validar contra la base de datos
    var totalValidar = imeisParaValidar.length;
    var validados = 0;
    var imeisAgregados = 0;
    
    imeisParaValidar.forEach(function(imei) {
        $.get('', {
            verificarImei: true,
            Ime_Num: imei
        }, function(response) {
            validados++;
            if (response.existe) {
                imeisExistentes.push(imei);
            } else {
                imeisValidos.push(imei);
                listaImei.push(imei);
                imeisAgregados++;
            }
            
            // Cuando se validen todos
            if (validados === totalValidar) {
                var mensajes = [];
                
                if (imeisExistentes.length > 0) {
                    mensajes.push('Los siguientes IMEI ya existen en el sistema y no se agregaron:\n' + imeisExistentes.join(', '));
                }
                
                if (imeisAgregados > 0) {
                    mensajes.push('Se agregaron ' + imeisAgregados + ' IMEI correctamente');
                    $('#imeiManualInput').val('');
                    actualizarListaImei();
                    actualizarDisplayImei();
                }
                
                if (mensajes.length > 0) {
                    $.alert(mensajes.join('\n\n'), null, imeisAgregados > 0 ? 'success' : 'remove');
                } else {
                    $.alert('No se agregaron nuevos IMEI.', null, 'remove');
                }
            }
        }, 'json').fail(function() {
            validados++;
            // En caso de error, no agregar el IMEI
            if (validados === totalValidar) {
                if (imeisAgregados > 0) {
                    $.alert('Se agregaron ' + imeisAgregados + ' IMEI. Algunos no pudieron ser validados.', null, 'remove');
                    $('#imeiManualInput').val('');
                    actualizarListaImei();
                    actualizarDisplayImei();
                } else {
                    $.alert('Error al validar los IMEI. Intente nuevamente.', null, 'remove');
                }
            }
        });
    });
}

/* Limpiar input manual */
function limpiarImeiManual() {
    $('#imeiManualInput').val('');
}


/* Importar Excel desde el modal */
function importarExcelDesdeModal() {
    var fileInput = $('#fileExcel')[0];
    if (!fileInput.files || !fileInput.files[0]) {
        $.alert('Debe seleccionar un archivo Excel', null, 'remove');
        return;
    }
    
    var formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('import', '1');
    formData.append('Pro_Cod', $('#Pro_Cod').val()); // Enviar el producto seleccionado
    
    // Mostrar el loader
    if ($('#loader').length) {
        $('#loader').show();
    } else if (document.getElementById('loader')) {
        document.getElementById('loader').style.display = 'block';
    }
    
    $.ajax({
        url: '',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // Ocultar el loader
            if ($('#loader').length) {
                $('#loader').fadeOut('slow');
            } else if (document.getElementById('loader')) {
                $(document.getElementById('loader')).fadeOut('slow');
            }
            
            if (typeof response === 'string') {
                response = JSON.parse(response);
            }
            
            if (response.success) {
                var mensaje = response.message;
                if (response.errores && response.errores.length > 0) {
                    mensaje += '<br/><br/><strong>Errores encontrados:</strong><br/>';
                    mensaje += response.errores.join('<br/>');
                }
                $.alert(mensaje, null, 'success');
                $('#fileExcel').val('');
                if (gridProductos && gridProductos.length > 0) {
                    gridProductos.trigger('reloadGrid');
                }
                $('#imeiModal').dialog('close');
            } else {
                $.alert(response.message, null, 'remove');
            }
        },
        error: function() {
            // Ocultar el loader
            if ($('#loader').length) {
                $('#loader').fadeOut('slow');
            } else if (document.getElementById('loader')) {
                $(document.getElementById('loader')).fadeOut('slow');
            }
            
            $.alert('Error al importar el archivo', null, 'remove');
        }
    });
}

/* Actualizar la lista visual de IMEI */
function actualizarListaImei() {
    var container = $('#imeiListItems');
    container.empty();
    
    if (listaImei.length === 0) {
        container.append($('<li></li>').text('No hay IMEI agregados').css('color', '#999'));
    } else {
        listaImei.forEach(function(imei, index) {
            var li = $('<li></li>')
                .css('padding', '5px')
                .css('border-bottom', '1px solid #eee');
            
            var span = $('<span></span>').text((index + 1) + '. ' + imei);
            var btn = $('<button></button>')
                .addClass('btn btn-xs btn-danger pull-right')
                .html('<span class="glyphicon glyphicon-remove"></span>')
                .click(function() {
                    listaImei.splice(index, 1);
                    actualizarListaImei();
                    actualizarDisplayImei();
                });
            
            li.append(span).append(btn);
            container.append(li);
        });
    }
}

/* Actualizar el display del campo IMEI */
function actualizarDisplayImei() {
    var total = listaImei.length;
    $('#totalImeiCount').text(total);
    // El campo Ime_Num_Display fue eliminado, solo actualizamos el contador
}

/* Limpiar lista de IMEI */
function limpiarListaImei() {
    listaImei = [];
    actualizarListaImei();
    actualizarDisplayImei();
}

/* Guardar todos los IMEI del modal */
function guardarImeiDelModal() {
    if (!$('#Pro_Cod').val()) {
        $.alert('Debe seleccionar un producto primero', null, 'remove');
        $('#imeiModal').dialog('close');
        return;
    }
    
    // Si estamos en modo edición, actualizar todos los IMEI de listaImei
    if (modoEdicion) {
        if (listaImei.length === 0) {
            $.alert('No hay IMEI para actualizar', null, 'remove');
            return;
        }
        
        // Verificar que la cantidad de IMEI coincida con los originales
        if (listaImei.length !== imeiOriginales.length) {
            $.alert('La cantidad de IMEI debe coincidir con los originales. No se pueden agregar o eliminar IMEI en modo edición.', null, 'remove');
            return;
        }
        
        // Crear lista de IMEI para actualizar basándose en la posición
        var imeisParaActualizar = [];
        
        // Comparar por posición: cada IMEI en listaImei corresponde a uno en imeiOriginales
        listaImei.forEach(function(imeiNuevo, index) {
            var imeiOriginal = imeiOriginales[index];
            
            // Obtener el Ime_Cod usando el índice (más confiable que el mapeo por IMEI)
            var Ime_Cod = mapaImeiCodPorIndice[index];
            
            // Si no está en el mapeo por índice, intentar obtener del mapeo por IMEI original
            if (!Ime_Cod && imeiOriginal && mapaImeiCod[imeiOriginal]) {
                Ime_Cod = mapaImeiCod[imeiOriginal];
                // Actualizar el mapeo por índice para futuras referencias
                mapaImeiCodPorIndice[index] = Ime_Cod;
            }
            
            // Si tenemos el Ime_Cod, agregar a la lista (solo actualizar si cambió, optimización)
            if (Ime_Cod) {
                if (imeiOriginal !== imeiNuevo) {
                    // El IMEI cambió, agregar para actualizar
                    imeisParaActualizar.push({ 
                        tipo: 'actualizar', 
                        imeiOriginal: imeiOriginal, 
                        imeiNuevo: imeiNuevo,
                        Ime_Cod: Ime_Cod
                    });
                }
                // Si no cambió, no se actualiza (optimización - solo actualiza si hay cambios)
            }
        });
        
        // Si no hay cambios, informar
        if (imeisParaActualizar.length === 0) {
            $.alert('No se detectaron cambios en los IMEI', null, 'remove');
            return;
        }
        
        // Mostrar el loader
        if ($('#loader').length) {
            $('#loader').show();
        } else if (document.getElementById('loader')) {
            document.getElementById('loader').style.display = 'block';
        }
        
        // Actualizar solo los IMEI que cambiaron
        var actualizados = 0;
        var errores = [];
        var total = imeisParaActualizar.length;
        var index = 0;
        
        function actualizarSiguiente() {
            if (index >= total) {
                // Ocultar el loader
                if ($('#loader').length) {
                    $('#loader').fadeOut('slow');
                } else if (document.getElementById('loader')) {
                    $(document.getElementById('loader')).fadeOut('slow');
                }
                
                var mensaje = 'Se actualizaron ' + actualizados + ' de ' + total + ' IMEI correctamente';
                if (errores.length > 0) {
                    mensaje += '<br/><br/><strong>Errores:</strong><br/>' + errores.join('<br/>');
                }
                $.alert(mensaje, null, actualizados > 0 ? 'success' : 'remove');
                
                if (actualizados > 0) {
                    limpiarListaImei();
                    limpiarFormulario();
                    if (gridProductos && gridProductos.length > 0) {
                        gridProductos.trigger('reloadGrid');
                    }
                    $('#imeiModal').dialog('close');
                }
                return;
            }
            
            var item = imeisParaActualizar[index];
            if (item.tipo === 'actualizar') {
                var data = {
                    saveImei: true,
                    Ime_Cod: item.Ime_Cod,
                    Pro_Cod: $('#Pro_Cod').val(),
                    Ime_Num: item.imeiNuevo
                };
                
                $.post('', data, function(response) {
                    if (response.success) {
                        actualizados++;
                    } else {
                        errores.push('IMEI ' + item.imeiOriginal + ' -> ' + item.imeiNuevo + ': ' + response.message);
                    }
                    index++;
                    actualizarSiguiente();
                }, 'json').fail(function() {
                    errores.push('IMEI ' + item.imeiOriginal + ': Error de conexión');
                    index++;
                    actualizarSiguiente();
                });
            } else {
                index++;
                actualizarSiguiente();
            }
        }
        
        actualizarSiguiente();
        return;
    }
    
    // Modo inserción normal
    if (listaImei.length === 0) {
        $.alert('No hay IMEI para guardar', null, 'remove');
        return;
    }
    
    // Guardar todos los IMEI
    var guardados = 0;
    var omitidos = 0;
    var errores = [];
    var total = listaImei.length;
    var index = 0;
    
    function guardarSiguiente() {
        if (index >= total) {
            // Ocultar el loader
            if ($('#loader').length) {
                $('#loader').fadeOut('slow');
            } else if (document.getElementById('loader')) {
                $(document.getElementById('loader')).fadeOut('slow');
            }
            
            // Terminó de guardar todos
            var mensaje = 'Se guardaron ' + guardados + ' de ' + total + ' IMEI correctamente';
            if (omitidos > 0) {
                mensaje += '<br/>Se omitieron ' + omitidos + ' IMEI que ya existían en el sistema';
            }
            if (errores.length > 0) {
                mensaje += '<br/><br/><strong>Errores:</strong><br/>' + errores.join('<br/>');
            }
            $.alert(mensaje, null, guardados > 0 ? 'success' : 'remove');
            
            if (guardados > 0) {
                limpiarListaImei();
                limpiarFormulario();
                if (gridProductos && gridProductos.length > 0) {
                    gridProductos.trigger('reloadGrid');
                }
                $('#imeiModal').dialog('close');
            }
            return;
        }
        
        var imei = listaImei[index];
        var Ime_Cod = mapaImeiCod[imei] || ''; // Obtener Ime_Cod si existe en el mapa
        
        // Preparar datos para guardar/actualizar
        var data = {
            saveImei: true,
            Pro_Cod: $('#Pro_Cod').val(),
            Ime_Num: imei
        };
        
        // Si tiene Ime_Cod, significa que es una actualización
        if (Ime_Cod) {
            data.Ime_Cod = Ime_Cod;
        }
        
        // Guardar o actualizar el IMEI
        $.post('', data, function(response) {
            if (response.success) {
                guardados++;
                // Si se insertó un nuevo IMEI, actualizar el mapa con el nuevo Ime_Cod si viene en la respuesta
                if (!Ime_Cod && response.Ime_Cod) {
                    mapaImeiCod[imei] = response.Ime_Cod;
                }
            } else {
                // Si el IMEI fue omitido (ya existe), no es un error
                if (response.omitido || (response.message && response.message.indexOf('ya existe') >= 0)) {
                    omitidos++;
                } else {
                    errores.push('IMEI ' + imei + ': ' + response.message);
                }
            }
            index++;
            guardarSiguiente();
        }, 'json').fail(function() {
            errores.push('IMEI ' + imei + ': Error de conexión');
            index++;
            guardarSiguiente();
        });
    }
    
    // Mostrar el loader en lugar del mensaje
    if ($('#loader').length) {
        $('#loader').show();
    } else if (document.getElementById('loader')) {
        document.getElementById('loader').style.display = 'block';
    }
    
    guardarSiguiente();
}

/* Guardar IMEI (función original mantenida para compatibilidad) */
function saveImeiData() {
    if (!$('#Pro_Cod').val()) {
        $.alert('Debe seleccionar un producto', null, 'remove');
        $('#Pro_Cod').focus();
        return;
    }
    
    if (listaImei.length === 0) {
        $.alert('Debe agregar al menos un IMEI. Haga clic en el botón "Agregar IMEI"', null, 'remove');
        $('#imeiModal').dialog('open');
        return;
    }
    
    guardarImeiDelModal();
}

/* Editar IMEI */
function editarImei(Ime_Cod) {
    $.post('', { getImei: true, Ime_Cod: Ime_Cod
    }, function(response) {
        if (response) {
            $('#Ime_Cod').val(response.Ime_Cod);
            $('#Pro_Cod').val(response.Pro_Cod);
            $('#producto').val(response.Pro_Nom || '');
            $('#Ime_Num').val(response.Ime_Num);
            
            // Actualizar información del producto
            $('#Pro_Nom_Info').val(response.Pro_Nom || '');
            $('#Mar_Des_Info').val(response.Mar_Des || 'NINGUNA');
            
            // Obtener stock del producto
            $.get('', {
                'Pro_Cod': response.Pro_Cod,
                'ajaxProd': true
            }, function(resp) {
                if (resp['success'] === true) {
                    $('#Stk_Can_Info').val(resp['prod']['Stk_Can'] || 0);
                }
            }, 'json');
            
            // Scroll al formulario
            $('html, body').animate({
                scrollTop: $('#formImei').offset().top - 100
            }, 500);
        }
    }, 'json').fail(function() {
        $.alert('Error al cargar el IMEI', null, 'remove');
    });
}

/* Eliminar IMEI */
function eliminarImei(Ime_Cod) {
    $.createDialogConfirm('¿Está seguro de eliminar este IMEI?', null, function() {
        $.post('', {
            deleteImei: true,
            Ime_Cod: Ime_Cod
        }, function(response) {
            if (response.success) {
                $.alert(response.message, null, 'success');
                gridImei.trigger('reloadGrid');
            } else {
                $.alert(response.message, null, 'remove');
            }
        }, 'json').fail(function() {
            $.alert('Error al eliminar el IMEI', null, 'remove');
        });
    });
}
/* Limpiar formulario */
function limpiarFormulario() {
    $('#formImei')[0].reset();
    $('#Ime_Cod').val('');
    $('#Pro_Cod').val('');
    $('#producto').val('');
    $('#Pro_Nom_Info').val('');
    $('#Mar_Des_Info').val('');
    $('#Stk_Can_Info').val('');
    limpiarListaImei();
}

/* Importar desde Excel (función original mantenida para compatibilidad) */
function importarExcel() {
    // Redirigir al modal
    $('#imeiModal').dialog('open');
    $('a[href="#tabExcel"]').tab('show');
}
/* Validar formato de IMEI */
function validarImei(imei) {
    // Remover caracteres no numéricos
    imei = imei.replace(/\D/g, '');
    // El IMEI debe tener 15 dígitos
    return imei.length === 15;
}

// Validación en tiempo real del campo IMEI (ya no se usa, pero se mantiene por compatibilidad)
$(document).on('input', '#Imei_Num', function() {
    var imei = $(this).val().replace(/\D/g, '');
    if (imei.length > 0 && imei.length !== 15) {
        $(this).addClass('error');
    } else {
        $(this).removeClass('error');
    }
});

// Validar IMEI antes de agregar a la lista
function validarFormatoImei(imei) {
    imei = imei.replace(/\D/g, '');
    return imei.length === 15;
}

/* Inicializar modal de búsqueda de productos */
function inicializarModalBusqueda() {
    var currentUrl = window.location.pathname;
    var gridUrl = currentUrl + '?searchPro=true';
    
    $.createSearchDialog('proDialog', [
        { label: 'C&oacute;d.Int.', name: 'Pro_Cod', key: true, width: 30, align: "center"},
        { label: 'Descripción', name: 'Ite_Lar', width: 130, classes: 'highlightSearch' },
        { label: 'Detalle', name: 'Pro_Obs', width: 80 },
        { label: 'Marca', name: 'Mar_Des', width: 40, align: "center", classes: 'highlightSearch' },
        { label: 'Categoria', name: 'Cat_Des', width: 20, align: "center" },
        { label: 'Stock', name: 'Stk_Can', width: 25, align: "center" },
        { label: '<i class="glyphicon glyphicon-arrrow-right"></i>', name: 'act1', width: 20, align: 'center', viewable: false,
            formatter: 'gridButton',
            formatoptions: { action: SelectProd }
        }
    ], null, 900, null, { 
        url: gridUrl, 
        datatype: 'json', 
        mtype: 'GET',
        rowNum: 100,
        rowList: [100, 250, 500, 1000, 2000],
        postData: {
            search: function () {
                return $('#proForm input[name="search"]').val();
            },
            op_opciones: function () {
                return $('#proForm select[name="op_opciones"]').val();
            }
        },
        loadComplete: function(data) {
            // Opcional: resaltar el texto de búsqueda
            var searchText = $('#proForm input[name="search"]').val();
            if (searchText && searchText.trim()) {
                $(this).highlightSearch(searchText.trim());
            }
        }
    },
    { text: 'search', title: 'Producto',
        options: [
            { label: '&nbsp;&nbsp;Descripci&oacute;n&nbsp;&nbsp;', value: 'd' }, 
            { label: '&nbsp;&nbsp;Marca&nbsp;&nbsp;', value: 'm' }
        ]
    });
}

/* Seleccionar producto del modal */
function SelectProd(producto) {
    $('#Pro_Cod').val(producto.Pro_Cod);
    $('#producto').val(producto.Ite_Lar || '');
    $('#proDialog').dialog('close');
    
    // Actualizar información del producto
    $.get('', {
        'Pro_Cod': producto.Pro_Cod,
        'ajaxProd': true
    }, function(response) {
        if (response['success'] === true) {
            $('#Pro_Nom_Info').val(response['prod']['Pro_Nom'] || producto.Ite_Lar);
            $('#Mar_Des_Info').val(response['prod']['Mar_Des'] || producto.Mar_Des || 'NINGUNA');
            $('#Stk_Can_Info').val(response['prod']['Stk_Can'] || producto.Stk_Can || 0);
        } else {
            $('#Pro_Nom_Info').val(producto.Ite_Lar);
            $('#Mar_Des_Info').val(producto.Mar_Des || 'NINGUNA');
            $('#Stk_Can_Info').val(producto.Stk_Can || 0);
        }
    }, 'json').fail(function() {
        $('#Pro_Nom_Info').val(producto.Ite_Lar);
        $('#Mar_Des_Info').val(producto.Mar_Des || 'NINGUNA');
        $('#Stk_Can_Info').val(producto.Stk_Can || 0);
    });
}

/* Limpiar selección de producto */
function limpiarProducto() {
    $('#Pro_Cod').val('');
    $('#producto').val('');
    $('#Pro_Nom_Info').val('');
    $('#Mar_Des_Info').val('');
    $('#Stk_Can_Info').val('');
}

/* Inicializar grid principal de productos */
function inicializarGridProductos() {
    gridProductos = $('#gridProductos');
    gridProductos.createGrid({
        caption: 'Lista de Productos',
        height: 320,
        url: '',
        datatype: 'json',
        mtype: 'GET',
        postData: { listProductosGrid: true },
        colModel: [
            { label: 'Cod.Int.', name: 'Pro_Cod', index: 'Pro_Cod', width: 20, key: true, align: "center" },
            { label: 'Producto', name: 'Pro_Nom', index: 'Pro_Nom', width: 100 },
            { label: 'Marca', name: 'Mar_Des', width: 40, align: "center" },
            { label: 'Stock', name: 'Stk_Can', index: 'Stk_Can', width: 20, align: 'center' },
            { label: 'Total IMEI', name: 'Total_Imei', index: 'Total_Imei', width: 20, align: 'center' },
            { label: 'Ver Imei', name: 'IMEI', index: 'IMEI', width: 50, align: 'center', sortable: false,
                formatter: function(cellvalue, options, rowObject) {
                    // Solo mostrar el botón si Total_Imei es mayor a 0
                    var totalImei = parseInt(rowObject.Total_Imei) || 0;
                    if (totalImei > 0) {
                        return '<button class="btn btn-sm btn-info" onclick="verImeiProducto(' + rowObject.Pro_Cod + ', \'' + (rowObject.Pro_Nom || '').replace(/'/g, "\\'") + '\');" title="Ver IMEI">' +
                                '<span class="glyphicon glyphicon-eye-open"></span> Vista Previa</button>';
                    }
                    return '';
                }
            },
            { label: '<i class="glyphicon glyphicon-check"></i>', name: 'actions', index: 'actions', width: 15, align: 'center', sortable: false,
                formatter: function(cellvalue, options, rowObject) {
                    return '<button class="btn btn-xs btn-primary" onclick="editarProducto(' + rowObject.Pro_Cod + ')" title="Editar">' +
                            '<span class="glyphicon glyphicon-edit"></span></button>';
                }
            }
        ],
        rowNum: 100,
        rowList: [100, 250, 500, 1000, 2000],
        sortname: 'Pro_Nom',
        sortorder: 'asc',
        loadComplete: function() {
            $(this).setGridWidth($('.imei-grid').width(), true);
        }
    }, false, '#pagerProductos', { 
        edit: false, 
        add: false, 
        del: false, 
        search: false, 
        refresh: true
    });
    
    // Ocultar botón de colapsar
    $('#gbox_gridProductos .ui-jqgrid-titlebar-close').hide();
    
    // Agregar input de búsqueda al título del grid después de la inicialización
    setTimeout(function() {
        var $titleBar = $('#gbox_gridProductos .ui-jqgrid-titlebar');
        var $title = $('#gbox_gridProductos .ui-jqgrid-title');
        
        if ($titleBar.length && $title.length) {
            // Modificar el título para que tenga flexbox y altura automática
            $titleBar.css({
                'height': 'auto',
                'width': '100%'
            });
            
            // Envolver el texto del título en un span si no existe para evitar que se divida
            var titleText = $title.text().trim();
            if ($title.find('span').length === 0 && titleText) {
                $title.html('<span style="white-space: nowrap; font-size: 15px;">' + titleText + '</span>');
            } else {
                $title.find('span').first().css({
                    'white-space': 'nowrap',
                    'font-size': '15px'
                });
            }
            
            // Asegurar que el título tenga flexbox
            $title.css({
                'display': 'flex',
                'justify-content': 'space-between',
                'align-items': 'center',
                'width': '100%',
                'height': 'auto'
            });
            
            // Crear contenedor para el input de búsqueda
            var $searchContainer = $('<div>').css({
                'position': 'relative',
                'width': '250px',
                'flex-shrink': '0'
            });
            
            // Crear icono de lupa
            var $searchIcon = $('<span>').addClass('glyphicon glyphicon-search').css({
                'position': 'absolute',
                'left': '10px',
                'top': '50%',
                'transform': 'translateY(-50%)',
                'color': '#999',
                'pointer-events': 'none',
                'z-index': '10',
                'font-size': '12px'
            });
            
            // Crear input de búsqueda
            var $searchInput = $('<input>').attr({
                'type': 'text',
                'id': 'searchProductos',
                'placeholder': 'search'
            }).addClass('form-control input-sm').css({
                'padding-left': '30px',
                'padding-right': '10px',
                'height': '28px',
                'font-size': '12px',
                'width': '100%'
            });
            
            // Agregar elementos al contenedor
            $searchContainer.append($searchIcon).append($searchInput);
            
            // Agregar el contenedor al título
            $title.append($searchContainer);
        }
    }, 100);
    
    // Configurar evento de búsqueda en tiempo real con debounce
    var searchTimeout;
    $(document).on('input', '#searchProductos', function() {
        var searchValue = $(this).val().trim();
        
        // Limpiar timeout anterior
        clearTimeout(searchTimeout);
        
        // Esperar 300ms después de que el usuario deje de escribir
        searchTimeout = setTimeout(function() {
            gridProductos.jqGrid('setGridParam', {
                postData: { 
                    listProductosGrid: true,
                    search: searchValue
                },
                page: 1
            }).trigger('reloadGrid');
        }, 300);
    });
    
    // También filtrar al presionar Enter
    $(document).on('keypress', '#searchProductos', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            clearTimeout(searchTimeout);
            var searchValue = $(this).val().trim();
            gridProductos.jqGrid('setGridParam', {
                postData: { 
                    listProductosGrid: true,
                    search: searchValue
                },
                page: 1
            }).trigger('reloadGrid');
        }
    });
    
    // Centrar el pager después de la inicialización
    setTimeout(function() {
        var $pager = $('#pagerProductos');
        if ($pager.length) {
            $pager.css({
                'text-align': 'center',
                'display': 'flex',
                'justify-content': 'center',
                'align-items': 'center'
            });
            $pager.find('.ui-pg-table').css({
                'margin': '0 auto',
                'display': 'inline-block'
            });
            $pager.find('.ui-paging-info').css({
                'text-align': 'center',
                'display': 'inline-block',
                'margin': '0 10px'
            });
        }
    }, 100);
}

/* Ver IMEI de un producto */
function verImeiProducto(Pro_Cod, Pro_Nom) {
    $('#imeiPreviewDialog').find('h4').text('IMEI del Producto: ' + Pro_Nom);
    
    // Guardar el Pro_Cod para usar en la actualización
    $('#imeiPreviewDialog').data('Pro_Cod', Pro_Cod);
    
    // Limpiar checkboxes de edición
    $('.edit-imei-checkbox').prop('checked', false);
    
    // Configurar el grid antes de abrir el modal
    $('#gridImeiPreview').jqGrid('setGridParam', {
        url: '',
        postData: { listImeiProducto: true, Pro_Cod: Pro_Cod },
        datatype: 'json',
        page: 1
    });
    
    // Abrir el modal (el evento open ajustará el ancho)
    $('#imeiPreviewDialog').dialog('open');
    
    // Cargar datos después de que el modal esté completamente abierto
    setTimeout(function() {
        $('#gridImeiPreview').trigger('reloadGrid');
    }, 50);
}

/* Toggle edición de IMEI en el grid */
function toggleEditImei(Ime_Cod, enabled) {
    var rowId = Ime_Cod;
    var grid = $('#gridImeiPreview');
    
    if (enabled) {
        // Verificar si la fila ya está en modo edición
        if (grid.jqGrid('getGridParam', 'selrow') === String(rowId)) {
            return; // Ya está en edición
        }
        
        // Habilitar edición de la fila (solo la columna Ime_Num)
        grid.jqGrid('editRow', rowId, {
            keys: true,
            oneditfunc: function(rowId) {
                // Marcar la fila como editable
                grid.jqGrid('setRowData', rowId, { 'editable': true });
            },
            aftersavefunc: function(rowId, response) {
                // No hacer nada después de guardar, se guardará con el botón de actualizar
            },
            errorfunc: function(rowId, response) {
                $.alert('Error al editar la fila: ' + (response.responseText || 'Error desconocido'), null, 'remove');
            },
            afterrestorefunc: function(rowId) {
                // Limpiar el checkbox cuando se cancela la edición
                grid.find('tr#' + rowId + ' input.edit-imei-checkbox').prop('checked', false);
            }
        });
    } else {
        // Restaurar la fila (cancelar edición)
        grid.jqGrid('restoreRow', rowId);
    }
}

/* Actualizar IMEI editados en el grid */
function actualizarImeiEditados() {
    var Pro_Cod = $('#imeiPreviewDialog').data('Pro_Cod');
    if (!Pro_Cod) {
        $.alert('Error: No se pudo identificar el producto', null, 'remove');
        return;
    }
    
    var grid = $('#gridImeiPreview');
    
    // Primero, guardar todas las filas que están en modo edición
    var ids = grid.jqGrid('getDataIDs');
    var filasParaGuardar = [];
    
    ids.forEach(function(id) {
        var checkbox = grid.find('tr#' + id + ' input.edit-imei-checkbox');
        if (checkbox.length && checkbox.is(':checked')) {
            // Si la fila está en modo edición, guardarla primero
            var selrow = grid.jqGrid('getGridParam', 'selrow');
            if (selrow === String(id)) {
                // Guardar la fila en edición
                grid.jqGrid('saveRow', id, {
                    aftersavefunc: function(rowId, response) {
                        // Continuar con la actualización
                    }
                });
            }
            
            // Obtener los datos de la fila
            var rowData = grid.jqGrid('getRowData', id);
            filasParaGuardar.push({
                Ime_Cod: rowData.Ime_Cod,
                Ime_Num: rowData.Ime_Num
            });
        }
    });
    
    if (filasParaGuardar.length === 0) {
        $.alert('No hay IMEI seleccionados para actualizar', null, 'remove');
        return;
    }
    
    // Mostrar el loader
    if ($('#loader').length) {
        $('#loader').show();
    } else if (document.getElementById('loader')) {
        document.getElementById('loader').style.display = 'block';
    }
    
    // Esperar un momento para que se guarden las filas en edición
    setTimeout(function() {
        // Obtener los datos actualizados después de guardar
        var filasEditadas = [];
        ids.forEach(function(id) {
            var checkbox = grid.find('tr#' + id + ' input.edit-imei-checkbox');
            if (checkbox.length && checkbox.is(':checked')) {
                var rowData = grid.jqGrid('getRowData', id);
                filasEditadas.push({
                    Ime_Cod: rowData.Ime_Cod,
                    Ime_Num: rowData.Ime_Num
                });
            }
        });
        
        // Actualizar cada IMEI
        var actualizados = 0;
        var errores = [];
        var total = filasEditadas.length;
        var index = 0;
        
        function actualizarSiguiente() {
            if (index >= total) {
                // Ocultar el loader
                if ($('#loader').length) {
                    $('#loader').fadeOut('slow');
                } else if (document.getElementById('loader')) {
                    $(document.getElementById('loader')).fadeOut('slow');
                }
                
                var mensaje = 'Se actualizaron ' + actualizados + ' de ' + total + ' IMEI correctamente';
                if (errores.length > 0) {
                    mensaje += '<br/><br/><strong>Errores:</strong><br/>' + errores.join('<br/>');
                }
                $.alert(mensaje, null, actualizados > 0 ? 'success' : 'remove');
                
                if (actualizados > 0) {
                    // Recargar el grid
                    grid.trigger('reloadGrid');
                    // Desmarcar todos los checkboxes
                    $('.edit-imei-checkbox').prop('checked', false);
                }
                return;
            }
            
            var imei = filasEditadas[index];
            var data = {
                saveImei: true,
                Ime_Cod: imei.Ime_Cod,
                Pro_Cod: Pro_Cod,
                Ime_Num: imei.Ime_Num
            };
            
            $.post('', data, function(response) {
                if (response.success) {
                    actualizados++;
                } else {
                    errores.push('IMEI ' + imei.Ime_Num + ': ' + response.message);
                }
                index++;
                actualizarSiguiente();
            }, 'json').fail(function() {
                errores.push('IMEI ' + imei.Ime_Num + ': Error de conexión');
                index++;
                actualizarSiguiente();
            });
        }
        
        actualizarSiguiente();
    }, 300);
}

/* Editar producto - Seleccionar producto y abrir modal de IMEI */
function editarProducto(Pro_Cod) {
    // Obtener los datos del producto del grid
    var rowData = gridProductos.jqGrid('getRowData', Pro_Cod);
    
    if (!rowData || !rowData.Pro_Cod) {
        $.alert('No se pudo obtener la información del producto', null, 'remove');
        return;
    }
    
    // Establecer el producto seleccionado
    $('#Pro_Cod').val(Pro_Cod);
    $('#producto').val(rowData.Pro_Nom || '');
    
    // Actualizar información del producto y cargar IMEI
    $.get('', {
        'Pro_Cod': Pro_Cod,
        'ajaxProd': true
    }, function(response) {
        if (response['success'] === true) {
            $('#Pro_Nom_Info').val(response['prod']['Pro_Nom'] || rowData.Pro_Nom);
            $('#Mar_Des_Info').val(response['prod']['Mar_Des'] || rowData.Mar_Des || 'NINGUNA');
            $('#Stk_Can_Info').val(response['prod']['Stk_Can'] || rowData.Stk_Can || 0);
        } else {
            // Usar datos del grid si falla la petición
            $('#Pro_Nom_Info').val(rowData.Pro_Nom || '');
            $('#Mar_Des_Info').val(rowData.Mar_Des || 'NINGUNA');
            $('#Stk_Can_Info').val(rowData.Stk_Can || 0);
        }
        
        // Cargar lista de IMEI del producto
        cargarListaImeiProducto(Pro_Cod);
    }, 'json').fail(function() {
        // Usar datos del grid si falla la petición
        $('#Pro_Nom_Info').val(rowData.Pro_Nom || '');
        $('#Mar_Des_Info').val(rowData.Mar_Des || 'NINGUNA');
        $('#Stk_Can_Info').val(rowData.Stk_Can || 0);
        
        // Cargar lista de IMEI del producto
        cargarListaImeiProducto(Pro_Cod);
    });
}

/* Cargar lista de IMEI de un producto */
function cargarListaImeiProducto(Pro_Cod) {
    // Marcar que debemos cargar IMEI al abrir el modal
    cargarImeiAlAbrir = true;
    proCodParaCargar = Pro_Cod;
    
    // Abrir el modal (el evento open manejará la carga)
    $('#imeiModal').dialog('open');
    
    // Scroll al formulario
    $('html, body').animate({
        scrollTop: $('#formImei').offset().top - 100
    }, 500);
}

/* Cargar IMEI en el modal (función auxiliar) */
function cargarImeiEnModal(Pro_Cod) {
    // Limpiar lista actual y mapa
    listaImei = [];
    mapaImeiCod = {};
    imeiOriginales = [];
    mapaImeiCodPorIndice = {};
    
    // Obtener IMEI del producto
    $.get('', {
        'listImeiProducto': true,
        'Pro_Cod': Pro_Cod,
        'page': 1,
        'rows': 10000  // Obtener todos los IMEI
    }, function(response) {
        var imeisTextArea = [];
        
        if (response && response.rows && response.rows.length > 0) {
            // Activar modo edición
            modoEdicion = true;
            
            // Extraer los números de IMEI y guardar el mapeo Ime_Num -> Ime_Cod y por índice
            response.rows.forEach(function(row, index) {
                if (row.Ime_Num) {
                    listaImei.push(row.Ime_Num);
                    imeisTextArea.push(row.Ime_Num);
                    imeiOriginales.push(row.Ime_Num); // Guardar originales para comparar
                    // Guardar el mapeo para poder actualizar después
                    if (row.Ime_Cod) {
                        mapaImeiCod[row.Ime_Num] = row.Ime_Cod;
                        // Guardar también por índice para obtener fácilmente por posición
                        mapaImeiCodPorIndice[index] = row.Ime_Cod;
                    }
                }
            });
            
            // Cambiar título del modal y texto del botón
            $('#imeiModal').dialog('option', 'title', 'Actualizar IMEI');
            $('#btnAgregarImei').html('<span class="glyphicon glyphicon-ok"></span> Actualizar IMEI');
            $('#btnGuardarImei').html('<span class="glyphicon glyphicon-floppy-disk"></span> Actualizar IMEI');
        } else {
            // No hay IMEI, modo inserción
            modoEdicion = false;
            $('#imeiModal').dialog('option', 'title', 'Agregar IMEI');
            $('#imeiModal button:contains("Actualizar IMEI")').html('<span class="glyphicon glyphicon-ok"></span> Agregar IMEI');
            $('#imeiModal button:contains("Actualizar IMEI")').html('<span class="glyphicon glyphicon-floppy-disk"></span> Guardar Todos los IMEI');
        }
        
        // Cargar IMEI en el textarea (formato vertical - uno por línea)
        if (imeisTextArea.length > 0) {
            $('#imeiManualInput').val(imeisTextArea.join('\n'));
        } else {
            $('#imeiManualInput').val('');
        }
        
        // Actualizar la lista visual y el contador
        actualizarListaImei();
        actualizarDisplayImei();
    }, 'json').fail(function() {
        // Si falla, dejar textarea vacío y modo inserción
        modoEdicion = false;
        $('#imeiManualInput').val('');
        actualizarListaImei();
        actualizarDisplayImei();
    });
}

/* Inicializar grid de vista previa de IMEI */
function inicializarGridImeiPreview() {
    $('#gridImeiPreview').createGrid({
        caption: 'IMEI del Producto',
        height: 250,
        url: '',
        datatype: 'local', // Inicializar como local para no cargar datos hasta que se abra el modal
        mtype: 'GET',
        postData: { listImeiProducto: true },
        colModel: [
            { label: 'Cod.Int.', name: 'Ime_Cod', index: 'Ime_Cod', width: 80, key: true, hidden: true },
            { label: 'IMEI', name: 'Ime_Num', index: 'Ime_Num', width: 250, editable: true, edittype: 'text',
                editoptions: {
                    size: 20,
                    maxlength: 15
                }
            },
            { label: 'Tipo', name: 'Ime_Tip_Des', index: 'Ime_Tip_Des', width: 150, align: 'center',
                formatter: function(cellvalue) {
                    var colores = {
                        'Pendiente': '#FFA500',
                        'Vendido': '#28a745',
                        'Con Novedad': '#dc3545',
                        'Rechazado': '#6c757d'
                    };
                    var color = colores[cellvalue] || '#000';
                    return '<span style="color: ' + color + '; font-weight: bold;">' + (cellvalue || 'Pendiente') + '</span>';
                }
            },
            { label: 'Estado', name: 'Ime_Est', index: 'Ime_Est', width: 100, align: 'center',
                formatter: function(cellvalue) {
                    return cellvalue === 'A' ? 'Activo' : 'Inactivo';
                }
            }
        ],
        rowNum: 100,
        rowList: [100, 200, 500, 1000, 2000],
        sortname: 'Ime_Sys',
        sortorder: 'desc',
        autowidth: false,
        shrinkToFit: false,
        width: null // Se establecerá dinámicamente cuando se abra el modal
    }, false, '#pagerImeiPreview', {
        edit: false,
        add: false,
        del: false,
        search: false,
        refresh: true,
        view: false  // Desactivar botón de vista/acciones
    });
    
    // Ocultar botón de colapsar
    $('#gbox_gridImeiPreview .ui-jqgrid-titlebar-close').hide();
    
    // Ocultar botones de acciones/view del pager
    setTimeout(function() {
        $('#pagerImeiPreview .ui-pg-button[title*="Ver"]').hide();
        $('#pagerImeiPreview .ui-pg-button[title*="View"]').hide();
        $('#pagerImeiPreview .ui-pg-button:contains("Acciones")').hide();
    }, 100);
}

/* Inicializar modal de vista previa */
function inicializarModalVistaPrevia() {
    // Configurar el modal de vista previa
    $('#imeiPreviewDialog').dialog({
        width: 600,
        height: 440,
        modal: true,
        resizable: true,
        autoOpen: false,
        open: function() {
            // Ajustar ancho del grid cuando se abre el modal
            var $modal = $(this);
            var contentWidth = $modal.find('.ui-dialog-content').width() || $modal.width();
            if (contentWidth > 0) {
                // Calcular el ancho total de las columnas visibles
                var totalColumnWidth = 0;
                $('#gridImeiPreview').jqGrid('getGridParam', 'colModel').forEach(function(col) {
                    if (!col.hidden && col.width) {
                        totalColumnWidth += parseInt(col.width) || 0;
                    }
                });
                
                // Si el ancho total de columnas es menor que el contenido, usar ese ancho
                // Si es mayor, usar el ancho del contenido
                var gridWidth = Math.max(totalColumnWidth + 30, contentWidth - 20);
                $('#gridImeiPreview').jqGrid('setGridWidth', gridWidth, false);
            }
        }
    });
}
