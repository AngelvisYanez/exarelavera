/**
 * EXA Workflow Builder JS Logic
 * Lógica interactiva SVG, Drag & Drop y Drawer de configuración.
 * @author Oz <oz-agent@warp.dev>
 */

let workflowId = null;
let workflowFamilyId = null;
let workflowMeta = null;
let nodes = [];
let connections = [];
let activeNode = null;
let activeConnection = null; // Conexión actualmente seleccionada
let isDraggingNode = false;
let draggedElement = null;
let dragPointerOffset = { x: 0, y: 0 };

function wfPointInCanvas(pageX, pageY) {
    const $canvas = $('#canvas');
    const canvasOffset = $canvas.offset();
    return {
        x: pageX - canvasOffset.left + $canvas.scrollLeft(),
        y: pageY - canvasOffset.top + $canvas.scrollTop()
    };
}

function setupNodeDrag() {
    const $canvas = $('#canvas');

    $canvas.off('mousedown.wfBuilderDrag', '.wf-node');
    $canvas.on('mousedown.wfBuilderDrag', '.wf-node', function(e) {
        if ($(e.target).closest('.node-port').length) {
            return;
        }
        if ($(e.target).closest('button').length) {
            return;
        }
        isDraggingNode = true;
        draggedElement = $(this);
        const pt = wfPointInCanvas(e.pageX, e.pageY);
        const nodeLeft = parseFloat(draggedElement.css('left')) || 0;
        const nodeTop = parseFloat(draggedElement.css('top')) || 0;
        dragPointerOffset = {
            x: pt.x - nodeLeft,
            y: pt.y - nodeTop
        };
        e.preventDefault();
    });

    $(document).off('mousemove.wfBuilderDrag mouseup.wfBuilderDrag');
    $(document).on('mousemove.wfBuilderDrag', function(e) {
        if (!isDraggingNode || !draggedElement) {
            return;
        }
        const pt = wfPointInCanvas(e.pageX, e.pageY);
        const x = Math.max(0, pt.x - dragPointerOffset.x);
        const y = Math.max(0, pt.y - dragPointerOffset.y);
        draggedElement.css({ left: x + 'px', top: y + 'px' });

        const nId = draggedElement.attr('id');
        const n = nodes.find(function(item) { return sameId(item.id, nId); });
        if (n) {
            n.x = x;
            n.y = y;
        }
        updateCanvasBounds();
        redrawConnections();
    });

    $(document).on('mouseup.wfBuilderDrag', function() {
        if (isDraggingNode) {
            isDraggingNode = false;
            draggedElement = null;
            updateCanvasBounds();
        }
    });
}
let workflowBuilderReady = false;
let pendingSaveAfterModal = false;

// Puerto de conexión temporal para dibujo de cable
let drawingConnection = false;
let connStartPort = null;
const WF_CANVAS_PAD = 160;
const WF_CANVAS_MIN_H = 560;

function wfEnsureCanvasSurface() {
    const $canvas = $('#canvas');
    if (!$canvas.length) {
        return $();
    }
    let $surface = $('#canvasSurface');
    if ($surface.length) {
        return $surface;
    }
    $surface = $('<div class="canvas-surface" id="canvasSurface"></div>');
    const $svg = $('#svgCanvas');
    if ($svg.length) {
        $surface.append($svg);
    } else {
        $surface.append('<svg class="svg-canvas" id="svgCanvas"></svg>');
    }
    $canvas.append($surface);
    $canvas.children('.wf-node').appendTo($surface);
    return $surface;
}

function wfNodeSize($node) {
    return {
        w: $node.outerWidth() || 180,
        h: $node.outerHeight() || 90
    };
}

function wfPortCanvasPoint(nodeId, portType) {
    const $node = wfNodeEl(nodeId);
    if (!$node.length) {
        return null;
    }
    const x = parseFloat($node.css('left')) || 0;
    const y = parseFloat($node.css('top')) || 0;
    const size = wfNodeSize($node);
    const cy = y + (size.h / 2);
    if (portType === 'out') {
        return { x: x + size.w + 6, y: cy };
    }
    return { x: x - 6, y: cy };
}

function updateCanvasBounds() {
    wfEnsureCanvasSurface();
    const $canvas = $('#canvas');
    const $surface = $('#canvasSurface');
    if (!$canvas.length || !$surface.length) {
        return;
    }

    const viewportW = $canvas.innerWidth() || 800;
    let maxRight = viewportW;
    let maxBottom = WF_CANVAS_MIN_H;

    $surface.find('.wf-node').each(function() {
        const $el = $(this);
        const x = parseFloat($el.css('left')) || 0;
        const y = parseFloat($el.css('top')) || 0;
        const size = wfNodeSize($el);
        maxRight = Math.max(maxRight, x + size.w + WF_CANVAS_PAD);
        maxBottom = Math.max(maxBottom, y + size.h + WF_CANVAS_PAD);
    });

    $surface.css({
        minWidth: Math.max(maxRight, viewportW) + 'px',
        minHeight: maxBottom + 'px'
    });
    resizeSVG();
}

function resizeSVG() {
    const $surface = $('#canvasSurface');
    const $canvas = $('#canvas');
    const target = $surface.length ? $surface[0] : ($canvas.length ? $canvas[0] : null);
    if (!target) {
        return;
    }
    const w = Math.max(target.scrollWidth, target.offsetWidth, $canvas.innerWidth() || 0);
    const h = Math.max(target.scrollHeight, target.offsetHeight, WF_CANVAS_MIN_H);
    $('#svgCanvas').attr({ width: w, height: h });
}

/** IDs numéricos de BD no son válidos como selector CSS (#5); usar prefijo estable. */
function wfEscHtml(text) {
    if (text === null || text === undefined) {
        return '';
    }
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function wfNodeDescFromApi(n) {
    if (!n) {
        return '';
    }
    if (n.descripcion !== null && n.descripcion !== undefined && String(n.descripcion) !== '') {
        return String(n.descripcion);
    }
    if (n.Nod_Des !== null && n.Nod_Des !== undefined && String(n.Nod_Des) !== '') {
        return String(n.Nod_Des);
    }
    if (n.nod_des !== null && n.nod_des !== undefined && String(n.nod_des) !== '') {
        return String(n.nod_des);
    }
    return '';
}

function wfNodeId(id) {
    if (id === null || id === undefined || id === '') {
        return 'node_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    }
    const s = String(id);
    if (s.indexOf('node_') === 0 || s.indexOf('n_') === 0) {
        return s;
    }
    return 'n_' + s;
}

function sameId(a, b) {
    return String(a) === String(b);
}

function wfNodeEl(id) {
    const el = document.getElementById(String(id));
    return el ? $(el) : $();
}

function wfNotify(tipo, mensaje, onAceptar) {
    if (typeof mostrarMensajeModal === 'function') {
        mostrarMensajeModal(tipo, mensaje, onAceptar);
    } else {
        alert(mensaje);
        if (typeof onAceptar === 'function') {
            onAceptar();
        }
    }
}

function initWorkflowBuilder() {
    if (!$('#canvas').length) {
        return;
    }
    wfEnsureCanvasSurface();
    setupCanvas();
    setupNodeDrag();
    setupToolbox();
    setupSVG();
    updateCanvasBounds();
    workflowBuilderReady = true;
    wfSetUserAsigControls(false);
}

$(document).ready(function() {
    initWorkflowBuilder();
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

        const pt = wfPointInCanvas(e.originalEvent.pageX, e.originalEvent.pageY);
        createNode(type, 'Nuevo ' + type, pt.x, pt.y);
    });

    // Deseleccionar al hacer clic en el fondo del lienzo
    $canvas.on('click', function(e) {
        if (e.target === this || e.target.id === 'svgCanvas' || e.target.id === 'canvasSurface') {
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
    resizeSVG();
    $(window).on('resize', function() {
        updateCanvasBounds();
        redrawConnections();
    });
}

function isNodoNotificable(tipo) {
    return ['APROBACION', 'RECEPCION', 'FACTURA', 'TAREA', 'AVANCE', 'FIN'].indexOf(tipo) >= 0;
}

function createNode(type, name, x, y, id = null, props = null) {
    const nodeId = wfNodeId(id);
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
        cot_edit: false,
        not_wa: false,
        not_em: false,
        not_asunto: '',
        not_texto: '',
        usu_asig: 'TODOS',
        asig_nombres: '',
        x: x,
        y: y
    };

    if (props && typeof props === 'object') {
        Object.assign(nodeObj, props);
    }

    nodes.push(nodeObj);
    renderNode(nodeObj);
    return nodeObj;
}

function isNodoTerminal(tipo) {
    return tipo === 'INICIO' || tipo === 'FIN';
}

function wfNodeHeaderIcon(tipo) {
    if (tipo === 'FACTURA') {
        return 'bi-receipt text-warning';
    }
    if (tipo === 'APROBACION') {
        return 'bi-check-circle-fill text-primary';
    }
    if (tipo === 'RECEPCION') {
        return 'bi-box-seam-fill text-purple';
    }
    if (tipo === 'TAREA') {
        return 'bi-card-checklist text-success';
    }
    if (tipo === 'AVANCE') {
        return 'bi-folder-plus text-info';
    }
    if (tipo === 'DECISION') {
        return 'bi-shuffle text-warning';
    }
    if (tipo === 'NOTIFICACION') {
        return 'bi-envelope-fill text-dark';
    }
    return 'bi-square-fill text-secondary';
}

function wfNodeAsigHtml(node) {
    const texto = node.asig_nombres ? String(node.asig_nombres).trim() : '';
    if (!texto) {
        return '';
    }
    return '<span class="wf-node-asig"><i class="bi bi-person-fill"></i> ' + wfEscHtml(texto) + '</span>';
}

function wfUpdateNodeAsigNombres(node) {
    if (!node || node.tipo === 'INICIO') {
        if (node) {
            node.asig_nombres = '';
        }
        return;
    }

    if (node.usu_asig && node.usu_asig !== 'TODOS') {
        const names = [];
        $('.chk-asig-usu:checked').each(function() {
            names.push($(this).closest('.form-check').find('label').text().trim());
        });
        if (names.length > 0) {
            node.asig_nombres = names.join(', ');
            return;
        }
        if (node.asig_nombres && String(node.asig_nombres).trim() !== '') {
            return;
        }
    }

    if (node.dep_cod) {
        const $opt = $('#nodeDep option[value="' + node.dep_cod + '"]');
        const depLabel = $opt.length ? $opt.text().trim() : '';
        if (depLabel && depLabel.indexOf('[') !== 0) {
            if (!node.usu_asig || node.usu_asig === 'TODOS') {
                node.asig_nombres = 'Todos (' + depLabel + ')';
                return;
            }
        }
    }

    if (node.per_cod) {
        const $per = $('#nodePer option[value="' + node.per_cod + '"]');
        if ($per.length) {
            const perLabel = $per.text().trim();
            if (perLabel) {
                node.asig_nombres = perLabel;
                return;
            }
        }
    }

    if (!node.usu_asig || node.usu_asig === 'TODOS') {
        node.asig_nombres = '';
    }
}

function refreshNodeView(node) {
    const $el = wfNodeEl(node.id);
    if (!$el.length) {
        return;
    }
    if (isNodoTerminal(node.tipo)) {
        const icon = node.tipo === 'INICIO' ? 'bi-play-fill' : 'bi-stop-fill';
        const color = node.tipo === 'INICIO' ? 'text-success' : 'text-danger';
        $el.find('.wf-node-header span').html(
            '<i class="bi ' + icon + ' ' + color + '"></i>' +
            '<span class="wf-node-terminal-label">' + wfEscHtml(node.nombre) + '</span>'
        );
        $el.find('.wf-node-body').empty();
        $el.find('.node-port-in').toggle(node.tipo !== 'INICIO');
        $el.find('.node-port-out').toggle(node.tipo !== 'FIN');
        return;
    }

    const desc = node.descripcion ? String(node.descripcion).trim() : '';
    const iconClass = wfNodeHeaderIcon(node.tipo);
    $el.find('.wf-node-header span').html(
        '<i class="bi ' + iconClass + ' wf-node-type-icon"></i>' +
        '<span class="wf-node-title-label">' + wfEscHtml(node.nombre) + '</span>'
    );
    $el.find('.wf-node-body').html(
        '<span class="wf-node-tipo-label">' + wfEscHtml(node.tipo) + '</span>' +
        '<span class="wf-node-desc">' + (desc ? wfEscHtml(desc) : 'Sin descripción') + '</span>' +
        wfNodeAsigHtml(node)
    );
    $el.find('.node-port-in, .node-port-out').show();
}

function renderNode(node) {
    const $nodeEl = $(`
        <div class="wf-node node-${node.tipo}" id="${node.id}" style="left: ${node.x}px; top: ${node.y}px;">
            <div class="wf-node-header">
                <span></span>
                <button type="button" class="btn btn-xs p-0 border-0 wf-node-delete-btn" onclick="deleteNode('${node.id}', event)"><i class="bi bi-x-lg text-danger"></i></button>
            </div>
            <div class="wf-node-body"></div>
            <div class="node-port node-port-in" data-node-id="${node.id}"></div>
            <div class="node-port node-port-out" data-node-id="${node.id}"></div>
        </div>
    `);

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

    wfEnsureCanvasSurface().append($nodeEl);
    refreshNodeView(node);
    updateCanvasBounds();
}

function openNodeProperties(id) {
    activeNode = nodes.find(item => sameId(item.id, id));
    if (!activeNode) return;

    $('#nodeId').val(activeNode.id);
    $('#nodeName').val(activeNode.nombre);
    $('#nodeDesc').val(activeNode.descripcion || '');
    $('#nodeDep').val(activeNode.dep_cod);
    $('#nodePer').val(activeNode.per_cod);
    $('#nodeSla').val(activeNode.sla);
    $('#nodeComObl').prop('checked', activeNode.com_obl);
    $('#nodeAdjObl').prop('checked', activeNode.adj_obl);
    $('#nodeCotEdit').prop('checked', !!activeNode.cot_edit);
    $('#nodeNotWa').prop('checked', !!activeNode.not_wa);
    $('#nodeNotEm').prop('checked', !!activeNode.not_em);
    $('#nodeNotAsunto').val(activeNode.not_asunto || '');
    $('#nodeNotTexto').val(activeNode.not_texto || '');

    // Ocultar/Mostrar campos según tipo de nodo
    if (activeNode.tipo === 'INICIO') {
        $('.sec-responsabilidad, .sec-sla, .sec-checks, .sec-notificaciones').hide();
    } else if (activeNode.tipo === 'DECISION') {
        $('.sec-responsabilidad, .sec-sla, .sec-checks, .sec-notificaciones').hide();
    } else if (activeNode.tipo === 'NOTIFICACION') {
        $('.sec-responsabilidad, .sec-checks, .sec-notificaciones').hide();
        $('.sec-sla').show();
    } else {
        $('.sec-responsabilidad, .sec-sla, .sec-checks').show();
        if (isNodoNotificable(activeNode.tipo)) {
            $('.sec-notificaciones').show();
        } else {
            $('.sec-notificaciones').hide();
        }
        
        // Cargar comportamiento de departamento y asignación de usuarios
        if (activeNode.dep_cod) {
            $('#btnManageDepUsers').show();
            $('.sec-asignacion-usuarios').show();
            $('#secNodePer').hide();
            wfSetUserAsigControls(true);
            
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
            wfSetUserAsigControls(false);
        }
    }

    $('#flujoProps').hide();
    $('#nodeProps').show();
    $('#propertiesDrawer').addClass('open');

    // Escuchar cambios en los inputs para actualizar el nodo en vivo
    $('#nodeName, #nodeDesc, #nodeDep, #nodePer, #nodeSla, #nodeComObl, #nodeAdjObl, #nodeCotEdit, #nodeNotWa, #nodeNotEm, #nodeNotAsunto, #nodeNotTexto').off('change input').on('change input', function() {
        activeNode.nombre = $('#nodeName').val();
        activeNode.descripcion = $('#nodeDesc').val();
        activeNode.dep_cod = $('#nodeDep').val();
        activeNode.per_cod = $('#nodePer').val();
        activeNode.sla = $('#nodeSla').val();
        activeNode.com_obl = $('#nodeComObl').is(':checked');
        activeNode.adj_obl = $('#nodeAdjObl').is(':checked');
        activeNode.cot_edit = $('#nodeCotEdit').is(':checked');
        activeNode.not_wa = $('#nodeNotWa').is(':checked');
        activeNode.not_em = $('#nodeNotEm').is(':checked');
        activeNode.not_asunto = $('#nodeNotAsunto').val();
        activeNode.not_texto = $('#nodeNotTexto').val();
        wfUpdateNodeAsigNombres(activeNode);
        refreshNodeView(activeNode);
    });
}

function wfSetUserAsigControls(hasDep) {
    $('#asigTodos, #asigEspecificos').prop('disabled', !hasDep);
    if (!hasDep) {
        $('#asigTodos').prop('checked', true);
        $('#secAsigEspecificosList').hide().html(
            '<div class="text-muted small p-1">Seleccione primero un departamento responsable.</div>'
        );
        $('#lblNodeDepHint').text('Seleccione un departamento para habilitar la asignacion de usuarios.');
    } else {
        $('#lblNodeDepHint').text('Use el boton de personas para registrar usuarios en el departamento si aun no aparecen.');
    }
}

function onDepartmentChange(depCod) {
    if (depCod) {
        $('#btnManageDepUsers').show();
        $('.sec-asignacion-usuarios').show();
        $('#secNodePer').hide();
        wfSetUserAsigControls(true);

        if (activeNode) {
            activeNode.dep_cod = depCod;
            if (!activeNode.usu_asig || activeNode.usu_asig === '') {
                activeNode.usu_asig = 'TODOS';
            }
        }
        $('#asigTodos').prop('checked', true);
        $('#secAsigEspecificosList').hide();

        cargarUsuariosAsignacionNodo(depCod);
    } else {
        $('#btnManageDepUsers').hide();
        $('.sec-asignacion-usuarios').hide();
        $('#secNodePer').show();
        wfSetUserAsigControls(false);
        if (activeNode) {
            activeNode.dep_cod = '';
            activeNode.usu_asig = 'TODOS';
            wfUpdateNodeAsigNombres(activeNode);
            refreshNodeView(activeNode);
        }
    }
}

function toggleAsigType(val) {
    const depCod = $('#nodeDep').val();
    if (val === 'ESPECIFICOS' && !depCod) {
        $('#asigTodos').prop('checked', true);
        $('#secAsigEspecificosList').hide();
        wfNotify('danger', 'Debe seleccionar un departamento responsable antes de elegir usuarios especificos.');
        return;
    }
    if (val === 'ESPECIFICOS') {
        $('#secAsigEspecificosList').show();
        cargarUsuariosAsignacionNodo(depCod);
    } else {
        $('#secAsigEspecificosList').hide();
        if (activeNode) {
            activeNode.usu_asig = 'TODOS';
            wfUpdateNodeAsigNombres(activeNode);
            refreshNodeView(activeNode);
        }
    }
}

function cargarUsuariosAsignacionNodo(depCod, callback) {
    if (!depCod) {
        wfSetUserAsigControls(false);
        if (callback) {
            callback();
        }
        return;
    }
    $('#secAsigEspecificosList').html('<div class="text-muted small p-1">Cargando usuarios...</div>');
    $.getJSON('adq_configuracion.php', { ajax_get_users_by_department: true, dep_cod: depCod }, function(res) {
        if (res.success) {
            let html = '';
            res.usuarios.forEach(function(u) {
                const equivIds = (u.Usu_Cods || String(u.Usu_Cod)).split(',');
                html += `
                    <div class="form-check">
                        <input class="form-check-input chk-asig-usu" type="checkbox" value="${u.Usu_Cod}" data-equiv="${equivIds.join(',')}" id="chkAsig_${u.Usu_Cod}" onchange="onUserAsigCheckboxChange()">
                        <label class="form-check-label small" for="chkAsig_${u.Usu_Cod}">
                            ${u.Usuario_Nom}
                        </label>
                    </div>
                `;
            });
            if (res.usuarios.length === 0) {
                html = '<div class="text-warning small p-1">No hay usuarios asignados a este departamento. Use el boton <i class="bi bi-people-fill"></i> junto al select para registrarlos.</div>';
            }
            $('#secAsigEspecificosList').html(html);

            if (activeNode && activeNode.usu_asig && activeNode.usu_asig !== 'TODOS') {
                const selectedUsers = activeNode.usu_asig.split(',');
                $('.chk-asig-usu').each(function() {
                    const equiv = ($(this).data('equiv') || $(this).val()).toString().split(',');
                    const match = selectedUsers.some(function(uId) {
                        return equiv.indexOf(String(uId).trim()) !== -1;
                    });
                    if (match) {
                        $(this).prop('checked', true);
                    }
                });
            }

            if (callback) {
                callback();
            }
            if (activeNode) {
                wfUpdateNodeAsigNombres(activeNode);
                refreshNodeView(activeNode);
            }
        } else {
            $('#secAsigEspecificosList').html(
                '<div class="text-danger small p-1">' + (res.message || 'No se pudo cargar usuarios del departamento.') + '</div>'
            );
        }
    }).fail(function() {
        $('#secAsigEspecificosList').html('<div class="text-danger small p-1">Error de red al cargar usuarios del departamento.</div>');
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
        activeNode.usu_asig = 'TODOS';
    }
    wfUpdateNodeAsigNombres(activeNode);
    refreshNodeView(activeNode);
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
            $('#modalDepUsers').modal('hide');
            cargarUsuariosAsignacionNodo(depCod, function() {
                if ($('#asigEspecificos').is(':checked')) {
                    $('#secAsigEspecificosList').show();
                }
            });
            wfNotify('success', 'Usuarios del departamento actualizados.');
        } else {
            wfNotify('danger', 'Error al guardar usuarios: ' + (res.message || 'Error desconocido'));
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
    nodes = nodes.filter(item => !sameId(item.id, id));
    // Eliminar conexiones asociadas
    connections = connections.filter(item => !sameId(item.origen, id) && !sameId(item.destino, id));
    // Eliminar del DOM
    wfNodeEl(id).remove();
    
    if (activeNode && sameId(activeNode.id, id)) {
        closeDrawer();
    }
    updateCanvasBounds();
    redrawConnections();
}

function createConnection(origenId, destinoId) {
    // Validar si ya existe
    const duplicada = connections.find(c => sameId(c.origen, origenId) && sameId(c.destino, destinoId));
    if (duplicada) return;

    let accion = 'APROBAR';
    const nodoOri = nodes.find(n => sameId(n.id, origenId));
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
    if (!connStartPort) {
        return;
    }
    const startNodeId = connStartPort.data('node-id');
    const p1 = wfPortCanvasPoint(startNodeId, 'out');
    if (!p1) {
        return;
    }

    const pt = wfPointInCanvas(toX, toY);
    const pathString = `M ${p1.x} ${p1.y} C ${(p1.x + pt.x) / 2} ${p1.y}, ${(p1.x + pt.x) / 2} ${pt.y}, ${pt.x} ${pt.y}`;
    
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
    const $svg = $('#svgCanvas');
    $svg.empty();

    const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
    defs.innerHTML = `
        <marker id="arrow" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
            <path d="M 0 0 L 10 5 L 0 10 z" fill="#495057"/>
        </marker>
        <marker id="arrow-selected" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
            <path d="M 0 0 L 10 5 L 0 10 z" fill="#0d6efd"/>
        </marker>
    `;
    $svg[0].appendChild(defs);

    connections.forEach(function(con, index) {
        const p1 = wfPortCanvasPoint(con.origen, 'out');
        const p2 = wfPortCanvasPoint(con.destino, 'in');

        if (p1 && p2) {
            const midX = (p1.x + p2.x) / 2;
            const pathString = `M ${p1.x} ${p1.y} C ${midX} ${p1.y}, ${midX} ${p2.y}, ${p2.x} ${p2.y}`;

            // Dibujar flecha dirigida
            const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
            path.setAttribute("d", pathString);
            path.setAttribute("fill", "none");
            
            // Si la conexión está activa/seleccionada, la destacamos con color azul brillante, mayor grosor y opacidad de 0.3
            const isSelected = (activeConnection && sameId(activeConnection.origen, con.origen) && sameId(activeConnection.destino, con.destino));
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
                e.stopPropagation();
                $('#propertiesDrawer').removeClass('open');
                activeNode = null;
                activeConnection = con;
                redrawConnections();
            });

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

function actualizarEstadoFlujoUI(flujo) {
    workflowMeta = flujo || null;
    if (!flujo || !flujo.nombre) {
        $('#lblFlowActiveName').hide();
        $('#lblFlowVersion').hide();
        $('#lblFlowDraft').hide();
        $('#lblFlowActiveInstances').hide();
        $('.wf-builder-status-bar').hide();
        return;
    }
    $('.wf-builder-status-bar').show();
    $('#lblFlowActiveName').show().find('span').text(flujo.nombre);
    if (flujo.version) {
        $('#lblFlowVersion').show().text('v' + flujo.version + (flujo.es_borrador ? ' (borrador)' : ' (publicada)'));
    } else {
        $('#lblFlowVersion').hide();
    }
    if (flujo.es_borrador) {
        $('#lblFlowDraft').show();
    } else {
        $('#lblFlowDraft').hide();
    }
    if (flujo.instancias_activas > 0) {
        $('#lblFlowActiveInstances').show().html('<i class="bi bi-hourglass-split"></i> ' + flujo.instancias_activas + ' solicitud(es) en curso con version anterior');
    } else {
        $('#lblFlowActiveInstances').hide();
    }
}

function cargarFlujo() {
    const id = $('#selWorkflow').val();
    if (!id) {
        workflowId = null;
        workflowFamilyId = null;
        workflowMeta = null;
        nodes = [];
        connections = [];
        $('.wf-node').remove();
        redrawConnections();
        updateCanvasBounds();
        $('#flowName').val('');
        $('#flowDesc').val('');
        actualizarEstadoFlujoUI(null);
        return;
    }

    $.getJSON('adq_configuracion.php', { ajax_load_workflow: true, id: id }, function(res) {
        if (res.success) {
            workflowId = res.flujo.id;
            workflowFamilyId = res.flujo.familia_cod || id;
            $('#flowName').val(res.flujo.nombre);
            $('#flowDesc').val(res.flujo.descripcion);
            actualizarEstadoFlujoUI(res.flujo);
            
            // Limpiar lienzo
            $('.wf-node').remove();
            nodes = [];
            connections = [];

            // Recrear nodos
            res.nodos.forEach(function(n) {
                const node = createNode(n.tipo, n.nombre, n.x, n.y, n.id, {
                    descripcion: wfNodeDescFromApi(n),
                    dep_cod: n.dep_cod || '',
                    per_cod: n.per_cod || '',
                    sla: n.sla || '',
                    com_obl: (parseInt(n.com_obl, 10) === 1),
                    adj_obl: (parseInt(n.adj_obl, 10) === 1),
                    cot_edit: (parseInt(n.cot_edit, 10) === 1),
                    not_wa: (parseInt(n.not_wa, 10) === 1),
                    not_em: (parseInt(n.not_em, 10) === 1),
                    not_asunto: n.not_asunto || '',
                    not_texto: n.not_texto || '',
                    usu_asig: n.usu_asig || 'TODOS',
                    asig_nombres: n.asig_nombres || ''
                });
                refreshNodeView(node);
            });

            // Recrear conexiones (mapear IDs de BD al prefijo del lienzo)
            connections = res.conexiones.map(function(c) {
                return {
                    origen: wfNodeId(c.origen),
                    destino: wfNodeId(c.destino),
                    accion: c.accion,
                    condicion: c.condicion
                };
            });
            redrawConnections();
            updateCanvasBounds();
        } else {
            wfNotify('danger', 'Error al cargar flujo: ' + (res.message || 'Error desconocido'));
        }
    }).fail(function() {
        wfNotify('danger', 'Error de red al cargar el flujo.');
    });
}

function abrirModalNuevoFlujo() {
    pendingSaveAfterModal = false;
    $('#modalFlowName').val('');
    $('#modalFlowDesc').val('');
    $('#modalWorkflowDataLabel').text('Crear Nuevo Flujo Modelo');
    $('#modalWorkflowData').modal('show');
}

function aceptarDatosFlujo() {
    const nombre = $('#modalFlowName').val().trim();
    if (!nombre) {
        wfNotify('danger', 'Por favor ingrese el nombre del flujo.');
        return;
    }

    const esGuardadoPendiente = pendingSaveAfterModal;
    pendingSaveAfterModal = false;

    $('#flowName').val(nombre);
    $('#flowDesc').val($('#modalFlowDesc').val());
    $('#modalWorkflowData').modal('hide');

    actualizarEstadoFlujoUI({
        nombre: nombre,
        es_borrador: true,
        instancias_activas: 0
    });

    if (esGuardadoPendiente) {
        guardarFlujo();
        return;
    }

    workflowId = null;
    nodes = [];
    connections = [];
    $('.wf-node').remove();
    redrawConnections();
    updateCanvasBounds();
    $('#selWorkflow').val('');
}

function wfFlowOptionLabel(nombre, version, esBorrador) {
    let label = nombre + ' (v' + (version || 1) + ')';
    if (esBorrador) {
        label += ' [Borrador]';
    }
    return label;
}

function guardarFlujo() {
    const nombre = $('#flowName').val().trim();
    if (!nombre) {
        pendingSaveAfterModal = true;
        $('#modalFlowName').val('');
        $('#modalFlowDesc').val($('#flowDesc').val());
        $('#modalWorkflowDataLabel').text('Definir Datos del Flujo para Guardar');
        $('#modalWorkflowData').modal('show');
        return;
    }

    if (!nodes.length) {
        wfNotify('danger', 'El flujo debe tener al menos un nodo antes de guardar.');
        return;
    }

    const payload = {
        id: workflowId,
        nombre: nombre,
        descripcion: $('#flowDesc').val() || '',
        nodos: nodes,
        conexiones: connections
    };

    fetch('adq_configuracion.php?ajax_save_workflow=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(function(r) {
        return r.text().then(function(text) {
            let res;
            try {
                res = JSON.parse(text);
            } catch (e) {
                throw new Error('Respuesta invalida del servidor.');
            }
            if (!r.ok && (!res || !res.message)) {
                throw new Error('Error HTTP ' + r.status);
            }
            return res;
        });
    })
    .then(function(res) {
        if (res.success) {
            const eraNuevo = !payload.id;
            workflowId = res.id;
            workflowFamilyId = res.familia_cod || workflowFamilyId;
            let msg = 'Borrador guardado correctamente.';
            if (res.instancias_activas > 0) {
                msg += ' Hay ' + res.instancias_activas + ' solicitud(es) en curso que seguiran con la version publicada hasta que publique.';
            }
            wfNotify('success', msg, function() {
                if (eraNuevo) {
                    const famId = res.familia_cod || res.id;
                    const nombre = $('#flowName').val();
                    if ($('#selWorkflow option[value="' + famId + '"]').length === 0) {
                        $('#selWorkflow').append('<option value="' + famId + '">' + wfFlowOptionLabel(nombre, res.version || 1, true) + '</option>');
                    } else {
                        $('#selWorkflow option[value="' + famId + '"]').text(wfFlowOptionLabel(nombre, res.version || 1, true));
                    }
                    $('#selWorkflow').val(String(famId));
                }
                cargarFlujo();
            });
        } else {
            wfNotify('danger', 'Error al guardar: ' + (res.message || 'Error desconocido'));
        }
    })
    .catch(function(err) {
        wfNotify('danger', 'No se pudo guardar el flujo: ' + (err.message || 'Error de red'));
    });
}

function publicarFlujo() {
    const nombre = $('#flowName').val().trim();
    if (!nombre || !nodes.length) {
        wfNotify('danger', 'Guarde un borrador con al menos un nodo antes de publicar.');
        return;
    }

    const publicarAhora = function(wfmId) {
        const activas = workflowMeta && workflowMeta.instancias_activas ? workflowMeta.instancias_activas : 0;
        let confirmMsg = 'Publicar esta version del flujo? Las solicitudes nuevas usaran esta version.';
        if (activas > 0) {
            confirmMsg += '\n\n' + activas + ' solicitud(es) en curso seguiran con la version anterior hasta finalizar.';
        }
        if (!confirm(confirmMsg)) {
            return;
        }

        fetch('adq_configuracion.php?ajax_publish_workflow=1', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: wfmId })
        })
        .then(function(r) {
            return r.text().then(function(text) {
                let res;
                try { res = JSON.parse(text); } catch (e) { throw new Error('Respuesta invalida del servidor.'); }
                return res;
            });
        })
        .then(function(res) {
            if (res.success) {
                wfNotify('success', res.message || 'Flujo publicado.', function() {
                    const famId = res.familia_cod || workflowFamilyId;
                    const label = wfFlowOptionLabel($('#flowName').val(), res.version || '', false);
                    if (famId && $('#selWorkflow option[value="' + famId + '"]').length) {
                        $('#selWorkflow option[value="' + famId + '"]').text(label);
                        $('#selWorkflow').val(String(famId));
                    }
                    cargarFlujo();
                });
            } else {
                wfNotify('danger', 'Error al publicar: ' + (res.message || 'Error desconocido'));
            }
        })
        .catch(function(err) {
            wfNotify('danger', 'No se pudo publicar: ' + (err.message || 'Error de red'));
        });
    };

    if (!workflowId) {
        guardarFlujoInterno(function(res) {
            if (res && res.success) {
                publicarAhora(res.id);
            }
        });
        return;
    }

    if (workflowMeta && !workflowMeta.es_borrador) {
        wfNotify('info', 'Primero guarde los cambios como borrador y luego publique.');
        return;
    }

    publicarAhora(workflowId);
}

function guardarFlujoInterno(onDone) {
    const nombre = $('#flowName').val().trim();
    const payload = {
        id: workflowId,
        nombre: nombre,
        descripcion: $('#flowDesc').val() || '',
        nodos: nodes,
        conexiones: connections
    };

    return fetch('adq_configuracion.php?ajax_save_workflow=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(function(r) {
        return r.text().then(function(text) {
            let res;
            try { res = JSON.parse(text); } catch (e) { throw new Error('Respuesta invalida del servidor.'); }
            if (!r.ok && (!res || !res.message)) {
                throw new Error('Error HTTP ' + r.status);
            }
            return res;
        });
    })
    .then(function(res) {
        if (res.success) {
            workflowId = res.id;
            workflowFamilyId = res.familia_cod || workflowFamilyId;
        }
        if (typeof onDone === 'function') {
            onDone(res);
        }
        return res;
    });
}
