$(document).ready(function() {
    cargarFlayers();
});

let flayersList = [];

function cargarFlayers() {
    $.getJSON('../config/getFlayers.php', function(data) {
        flayersList = data;
        renderFlayers();
    });
}

function renderFlayers() {
    const container = $('#flayersContainer');
    container.empty();

    if (flayersList.length === 0) {
        container.append('<div class="col-xs-12 text-center text-muted" style="padding:50px;"><h3><i class="fa fa-info-circle"></i> No hay flayers configurados.</h3><p>Haz clic en "Nuevo Flayer" para comenzar.</p></div>');
        return;
    }

    flayersList.forEach((flayer, index) => {
        const activoIcon = flayer.activo ? 
            '<span class="text-success" title="Publicado"><i class="fa fa-check-circle"></i> Público</span>' : 
            '<span class="text-danger" title="Oculto"><i class="fa fa-eye-slash"></i> Oculto</span>';
        
        const preview = flayer.ruta_imagen ? 
            `<img src="${flayer.ruta_imagen}" alt="preview">` : 
            '<i class="fa fa-picture-o"></i>';

        const html = `
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="flayer-card-grid">
                    <span class="card-order-badge">ORDEN: ${flayer.orden || 0}</span>
                    <div>
                        <div class="card-title-text">${flayer.titulo || 'Sin Título'}</div>
                        <div class="card-desc-text">${flayer.descripcion || 'Sin descripción...'}</div>
                    </div>
                    
                    <div class="card-img-preview">
                        ${preview}
                    </div>

                    <div class="card-actions">
                        <div style="font-size: 10px; font-weight: bold;">${activoIcon}</div>
                        <div>
                            <button class="btn btn-xs btn-primary" onclick="abrirModalEditar(${index})" title="Editar">
                                <i class="fa fa-pencil"></i>
                            </button>
                            <button class="btn btn-xs btn-danger" onclick="confirmarEliminar(${index})" title="Eliminar">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.append(html);
    });
}

function abrirModalNuevo() {
    $('#modalTitle').text('Registrar Nuevo Flayer');
    $('#editIndex').val(-1);
    $('#mTitulo').val('');
    $('#mOrden').val(flayersList.length + 1);
    $('#mDescripcion').val('');
    $('#mImagen').val('0');
    $('#mActivo').prop('checked', true);
    $('#mFile').val('');
    $('#mAdjuntoActual').empty();
    $('#flayerModal').modal('show');
}

function abrirModalEditar(index) {
    const flayer = flayersList[index];
    $('#modalTitle').text('Editar Flayer #' + (index + 1));
    $('#editIndex').val(index);
    $('#mTitulo').val(flayer.titulo);
    $('#mOrden').val(flayer.orden);
    $('#mDescripcion').val(flayer.descripcion);
    $('#mImagen').val(flayer.imagen);
    $('#mActivo').prop('checked', flayer.activo);
    $('#mFile').val('');
    
    if (flayer.ruta_imagen) {
        $('#mAdjuntoActual').html(`
            <div class="alert alert-info" style="padding:5px; margin:5px 0 0 0; font-size:10px; display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fa fa-image"></i> Imagen: ${flayer.ruta_imagen.split('/').pop()}</span>
                <button type="button" class="btn btn-xs btn-link text-danger" onclick="quitarImagenActual(${index})" style="padding:0; line-height:1;"><i class="fa fa-times"></i></button>
            </div>
        `);
    } else {
        $('#mAdjuntoActual').empty();
    }
    
    $('#flayerModal').modal('show');
}

function quitarImagenActual(index) {
    flayersList[index].ruta_imagen = '';
    $('#mAdjuntoActual').empty();
}

function guardarFlayer() {
    const index = parseInt($('#editIndex').val());
    
    if(!$('#mTitulo').val()) {
        $.alert('El título es obligatorio');
        return;
    }

    const nuevoFlayer = {
        titulo: $('#mTitulo').val(),
        descripcion: $('#mDescripcion').val(),
        imagen: $('#mImagen').val(),
        orden: parseInt($('#mOrden').val()) || 0,
        activo: $('#mActivo').is(':checked'),
        ruta_imagen: index === -1 ? '' : flayersList[index].ruta_imagen
    };

    if (index === -1) {
        flayersList.push(nuevoFlayer);
    } else {
        flayersList[index] = nuevoFlayer;
    }

    enviarAlServidor(index);
}

function enviarAlServidor(index) {
    const formData = new FormData();
    formData.append('flayers_data', JSON.stringify(flayersList));

    const fileInput = $('#mFile')[0];
    // Si es nuevo, el archivo se asocia al último índice del array
    const targetIndex = index === -1 ? flayersList.length - 1 : index;

    if (fileInput.files.length > 0) {
        formData.append('file_' + targetIndex, fileInput.files[0]);
    }

    $.ajax({
        url: '../config/saveFlayers.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#flayerModal').modal('hide');
                cargarFlayers(); // Recargar la galería
                $.alert({
                    title: 'Completado',
                    content: 'Los cambios se han guardado con éxito.',
                    type: 'blue',
                    icon: 'fa fa-check-circle'
                });
            } else {
                $.alert({
                    title: 'Atención',
                    content: response.message,
                    type: 'orange'
                });
            }
        },
        error: function() {
            $.alert({
                title: 'Error',
                content: 'Error de conexión con el servidor.',
                type: 'red'
            });
        }
    });
}

function confirmarEliminar(index) {
    $.confirm({
        title: '<i class="fa fa-trash text-danger"></i> Eliminar Flayer',
        content: '¿Estás seguro de que deseas eliminar este flayer de la lista?',
        buttons: {
            confirmar: {
                text: 'Sí, Eliminar',
                btnClass: 'btn-danger',
                action: function() {
                    flayersList.splice(index, 1);
                    enviarAlServidor(-2); // Indicar que es solo actualización de lista
                }
            },
            cancelar: {
                text: 'Cancelar'
            }
        }
    });
}
