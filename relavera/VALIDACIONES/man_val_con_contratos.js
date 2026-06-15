var contratosGrid;
var respaldosGrid;
var documentacionGrid;
var documentacionMcoCod = 0;
var respaldosPendientes = [];
var respaldosServidor = [];
var respaldosEliminar = [];
var respaldosTmpSeq = 0;
var contratoModalModo = 'full';

$(function () {
    initContratoDialog();
    initDocumentacionDialog();
    initPlantaSearchDialog();
    initContratosGrid();
    initRespaldosGrid();
    initContratoForm();
    initEventosContratos();
    buscarContratos();
});

function initContratoDialog() {
    var $dlg = $('#contratoDialog');
    $dlg.createDialog({
        width: 680,
        height: 'auto',
        minHeight: 0,
        icon: 'file',
        autoOpen: false,
        modal: true,
        noOverflow: true,
        afterOpen: function () {
            var $el = $(this);
            $el.css({ overflow: 'hidden', height: 'auto' });
            $el.closest('.ui-dialog').css('height', 'auto');
            if (respaldosGrid && respaldosGrid.length) {
                respaldosGrid.jqGrid('setGridWidth', $el.width() - 4, true);
            }
        }
    });
    if ($dlg.hasClass('ui-dialog-content')) {
        $dlg.dialog('close');
    }
}

function initRespaldosGrid() {
    respaldosGrid = $('#respaldosGrid').createGrid({
        caption: '',
        datatype: 'local',
        data: [],
        height: 95,
        rowNum: 100,
        colModel: [
            { label: 'Id', name: '_rowId', width: 1, key: true, hidden: true },
            { label: 'Tipo', name: '_tipo', hidden: true },
            { label: 'Cod.', name: 'Mcd_Cod', hidden: true },
            {
                label: 'Titulo', name: 'Mcd_Tip', width: 130,
                formatter: function (cellvalue, options, rowObject) {
                    var txt = $('<div/>').text(cellvalue || '').html();
                    if (rowObject._tipo === 'tmp') {
                        txt += ' <span class="label label-warning" style="font-size:9px;padding:2px 5px;">Pendiente</span>';
                    }
                    return txt;
                }
            },
            {
                label: 'Nombre Archivo', name: 'Mcd_Nom', width: 180,
                formatter: function (cellvalue, options, rowObject) {
                    var nom = cellvalue || '';
                    var esc = $('<div/>').text(nom).html();
                    if (rowObject._tipo === 'tmp') {
                        return '<a href="javascript:void(0);" class="link-pdf" onclick="verRespaldoTmp(\'' + rowObject._rowId + '\'); return false;" title="Vista previa">' + esc + '</a>';
                    }
                    return '<a href="javascript:void(0);" class="link-pdf" onclick="verRespaldoPdf(' + rowObject.Mcd_Cod + '); return false;" title="Ver PDF">' + esc + '</a>';
                }
            },
            {
                label: '', name: 'accion', width: 36, align: 'center', sortable: false,
                formatter: function (cellvalue, options, rowObject) {
                    var rid = String(rowObject._rowId).replace(/'/g, "\\'");
                    return '<button type="button" class="btn btn-danger btn-xs btn-del-respaldo" data-rowid="' + rid + '" data-tipo="' + rowObject._tipo + '" data-cod="' + (rowObject.Mcd_Cod || '') + '" title="Eliminar">' +
                        '<span class="glyphicon glyphicon-trash"></span></button>';
                }
            }
        ],
        gridComplete: function () {
            bindRespaldosEliminarClick();
        }
    }, false, '', { view: false, refresh: false, add: false, edit: false, del: false });

    bindRespaldosEliminarClick();
}

function bindRespaldosEliminarClick() {
    var $grid = $('#respaldosGrid');
    if ($grid.data('delBound')) {
        return;
    }
    $grid.data('delBound', true);
    $grid.on('click', '.btn-del-respaldo', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        confirmarQuitarRespaldo(
            $btn.attr('data-rowid'),
            $btn.attr('data-tipo'),
            $btn.attr('data-cod')
        );
        return false;
    });
}

function initDocumentacionDialog() {
    $('#documentacionDialog').createDialog({
        width: 640,
        height: 'auto',
        minHeight: 0,
        icon: 'download',
        autoOpen: false,
        modal: true,
        noOverflow: true,
        afterOpen: function () {
            var $el = $(this);
            $el.css({ overflow: 'hidden', height: 'auto' });
            $el.closest('.ui-dialog').css('height', 'auto');
            if (documentacionGrid && documentacionGrid.length) {
                documentacionGrid.jqGrid('setGridWidth', $el.width() - 4, true);
            }
        }
    });

    documentacionGrid = $('#documentacionGrid').createGrid({
        caption: 'Archivos PDF del contrato',
        datatype: 'local',
        data: [],
        height: 200,
        rowNum: 100,
        colModel: [
            { label: 'Cod.', name: 'Mcd_Cod', key: true, hidden: true },
            { label: 'Titulo', name: 'Mcd_Tip', width: 170 },
            { label: 'Nombre archivo', name: 'Mcd_Nom', width: 200 },
            { label: 'Registro', name: 'Mcd_Sys', width: 110, align: 'center' },
            {
                label: 'Accion', name: 'accion', width: 72, align: 'center', sortable: false,
                formatter: function (cellvalue, options, rowObject) {
                    return '<button type="button" class="btn btn-default btn-xs" onclick="verRespaldoPdf(' + rowObject.Mcd_Cod + '); return false;" title="Ver PDF">' +
                        '<span class="glyphicon glyphicon-eye-open"></span></button> ' +
                        '<button type="button" class="btn btn-success btn-xs" onclick="descargarRespaldoIndividual(' + rowObject.Mcd_Cod + '); return false;" title="Descargar">' +
                        '<span class="glyphicon glyphicon-download-alt"></span></button>';
                }
            }
        ]
    }, false, '', { view: false, refresh: false, add: false, edit: false, del: false });

    $('#btnDescargarTodoZip').on('click', descargarDocumentacionZip);
}

function initPlantaSearchDialog() {
    $.createSearchDialog('plantaDialog', [
        { label: 'Cod.', name: 'Pla_Cod', key: true, width: 50, align: 'center' },
        { label: 'Planta', name: 'Pla_Nom', width: 180, classes: 'highlightSearch' },
        { label: 'Ciudad', name: 'Ciu_Des', width: 100, align: 'center' },
        { label: 'Cliente', name: 'Cliente', width: 160, classes: 'highlightSearch' },
        { label: 'Licencia', name: 'Pla_Lic', width: 100 },
        {
            label: '&nbsp;', name: 'act1', width: 32, align: 'center', viewable: false,
            formatter: 'gridButton', formatoptions: { action: selectPlantaContrato }
        }
    ], 460, 740, null, {
        url: 'man_alt_contratos.php',
        mtype: 'GET'
    }, {
        title: 'Planta',
        text: 'search',
        options: [
            { label: ' &nbsp;Planta&nbsp; ', value: 'p' },
            { label: ' &nbsp;Cliente&nbsp; ', value: 'c' }
        ]
    });
}

function initContratosGrid() {
    contratosGrid = $('#contratosGrid').createGrid({
        caption: 'Contratos registrados',
        url: 'man_alt_contratos.php?listarContratosAjax=true',
        datatype: 'json',
        height: 320,
        rowNum: 50,
        colModel: [
            { label: 'Cod.', name: 'Mco_Cod', width: 55, align: 'center', key: true },
            { label: 'Planta', name: 'Pla_Nom', width: 180 },
            { label: 'N\u00b0 Contrato', name: 'Mco_Num', width: 110 },
            { label: 'Notario(a)', name: 'Mco_Not', width: 150 },
            { label: 'F. Apertura', name: 'Mco_Fap', width: 95, align: 'center' },
            {
                label: 'F. Caducidad', name: 'Mco_Fca', width: 110, align: 'center',
                formatter: function (cellvalue, options, rowObject) {
                    var txt = cellvalue || '';
                    if (rowObject.Mco_Vig_Cod === 'P') {
                        var dias = parseInt(rowObject.Mco_Dias_Cad, 10);
                        var hint = (!isNaN(dias) && dias >= 0) ? ('Caduca en ' + dias + ' dia(s)') : 'Por caducar';
                        return '<span class="contrato-fca-alerta" title="' + hint + '">' +
                            '<i class="glyphicon glyphicon-warning-sign"></i>' + txt + '</span>';
                    }
                    return txt;
                }
            },
            {
                label: 'Vigencia', name: 'Mco_Vig_Des', width: 95, align: 'center',
                formatter: function (cellvalue, options, rowObject) {
                    var cod = rowObject.Mco_Vig_Cod || 'V';
                    var cls = 'badge-vig-' + cod;
                    var txt = cellvalue || '';
                    if (cod === 'P') {
                        var dias = parseInt(rowObject.Mco_Dias_Cad, 10);
                        if (!isNaN(dias) && dias >= 0) {
                            txt = 'Por caducar (' + dias + 'd)';
                        }
                    }
                    return '<span class="' + cls + '" title="' + (cellvalue || '') + '">' + txt + '</span>';
                }
            },
            { label: 'Usuario', name: 'Usuario', width: 130, hidden: true },
            { label: 'Observaci\u00f3n', name: 'Mco_Obs', width: 160 },
            {
                label: 'Estado', name: 'Mco_Est_Des', width: 80, align: 'center',
                formatter: function (cellvalue, options, rowObject) {
                    var cls = rowObject.Mco_Est === 'A' ? 'badge-estado-A' : 'badge-estado-I';
                    return '<span class="' + cls + '">' + (cellvalue || '') + '</span>';
                }
            },
            {
                label: 'Acci\u00f3n', name: 'accion', width: 130, align: 'center', sortable: false,
                formatter: function (cellvalue, options, rowObject) {
                    var editBtn = '<button type="button" class="btn btn-primary btn-xs btn-editar-fila" data-cod="' + rowObject.Mco_Cod + '" title="Editar"><i class="fa fa-pencil"></i></button> ';
                    var dlBtn = '<button type="button" class="btn btn-success btn-xs btn-descargar-fila" data-cod="' + rowObject.Mco_Cod + '" title="Ver documentacion PDF"><i class="glyphicon glyphicon-download-alt"></i></button> ';
                    if (rowObject.Mco_Est === 'I') {
                        return editBtn + dlBtn;
                    }
                    var pdfBtn = '<button type="button" class="btn btn-info btn-xs btn-respaldo-fila" data-cod="' + rowObject.Mco_Cod + '" title="Agregar respaldos PDF"><i class="glyphicon glyphicon-paperclip"></i></button> ';
                    return editBtn + pdfBtn + dlBtn + '<button type="button" class="btn btn-danger btn-xs btn-inactivar-fila" data-cod="' + rowObject.Mco_Cod + '" title="Inactivar"><i class="glyphicon glyphicon-trash"></i></button>';
                }
            },
            { label: 'Pla_Cod', name: 'Pla_Cod', hidden: true },
            { label: 'Mco_Est', name: 'Mco_Est', hidden: true },
            { label: 'Mco_Vig_Cod', name: 'Mco_Vig_Cod', hidden: true },
            { label: 'Mco_Dias_Cad', name: 'Mco_Dias_Cad', hidden: true }
        ],
        rowattr: function (rowData) {
            if (rowData.Mco_Vig_Cod === 'P') {
                return { 'class': 'contrato-row-por-caducar' };
            }
            if (rowData.Mco_Vig_Cod === 'C') {
                return { 'class': 'contrato-row-caducado' };
            }
            return {};
        },
        loadComplete: function () {
            $('.btn-editar-fila').off('click').on('click', function (e) {
                e.stopPropagation();
                abrirContrato($(this).data('cod'));
            });
            $('.btn-inactivar-fila').off('click').on('click', function (e) {
                e.stopPropagation();
                inactivarContrato($(this).data('cod'));
            });
            $('.btn-respaldo-fila').off('click').on('click', function (e) {
                e.stopPropagation();
                abrirRespaldosContrato($(this).data('cod'));
            });
            $('.btn-descargar-fila').off('click').on('click', function (e) {
                e.stopPropagation();
                descargarDocumentacionContrato($(this).data('cod'));
            });
        }
    }, false, '#contratosGridPager', { refresh: true, add: false, edit: false, del: false }).gridButtonsAdd([
        {
            caption: 'Exportar Excel',
            buttonicon: 'glyphicon glyphicon-download',
            onClickButton: function () {
                exportarContratosExcel();
            }
        },
        {
            caption: 'Imprimir',
            buttonicon: 'glyphicon glyphicon-print',
            onClickButton: function () {
                imprimirContratos();
            }
        }
    ]);
}

function exportarContratosExcel() {
    contratosGrid.jqGrid('exportGridExcel', {
        nombre: 'Contratos_Plant',
        hoja: 'HOJA 1',
        footer: false,
        removeHiddens: true,
        removeCols: [10]
    });
}

function escRepContrato(v) {
    return $('<div/>').text(v || '').html();
}

function fmtFechaRepContrato(v) {
    if (!v) {
        return '-';
    }
    var m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})/);
    return m ? (m[3] + '/' + m[2] + '/' + m[1]) : escRepContrato(v);
}

function stripHtmlRep(v) {
    if (v === null || v === undefined) {
        return '';
    }
    return $('<div/>').html(String(v)).text().replace(/\s+/g, ' ').trim();
}

function vigenciaRepLabel(row) {
    var cod = stripHtmlRep(row.Mco_Vig_Cod) || 'V';
    if (cod === 'P') {
        var dias = parseInt(row.Mco_Dias_Cad, 10);
        if (!isNaN(dias) && dias >= 0) {
            return 'Por caducar (' + dias + 'd)';
        }
        return 'Por caducar';
    }
    if (cod === 'C') {
        return 'Caducado';
    }
    return 'Vigente';
}

function inferVigCod(des) {
    var txt = stripHtmlRep(des).toLowerCase();
    if (txt.indexOf('por caducar') >= 0) {
        return 'P';
    }
    if (txt.indexOf('caducado') >= 0) {
        return 'C';
    }
    return 'V';
}

function getContratoRowReporte(id) {
    var raw = contratosGrid.jqGrid('getLocalRow', id);
    if (!raw || raw.Mco_Cod === undefined || raw.Mco_Cod === null || raw.Mco_Cod === '') {
        raw = contratosGrid.jqGrid('getRowData', id);
    }
    var est = stripHtmlRep(raw.Mco_Est);
    if (est !== 'A' && est !== 'I') {
        var estDes = stripHtmlRep(raw.Mco_Est_Des).toLowerCase();
        est = estDes.indexOf('inactiv') >= 0 ? 'I' : 'A';
    }
    var vigCod = stripHtmlRep(raw.Mco_Vig_Cod);
    if (vigCod !== 'V' && vigCod !== 'P' && vigCod !== 'C') {
        vigCod = inferVigCod(raw.Mco_Vig_Des);
    }
    return {
        Mco_Cod: stripHtmlRep(raw.Mco_Cod),
        Pla_Nom: stripHtmlRep(raw.Pla_Nom),
        Mco_Num: stripHtmlRep(raw.Mco_Num),
        Mco_Not: stripHtmlRep(raw.Mco_Not),
        Mco_Fap: stripHtmlRep(raw.Mco_Fap),
        Mco_Fca: stripHtmlRep(raw.Mco_Fca),
        Mco_Obs: stripHtmlRep(raw.Mco_Obs),
        Mco_Est: est,
        Mco_Vig_Cod: vigCod,
        Mco_Dias_Cad: stripHtmlRep(raw.Mco_Dias_Cad),
        Mco_Est_Des: est === 'I' ? 'Inactivo' : 'Activo'
    };
}

function vigenciaRepContratoHtml(row) {
    var cod = row.Mco_Vig_Cod || 'V';
    var txt = vigenciaRepLabel(row);
    var cls = cod === 'V' ? 'rep-txt-vig' : (cod === 'P' ? 'rep-txt-por' : 'rep-txt-cad');
    return '<span class="' + cls + '">' + escRepContrato(txt) + '</span>';
}

function construirTablaReporteContratos(ids) {
    var html = '<thead><tr>' +
        '<th>N&deg;</th>' +
        '<th>Cod.</th>' +
        '<th>Planta</th>' +
        '<th>N&deg; Contrato</th>' +
        '<th>Notario(a)</th>' +
        '<th>F. Apertura</th>' +
        '<th>F. Caducidad</th>' +
        '<th>Vigencia</th>' +
        '</tr></thead><tbody>';

    if (!ids.length) {
        html += '<tr><td colspan="8" class="rep-empty">Sin registros para mostrar</td></tr>';
    } else {
        $.each(ids, function (idx, id) {
            var r = getContratoRowReporte(id);
            var trCls = '';
            if (r.Mco_Vig_Cod === 'C') {
                trCls = 'rep-row-caducado';
            } else if (r.Mco_Vig_Cod === 'P') {
                trCls = 'rep-row-por-caducar';
            }
            if (r.Mco_Est === 'I') {
                trCls += (trCls ? ' ' : '') + 'rep-row-inactivo';
            }

            html += '<tr class="' + trCls + '">';
            html += '<td class="rep-num">' + (idx + 1) + '</td>';
            html += '<td class="rep-cod">' + escRepContrato(r.Mco_Cod) + '</td>';
            html += '<td class="rep-planta">' + escRepContrato(r.Pla_Nom) + '</td>';
            html += '<td class="rep-contrato">' + escRepContrato(r.Mco_Num || '-') + '</td>';
            html += '<td>' + escRepContrato(r.Mco_Not) + '</td>';
            html += '<td class="rep-fecha">' + fmtFechaRepContrato(r.Mco_Fap) + '</td>';
            html += '<td class="rep-fecha">' + fmtFechaRepContrato(r.Mco_Fca) + '</td>';
            html += '<td class="rep-vig">' + vigenciaRepContratoHtml(r) + '</td>';
            html += '</tr>';
        });
    }
    html += '</tbody>';
    return html;
}

function imprimirContratos() {
    var ids = contratosGrid.jqGrid('getDataIDs');

    $('#contratosReporteSubtitulo').text('Listado de contratos registrados \u2014 ' + $.getDate());
    $('#tablaReporteContratos').html(construirTablaReporteContratos(ids));

    corregirImagenesReporteContratos();

    $('#imprimirContratos').printElement({
        pageTitle: 'Reporte Contratos de Plantas',
        printMode: 'iframe',
        overrideElementCSS: [
            { href: '../../mascaras/model1/estilos/print.css', media: 'print' }
        ]
    });
}

function corregirImagenesReporteContratos() {
    var pageDir = window.location.pathname.replace(/[^\/]+$/, '');
    $('#imprimirContratos img').each(function () {
        var src = $(this).attr('src');
        if (!src || /^https?:\/\//i.test(src) || /^data:/i.test(src)) {
            return;
        }
        if (src.indexOf('/') === 0) {
            $(this).attr('src', window.location.origin + src);
        } else if (src.indexOf('../') === 0) {
            var a = document.createElement('a');
            a.href = pageDir + src;
            $(this).attr('src', a.href);
        }
    });
}

function initContratoForm() {
    $('#contratoForm').validate({
        ignore: [],
        rules: {
            Mco_Not: { required: true, maxlength: 50 },
            Mco_Num: { maxlength: 20 },
            Mco_Fap: { required: true },
            Mco_Fca: { required: true }
        },
        messages: {
            Mco_Not: { required: 'Ingrese el nombre del notario' },
            Mco_Fap: { required: 'Ingrese la fecha de apertura' },
            Mco_Fca: { required: 'Ingrese la fecha de caducidad' }
        },
        submitHandler: function () {
            guardarContrato();
        }
    });
}

function initEventosContratos() {
    $('#btnNuevoContrato').on('click', function () {
        nuevoContrato();
        $('#contratoDialog').dialog('open');
        setContratoModalModo('new');
    });

    $('#btnBuscarPlantaContrato, #Pla_Nom').on('click', function () {
        abrirBuscarPlanta();
    });

    $('#btnLimpiarPlantaContrato').on('click', limpiarPlantaContrato);

    $('#search_contrato').on('keypress', function (e) {
        if (e.which === 13) {
            buscarContratos();
            return false;
        }
    }).on('clearable', buscarContratos);

    $('#filtro_estado').on('change', buscarContratos);
    $('#filtro_vigencia').on('change', buscarContratos);
    $('#btnBuscarContratosMain').on('click', buscarContratos);
    $('#btnLimpiarFiltros').on('click', limpiarFiltrosContratos);

    $('#btnAgregarRespaldo').on('click', agregarRespaldoPdf);
    $('#btnGuardarRespaldos').on('click', guardarRespaldos);
    $('#btnDescargarDocumentacion').on('click', function () {
        descargarDocumentacionContrato($('#Mco_Cod').val());
    });
    $('#contratoDialog').on('dialogclose', function () {
        setContratoModalModo('full');
        nuevoContrato();
    });

    $(window).on('pageshow', function () {
        if ($('#contratoDialog').hasClass('ui-dialog-content') && $('#contratoDialog').dialog('isOpen')) {
            $('#contratoDialog').dialog('close');
        }
    });
}

function resetRespaldos() {
    var i;
    for (i = 0; i < respaldosPendientes.length; i++) {
        if (respaldosPendientes[i].blobUrl) {
            URL.revokeObjectURL(respaldosPendientes[i].blobUrl);
        }
    }
    respaldosPendientes = [];
    respaldosServidor = [];
    respaldosEliminar = [];
    respaldosTmpSeq = 0;
    limpiarRespaldosForm();
    renderRespaldosGrid();
}

function cargarRespaldosServidor(mcoCod) {
    if (!mcoCod) {
        respaldosServidor = [];
        renderRespaldosGrid();
        return;
    }
    $.get('man_alt_contratos.php', { listRespaldoAjax: true, Mco_Cod: mcoCod }, function (data) {
        respaldosServidor = (data && data.rows) ? data.rows : [];
        renderRespaldosGrid();
    }, 'json');
}

function renderRespaldosGrid() {
    respaldosGrid.jqGrid('clearGridData');
    var i, r, rows = [];

    for (i = 0; i < respaldosServidor.length; i++) {
        r = respaldosServidor[i];
        if (respaldosEliminar.indexOf(parseInt(r.Mcd_Cod, 10)) < 0) {
            rows.push({
                _rowId: 'bd_' + r.Mcd_Cod,
                _tipo: 'bd',
                Mcd_Cod: r.Mcd_Cod,
                Mcd_Tip: r.Mcd_Tip,
                Mcd_Nom: r.Mcd_Nom
            });
        }
    }
    for (i = 0; i < respaldosPendientes.length; i++) {
        r = respaldosPendientes[i];
        rows.push({
            _rowId: r._id,
            _tipo: 'tmp',
            Mcd_Cod: r._id,
            Mcd_Tip: r.Mcd_Tip,
            Mcd_Nom: r.Mcd_Nom
        });
    }
    for (i = 0; i < rows.length; i++) {
        respaldosGrid.jqGrid('addRowData', rows[i]._rowId, rows[i]);
    }
}

function limpiarRespaldosForm() {
    $('#Mcd_Tip_New').val('');
    $('#Mcd_File_New').val('');
}

function esPdfValido(file) {
    if (!file) {
        return false;
    }
    if (file.type && file.type !== 'application/pdf') {
        return false;
    }
    if (file.name && file.name.split('.').pop().toLowerCase() !== 'pdf') {
        return false;
    }
    if (file.size > 10 * 1024 * 1024) {
        $.alert('El archivo "' + file.name + '" supera 10 MB.');
        return false;
    }
    return true;
}

function agregarRespaldoPdf() {
    var tituloBase = $.trim($('#Mcd_Tip_New').val());
    var fileInput = $('#Mcd_File_New')[0];
    if (!fileInput.files || !fileInput.files.length) {
        $.alert('Seleccione uno o mas archivos PDF.');
        return;
    }

    var agregados = 0;
    var n = fileInput.files.length;

    for (var i = 0; i < n; i++) {
        var file = fileInput.files[i];
        if (!esPdfValido(file)) {
            if (file.type && file.type !== 'application/pdf') {
                $.alert('Solo se permiten archivos PDF: ' + file.name);
            }
            continue;
        }
        var titulo = tituloBase;
        if (n === 1) {
            if (!titulo) {
                titulo = file.name.replace(/\.pdf$/i, '');
            }
        } else if (!titulo) {
            titulo = file.name.replace(/\.pdf$/i, '');
        } else if (n > 1) {
            titulo = tituloBase + ' (' + (i + 1) + ')';
        }
        if (!titulo) {
            $.alert('Ingrese el titulo del documento.');
            return;
        }

        respaldosTmpSeq++;
        var id = 'tmp_' + respaldosTmpSeq;
        respaldosPendientes.push({
            _id: id,
            Mcd_Tip: titulo,
            Mcd_Nom: file.name,
            file: file,
            blobUrl: URL.createObjectURL(file)
        });
        agregados++;
    }

    if (agregados > 0) {
        limpiarRespaldosForm();
        renderRespaldosGrid();
    }
}

function verRespaldoPdf(mcdCod) {
    window.open('man_alt_contratos.php?viewRespaldoAjax=true&Mcd_Cod=' + mcdCod, '_blank');
}

function verRespaldoTmp(rowId) {
    var i, r;
    for (i = 0; i < respaldosPendientes.length; i++) {
        r = respaldosPendientes[i];
        if (r._id === rowId && r.blobUrl) {
            window.open(r.blobUrl, '_blank');
            return;
        }
    }
}

function confirmarQuitarRespaldo(rowId, tipo, mcdCod) {
    var msg;
    if (tipo === 'tmp') {
        msg = '\u00bfDesea quitar este PDF del listado?';
    } else if (contratoModalModo === 'respaldos') {
        msg = '\u00bfEst\u00e1 seguro de eliminar este respaldo PDF? El archivo se borrar\u00e1 permanentemente del servidor.';
    } else {
        msg = '\u00bfEst\u00e1 seguro de eliminar este respaldo PDF? El archivo se borrar\u00e1 al guardar el contrato.';
    }
    $.createDialogConfirm(msg, null, function () {
        quitarRespaldoFila(rowId, tipo, mcdCod);
    });
}

function eliminarRespaldoServidor(mcdCod) {
    mcdCod = parseInt(mcdCod, 10);
    if (mcdCod <= 0) {
        return;
    }
    $.post('man_alt_contratos.php', {
        deleteRespaldoAjax: true,
        Mcd_Cod: mcdCod
    }, function (resp) {
        if (resp && resp.success) {
            var i;
            for (i = respaldosServidor.length - 1; i >= 0; i--) {
                if (parseInt(respaldosServidor[i].Mcd_Cod, 10) === mcdCod) {
                    respaldosServidor.splice(i, 1);
                    break;
                }
            }
            renderRespaldosGrid();
        } else {
            $.alert((resp && resp.message) ? resp.message : 'No se pudo eliminar el respaldo.');
        }
    }, 'json').fail(function () {
        $.alert('Error de conexion con el servidor');
    });
}

function quitarRespaldoFila(rowId, tipo, mcdCod) {
    if (tipo === 'tmp') {
        var i;
        for (i = 0; i < respaldosPendientes.length; i++) {
            if (respaldosPendientes[i]._id === rowId) {
                if (respaldosPendientes[i].blobUrl) {
                    URL.revokeObjectURL(respaldosPendientes[i].blobUrl);
                }
                respaldosPendientes.splice(i, 1);
                break;
            }
        }
        renderRespaldosGrid();
        return;
    }

    mcdCod = parseInt(mcdCod, 10);
    if (mcdCod <= 0) {
        return;
    }

    if (contratoModalModo === 'respaldos') {
        eliminarRespaldoServidor(mcdCod);
        return;
    }

    if (respaldosEliminar.indexOf(mcdCod) < 0) {
        respaldosEliminar.push(mcdCod);
    }
    renderRespaldosGrid();
}

function abrirBuscarPlanta() {
    $('#plantaDialog').dialog('open');
    $.Search('planta');
}

function selectPlantaContrato(row) {
    $('#Pla_Cod').val(row.Pla_Cod);
    $('#Pla_Nom').val(row.Pla_Nom);
    $('#plantaDialog').dialog('close');
    $('#Mco_Num').focus();
}

function limpiarPlantaContrato() {
    $('#Pla_Cod').val('');
    $('#Pla_Nom').val('');
}

function limpiarFiltrosContratos() {
    $('#filtro_estado').val('');
    $('#filtro_vigencia').val('');
    $('#search_contrato').val('');
    $('#filtro_p').prop('checked', true);
    buscarContratos();
}

function buscarContratos() {
    contratosGrid.jqGrid('setGridParam', {
        postData: {
            listarContratosAjax: true,
            search: $('#search_contrato').val(),
            filtro: $('input[name="filtro"]:checked').val(),
            Mco_Est: $('#filtro_estado').val(),
            Mco_Vig: $('#filtro_vigencia').val()
        },
        page: 1
    }).trigger('reloadGrid');
}

function abrirContrato(mcoCod) {
    $.get('man_alt_contratos.php', { getContratoAjax: true, Mco_Cod: mcoCod }, function (data) {
        if (!data || !data.Mco_Cod) {
            $.alert('No se pudo cargar el contrato.');
            return;
        }
        resetRespaldos();
        cargarContratoEnForm(data);
        cargarRespaldosServidor(data.Mco_Cod);
        $('#contratoDialog').dialog('open');
        setContratoModalModo('edit', data);
    }, 'json');
}

function abrirRespaldosContrato(mcoCod) {
    $.get('man_alt_contratos.php', { getContratoAjax: true, Mco_Cod: mcoCod }, function (data) {
        if (!data || !data.Mco_Cod) {
            $.alert('No se pudo cargar el contrato.');
            return;
        }
        if (data.Mco_Est === 'I') {
            $.alert('No se pueden agregar respaldos a un contrato inactivo.');
            return;
        }
        resetRespaldos();
        $('#Mco_Cod').val(data.Mco_Cod);
        $('#resPla_Nom').text(data.Pla_Nom || '');
        $('#resMco_Num').text(data.Mco_Num || '-');
        $('#resMco_Not').text(data.Mco_Not || '');
        cargarRespaldosServidor(data.Mco_Cod);
        $('#contratoDialog').dialog('open');
        setContratoModalModo('respaldos', data);
    }, 'json');
}

function setContratoModalModo(modo, data) {
    contratoModalModo = modo || 'full';
    var esEdicion = modo === 'edit';
    var soloRespaldos = modo === 'respaldos';
    try {
        if (soloRespaldos) {
            $('#contratoDialog').dialog('option', 'title', 'Respaldos PDF del contrato');
        } else {
            $('#contratoDialog').dialog('option', 'title', esEdicion ? 'Editar contrato' : 'Nuevo contrato');
        }
    } catch (e) { /* dialog aun no inicializado */ }

    $('.contrato-campos-contrato').toggle(!soloRespaldos);
    $('#contratoResumenRespaldos').toggle(soloRespaldos);
    $('#btnGuardarContrato').toggle(!soloRespaldos);
    $('#btnGuardarRespaldos').toggle(soloRespaldos);
    $('#btnDescargarDocumentacion').toggle((esEdicion || soloRespaldos) && data && data.Mco_Cod);
    $('#respaldosHint').text(soloRespaldos
        ? 'Agregue PDFs al listado y pulse Guardar respaldos. Puede repetir el proceso las veces que necesite.'
        : 'Agregue PDFs al listado; se guardaran al pulsar Guardar.');

    if ((esEdicion || soloRespaldos) && data && data.Mco_Cod) {
        $('#contratoMetaCod').show();
        $('#contratoMetaCodVal').text(data.Mco_Cod);
    } else {
        $('#contratoMetaCod').hide();
        $('#contratoMetaCodVal').text('');
    }
}

function cargarContratoEnForm(data) {
    $('#Mco_Cod').val(data.Mco_Cod);
    $('#Pla_Cod').val(data.Pla_Cod);
    $('#Pla_Nom').val(data.Pla_Nom || '');
    $('#Mco_Num').val(data.Mco_Num || '');
    $('#Mco_Not').val(data.Mco_Not || '');
    $('#Mco_Fap').val(data.Mco_Fap || '');
    $('#Mco_Fca').val(data.Mco_Fca || '');
    $('#Mco_Obs').val(data.Mco_Obs || '');
    $('#Mco_Est').val(data.Mco_Est || 'A');
    $('#Usuario_Reg').text(data.Usuario || $('#Usu_Nom').val());
}

function nuevoContrato() {
    $('#contratoForm')[0].reset();
    $('#Mco_Cod').val('');
    limpiarPlantaContrato();
    $('#Mco_Fap').val($('#Hoy_Fec').val() || new Date().toISOString().slice(0, 10));
    $('#Mco_Est').val('A');
    $('#Usuario_Reg').text($('#Usu_Nom').val());
    $('#contratoForm').validate().resetForm();
    resetRespaldos();
}

function descargarDocumentacionContrato(mcoCod) {
    mcoCod = parseInt(mcoCod, 10);
    if (!mcoCod || mcoCod <= 0) {
        $.alert('Contrato no valido.');
        return;
    }

    $.get('man_alt_contratos.php', { listarDocumentacionAjax: true, Mco_Cod: mcoCod }, function (resp) {
        if (!resp || !resp.success) {
            $.alert((resp && resp.message) ? resp.message : 'No se pudo cargar la documentacion.');
            return;
        }
        if (!resp.rows || resp.rows.length === 0) {
            $.alert(resp.message || 'El contrato no tiene documentacion PDF disponible.');
            return;
        }

        documentacionMcoCod = mcoCod;
        var c = resp.contrato || {};
        $('#docResumenCod').text(c.Mco_Cod || mcoCod);
        $('#docResumenPlanta').text(c.Pla_Nom || '');
        $('#docResumenNum').text(c.Mco_Num || '-');
        $('#docResumenNot').text(c.Mco_Not || '');

        documentacionGrid.jqGrid('clearGridData');
        var i;
        for (i = 0; i < resp.rows.length; i++) {
            documentacionGrid.jqGrid('addRowData', resp.rows[i].Mcd_Cod, resp.rows[i]);
        }

        $('#btnDescargarTodoZip').toggle(resp.rows.length > 1);
        try {
            $('#documentacionDialog').dialog('option', 'title', 'Documentacion del contrato #' + (c.Mco_Cod || mcoCod));
        } catch (e) { /* dialog aun no inicializado */ }
        $('#documentacionDialog').dialog('open');
    }, 'json').fail(function () {
        $.alert('Error de conexion con el servidor');
    });
}

function descargarRespaldoIndividual(mcdCod) {
    mcdCod = parseInt(mcdCod, 10);
    if (!mcdCod || mcdCod <= 0) {
        return;
    }
    window.location.href = 'man_alt_contratos.php?descargarRespaldoAjax=1&Mcd_Cod=' + encodeURIComponent(mcdCod);
}

function descargarDocumentacionZip() {
    if (!documentacionMcoCod || documentacionMcoCod <= 0) {
        return;
    }
    window.location.href = 'man_alt_contratos.php?descargarContratoDocAjax=1&Mco_Cod=' + encodeURIComponent(documentacionMcoCod);
}

function buildRespaldoFormData(mcoCod) {
    var fd = new FormData();
    fd.append('Mco_Cod', mcoCod);
    var i;
    for (i = 0; i < respaldosPendientes.length; i++) {
        fd.append('Mcd_Tip[]', respaldosPendientes[i].Mcd_Tip);
        fd.append('Mcd_File[]', respaldosPendientes[i].file);
    }
    for (i = 0; i < respaldosEliminar.length; i++) {
        fd.append('Mcd_Del[]', respaldosEliminar[i]);
    }
    return fd;
}

function guardarRespaldos() {
    var mcoCod = $('#Mco_Cod').val();
    if (!mcoCod) {
        $.alert('Contrato no valido.');
        return;
    }
    if (respaldosPendientes.length === 0 && respaldosEliminar.length === 0) {
        $.alert('Agregue al menos un PDF o marque respaldos para eliminar.');
        return;
    }

    var fd = buildRespaldoFormData(mcoCod);
    fd.append('guardarRespaldosAjax', '1');

    $('#btnGuardarRespaldos').prop('disabled', true);
    $.ajax({
        url: 'man_alt_contratos.php',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json'
    }).done(function (resp) {
        if (resp.success) {
            resetRespaldos();
            cargarRespaldosServidor(mcoCod);
            buscarContratos();
            $.alert(resp.message || 'Respaldos guardados correctamente.');
        } else {
            $.alert(resp.message || 'Error al guardar respaldos');
        }
    }).fail(function () {
        $.alert('Error de conexion con el servidor');
    }).always(function () {
        $('#btnGuardarRespaldos').prop('disabled', false);
    });
}

function guardarContrato() {
    if (!$('#Pla_Cod').val()) {
        $.alert('Seleccione una planta.');
        return;
    }

    var fap = $('#Mco_Fap').val();
    var fca = $('#Mco_Fca').val();
    if (fca && fap && fca < fap) {
        $.alert('La fecha de caducidad no puede ser anterior a la fecha de apertura.');
        return;
    }

    var fd = new FormData();
    fd.append('guardarContratoAjax', '1');
    fd.append('Mco_Cod', $('#Mco_Cod').val());
    fd.append('Pla_Cod', $('#Pla_Cod').val());
    fd.append('Mco_Num', $('#Mco_Num').val());
    fd.append('Mco_Not', $('#Mco_Not').val());
    fd.append('Mco_Fap', fap);
    fd.append('Mco_Fca', fca);
    fd.append('Mco_Obs', $('#Mco_Obs').val());
    fd.append('Mco_Est', $('#Mco_Est').val());

    var i;
    for (i = 0; i < respaldosPendientes.length; i++) {
        fd.append('Mcd_Tip[]', respaldosPendientes[i].Mcd_Tip);
        fd.append('Mcd_File[]', respaldosPendientes[i].file);
    }
    for (i = 0; i < respaldosEliminar.length; i++) {
        fd.append('Mcd_Del[]', respaldosEliminar[i]);
    }

    $('#btnGuardarContrato').prop('disabled', true);
    $.ajax({
        url: 'man_alt_contratos.php',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json'
    }).done(function (resp) {
        if (resp.success) {
            buscarContratos();
            $('#contratoDialog').dialog('close');
            $.alert(resp.message);
        } else {
            $.alert(resp.message || 'Error al guardar');
        }
    }).fail(function () {
        $.alert('Error de conexion con el servidor');
    }).always(function () {
        $('#btnGuardarContrato').prop('disabled', false);
    });
}

function inactivarContrato(mcoCod) {
    $.createDialogConfirm('\u00bfEst\u00e1 seguro de inactivar este contrato?', null, function () {
        $.post('man_alt_contratos.php', {
            inactivarContratoAjax: true,
            Mco_Cod: mcoCod
        }, function (resp) {
            if (resp.success) {
                $.alert(resp.message);
                buscarContratos();
            } else {
                $.alert(resp.message || 'Error al inactivar');
            }
        }, 'json').fail(function () {
            $.alert('Error de conexion con el servidor');
        });
    });
}
