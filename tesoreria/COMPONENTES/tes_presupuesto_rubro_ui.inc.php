<?php
/**
 * Casillero de rubro de presupuesto (misma grilla flex que Periodo/Banco).
 * Requiere: $verPre === true, $Ses_Emp_Cod, $obBD_conexion
 */
if (empty($verPre)) {
    return;
}

$ppa_proyectos = array();
if (isset($obBD_conexion) && !empty($obBD_conexion->conexion) && !empty($Ses_Emp_Cod)) {
    $empPpa = (int)$Ses_Emp_Cod;
    $qPpaProy = @$obBD_conexion->conexion->query(
        "SELECT Pro_Cod, Pro_Ide, Pro_Nom FROM pre_proyectos WHERE Emp_Cod = $empPpa AND Pro_Est = 'A' ORDER BY Pro_Ide, Pro_Nom"
    );
    if ($qPpaProy) {
        while ($rowPpaProy = $qPpaProy->fetch_assoc()) {
            $ppa_proyectos[] = $rowPpaProy;
        }
    }
}
?>
<style>
    #ppaDialog td.ppa-arbol { white-space: normal !important; padding-top: 2px !important; padding-bottom: 2px !important; }
    #ppaDialog .ppa-filtros { display: table; width: 100%; table-layout: fixed; border-spacing: 4px 0; margin: 0 -4px 3px; }
    #ppaDialog .ppa-fg { display: table-cell; vertical-align: middle; }
    #ppaDialog .ppa-fg .input-group { width: 100%; }
    #ppaDialog .ppa-fg .input-group-addon { padding: 2px 6px; color: #5f4588; background: #f4effa; border-color: #e4dcf0; font-size: 10px; font-weight: 700; white-space: nowrap; }
    #ppaDialog .ppa-hint { display: block; margin: 2px 0 4px; min-height: 14px; color: #8b79a8; font-size: 10px; line-height: 14px; }
    #ppaDialog .ppa-hint b { color: #5f4588; }
    #ppaDialog .ppa-origen { margin: 0 0 4px; }
    #ppaDialog .ppa-origen label { margin: 0 12px 0 0; font-size: 11px; font-weight: 600; color: #5f4588; cursor: pointer; }
    #ppaDialog .ppa-origen input { margin: 0 4px 0 0; vertical-align: -1px; }
    #ppaDialog .ppa-proy-only.is-off { opacity: .45; pointer-events: none; }
    .exa-arbol { display: block; }
    .exa-arbol .n { display: block; color: #94a5b2; font-size: 10px; line-height: 1.35; }
    .exa-arbol .n.pad { color: #6b7d8b; font-weight: 600; }
    .exa-arbol .n .cn { color: #c8d2da; }
    .exa-sinpadre { color: #b3bfc9; font-size: 10px; font-style: italic; }
</style>

<div class="lib-ban-cell lib-ban-rubro is-off" id="ppaLibBanInline">
    <label for="Ppa_Label">Rubro presupuesto</label>
    <form id="ppaForm" onsubmit="return false;" style="margin:0;">
        <div class="input-group input-group-sm">
            <span class="input-group-addon lib-ban-addon-ppa" title="Presupuesto">
                <i class="glyphicon glyphicon-stats"></i>
            </span>
            <input type="text" name="Ppa_Label" id="Ppa_Label" placeholder="Seleccione rubro..." class="form-control" readonly title="" />
            <input type="hidden" name="Ppa_Cod" id="Ppa_Cod" value="" />
            <input type="hidden" name="Pdp_Cod" id="Pdp_Cod" value="" />
            <input type="hidden" name="Ppa_Cla" id="Ppa_Cla" value="" />
            <input type="hidden" name="Ppa_Des" id="Ppa_Des" value="" />
            <input type="hidden" name="Ppa_Ruta" id="Ppa_Ruta" value="" />
            <span class="input-group-btn">
                <button type="button" onclick="abrirBusquedaPresupuesto()" class="btn btn-success" title="Buscar rubro">
                    <i class="glyphicon glyphicon-search"></i>
                </button>
                <button type="button" onclick="limpiarCamposPresupuesto()" class="btn btn-danger" title="Quitar rubro">
                    <i class="glyphicon glyphicon-remove"></i>
                </button>
            </span>
        </div>
        <small class="lib-ban-ruta" id="Ppa_Ruta_Txt"></small>
    </form>
</div>

<div id="ppaDialog" title="B&uacute;squeda de Rubros de Presupuesto" style="display:none;">
    <div id="frm_ppa" class="form-horizontal normal">
        <div class="ppa-origen">
            <label>
                <input type="radio" name="ppa_Origen" value="proyecto" checked /> Presupuesto por Proyecto
            </label>
            <label>
                <input type="radio" name="ppa_Origen" value="general" /> Presupuesto General
            </label>
        </div>
        <div class="ppa-filtros ppa-proy-only">
            <div class="ppa-fg" style="width:100%;">
                <div class="input-group input-group-xs">
                    <span class="input-group-addon">Proyecto</span>
                    <select id="ppa_Pro_Cod" class="form-control input-xs">
                        <option value="">Todos...</option>
                        <?php foreach ($ppa_proyectos as $proyPpa) {
                            $lblPpa = trim($proyPpa['Pro_Ide'] . ' - ' . $proyPpa['Pro_Nom']);
                            $lblPpaEsc = htmlspecialchars($lblPpa, ENT_QUOTES, 'UTF-8');
                            echo '<option value="' . (int)$proyPpa['Pro_Cod'] . '" title="' . $lblPpaEsc . '">' . $lblPpaEsc . '</option>';
                        } ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="input-group input-group-xs" style="width:100%;margin-bottom:4px;">
            <input id="ppa_search" type="text" maxlength="80" placeholder="Filtrar por c&oacute;digo, nombre o ruta..." class="form-control input-xs clearable"
                   onkeydown="if((event.keyCode||event.which)===13){event.preventDefault();buscarPresupuesto();return false;}" />
            <span class="input-group-btn">
                <button type="button" onclick="buscarPresupuesto()" class="btn btn-success btn-xs" title="Filtrar rubros">
                    <span class="glyphicon glyphicon-filter"></span>
                </button>
            </span>
        </div>
        <small class="ppa-hint" id="ppa_hint">Presupuesto por Proyecto: filtre por proyecto y pulse <b>buscar</b>.</small>
        <table id="containerPpa"></table>
    </div>
</div>

<script type="text/javascript">
(function () {
    function ppaEsc(t) {
        return $('<i/>').text(t == null ? '' : t).html().replace(/"/g, '&quot;');
    }
    function rutaTexto(ruta) {
        return $.trim(ruta || '');
    }
    function rutaArbol(ruta) {
        var txt = $.trim(ruta || ''), html = '';
        if (txt === '') return '';
        var padres = txt.split('>').slice(0, -1);
        if (!padres.length) return '<span class="exa-sinpadre">ù sin grupo ù</span>';
        $.each(padres, function (i, p) {
            var directo = (i === padres.length - 1);
            html += '<span class="n' + (directo ? ' pad' : '') + '" style="padding-left:' + (i * 11) + 'px">'
                + (i > 0 ? '<span class="cn">\u2514 </span>' : '')
                + ppaEsc($.trim(p)) + '</span>';
        });
        return '<span class="exa-arbol" title="' + ppaEsc(txt) + '">' + html + '</span>';
    }
    function ppaOrigen() {
        return ($('input[name=ppa_Origen]:checked').val() || 'proyecto');
    }
    function setPpaHint(txt) {
        $('#ppa_hint').html(txt || (ppaOrigen() === 'general'
            ? 'Presupuesto General: rubros del catùlogo que <b>no</b> pertenecen a proyectos.'
            : 'Presupuesto por Proyecto: filtre por proyecto y pulse <b>buscar</b>.'));
    }
    function limpiarGridPresupuesto() {
        var $g = $('#containerPpa');
        if (!$g.length || !$g[0].grid) return;
        try {
            if (typeof $g.clearGrid === 'function') $g.clearGrid();
            else if (typeof $g.jqGrid === 'function') $g.jqGrid('clearGridData', true);
        } catch (e) { /* grid vacùo */ }
    }
    function aplicarModoPresupuesto() {
        var esProy = ppaOrigen() === 'proyecto';
        $('.ppa-proy-only').toggleClass('is-off', !esProy);
        limpiarGridPresupuesto();
        setPpaHint();
    }
    function armarGridPresupuesto() {
        var $g = $('#containerPpa');
        if (!$g.length || $g[0].grid || typeof $g.createGrid !== 'function') return;
        $g.createGrid({
            width: 820,
            height: 310,
            colModel: [
                { name: 'Pdp_Cod', hidden: true },
                { name: 'Ppa_Cod', hidden: true },
                { label: 'Cùdigo', name: 'Ppa_Cla', width: 80 },
                { label: 'Rubro', name: 'Pdp_Rubro', width: 150 },
                { label: 'Descripciùn', name: 'Ppa_Des', width: 150 },
                { label: 'Ruta', name: 'Ppa_Ruta', width: 250, classes: 'ppa-arbol', title: false, formatter: rutaArbol },
                {
                    label: '&nbsp;', name: 'act1', width: 30, align: 'center', viewable: false,
                    formatter: 'gridButton',
                    formatoptions: { action: 'selectPresupuestoRubro', title: 'Seleccionar rubro' }
                }
            ],
            jsonReader: { root: 'response', repeatitems: false },
            datatype: 'local',
            footerrow: false
        });
    }
    window.limpiarCamposPresupuesto = function () {
        $('#Ppa_Cod,#Pdp_Cod,#Ppa_Cla,#Ppa_Des,#Ppa_Label,#Ppa_Ruta').val('');
        $('#Ppa_Label').attr('title', '');
        $('#Ppa_Ruta_Txt').text('').attr('title', '');
    };
    window.buscarPresupuesto = function () {
        var $g = $('#containerPpa');
        if (!$g.length || !$g[0].grid) return;
        $('#frm_ppa').effect('highlight', {}, 400);
        var origen = ppaOrigen();
        var proCod = $('#ppa_Pro_Cod').val() || '';
        setPpaHint(origen === 'general'
            ? 'Presupuesto General: rubros del catùlogo que <b>no</b> pertenecen a proyectos.'
            : (proCod ? 'Rubros del <b>proyecto</b> seleccionado.' : 'Presupuesto por Proyecto: filtre por proyecto y/o pulse <b>buscar</b>.'));
        $g.Search({
            search: $.trim($('#ppa_search').val() || ''),
            ppa_Origen: origen,
            ppa_Pro_Cod: origen === 'proyecto' ? proCod : '',
            ppa_Sol_Cod: ''
        }, 'presupuestoRubrosAjax');
    };
    window.abrirBusquedaPresupuesto = function () {
        var $dlg = $('#ppaDialog');
        if (!$dlg.length) return;
        if (!$dlg.data('ui-dialog')) {
            $dlg.dialog({
                autoOpen: false,
                modal: true,
                width: 860,
                height: 500,
                resizable: true,
                open: function () {
                    setTimeout(function () { $('#ppa_Pro_Cod').focus(); }, 60);
                    armarGridPresupuesto();
                    aplicarModoPresupuesto();
                    limpiarGridPresupuesto();
                }
            });
            $('input[name=ppa_Origen]').on('change', aplicarModoPresupuesto);
            $('#ppa_Pro_Cod').on('change', function () {
                limpiarGridPresupuesto();
                setPpaHint();
            });
        }
        $dlg.dialog('open');
    };
    window.selectPresupuestoRubro = function (data) {
        data = data || {};
        var label = data['Ppa_Label'] || ((data['Ppa_Cla'] || '') + ' - ' + (data['Pdp_Rubro'] || data['Ppa_Des'] || ''));
        var ruta = rutaTexto(data['Ppa_Ruta'] || '');
        $('#Ppa_Cod').val(data['Ppa_Cod'] || '');
        $('#Pdp_Cod').val(data['Pdp_Cod'] || '');
        $('#Ppa_Cla').val(data['Ppa_Cla'] || '');
        $('#Ppa_Des').val(data['Ppa_Des'] || '');
        $('#Ppa_Label').val(label).attr('title', ruta || label);
        $('#Ppa_Ruta').val(ruta);
        $('#Ppa_Ruta_Txt').text(ruta).attr('title', ruta);
        if ($('#ppaDialog').data('ui-dialog')) $('#ppaDialog').dialog('close');
    };
    $(function () {
        $('#ppaDialog').appendTo('body');
    });
})();
</script>
