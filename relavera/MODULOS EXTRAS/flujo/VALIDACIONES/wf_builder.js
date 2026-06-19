/**
 * EXA Workflow Builder JS Logic
 * Lógica interactiva SVG, Drag & Drop y Drawer de configuración.
 * @author Oz <oz-agent@warp.dev>
 */

let workflowId = null;
let nodes = [];
let connections = [];
let activeNode = null;
let activeConnection = null; // Conexión actualmente seleccionada
let isDraggingNode = false;
let draggedElement = null;
let offset = { x: 0, y: 0 };

// Puerto de conexión temporal para dibujo de cable
let drawingConnection = false;
let connStartPort = null;

$(document).ready(function() {
    setupCanvas();
    setupToolbox();
    setupSVG();
});

function setupCanvas() {
    const $canvas = $('#canvas');
    
    $canvas.on('dragover', function(e) {
        e.preventDefault();
    });

    $canvas.on('drop', function(e) {
        e.preventDefault();
        const type = e.originalEvent.dataTransfer.getData('node-type');
        if (!type) return;

        const canvasOffset = $canvas.offset();
        const x = e.originalEvent.pageX - canvasOffset.left + $canvas.scrollTop();
        const y = e.originalEvent.pageY - canvasOffset.top + $canvas.scrollLeft();

        createNode(type, 'Nuevo ' + type, x, y);
    });

    // Deseleccionar al hacer clic en el fondo del lienzo
    $canvas.on('click', function(e) {
        if (e.target === this || e.target.id === 'svgCanvas') {
            closeDrawer();
            activeConnection = null;
            redrawConnections();
        }
    });

    // Dibujo dinámico de conexiones con el ratón
    $(document).on('mousemove', function(e) {
        if (drawingConnection && connStartPort) {
            drawTempCable(e.pageX, e.pageY);
        }
    });

    $(document).on('mouseup', function(e) {
        if (drawingConnection) {
            drawingConnection = false;
            $('#tempCable').remove();
        }
    });
}

function setupToolbox() {
    $('.toolbox-item').on('dragstart', function(e) {
        e.originalEvent.dataTransfer.setData('node-type', $(this).data('type'));
    });
}

function setupSVG() {
    // Redimensionar el lienzo SVG automáticamente
    const resizeSVG = () => {
        const $canvas = $('#canvas');
        $('#svgCanvas').attr({
            width: Math.max($canvas[0].scrollWidth, $canvas.outerWidth()),
            height: Math.max($canvas[0].scrollHeight, $canvas.outerHeight())
        });
    };
    resizeSVG();
    $('#canvas').on('scroll', resizeSVG);
    $(window).on('resize', resizeSVG);
}

function createNode(type, name, x, y, id = null) {
    const nodeId = id || 'node_' + Date.now();
    const nodeObj = {
        id: nodeId,
        tipo: type,
        nombre: name,
        descripcion: '',
        dep_cod: '',
        per_cod: '',
        sla: '',
        com_obl: false,
        adj_obl: false,
        usu_asig: 'TODOS',
        x: x,
        y: y
    };

    nodes.push(nodeObj);
    renderNode(nodeObj);
    return nodeObj;
}

function renderNode(node) {
    const $nodeEl = $(`
        <div class="wf-node node-${node.tipo}" id="${node.id}" style="left: ${node.x}px; top: ${node.y}px;">
            <div class="wf-node-header">
                <span><i class="bi bi-square-fill text-secondary"></i> ${node.nombre}</span>
                <button type="button" class="btn btn-xs p-0 border-0" onclick="deleteNode('${node.id}', event)"><i class="bi bi-x text-danger"></i></button>
            </div>
            <div class="wf-node-body">
                ${node.tipo}<br>
                <small class="text-muted">${node.descripcion || 'Sin descripción'}</small>
            </div>
            <div class="node-port node-port-in" data-node-id="${node.id}"></div>
            <div class="node-port node-port-out" data-node-id="${node.id}"></div>
        </div>
    `);

    // Eventos de arrastre del nodo
    $nodeEl.on('mousedown', function(e) {
        if ($(e.target).hasClass('node-port') || $(e.target).hasClass('btn-close') || $(e.target).is('i')) return;
        isDraggingNode = true;
        draggedElement = $(this);
        const nodePos = draggedElement.position();
        offset = {
            x: e.pageX - nodePos.left,
            y: e.pageY - nodePos.top
        };
        e.preventDefault();
    });

    $(document).on('mousemove', function(e) {
        if (isDraggingNode && draggedElement) {
            const x = Math.max(10, e.pageX - offset.x);
            const y = Math.max(10, e.pageY - offset.y);
            draggedElement.css({ left: x + 'px', top: y + 'px' });
            
            // Actualizar datos del nodo
            const nId = draggedElement.attr('id');
            const n = nodes.find(item => item.id === nId);
            if (n) {
                n.x = x;
                n.y = y;
            }
            redrawConnections();
        }
    });

    $(document).on('mouseup', function() {
        if (isDraggingNode) {
            isDraggingNode = false;
            draggedElement = null;
        }
    });

    // Configurar evento de doble clic para abrir propiedades
    $nodeEl.on('dblclick', function() {
        openNodeProperties(node.id);
    });

    // Configurar puertos de conexión
    $nodeEl.find('.node-port-out').on('mousedown', function(e) {
        drawingConnection = true;
        connStartPort = $(this);
        e.stopPropagation();
        e.preventDefault();
    });

    $nodeEl.find('.node-port-in').on('mouseup', function(e) {
        if (drawingConnection && connStartPort) {
            const startNodeId = connStartPort.data('node-id');
            const endNodeId = $(this).data('node-id');
            if (startNodeId !== endNodeId) {
                createConnection(startNodeId, endNodeId);
            }
        }
    });

    $('#canvas').append($nodeEl);
}

function openNodeProperties(id) {
    activeNode = nodes.find(item => item.id === id);
    if (!activeNode) return;

    $('#nodeId').val(activeNode.id);
    $('#nodeName').val(activeNode.nombre);
    $('#nodeDesc').val(activeNode.descripcion);
    $('#nodeDep').val(activeNode.dep_cod);
    $('#nodePer').val(activeNode.per_cod);
    $('#nodeSla').val(activeNode.sla);
    $('#nodeComObl').prop('checked', activeNode.com_obl);
    $('#nodeAdjObl').prop('checked', activeNode.adj_obl);

    // Ocultar/Mostrar campos según tipo de nodo
    if (activeNode.tipo === 'INICIO' || activeNode.tipo === 'FIN') {
        $('.sec-responsabilidad, .sec-sla, .sec-checks').hide();
    } else if (activeNode.tipo === 'DECISION') {
        $('.sec-responsabilidad, .sec-sla, .sec-checks').hide();
    } else if (activeNode.tipo === 'NOTIFICACION') {
        $('.sec-responsabilidad, .sec-checks').hide();
        $('.sec-sla').show();
    } else {
        $('.sec-responsabilidad, .sec-sla, .sec-checks').show();
        
        // Cargar comportamiento de departamento y asignación de usuarios
        if (activeNode.dep_cod) {
            $('#btnManageDepUsers').show();
            $('.sec-asignacion-usuarios').show();
            $('#secNodePer').hide();
            
            const isTodos = !activeNode.usu_asig || activeNode.usu_asig === 'TODOS';
            if (isTodos) {
                $('#asigTodos').prop('checked', true);
                $('#secAsigEspecificosList').hide();
            } else {
                $('#asigEspecificos').prop('checked', true);
                $('#secAsigEspecificosList').show();
            }
            cargarUsuariosAsignacionNodo(activeNode.dep_cod);
        } else {
            $('#btnManageDepUsers').hide();
            $('.sec-asignacion-usuarios').hide();
            $('#secNodePer').show();
        }
    }

    $('#flujoProps').hide();
    $('#nodeProps').show();
    $('#propertiesDrawer').addClass('open');

    // Escuchar cambios en los inputs para actualizar el nodo en vivo
    $('#nodeName, #nodeDesc, #nodeDep, #nodePer, #nodeSla, #nodeComObl, #nodeAdjObl').off('change input').on('change input', function() {
        activeNode.nombre = $('#nodeName').val();
        activeNode.descripcion = $('#nodeDesc').val();
        activeNode.dep_cod = $('#nodeDep').val();
        activeNode.per_cod = $('#nodePer').val();
        activeNode.sla = $('#nodeSla').val();
        activeNode.com_obl = $('#nodeComObl').is(':checked');
        activeNode.adj_obl = $('#nodeAdjObl').is(':checked');

        // Actualizar etiqueta del nodo visual
        const $hdr = $(`#${activeNode.id}`).find('.wf-node-header span');
        $hdr.html(`<i class="bi bi-square-fill text-secondary"></i> ${activeNode.nombre}`);
        const $bdy = $(`#${activeNode.id}`).find('.wf-node-body');
        $bdy.html(`${activeNode.tipo}<br><small class="text-muted">${activeNode.descripcion || 'Sin descripción'}</small>`);
    });
}

function onDepartmentChange(depCod) {
    if (depCod) {
        $('#btnManageDepUsers').show();
        $('.sec-asignacion-usuarios').show();
        $('#secNodePer').hide(); // Ocultar perfiles cuando hay departamento
        
        // Cargar usuarios de este departamento para la selección del nodo
        cargarUsuariosAsignacionNodo(depCod);
    } else {
        $('#btnManageDepUsers').hide();
        $('.sec-asignacion-usuarios').hide();
        $('#secNodePer').show(); // Mostrar perfiles si no hay departamento
        if (activeNode) {
            activeNode.usu_asig = 'TODOS';
        }
    }
}

function toggleAsigType(val) {
    if (val === 'ESPECIFICOS') {
        $('#secAsigEspecificosList').show();
    } else {
        $('#secAsigEspecificosList').hide();
        if (activeNode) {
            activeNode.usu_asig = 'TODOS';
        }
    }
}

function cargarUsuariosAsignacionNodo(depCod, callback) {
    $.getJSON('adq_configuracion.php', { ajax_get_users_by_department: true, dep_cod: depCod }, function(res) {
        if (res.success) {
            let html = '';
            res.usuarios.forEach(function(u) {
                html += `
                    <div class="form-check">
                        <input class="form-check-input chk-asig-usu" type="checkbox" value="${u.Usu_Cod}" id="chkAsig_${u.Usu_Cod}" onchange="onUserAsigCheckboxChange()">
                        <label class="form-check-label small" for="chkAsig_${u.Usu_Cod}">
                            ${u.Usuario_Nom}
                        </label>
                    </div>
                `;
            });
            if (res.usuarios.length === 0) {
                html = '<div class="text-danger small p-1">No hay usuarios asignados a este departamento.</div>';
            }
            $('#secAsigEspecificosList').html(html);
            
            // Si el nodo activo tiene usuarios específicos, marcarlos
            if (activeNode && activeNode.usu_asig && activeNode.usu_asig !== 'TODOS') {
                const selectedUsers = activeNode.usu_asig.split(',');
                selectedUsers.forEach(function(uId) {
                    $(`#chkAsig_${uId}`).prop('checked', true);
                });
            }
            
            if (callback) callback();
        }
    });
}

function onUserAsigCheckboxChange() {
    if (!activeNode) return;
    const selected = [];
    $('.chk-asig-usu:checked').each(function() {
        selected.push($(this).val());
    });
    if (selected.length > 0) {
        activeNode.usu_asig = selected.join(',');
    } else {
        activeNode.usu_asig = 'TODOS'; // Fallback si ninguno está marcado
    }
}

// Modal: Gestión de Usuarios por Departamento
function abrirGestionUsuarios() {
    const depCod = $('#nodeDep').val();
    if (!depCod) return;
    
    $('#manageDepCod').val(depCod);
    $('#depUsersList').html('<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-secondary" role="status"></div> Cargando...</div>');
    
    $('#modalDepUsers').modal('show');
    
    $.getJSON('adq_configuracion.php', { ajax_get_department_users: true, dep_cod: depCod }, function(res) {
        if (res.success) {
            let html = '';
            res.usuarios.forEach(function(u) {
                const checked = parseInt(u.asignado) === 1 ? 'checked' : '';
                html += `
                    <label class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <input class="form-check-input me-2 chk-dep-usu" type="checkbox" value="${u.Usu_Cod}" ${checked}>
                            ${u.Usuario_Nom}
                        </div>
                    </label>
                `;
            });
            if (res.usuarios.length === 0) {
                html = '<div class="text-center p-3 text-muted">No hay usuarios activos en el sistema.</div>';
            }
            $('#depUsersList').html(html);
        } else {
            $('#depUsersList').html(`<div class="alert alert-danger p-2 small">${res.message}</div>`);
        }
    });
}

function guardarUsuariosDepartamento() {
    const depCod = $('#manageDepCod').val();
    const selectedUsers = [];
    $('.chk-dep-usu:checked').each(function() {
        selectedUsers.push($(this).val());
    });
    
    $.post('adq_configuracion.php', {
        ajax_save_department_users: true,
        dep_cod: depCod,
        usuarios: selectedUsers
    }, function(res) {
        if (res.success) {
            // Cerrar modal
            $('#modalDepUsers').modal('hide');
            
            // Recargar la lista de asignación del nodo para reflejar los cambios
            cargarUsuariosAsignacionNodo(depCod);
        } else {
            alert('Error al guardar usuarios: ' + res.message);
        }
    }, 'json');
}

function closeDrawer() {
    $('#propertiesDrawer').removeClass('open');
    $('#nodeProps').hide();
    $('#flujoProps').show();
    activeConnection = null;
    redrawConnections();
}

function deleteNode(id, e) {
    if (e) e.stopPropagation();
    
    // Eliminar del array
    nodes = nodes.filter(item => item.id !== id);
    // Eliminar conexiones asociadas
    connections = connections.filter(item => item.origen !== id && item.destino !== id);
    // Eliminar del DOM
    $(`#${id}`).remove();
    
    if (activeNode && activeNode.id === id) {
        closeDrawer();
    }
    redrawConnections();
}

function createConnection(origenId, destinoId) {
    // Validar si ya existe
    const duplicada = connections.find(c => c.origen === origenId && c.destino === destinoId);
    if (duplicada) return;

    let accion = 'APROBAR';
    const nodoOri = nodes.find(n => n.id === origenId);
    if (nodoOri && nodoOri.tipo === 'DECISION') {
        accion = 'CONDICIONAL';
    }

    connections.push({
        origen: origenId,
        destino: destinoId,
        accion: accion,
        condicion: null
    });

    redrawConnections();
}

function drawTempCable(toX, toY) {
    $('#tempCable').remove();
    const startOffset = connStartPort.offset();
    const canvasOffset = $('#canvas').offset();
    
    const x1 = startOffset.left - canvasOffset.left + $('#canvas').scrollLeft() + 6;
    const y1 = startOffset.top - canvasOffset.top + $('#canvas').scrollTop() + 6;
    
    // Convertir coordenadas de ratón globales a coordenadas locales del canvas
    const localToX = toX - canvasOffset.left + $('#canvas').scrollLeft();
    const localToY = toY - canvasOffset.top + $('#canvas').scrollTop();
    
    const pathString = `M ${x1} ${y1} C ${(x1 + localToX)/2} ${y1}, ${(x1 + localToX)/2} ${localToY}, ${localToX} ${localToY}`;
    
    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("id", "tempCable");
    path.setAttribute("d", pathString);
    path.setAttribute("fill", "none");
    path.setAttribute("stroke", "#0d6efd");
    path.setAttribute("stroke-width", "3");
    path.setAttribute("stroke-dasharray", "5,5");
    path.setAttribute("opacity", "0.35");
    
    document.getElementById('svgCanvas').appendChild(path);
}

function redrawConnections() {
    // Limpiar todas las líneas de conexión previas
    $('#svgCanvas').empty();
    const canvasOffset = $('#canvas').offset();

    connections.forEach(function(con, index) {
        const $oriNode = $(`#${con.origen}`);
        const $desNode = $(`#${con.destino}`);

        if ($oriNode.length && $desNode.length) {
            const oriPort = $oriNode.find('.node-port-out');
            const desPort = $desNode.find('.node-port-in');

            const oriOffset = oriPort.offset();
            const desOffset = desPort.offset();

            const x1 = oriOffset.left - canvasOffset.left + $('#canvas').scrollLeft() + 6;
            const y1 = oriOffset.top - canvasOffset.top + $('#canvas').scrollTop() + 6;
            const x2 = desOffset.left - canvasOffset.left + $('#canvas').scrollLeft() + 6;
            const y2 = desOffset.top - canvasOffset.top + $('#canvas').scrollTop() + 6;

            const midX = (x1 + x2) / 2;
            const pathString = `M ${x1} ${y1} C ${midX} ${y1}, ${midX} ${y2}, ${x2} ${y2}`;

            // Dibujar flecha dirigida
            const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
            path.setAttribute("d", pathString);
            path.setAttribute("fill", "none");
            
            // Si la conexión está activa/seleccionada, la destacamos con color azul brillante, mayor grosor y opacidad de 0.3
            const isSelected = (activeConnection && activeConnection.origen === con.origen && activeConnection.destino === con.destino);
            if (isSelected) {
                path.setAttribute("stroke", "#0d6efd");
                path.setAttribute("stroke-width", "4");
                path.setAttribute("opacity", "0.3");
                path.setAttribute("marker-end", "url(#arrow-selected)");
            } else {
                path.setAttribute("stroke", "#495057");
                path.setAttribute("stroke-width", "3");
                path.setAttribute("opacity", "1.0");
                path.setAttribute("marker-end", "url(#arrow)");
            }
            
            // Añadir efectos de hover (cursor pointer y cambio de color sutil)
            path.style.cursor = "pointer";
            $(path).hover(
                function() { 
                    if (!isSelected) {
                        path.setAttribute("stroke", "#0d6efd"); 
                        path.setAttribute("stroke-width", "3.5");
                    }
                },
                function() { 
                    if (!isSelected) {
                        path.setAttribute("stroke", "#495057"); 
                        path.setAttribute("stroke-width", "3");
                    }
                }
            );

            // Al hacer clic izquierdo, seleccionamos la conexión
            $(path).on('click', function(e) {
                e.stopPropagation(); // Evitar deselección por clic en canvas
                $('#propertiesDrawer').removeClass('open'); // Cerrar el drawer de propiedades de nodo
                activeNode = null;
                activeConnection = con;
                redrawConnections();
            });
            
            // Marker de la flecha
            if (!$('#arrow').length) {
                const defs = document.createElementNS("http://www.w3.org/2000/svg", "defs");
                defs.innerHTML = `
                    <marker id="arrow" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                        <path d="M 0 0 L 10 5 L 0 10 z" fill="#495057"/>
                    </marker>
                    <marker id="arrow-selected" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                        <path d="M 0 0 L 10 5 L 0 10 z" fill="#0d6efd"/>
                    </marker>
                `;
                document.getElementById('svgCanvas').appendChild(defs);
            }

            document.getElementById('svgCanvas').appendChild(path);

            // Añadir manejador de eliminación de cable por clic derecho
            $(path).on('contextmenu', function(e) {
                e.preventDefault();
                if (confirm('¿Desea eliminar esta conexión?')) {
                    connections.splice(index, 1);
                    redrawConnections();
                }
            });
        }
    });
}

function cargarFlujo() {
    const id = $('#selWorkflow').val();
    if (!id) {
        // Modo creación
        workflowId = null;
        nodes = [];
        connections = [];
        $('.wf-node').remove();
        redrawConnections();
        $('#flowName').val('');
        $('#flowDesc').val('');
        $('#lblFlowActiveName').hide().find('span').text('');
        return;
    }

    $.getJSON('adq_configuracion.php', { ajax_load_workflow: true, id: id }, function(res) {
        if (res.success) {
            workflowId = res.flujo.id;
            $('#flowName').val(res.flujo.nombre);
            $('#flowDesc').val(res.flujo.descripcion);
            $('#lblFlowActiveName').show().find('span').text(res.flujo.nombre);
            
            // Limpiar lienzo
            $('.wf-node').remove();
            nodes = [];
            connections = [];

            // Recrear nodos
            res.nodos.forEach(function(n) {
                createNode(n.tipo, n.nombre, n.x, n.y, n.id);
                // Cargar datos extra en memoria
                const nodeMemory = nodes.find(item => item.id === n.id);
                nodeMemory.descripcion = n.descripcion;
                nodeMemory.dep_cod = n.dep_cod;
                nodeMemory.per_cod = n.per_cod;
                nodeMemory.sla = n.sla;
                nodeMemory.com_obl = (parseInt(n.com_obl) === 1);
                nodeMemory.adj_obl = (parseInt(n.adj_obl) === 1);
                nodeMemory.usu_asig = n.usu_asig || 'TODOS';
            });

            // Recrear conexiones
            connections = res.conexiones;
            redrawConnections();
        } else {
            alert('Error al cargar flujo: ' + res.message);
        }
    });
}

function abrirModalNuevoFlujo() {
    // Si ya hay un id seleccionado, lo limpiamos para que sea un flujo nuevo
    $('#modalFlowName').val('');
    $('#modalFlowDesc').val('');
    $('#modalWorkflowDataLabel').text('Crear Nuevo Flujo Modelo');
    $('#modalWorkflowData').modal('show');
}

function aceptarDatosFlujo() {
    const nombre = $('#modalFlowName').val().trim();
    if (!nombre) {
        alert('Por favor ingrese el nombre del flujo.');
        return;
    }
    
    // Asignar a los campos ocultos
    $('#flowName').val(nombre);
    $('#flowDesc').val($('#modalFlowDesc').val());
    
    // Limpiar lienzo para empezar un flujo nuevo
    workflowId = null;
    nodes = [];
    connections = [];
    $('.wf-node').remove();
    redrawConnections();
    
    // Mostrar indicador de flujo activo
    $('#lblFlowActiveName').show().find('span').text(nombre);
    
    // Deseleccionar cualquier flujo en el combo
    $('#selWorkflow').val('');
    
    $('#modalWorkflowData').modal('hide');
}

function guardarFlujo() {
    const nombre = $('#flowName').val().trim();
    if (!nombre) {
        // Si no tiene nombre, forzar a que abra el modal para definirlo
        $('#modalFlowName').val('');
        $('#modalFlowDesc').val($('#flowDesc').val());
        $('#modalWorkflowDataLabel').text('Definir Datos del Flujo para Guardar');
        $('#modalWorkflowData').modal('show');
        return;
    }

    const payload = {
        id: workflowId,
        nombre: nombre,
        descripcion: $('#flowDesc').val(),
        nodos: nodes,
        conexiones: connections
    };

    fetch('adq_configuracion.php?ajax_save_workflow=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('¡El flujo modelo se ha guardado con éxito!');
            if (!workflowId) {
                // Agregar opción al combo y seleccionarlo
                $('#selWorkflow').append(`<option value="${res.id}">${nombre}</option>`);
                $('#selWorkflow').val(res.id);
                workflowId = res.id;
            }
            $('#lblFlowActiveName').show().find('span').text(nombre);
        } else {
            alert('Error al guardar: ' + res.message);
        }
    });
}
