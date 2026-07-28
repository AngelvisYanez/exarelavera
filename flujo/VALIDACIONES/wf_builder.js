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
let dragMoved = false;
let dragSelectNodeId = null;

function highlightActiveNode(nodeId) {
    $('.wf-node').removeClass('is-selected');
    if (nodeId === undefined || nodeId === null || nodeId === '') {
        return;
    }
    const $el = wfNodeEl(nodeId);
    if ($el && $el.length) {
        $el.addClass('is-selected');
    }
}

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
        dragMoved = false;
        draggedElement = $(this);
        dragSelectNodeId = draggedElement.attr('id');
        highlightActiveNode(dragSelectNodeId);
        wfHideNodeTip();
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
        const prevLeft = parseFloat(draggedElement.css('left')) || 0;
        const prevTop = parseFloat(draggedElement.css('top')) || 0;
        if (Math.abs(x - prevLeft) > 3 || Math.abs(y - prevTop) > 3) {
            dragMoved = true;
        }
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
        if (!isDraggingNode) {
            return;
        }
        const nodeId = dragSelectNodeId;
        const wasClick = !dragMoved && !!nodeId;
        isDraggingNode = false;
        draggedElement = null;
        dragSelectNodeId = null;
        updateCanvasBounds();
        if (wasClick) {
            openNodeProperties(nodeId);
        } else if (nodeId) {
            highlightActiveNode(nodeId);
        }
    });
}
let workflowBuilderReady = false;
let pendingSaveAfterModal = false;
let pendingDuplicateFlowId = null;

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
        h: $node.outerHeight() || 150
    };
}

function wfPortCanvasPoint(nodeId, side) {
    const $node = wfNodeEl(nodeId);
    if (!$node.length) {
        return null;
    }
    const x = parseFloat($node.css('left')) || 0;
    const y = parseFloat($node.css('top')) || 0;
    const size = wfNodeSize($node);
    const cx = x + (size.w / 2);
    const cy = y + (size.h / 2);
    const s = wfNormalizePortSide(side);

    if (s === 'right' || s === 'out') {
        return { x: x + size.w + 6, y: cy, side: 'right' };
    }
    if (s === 'top') {
        return { x: cx, y: y - 6, side: 'top' };
    }
    if (s === 'bottom') {
        return { x: cx, y: y + size.h + 6, side: 'bottom' };
    }
    // left / in
    return { x: x - 6, y: cy, side: 'left' };
}

function wfNormalizePortSide(side) {
    const s = String(side || '').toLowerCase();
    if (s === 'out' || s === 'right') return 'right';
    if (s === 'in' || s === 'left') return 'left';
    if (s === 'top' || s === 'bottom') return s;
    return 'right';
}

function wfNodeCenter(nodeId) {
    const $node = wfNodeEl(nodeId);
    if (!$node.length) {
        return null;
    }
    const x = parseFloat($node.css('left')) || 0;
    const y = parseFloat($node.css('top')) || 0;
    const size = wfNodeSize($node);
    return { x: x + size.w / 2, y: y + size.h / 2 };
}

/** Elige lados de salida/entrada segun la posicion relativa de los nodos. */
function wfBestPortSides(origenId, destinoId) {
    const c1 = wfNodeCenter(origenId);
    const c2 = wfNodeCenter(destinoId);
    if (!c1 || !c2) {
        return { from: 'right', to: 'left' };
    }
    const dx = c2.x - c1.x;
    const dy = c2.y - c1.y;
    if (Math.abs(dx) >= Math.abs(dy)) {
        return dx >= 0
            ? { from: 'right', to: 'left' }
            : { from: 'left', to: 'right' };
    }
    return dy >= 0
        ? { from: 'bottom', to: 'top' }
        : { from: 'top', to: 'bottom' };
}

function wfPortControlOffset(side, amount) {
    const s = wfNormalizePortSide(side);
    if (s === 'left') return { x: -amount, y: 0 };
    if (s === 'right') return { x: amount, y: 0 };
    if (s === 'top') return { x: 0, y: -amount };
    return { x: 0, y: amount };
}

function wfBezierPath(p1, p2, sideFrom, sideTo) {
    const dist = Math.hypot(p2.x - p1.x, p2.y - p1.y);
    const amount = Math.max(40, Math.min(120, dist * 0.45));
    const o1 = wfPortControlOffset(sideFrom || p1.side || 'right', amount);
    const o2 = wfPortControlOffset(sideTo || p2.side || 'left', amount);
    const c1x = p1.x + o1.x;
    const c1y = p1.y + o1.y;
    const c2x = p2.x + o2.x;
    const c2y = p2.y + o2.y;
    return 'M ' + p1.x + ' ' + p1.y + ' C ' + c1x + ' ' + c1y + ', ' + c2x + ' ' + c2y + ', ' + p2.x + ' ' + p2.y;
}

function wfConnectionPoints(con) {
    let sideFrom = con.side_ori ? wfNormalizePortSide(con.side_ori) : null;
    let sideTo = con.side_des ? wfNormalizePortSide(con.side_des) : null;
    if (!sideFrom || !sideTo) {
        const best = wfBestPortSides(con.origen, con.destino);
        sideFrom = sideFrom || best.from;
        sideTo = sideTo || best.to;
        // Persistir en memoria para que no cambie al mover nodos o guardar
        con.side_ori = sideFrom;
        con.side_des = sideTo;
    }
    const p1 = wfPortCanvasPoint(con.origen, sideFrom);
    const p2 = wfPortCanvasPoint(con.destino, sideTo);
    return { p1: p1, p2: p2, sideFrom: sideFrom, sideTo: sideTo };
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
    $(document).off('change.wfCondDefault', '#modalConnectionCondition #condDefault')
        .on('change.wfCondDefault', '#modalConnectionCondition #condDefault', function() {
            wfModalCondicionCampo('#condFields').toggle(!this.checked);
        });
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
        createNode(type, wfNodeTipoLabel(type), pt.x, pt.y);
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

const WF_NODE_TIPO_AYUDA = {
    INICIO: 'Punto de partida del flujo. Configure quién puede crear solicitudes, asigne departamento/usuarios y active notificaciones WhatsApp/correo al poner el requerimiento en ejecución.',
    APROBACION: 'Etapa de revisión y aprobación. El responsable puede aprobar, observar, devolver o rechazar la solicitud.',
    DECISION: 'Bifurca el flujo según condiciones o reglas definidas entre caminos posibles.',
    RECEPCION: 'Etapa para confirmar la recepción de bienes o servicios solicitados.',
    FACTURA: 'Permite vincular facturas de compra del sistema a la solicitud.',
    NOTIFICACION: 'Envía notificaciones (correo o WhatsApp) según la configuración del nodo.',
    TAREA: 'Asigna una tarea pendiente que debe completarse para continuar el flujo.',
    AVANCE: 'Permite cargar documentos o facturas de avance durante el proceso.',
    FISCALIZACION: 'Etapa de fiscalización: permite aprobar con comentario/justificación, vincular facturas y cargar varios archivos de sustento.',
    FIN: 'Cierra el flujo. Genera o firma el expediente final de la solicitud.'
};

function wfNodeTipoAyuda(tipo) {
    const key = String(tipo || '').toUpperCase();
    return WF_NODE_TIPO_AYUDA[key] || 'Nodo del flujo de adquisiciones.';
}

function wfEnsureNodeTipEl() {
    let $tip = $('#wfNodeTip');
    if (!$tip.length) {
        $tip = $('<div id="wfNodeTip" class="wf-node-tip" role="tooltip"></div>').appendTo('body');
    }
    return $tip;
}

function wfShowNodeTip(text, clientX, clientY) {
    const msg = $.trim(String(text || ''));
    if (!msg) {
        wfHideNodeTip();
        return;
    }
    const $tip = wfEnsureNodeTipEl();
    $tip.text(msg).addClass('is-visible');
    const tipW = $tip.outerWidth() || 240;
    const tipH = $tip.outerHeight() || 40;
    let left = clientX + 14;
    let top = clientY + 16;
    const maxL = $(window).width() - tipW - 10;
    const maxT = $(window).height() - tipH - 10;
    if (left > maxL) left = Math.max(8, clientX - tipW - 14);
    if (top > maxT) top = Math.max(8, clientY - tipH - 12);
    $tip.css({ left: left + 'px', top: top + 'px' });
}

function wfHideNodeTip() {
    $('#wfNodeTip').removeClass('is-visible').text('');
}

function wfNodeTooltipText(node) {
    if (!node) {
        return '';
    }
    const ayuda = wfNodeTipoAyuda(node.tipo);
    const desc = node.descripcion ? String(node.descripcion).trim() : '';
    if (desc) {
        return ayuda + ' | ' + desc;
    }
    return ayuda;
}

function setupToolbox() {
    $('.toolbox-item').each(function() {
        const tipo = $(this).data('type');
        const ayuda = wfNodeTipoAyuda(tipo);
        $(this).attr('data-wf-tip', ayuda).removeAttr('title');
    });

    $('.toolbox-item').off('dragstart.wfTip mouseenter.wfTip mousemove.wfTip mouseleave.wfTip')
        .on('dragstart.wfTip', function(e) {
            wfHideNodeTip();
            e.originalEvent.dataTransfer.setData('node-type', $(this).data('type'));
        })
        .on('mouseenter.wfTip mousemove.wfTip', function(e) {
            wfShowNodeTip($(this).attr('data-wf-tip') || wfNodeTipoAyuda($(this).data('type')), e.clientX, e.clientY);
        })
        .on('mouseleave.wfTip', function() {
            wfHideNodeTip();
        });

    // Tooltip al pasar el mouse sobre nodos del lienzo
    const $canvas = $('#canvas');
    $canvas.off('mouseenter.wfTip mousemove.wfTip mouseleave.wfTip', '.wf-node')
        .on('mouseenter.wfTip mousemove.wfTip', '.wf-node', function(e) {
            if (isDraggingNode || drawingConnection) {
                wfHideNodeTip();
                return;
            }
            const nodeId = this.id;
            const node = nodes.find(function(n) { return sameId(n.id, nodeId); });
            const tip = node ? wfNodeTooltipText(node) : ($(this).attr('data-wf-tip') || '');
            wfShowNodeTip(tip, e.clientX, e.clientY);
        })
        .on('mouseleave.wfTip', '.wf-node', function() {
            wfHideNodeTip();
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
    return ['APROBACION', 'RECEPCION', 'FACTURA', 'TAREA', 'AVANCE', 'FISCALIZACION', 'FIN'].indexOf(tipo) >= 0;
}

function wfNodeTipoLabel(tipo) {
    if (tipo === 'AVANCE') {
        return 'Avance/Facturas';
    }
    return tipo || '';
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
        cot_sel: false,
        cre_sol: (type === 'INICIO'),
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
    if (tipo === 'INICIO') {
        return 'bi-play-fill text-success';
    }
    if (tipo === 'FIN') {
        return 'bi-stop-fill text-danger';
    }
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
    if (tipo === 'FISCALIZACION') {
        return 'bi-shield-check text-secondary';
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
    if (!node) {
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
    const tipText = wfNodeTooltipText(node);
    $el.attr('data-wf-tip', tipText).removeAttr('title');

    if (isNodoTerminal(node.tipo)) {
        const icon = node.tipo === 'INICIO' ? 'bi-play-fill text-success' : 'bi-stop-fill text-danger';
        $el.find('.wf-node-header span').html(
            '<i class="bi ' + icon + ' wf-node-type-icon"></i>' +
            '<span class="wf-node-terminal-label">' + wfEscHtml(node.nombre) + '</span>'
        );
        if (node.tipo === 'INICIO') {
            const creOk = node.cre_sol !== false && node.cre_sol !== 0 && node.cre_sol !== '0';
            $el.find('.wf-node-body').html(
                '<span class="wf-node-tipo-label">INICIO</span>' +
                '<span class="wf-node-desc">' + (creOk ? 'Puede modificar solicitud' : 'Modificar solicitud desactivado') + '</span>' +
                wfNodeAsigHtml(node)
            );
        } else {
            const desc = node.descripcion ? String(node.descripcion).trim() : '';
            $el.find('.wf-node-body').html(
                '<span class="wf-node-tipo-label">FIN</span>' +
                '<span class="wf-node-desc">' + (desc ? wfEscHtml(desc) : 'Cierre del flujo') + '</span>' +
                wfNodeAsigHtml(node)
            );
        }
        $el.find('.node-port').show();
        return;
    }

    const desc = node.descripcion ? String(node.descripcion).trim() : '';
    const iconClass = wfNodeHeaderIcon(node.tipo);
    $el.find('.wf-node-header span').html(
        '<i class="bi ' + iconClass + ' wf-node-type-icon"></i>' +
        '<span class="wf-node-title-label">' + wfEscHtml(node.nombre) + '</span>'
    );
    $el.find('.wf-node-body').html(
        '<span class="wf-node-tipo-label">' + wfEscHtml(wfNodeTipoLabel(node.tipo)) + '</span>' +
        '<span class="wf-node-desc">' + (desc ? wfEscHtml(desc) : 'Sin descripción') + '</span>' +
        wfNodeAsigHtml(node)
    );
    $el.find('.node-port').show();
}

function renderNode(node) {
    const $nodeEl = $(`
        <div class="wf-node node-${node.tipo}" id="${node.id}" style="left: ${node.x}px; top: ${node.y}px;">
            <div class="wf-node-header">
                <span></span>
                <button type="button" class="btn btn-xs p-0 border-0 wf-node-delete-btn" onclick="deleteNode('${node.id}', event)"><i class="bi bi-x-lg text-danger"></i></button>
            </div>
            <div class="wf-node-body"></div>
            <div class="node-port node-port-left" data-node-id="${node.id}" data-side="left"></div>
            <div class="node-port node-port-right" data-node-id="${node.id}" data-side="right"></div>
            <div class="node-port node-port-top" data-node-id="${node.id}" data-side="top"></div>
            <div class="node-port node-port-bottom" data-node-id="${node.id}" data-side="bottom"></div>
        </div>
    `);

    // Configurar evento de doble clic para abrir propiedades
    $nodeEl.on('dblclick', function() {
        openNodeProperties(node.id);
    });

    // Cualquier punto puede iniciar o recibir la conexion (salvo reglas de INICIO/FIN)
    $nodeEl.find('.node-port').on('mousedown', function(e) {
        if (node.tipo === 'FIN') {
            return;
        }
        drawingConnection = true;
        connStartPort = $(this);
        e.stopPropagation();
        e.preventDefault();
    });

    $nodeEl.find('.node-port').on('mouseup', function(e) {
        if (drawingConnection && connStartPort) {
            if (node.tipo === 'INICIO') {
                return;
            }
            const startNodeId = connStartPort.data('node-id');
            const endNodeId = $(this).data('node-id');
            const sideOri = connStartPort.data('side') || 'right';
            const sideDes = $(this).data('side') || 'left';
            if (startNodeId !== endNodeId) {
                createConnection(startNodeId, endNodeId, sideOri, sideDes);
            }
        }
    });

    wfEnsureCanvasSurface().append($nodeEl);
    refreshNodeView(node);
    updateCanvasBounds();
}

/**
 * Actualiza el combo de departamentos del nodo sin recargar la pagina.
 * Se usa al volver al diseñador o al abrir propiedades tras crear un departamento.
 */
function refreshNodeDepartments(done, preferredValue) {
    const $sel = $('#nodeDep');
    if (!$sel.length) {
        if (typeof done === 'function') {
            done();
        }
        return;
    }
    const selected = (preferredValue !== undefined && preferredValue !== null && preferredValue !== '')
        ? String(preferredValue)
        : String($sel.val() || '');

    $.getJSON('adq_configuracion.php', { ajax_get_departamentos_disenador: 1 }, function(res) {
        if (!res || !res.success) {
            if (typeof done === 'function') {
                done();
            }
            return;
        }
        $sel.empty().append($('<option></option>').val('').text('[Cualquiera/Solicitante]'));
        const deps = res.departamentos || [];
        for (let i = 0; i < deps.length; i++) {
            const dep = deps[i];
            const cant = parseInt(dep.Cant_Usuarios, 10) || 0;
            const label = String(dep.Dep_Des || '')
                + (cant > 0
                    ? (' (' + cant + ' usuario' + (cant === 1 ? '' : 's') + ')')
                    : ' (sin usuarios WF)');
            $sel.append($('<option></option>').val(String(dep.Dep_Cod)).text(label));
        }
        if (selected) {
            $sel.val(selected);
            if ($sel.val() !== selected) {
                // Si el valor ya no existe (inactivo), dejar en blanco
                $sel.val('');
            }
        }
        if (typeof done === 'function') {
            done();
        }
    }).fail(function() {
        if (typeof done === 'function') {
            done();
        }
    });
}

function openNodeProperties(id) {
    // Persistir el nodo actual antes de cambiar (evita que Cot_Sel/Cot_Edit se "peguen" al siguiente).
    if (activeNode) {
        syncFormToActiveNode();
    }
    activeNode = nodes.find(item => sameId(item.id, id));
    if (!activeNode) return;
    activeConnection = null;
    highlightActiveNode(activeNode.id);
    redrawConnections();

    refreshNodeDepartments(function() {
        fillNodePropertiesForm();
    }, activeNode.dep_cod);
}

/** Copia el formulario de propiedades al nodo activo en memoria (por nodo, sin heredar). */
function syncFormToActiveNode() {
    if (!activeNode) {
        return;
    }
    const $drawer = $('.properties-drawer').first();
    const readCheck = function(id) {
        const $el = $drawer.find('[id="' + id + '"]').first();
        if ($el.length) {
            return $el.is(':checked');
        }
        return $('[id="' + id + '"]').first().is(':checked');
    };
    const readVal = function(id) {
        const $el = $drawer.find('[id="' + id + '"]').first();
        if ($el.length) {
            return $el.val();
        }
        return $('[id="' + id + '"]').first().val();
    };
    activeNode.nombre = readVal('nodeName');
    activeNode.descripcion = readVal('nodeDesc');
    activeNode.dep_cod = readVal('nodeDep');
    activeNode.per_cod = readVal('nodePer');
    activeNode.sla = readVal('nodeSla');
    activeNode.com_obl = readCheck('nodeComObl');
    activeNode.adj_obl = readCheck('nodeAdjObl');
    if (activeNode.tipo === 'FIN') {
        activeNode.cot_edit = false;
        activeNode.cot_sel = false;
    } else {
        activeNode.cot_edit = readCheck('nodeCotEdit');
        activeNode.cot_sel = readCheck('nodeCotSel');
    }
    activeNode.cre_sol = readCheck('nodeCreSol');
    activeNode.not_wa = readCheck('nodeNotWa');
    activeNode.not_em = readCheck('nodeNotEm');
    activeNode.not_asunto = readVal('nodeNotAsunto');
    activeNode.not_texto = readVal('nodeNotTexto');
}

function fillNodePropertiesForm() {
    if (!activeNode) return;

    // Evitar que al rellenar el form se escriba el valor viejo en otro nodo.
    $('[id="nodeName"], [id="nodeDesc"], [id="nodeDep"], [id="nodePer"], [id="nodeSla"], [id="nodeComObl"], [id="nodeAdjObl"], [id="nodeCotEdit"], [id="nodeCotSel"], [id="nodeCreSol"], [id="nodeNotWa"], [id="nodeNotEm"], [id="nodeNotAsunto"], [id="nodeNotTexto"]').off('change.adqNode input.adqNode');

    $('[id="nodeId"]').val(activeNode.id);
    $('[id="nodeName"]').val(activeNode.nombre);
    $('[id="nodeDesc"]').val(activeNode.descripcion || '');
    $('[id="nodeDep"]').val(activeNode.dep_cod);
    $('[id="nodePer"]').val(activeNode.per_cod);
    $('[id="nodeSla"]').val(activeNode.sla);
    $('[id="nodeComObl"]').prop('checked', !!activeNode.com_obl);
    $('[id="nodeAdjObl"]').prop('checked', !!activeNode.adj_obl);
    // Flags independientes por nodo (no compartir entre Aprobacion/Tarea).
    $('[id="nodeCotEdit"]').prop('checked', !!activeNode.cot_edit);
    $('[id="nodeCotSel"]').prop('checked', !!activeNode.cot_sel);
    $('[id="nodeNotWa"]').prop('checked', !!activeNode.not_wa);
    $('[id="nodeNotEm"]').prop('checked', !!activeNode.not_em);
    $('[id="nodeNotAsunto"]').val(activeNode.not_asunto || '');
    $('[id="nodeNotTexto"]').val(activeNode.not_texto || '');
    $('.node-not-wa-label').text('WhatsApp');
    $('.node-not-em-label').text('Correo electrónico');

    // Ocultar/Mostrar campos según tipo de nodo
    $('.sec-inicio-crear').hide();
    if (activeNode.tipo === 'INICIO') {
        $('.sec-checks').show();
        $('.sec-cot-edit').show();
        $('.sec-sla, .sec-responsabilidad').show();
        $('.sec-inicio-crear').show();
        $('.sec-notificaciones').show();
        $('#lblNodeNotTitle').text('Al poner en ejecución el requerimiento, notificar a los usuarios de este nodo');
        $('#lblNodeNotHelp').text('Configure los avisos que se enviarán cuando una nueva solicitud quede en el nodo Inicio para ser atendida.');
        $('[id="nodeCreSol"]').prop('checked', activeNode.cre_sol !== false && activeNode.cre_sol !== 0 && activeNode.cre_sol !== '0');
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
    } else if (activeNode.tipo === 'DECISION') {
        $('.sec-responsabilidad, .sec-sla, .sec-checks, .sec-notificaciones').hide();
    } else if (activeNode.tipo === 'NOTIFICACION') {
        $('#lblNodeNotTitle').text('Al completar esta etapa, notificar al siguiente responsable');
        $('#lblNodeNotHelp').text('Se envía WhatsApp o correo a quien debe atender la siguiente tarea. En la primera etapa humana, también aplica al enviar la solicitud.');
        $('.sec-responsabilidad, .sec-checks, .sec-notificaciones').hide();
        $('.sec-sla').show();
    } else {
        $('.sec-responsabilidad, .sec-sla, .sec-checks').show();
        if (activeNode.tipo === 'FIN') {
            $('#lblNodeNotTitle').text('Notificación final del esquema');
            $('#lblNodeNotHelp').text('Al marcar cualquiera de estas opciones, se enviará por correo el expediente final y el comentario de cierre a todos los usuarios asignados al esquema.');
            $('.node-not-wa-label, .node-not-em-label').text('Notificar a todos los usuarios');
            $('.sec-cot-edit').hide();
            $('[id="nodeCotEdit"]').prop('checked', false);
            $('[id="nodeCotSel"]').prop('checked', false);
            activeNode.cot_edit = false;
            activeNode.cot_sel = false;
        } else {
            $('#lblNodeNotTitle').text('Al completar esta etapa, notificar al siguiente responsable');
            $('#lblNodeNotHelp').text('Se envía WhatsApp o correo a quien debe atender la siguiente tarea. En la primera etapa humana, también aplica al enviar la solicitud.');
            $('.sec-cot-edit').show();
        }
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

    $('.flujoProps, [id="flujoProps"]').hide();
    $('[id="nodeProps"]').show();
    $('.properties-drawer').first().addClass('open');

    // Escuchar cambios en los inputs para actualizar el nodo en vivo
    // Usar [id=...] porque el HTML del builder tiene IDs duplicados.
    $('[id="nodeName"], [id="nodeDesc"], [id="nodeDep"], [id="nodePer"], [id="nodeSla"], [id="nodeComObl"], [id="nodeAdjObl"], [id="nodeCotEdit"], [id="nodeCotSel"], [id="nodeCreSol"], [id="nodeNotWa"], [id="nodeNotEm"], [id="nodeNotAsunto"], [id="nodeNotTexto"]').off('change.adqNode input.adqNode').on('change.adqNode input.adqNode', function() {
        if (!activeNode) return;
        const $t = $(this);
        const id = $t.attr('id');
        // Sincronizar solo clones del mismo control (drawers duplicados), no otros nodos.
        if (id) {
            $('[id="' + id + '"]').not($t).each(function() {
                if ($t.is(':checkbox') || $t.is(':radio')) {
                    $(this).prop('checked', $t.is(':checked'));
                } else {
                    $(this).val($t.val());
                }
            });
        }
        syncFormToActiveNode();
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
    if (activeNode) {
        syncFormToActiveNode();
    }
    $('#propertiesDrawer').removeClass('open');
    $('.properties-drawer').removeClass('open');
    $('#nodeProps').hide();
    $('[id="nodeProps"]').hide();
    $('#flujoProps').show();
    $('.flujoProps, [id="flujoProps"]').show();
    activeNode = null;
    activeConnection = null;
    highlightActiveNode(null);
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

function createConnection(origenId, destinoId, sideOri, sideDes) {
    // Validar si ya existe
    const duplicada = connections.find(c => sameId(c.origen, origenId) && sameId(c.destino, destinoId));
    if (duplicada) return;

    let accion = 'APROBAR';
    const nodoOri = nodes.find(n => sameId(n.id, origenId));
    if (nodoOri && nodoOri.tipo === 'DECISION') {
        accion = 'CONDICIONAL';
    }

    const sides = (sideOri && sideDes)
        ? { from: wfNormalizePortSide(sideOri), to: wfNormalizePortSide(sideDes) }
        : wfBestPortSides(origenId, destinoId);

    const nuevaConexion = {
        origen: origenId,
        destino: destinoId,
        accion: accion,
        condicion: null,
        comentario: '',
        side_ori: sides.from,
        side_des: sides.to
    };
    connections.push(nuevaConexion);

    redrawConnections();
    if (nodoOri && nodoOri.tipo === 'DECISION') {
        openConnectionCondition(nuevaConexion);
    }
}

function drawTempCable(toX, toY) {
    $('#tempCable').remove();
    if (!connStartPort) {
        return;
    }
    const startNodeId = connStartPort.data('node-id');
    const sideFrom = connStartPort.data('side') || 'right';
    const p1 = wfPortCanvasPoint(startNodeId, sideFrom);
    if (!p1) {
        return;
    }

    const pt = wfPointInCanvas(toX, toY);
    const pathString = wfBezierPath(p1, { x: pt.x, y: pt.y, side: 'left' }, sideFrom, 'left');
    
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

function deleteConnection(origenId, destinoId, e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    connections = connections.filter(function(c) {
        return !(sameId(c.origen, origenId) && sameId(c.destino, destinoId));
    });
    if (activeConnection && sameId(activeConnection.origen, origenId) && sameId(activeConnection.destino, destinoId)) {
        activeConnection = null;
    }
    redrawConnections();
}

function wfConnectionMidpoint(p1, p2) {
    return {
        x: (p1.x + p2.x) / 2,
        y: (p1.y + p2.y) / 2
    };
}

function wfGetNode(nodeId) {
    return nodes.find(function(n) { return sameId(n.id, nodeId); }) || null;
}

function wfIsDecisionConnection(con) {
    const ori = con ? wfGetNode(con.origen) : null;
    return !!ori && ori.tipo === 'DECISION';
}

function wfFindConnection(origenId, destinoId) {
    return connections.find(function(c) {
        return sameId(c.origen, origenId) && sameId(c.destino, destinoId);
    }) || null;
}

function wfCloneCondicion(cond) {
    if (!cond || typeof cond !== 'object') {
        return null;
    }
    try {
        return JSON.parse(JSON.stringify(cond));
    } catch (e) {
        return null;
    }
}

function wfLeerComentarioConexion(con) {
    if (!con) {
        return '';
    }
    if (con.comentario != null && String(con.comentario).trim() !== '') {
        return String(con.comentario).trim();
    }
    if (con.condicion && con.condicion.comentario != null) {
        return String(con.condicion.comentario).trim();
    }
    return '';
}

function wfIsDecisionDefaultConnection(con) {
    if (!con) {
        return false;
    }
    if (con.accion === 'APROBAR') {
        return !(con.condicion && con.condicion.campo);
    }
    return !!(con.condicion && !con.condicion.campo);
}

function wfCondicionTexto(con) {
    if (!wfIsDecisionConnection(con)) {
        return '';
    }
    const comentario = wfLeerComentarioConexion(con);
    if (wfIsDecisionDefaultConnection(con)) {
        return comentario || 'Por defecto';
    }
    if (con.condicion && con.condicion.campo) {
        if (comentario) {
            return comentario;
        }
        return String(con.condicion.campo) + ' ' +
            String(con.condicion.operador || '=') + ' ' +
            String(con.condicion.valor == null ? '' : con.condicion.valor);
    }
    return comentario || 'Configurar condición';
}

function wfModalCondicionCampo(selector) {
    return $('#modalConnectionCondition').find(selector);
}

function openConnectionCondition(con) {
    if (!wfIsDecisionConnection(con)) {
        return;
    }
    activeConnection = con;
    wfModalCondicionCampo('#condOrigen').val(con.origen);
    wfModalCondicionCampo('#condDestino').val(con.destino);
    const esDefault = wfIsDecisionDefaultConnection(con);
    wfModalCondicionCampo('#condDefault').prop('checked', esDefault);
    wfModalCondicionCampo('#condFields').toggle(!esDefault);
    const cond = con.condicion || {};
    // Comentario propio de ESTA flecha/rama (no se comparte con otras)
    wfModalCondicionCampo('#condComentario').val(wfLeerComentarioConexion(con));
    wfModalCondicionCampo('#condCampo').val(cond.campo || 'Sol_Val_Est');
    wfModalCondicionCampo('#condOperador').val(cond.operador || '>');
    wfModalCondicionCampo('#condValor').val(cond.valor == null ? '' : cond.valor);
    $('#modalConnectionCondition').modal('show');
}

function guardarCondicionConexion() {
    const origenId = wfModalCondicionCampo('#condOrigen').val();
    const destinoId = wfModalCondicionCampo('#condDestino').val();
    let con = activeConnection;
    if (!con || !sameId(con.origen, origenId) || !sameId(con.destino, destinoId)) {
        con = wfFindConnection(origenId, destinoId);
    }
    if (!con) {
        alert('No se encontró la conexión.');
        return;
    }
    const comentario = $.trim(wfModalCondicionCampo('#condComentario').val() || '');
    // Siempre propio de esta conexión
    con.comentario = comentario;

    if (wfModalCondicionCampo('#condDefault').is(':checked')) {
        con.accion = 'APROBAR';
        con.condicion = comentario !== '' ? { comentario: comentario } : null;
    } else {
        const campo = wfModalCondicionCampo('#condCampo').val();
        const operador = wfModalCondicionCampo('#condOperador').val();
        const valor = $.trim(wfModalCondicionCampo('#condValor').val() || '');
        if (!campo || valor === '') {
            alert('Ingrese el campo, operador y valor de la condición.');
            return;
        }
        const nuevaCond = {
            campo: campo,
            operador: operador,
            valor: valor
        };
        if (comentario !== '') {
            nuevaCond.comentario = comentario;
        }
        con.condicion = nuevaCond;
    }
    $('#modalConnectionCondition').modal('hide');
    activeConnection = con;
    redrawConnections();
}

function limpiarCondicionConexion() {
    const origenId = wfModalCondicionCampo('#condOrigen').val();
    const destinoId = wfModalCondicionCampo('#condDestino').val();
    let con = activeConnection;
    if (!con || !sameId(con.origen, origenId) || !sameId(con.destino, destinoId)) {
        con = wfFindConnection(origenId, destinoId);
    }
    if (!con) {
        return;
    }
    con.accion = 'CONDICIONAL';
    con.condicion = null;
    con.comentario = '';
    wfModalCondicionCampo('#condDefault').prop('checked', false);
    wfModalCondicionCampo('#condComentario').val('');
    wfModalCondicionCampo('#condValor').val('');
    wfModalCondicionCampo('#condFields').show();
    activeConnection = con;
    redrawConnections();
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

    connections.forEach(function(con) {
        const pts = wfConnectionPoints(con);
        const p1 = pts.p1;
        const p2 = pts.p2;

        if (!p1 || !p2) {
            return;
        }

        const pathString = wfBezierPath(p1, p2, pts.sideFrom, pts.sideTo);
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'wf-conn-group');
        g.setAttribute('data-ori', con.origen);
        g.setAttribute('data-des', con.destino);

        const isSelected = (activeConnection && sameId(activeConnection.origen, con.origen) && sameId(activeConnection.destino, con.destino));

        // Hit area mas ancha para facilitar el clic
        const hit = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        hit.setAttribute('d', pathString);
        hit.setAttribute('fill', 'none');
        hit.setAttribute('stroke', 'transparent');
        hit.setAttribute('stroke-width', '16');
        hit.style.cursor = 'pointer';
        g.appendChild(hit);

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', pathString);
        path.setAttribute('fill', 'none');
        path.setAttribute('class', 'wf-conn-line');
        if (isSelected) {
            path.setAttribute('stroke', '#0d6efd');
            path.setAttribute('stroke-width', '4');
            path.setAttribute('opacity', '0.55');
            path.setAttribute('marker-end', 'url(#arrow-selected)');
            g.classList.add('is-selected');
        } else {
            path.setAttribute('stroke', '#495057');
            path.setAttribute('stroke-width', '3');
            path.setAttribute('opacity', '1');
            path.setAttribute('marker-end', 'url(#arrow)');
        }
        path.style.cursor = 'pointer';
        g.appendChild(path);

        const mid = wfConnectionMidpoint(p1, p2);
        const esDecision = wfIsDecisionConnection(con);
        const btnDeleteX = esDecision ? 14 : 0;

        if (esDecision) {
            const condText = wfCondicionTexto(con);
            const condBtn = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            condBtn.setAttribute('class', 'wf-conn-condition');
            condBtn.setAttribute('transform', 'translate(' + (mid.x - 14) + ',' + mid.y + ')');
            condBtn.style.cursor = 'pointer';

            const condCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            condCircle.setAttribute('r', '9');
            condCircle.setAttribute('cx', '0');
            condCircle.setAttribute('cy', '0');
            condCircle.setAttribute('fill', '#0d6efd');
            condCircle.setAttribute('stroke', '#ffffff');
            condCircle.setAttribute('stroke-width', '2');
            condBtn.appendChild(condCircle);

            const condIcon = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            condIcon.setAttribute('x', '0');
            condIcon.setAttribute('y', '1');
            condIcon.setAttribute('text-anchor', 'middle');
            condIcon.setAttribute('dominant-baseline', 'middle');
            condIcon.setAttribute('fill', '#ffffff');
            condIcon.setAttribute('font-size', '12');
            condIcon.setAttribute('font-weight', '700');
            condIcon.setAttribute('font-family', 'Arial, sans-serif');
            condIcon.setAttribute('pointer-events', 'none');
            condIcon.textContent = '?';
            condBtn.appendChild(condIcon);
            g.appendChild(condBtn);

            const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            label.setAttribute('x', mid.x);
            label.setAttribute('y', mid.y - 16);
            label.setAttribute('text-anchor', 'middle');
            label.setAttribute('fill', wfLeerComentarioConexion(con) || (con.condicion && con.condicion.campo) || con.accion === 'APROBAR' ? '#0f172a' : '#dc3545');
            label.setAttribute('font-size', '11');
            label.setAttribute('font-weight', '700');
            label.setAttribute('font-family', 'Arial, sans-serif');
            label.setAttribute('paint-order', 'stroke');
            label.setAttribute('stroke', '#ffffff');
            label.setAttribute('stroke-width', '3');
            label.textContent = condText.length > 32 ? condText.substring(0, 29) + '...' : condText;
            g.appendChild(label);

            $(condBtn).on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openConnectionCondition(con);
            });
        }

        const btn = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        btn.setAttribute('class', 'wf-conn-delete');
        btn.setAttribute('transform', 'translate(' + (mid.x + btnDeleteX) + ',' + mid.y + ')');
        btn.style.cursor = 'pointer';

        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('r', '9');
        circle.setAttribute('cx', '0');
        circle.setAttribute('cy', '0');
        circle.setAttribute('fill', '#dc3545');
        circle.setAttribute('stroke', '#ffffff');
        circle.setAttribute('stroke-width', '2');
        btn.appendChild(circle);

        const cross = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        cross.setAttribute('x', '0');
        cross.setAttribute('y', '1');
        cross.setAttribute('text-anchor', 'middle');
        cross.setAttribute('dominant-baseline', 'middle');
        cross.setAttribute('fill', '#ffffff');
        cross.setAttribute('font-size', '11');
        cross.setAttribute('font-weight', '700');
        cross.setAttribute('font-family', 'Arial, sans-serif');
        cross.setAttribute('pointer-events', 'none');
        cross.textContent = '×';
        btn.appendChild(cross);

        g.appendChild(btn);
        $svg[0].appendChild(g);

        $(hit).add(path).on('click', function(e) {
            e.stopPropagation();
            if (activeNode) {
                syncFormToActiveNode();
            }
            $('#propertiesDrawer').removeClass('open');
            $('.properties-drawer').removeClass('open');
            activeNode = null;
            highlightActiveNode(null);
            activeConnection = con;
            redrawConnections();
        });

        $(hit).add(path).on('dblclick', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openConnectionCondition(con);
        });

        $(btn).on('click', function(e) {
            deleteConnection(con.origen, con.destino, e);
        });

        $(g).on('contextmenu', function(e) {
            e.preventDefault();
            deleteConnection(con.origen, con.destino, e);
        });
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
                    cot_edit: (n.tipo === 'FIN') ? false : (parseInt(n.cot_edit, 10) === 1),
                    cot_sel: (n.tipo === 'FIN') ? false : (parseInt(n.cot_sel, 10) === 1),
                    cre_sol: (n.tipo === 'INICIO')
                        ? (n.cre_sol === undefined || n.cre_sol === null || parseInt(n.cre_sol, 10) === 1)
                        : false,
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
            // Clonar condicion por conexión para que cada rama tenga su propio comentario
            connections = res.conexiones.map(function(c) {
                const cond = wfCloneCondicion(c.condicion);
                let comentario = (c.comentario != null) ? String(c.comentario).trim() : '';
                if (!comentario && cond && cond.comentario != null) {
                    comentario = String(cond.comentario).trim();
                }
                return {
                    origen: wfNodeId(c.origen),
                    destino: wfNodeId(c.destino),
                    accion: c.accion,
                    condicion: cond,
                    comentario: comentario,
                    side_ori: c.side_ori || null,
                    side_des: c.side_des || null
                };
            });
            // Fijar puertos una sola vez si no vienen guardados (no recalcular al mover/guardar)
            connections.forEach(function(c) {
                if (!c.side_ori || !c.side_des) {
                    const best = wfBestPortSides(c.origen, c.destino);
                    c.side_ori = c.side_ori || best.from;
                    c.side_des = c.side_des || best.to;
                } else {
                    c.side_ori = wfNormalizePortSide(c.side_ori);
                    c.side_des = wfNormalizePortSide(c.side_des);
                }
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
    pendingDuplicateFlowId = null;
    $('#modalFlowName').val('');
    $('#modalFlowDesc').val('');
    $('#modalWorkflowDataLabel').text('Crear Nuevo Flujo Modelo');
    $('#modalWorkflowData').modal('show');
}

function abrirModalDuplicarFlujo() {
    const selectorId = $('#selWorkflow').val();
    if (!selectorId) {
        wfNotify('warning', 'Seleccione el esquema que desea duplicar.');
        return;
    }
    pendingSaveAfterModal = false;
    pendingDuplicateFlowId = selectorId;
    let nombreBase = $('#selWorkflow option:selected').text() || 'Esquema';
    nombreBase = nombreBase.replace(/\s*\(v\d+\)\s*(\[Borrador\])?\s*$/i, '').trim();
    if (workflowFamilyId && String(workflowFamilyId) === String(selectorId) && $('#flowName').val().trim()) {
        nombreBase = $('#flowName').val().trim();
        $('#modalFlowDesc').val($('#flowDesc').val() || '');
    } else {
        $('#modalFlowDesc').val('');
    }
    $('#modalFlowName').val('Copia de ' + nombreBase);
    $('#modalWorkflowDataLabel').text('Duplicar Esquema');
    $('#modalWorkflowData').modal('show');
}

function duplicarFlujoServidor(selectorId, nombre, descripcion) {
    fetch('adq_configuracion.php?ajax_duplicate_workflow=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: selectorId,
            nombre: nombre,
            descripcion: descripcion
        })
    })
    .then(function(r) {
        return r.text().then(function(text) {
            let res;
            try {
                res = JSON.parse(text);
            } catch (e) {
                throw new Error('Respuesta invalida del servidor.');
            }
            return res;
        });
    })
    .then(function(res) {
        if (!res.success) {
            throw new Error(res.message || 'No se pudo duplicar el esquema.');
        }
        const famId = res.familia_cod || res.id;
        const label = wfFlowOptionLabel(res.nombre || nombre, res.version || 1, true);
        let $option = $('#selWorkflow option[value="' + famId + '"]');
        if (!$option.length) {
            $option = $('<option></option>').val(String(famId)).appendTo('#selWorkflow');
        }
        $option.text(label);
        $('#selWorkflow').val(String(famId));
        wfNotify('success', res.message || 'Esquema duplicado correctamente.', function() {
            cargarFlujo();
        });
    })
    .catch(function(err) {
        wfNotify('danger', 'No se pudo duplicar el esquema: ' + (err.message || 'Error de red'));
    });
}

function aceptarDatosFlujo() {
    const nombre = $('#modalFlowName').val().trim();
    if (!nombre) {
        wfNotify('danger', 'Por favor ingrese el nombre del flujo.');
        return;
    }

    const esGuardadoPendiente = pendingSaveAfterModal;
    const duplicarId = pendingDuplicateFlowId;
    pendingSaveAfterModal = false;
    pendingDuplicateFlowId = null;

    if (duplicarId) {
        const descripcion = $('#modalFlowDesc').val();
        $('#modalWorkflowData').modal('hide');
        duplicarFlujoServidor(duplicarId, nombre, descripcion);
        return;
    }

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

/** Serializa conexiones con comentario independiente por cada flecha/rama. */
function wfConexionesPayloadParaGuardar() {
    return connections.map(function(c) {
        const copia = {
            origen: c.origen,
            destino: c.destino,
            accion: c.accion,
            condicion: wfCloneCondicion(c.condicion),
            comentario: wfLeerComentarioConexion(c),
            side_ori: c.side_ori || null,
            side_des: c.side_des || null
        };
        if (copia.comentario) {
            if (!copia.condicion) {
                copia.condicion = {};
            }
            copia.condicion.comentario = copia.comentario;
        } else if (copia.condicion && Object.prototype.hasOwnProperty.call(copia.condicion, 'comentario')) {
            delete copia.condicion.comentario;
            if (!copia.condicion.campo && Object.keys(copia.condicion).length === 0) {
                copia.condicion = null;
            }
        }
        return copia;
    });
}

function wfNodosPayloadParaGuardar() {
    return nodes.map(function(n) {
        const copia = Object.assign({}, n);
        if (copia.tipo === 'FIN') {
            copia.cot_edit = false;
            copia.cot_sel = false;
        }
        return copia;
    });
}

function guardarFlujo() {
    if (activeNode) {
        syncFormToActiveNode();
    }
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
        nodos: wfNodosPayloadParaGuardar(),
        conexiones: wfConexionesPayloadParaGuardar()
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
    if (activeNode) {
        syncFormToActiveNode();
    }
    const nombre = $('#flowName').val().trim();
    const payload = {
        id: workflowId,
        nombre: nombre,
        descripcion: $('#flowDesc').val() || '',
        nodos: wfNodosPayloadParaGuardar(),
        conexiones: wfConexionesPayloadParaGuardar()
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
