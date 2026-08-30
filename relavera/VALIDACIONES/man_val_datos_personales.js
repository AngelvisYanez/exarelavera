// Sección para inicializar componentes
$(function () {
    // Se declara datepicker para fecha de nacimiento y caducidad de licencia
    $.createDatePickers("#Prs_Fec");
    $.createDatePickers("#Cho_Cli");
    
    // Se declara chosen para ciudades
    $("#Ciu_Cod").createChosen('input-sm', {
        template: function (text, templateData) {
            return [
                "<div>" + text + "</div>",
                "<div style='font-size:11px;'><b>Provincia:</b> " + (templateData.Pro_Nom || '') + " <b>Pais:</b> " + (templateData.Pas_Nom || '') + "</div>"
            ].join("");
        }
    });

    // Carga manual de ciudades para evitar errores de función inexistente
    $.getJSON('', { listCiudadesAjax: true }, function (data) {
        var $select = $('#Ciu_Cod');
        $select.empty().append('<option value="">Seleccione Ciudad...</option>');
        $.each(data, function (i, item) {
            var $opt = $('<option>', {
                value: item.Ciu_Cod,
                text: item.Ciu_Des
            });
            // Guardar datos para el template de chosenDesc
            $opt.attr('data-Pro_Nom', item.Pro_Nom);
            $opt.attr('data-Pas_Nom', item.Pas_Nom);
            $select.append($opt);
        });
        $select.trigger('chosen:updated');
    });

    // Inicializar el Grid de Personal solo si el elemento existe en el DOM
    if ($('#gridPersonal').length > 0) {
        createGridPersonal();
    }

    // Inicializar botones de radio
    $(".radioset").buttonset();

    // Si es perfil planta y la campaña está activa, mostrar formulario directo
    if (typeof mostrarDirecto !== 'undefined' && mostrarDirecto === true) {
        if (typeof dataLog !== 'undefined' && dataLog && dataLog.Prs_Cod) {
            setTimeout(function() {
                mostrarFormulario(dataLog);
                // Ocultar botón cancelar para forzar la actualización
                $('.btn-save').each(function() {
                    if ($(this).text().indexOf('Cancelar') !== -1) {
                        $(this).hide();
                    }
                });
            }, 500); // Pequeño delay para asegurar que el grid y combos estén listos
        }
    }
});

// ==================== FUNCIONES DE AMBIENTES ====================

function mostrarFormulario(row) {
    limpiar();
    $('#divListado').hide();
    $('#divFormulario').fadeIn();
    
    if (row) {
        console.log("Cargando datos para edición:", row);
        // Default Carga Familiar to 0 if empty
        row.Per_Car = (row.Per_Car === null || row.Per_Car === '' || row.Per_Car === undefined) ? 0 : row.Per_Car;
        
        // Llenar el formulario con los datos del row
        $('#formPersonal').setData(row, false);
        $('#Ciu_Cod').val(row.Ciu_Cod).trigger("chosen:updated");

        // Lógica para Tipo de Sangre
        if (row.Prs_San) {
            var valSan = row.Prs_San.toUpperCase().trim();
            var options = [];
            $('#Prs_San_Sel option').each(function() { options.push($(this).val()); });
            
            if (options.indexOf(valSan) !== -1 && valSan !== 'OTROS' && valSan !== '') {
                $('#Prs_San_Sel').val(valSan);
                $('#Prs_San_Otro').hide();
            } else if (valSan !== '') {
                $('#Prs_San_Sel').val('OTROS');
                $('#Prs_San_Otro').val(valSan).show();
            }
            $('#Prs_San').val(valSan);
        }

        // Manejo de la foto (Nueva lógica)
        $('#Per_Fot_Hidden').val(row.Per_Fot || '');
        if (row.Per_Fot && row.Per_Fot !== 'NULL' && row.Per_Fot !== '') {
            $('#imgPreview').attr('src', "../../mascaras/model1/imagenes/personal/" + row.Per_Fot);
        } else {
            $('#imgPreview').attr('src', "../../mascaras/model1/imagenes/128x128/perfil.png");
        }
    }
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        var fileSize = file.size / 1024 / 1024; // en MB
        
        if (fileSize > 5) {
            $.alert("La imagen es demasiado pesada (" + fileSize.toFixed(2) + " MB). El límite es de 5 MB.");
            $(input).val('');
            return false;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            var img = new Image();
            img.onload = function() {
                // Crear un canvas pequeño para analizar los píxeles
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                canvas.width = 100; // Resolución baja es suficiente
                canvas.height = 133; // Mantener proporción 3:4
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                
                // Muestrear puntos estratégicos del fondo (esquinas superiores y centro superior)
                var points = [
                    ctx.getImageData(5, 5, 1, 1).data,   // Superior Izquierda
                    ctx.getImageData(95, 5, 1, 1).data,  // Superior Derecha
                    ctx.getImageData(50, 5, 1, 1).data   // Centro Superior
                ];
                
                var avgBrightness = 0;
                points.forEach(function(p) {
                    // Fórmula de luminosidad: 0.299*R + 0.587*G + 0.114*B
                    avgBrightness += (0.299 * p[0] + 0.587 * p[1] + 0.114 * p[2]);
                });
                avgBrightness /= points.length;

                // Un valor de 200+ suele indicar un fondo muy claro/blanco
                if (avgBrightness < 190) {
                    $.alert("<b>Aviso de Calidad:</b> La foto parece tener un fondo oscuro o colorido. Se recomienda usar una foto con <u>fondo blanco</u> tamaño carnet para una mejor identificaci&oacute;n.");
                }

                $('#imgPreview').attr('src', e.target.result);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function resetImage() {
    $('#fileInput').val('');
    $('#Per_Fot_Hidden').val('');
    $('#imgPreview').attr('src', "../../mascaras/model1/imagenes/128x128/perfil.png");
}

function mostrarListado() {
    $('#divFormulario').hide();
    $('#divListado').fadeIn();
    // Ajustar el ancho del grid por si acaso
    $(window).trigger('resize');
}

// ==================== LÓGICA DEL GRID ====================

function createGridPersonal() {
    $('#gridPersonal').createGrid({
        caption: 'Personal de la Planta',
        url: location.href,
        height: 400,
        colModel: [
            { label: 'Código', name: 'Per_Cod', key: true, width: 50, align: "center", hidden: true },
            { label: 'Prs_Cod', name: 'Prs_Cod', hidden: true },
            { label: 'Cédula', name: 'Prs_Ced', width: 90, align: "center" },
            { label: 'Nombres', name: 'Prs_Nom', width: 120 },
            { label: 'Apellidos', name: 'Prs_Ape', width: 120 },
            { label: 'Ciudad', name: 'Ciu_Des', width: 100 },
            { label: 'Teléfono', name: 'Prs_Tel', width: 90, align: "center" },
            { label: 'Celular', name: 'Prs_Cel', width: 90, align: "center" },
            { label: 'Licencia', name: 'Cho_Tli', width: 60, align: "center" },
            { label: 'Caducidad', name: 'Cho_Cli', width: 80, align: "center" },
            {
                label: 'Acciones',
                name: 'acciones',
                width: 60,
                align: 'center',
                formatter: function (cellvalue, options, o) {
                    return $.getGridButton('mostrarFormulario', o, 'Editar Datos', 'pencil', '', 'success');
                }
            }
        ],
        rowNum: 50,
        viewrecords: true,
        postData: {
            listPersonalGridAjax: true
        }
    }, false, '#gridPersonalPager', {
        refresh: true,
        view: false
    });
}

function actualizarGridPersonal() {
    var search = $('#txtBuscarPersonal').val();
    var op_opciones = $('input[name="op_opciones"]:checked', '#filtroPersonalForm').val() || 'n';
    $('#gridPersonal').jqGrid('setGridParam', {
        postData: {
            listPersonalGridAjax: true,
            search: search,
            op_opciones: op_opciones
        },
        page: 1
    }).trigger('reloadGrid');
}

function limpiarFiltroPersonal() {
    $('#txtBuscarPersonal').val('');
    $('#radNombre').prop('checked', true).trigger('change');
    actualizarGridPersonal();
}

// ==================== LÓGICA DE FORMULARIO ====================

function saveForm() {
    // Validaciones básicas
    if (!$('#Ciu_Cod').val()) {
        $.alert("Debe seleccionar una Ciudad.");
        return;
    }

    var form = $('#formPersonal')[0];
    var formData = new FormData(form);
    formData.append('savePersonal', true);
    
    // Depuración: ver qué se envía en la consola (F12)
    for (var pair of formData.entries()) {
        console.log(pair[0]+ ': ' + pair[1]); 
    }

    $('#loader').show();
    
    $.ajax({
        url: location.href,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json"
    })
    .done(function (response) {
        $('#loader').fadeOut('slow');
        if (response.success === true) {
            var msg = "¡Datos actualizados con éxito!";
            if (response.msg_foto) {
                msg += "\n\nNota: " + response.msg_foto;
            }
            $.alert(msg);
            // Regresar al listado y recargar
            mostrarListado();
            $('#gridPersonal').trigger('reloadGrid');
        } else {
            $.alert(response.error || response.message || "Error al guardar los datos.");
        }
    })
    .fail(function() {
        $('#loader').fadeOut('slow');
        $.alert("Error de comunicación con el servidor.");
    });
}

function limpiar() {
    $('#formPersonal')[0].reset();
    $('#Prs_Cod').val('0');
    $('#Per_Cod').val('0');
    $('#Per_Fot_Hidden').val('');
    $('#Per_Car').val('0');
    $('#Prs_San').val('');
    $('#Prs_San_Sel').val('');
    $('#Prs_San_Otro').val('').hide();
    $('#Ciu_Cod').val('').trigger('chosen:updated');
}

function cambioTipoSangre(val) {
    if (val === 'OTROS') {
        $('#Prs_San_Otro').val('').show().focus();
        $('#Prs_San').val('');
    } else {
        $('#Prs_San_Otro').hide();
        $('#Prs_San').val(val);
    }
}
