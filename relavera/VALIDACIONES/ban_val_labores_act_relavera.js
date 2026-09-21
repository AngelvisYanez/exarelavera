var esMod = false;
var esCrear = false;
var arrayDetalle;
var arrayD = [];
/** Maestro-detalle registro actividades (solo ban_alt_labores_v2.php) */
var g_v2Lineas = [];
var g_v2SelectedEmpRowId = null;
var g_v2EmpPickMode = false;
/** Per_Cod/Personal por id de fila empleado (getRowData suele fallar en columnas ocultas) */
var g_v2EmpMeta = {};
/** Cabecera actividad cargada en pesta&ntilde;a Modificar (v2) */
var g_modActividadCab = null;
var g_modPerSel = null;
/** Maestro-detalle nativo en pesta&ntilde;a Modificar (mismo patr&oacute;n que registro v2) */
var g_modV2Lineas = [];
var g_modV2SelectedEmpRowId = null;
var g_modV2EmpPickMode = false;
/** B&uacute;squeda Modificar: elegir empleado para localizar actividad sin conocer finca/semana */
var g_modEmpLookupMode = false;
var g_modV2EmpMeta = {};
/** &Uacute;ltimo detalle cargado desde servidor (solo Modificar): restaurar solo este empleado sin cancelar lo dem&aacute;s. */
var g_modV2LineasBaseline = [];
/** Evita marcar fila empleado en amarillo durante repoblado autom&aacute;tico de labores. */
var g_v2ModLabDirtySuppress = 0;
/** Evita recursi&oacute;n al a&ntilde;adir fila borrador de labores (Registrar). */
var g_v2LabTrailSuppress = 0;

/** Copia profunda de filas de labor (objetos planos). */
function v2CloneLineasArray(arr) {
    var out = [];
    if (!arr || !arr.length) return out;
    var i;
    for (i = 0; i < arr.length; i++) {
        out.push($.extend(true, {}, arr[i]));
    }
    return out;
}

/** Botones de acci&oacute;n en grillas nativas (mismo estilo que maestro Labores-Fincas). */
function v2GridActionBtn(kind, title) {
    var isDel = kind === 'delete';
    return $('<button type="button"/>')
        .addClass('btn btn-xs v2-grid-act-btn')
        .addClass(isDel ? 'btn-danger v2-grid-act-del' : 'btn-info v2-grid-act-edit')
        .attr('title', title || (isDel ? 'Eliminar' : 'Modificar'))
        .append($('<span/>').addClass('glyphicon glyphicon-' + (isDel ? 'trash' : 'pencil')));
}

function v2EmpNativeRow$(rowid) {
    if (rowid == null || rowid === '') return $();
    var s = String(rowid);
    return $('#v2EmpTbody tr[data-v2-rid]').filter(function() {
        return String($(this).attr('data-v2-rid')) === s;
    });
}

function v2EmpGetRowIds() {
    var out = [];
    $('#v2EmpTbody tr[data-v2-rid]').each(function() {
        out.push(String($(this).attr('data-v2-rid')));
    });
    return out;
}

function v2EmpAllocRowId() {
    var max = 0;
    $('#v2EmpTbody tr[data-v2-rid]').each(function() {
        var n = parseInt($(this).attr('data-v2-rid'), 10);
        if (!isNaN(n) && n > max) max = n;
    });
    return String(max + 1);
}

function v2EmpRenumberRows() {
    $('#v2EmpTbody tr[data-v2-rid]').each(function(i) {
        $(this)
            .find('td.v2-col-idx')
            .text(i + 1);
    });
}

function v2EmpUpdateFooterCount() {
    var n = $('#v2EmpTbody tr[data-v2-rid]').length;
    var $st = $('#v2EmpStatus');
    if (!$st.length) return;
    if (n === 0) {
        $st.text('Sin empleados');
    } else {
        $st.text('Mostrando 1 - ' + n + ' de ' + n);
    }
    v2EmpUpdateGrandTotalLabores();
}

/** Suma la columna &laquo;Tot. Labores&raquo; de todos los empleados (pie izquierdo). */
function v2EmpUpdateGrandTotalLabores() {
    var sum = 0;
    $('#v2EmpTbody tr[data-v2-rid]').each(function() {
        var t = parseFloat($.trim($(this).find('.v2-emp-total-cell').first().text()), 10);
        if (!isNaN(t)) sum += t;
    });
    var $g = $('#v2EmpGrandTotal');
    if ($g.length) $g.text(sum.toFixed(2));
}

/** A&ntilde;ade fila empleado; devuelve el id de fila (data-v2-rid). */
function v2EmpAppendRow(perCod, personal, totalStr) {
    var nidStr = v2EmpAllocRowId();
    g_v2EmpMeta[nidStr] = {
        Per_Cod: String(perCod != null ? perCod : ''),
        Personal: personal != null ? String(personal) : ''
    };
    var $tr = $('<tr class="v2-emp-row"/>')
        .attr('data-v2-rid', nidStr)
        .attr('data-per-cod', String(perCod != null ? perCod : ''));
    $tr.append($('<td class="v2-col-idx text-center"/>'));
    $tr.append($('<td class="v2-emp-nombre"/>').text(personal != null ? personal : ''));
    $tr.append(
        $('<td class="v2-emp-total-cell text-right"/>').text(totalStr != null ? String(totalStr) : '0.00')
    );
    $tr.append(
        $('<td class="text-center"/>').append(
            v2GridActionBtn('edit', 'Ver / editar labores de este empleado').addClass('v2-btn-sel-emp')
        )
    );
    $tr.append(
        $('<td class="text-center"/>').append(
            v2GridActionBtn('delete', 'Quitar empleado y sus labores').addClass('v2-btn-quitar-emp')
        )
    );
    $('#v2EmpTbody').append($tr);
    v2EmpRenumberRows();
    v2EmpUpdateFooterCount();
    return nidStr;
}

function v2EmpClearAll() {
    $('#v2EmpTbody').empty();
    g_v2EmpMeta = {};
    v2EmpUpdateFooterCount();
    v2UpdateLaboresCaptionReg('');
}

function v2InitEmpNativeTable() {
    var $w = $('#tableEmpRegAct');
    if (!$w.length || !$('#v2EmpTbody').length) return;
    $w.off('click.v2empnat').on('click.v2empnat', '.v2-btn-sel-emp', function(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        var rid = $(this)
            .closest('tr[data-v2-rid]')
            .attr('data-v2-rid');
        if (rid) v2SwitchEmpleado(rid);
    });
    $w.off('click.v2quitar').on('click.v2quitar', '.v2-btn-quitar-emp', function(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        var rid = $(this)
            .closest('tr[data-v2-rid]')
            .attr('data-v2-rid');
        if (rid) v2QuitarEmpleadoPorDomRid(rid);
    });
    $('#btn_agr_emp')
        .off('click.v2agr')
        .on('click.v2agr', function() {
            v2AbrirBuscarEmpleado();
        });
    v2EmpUpdateFooterCount();
}

function v2LabIsNative() {
    return $('#regActividadV2').length > 0 && $('#v2LabTbody').length > 0;
}

function v2LabRow$(lid) {
    if (lid == null || lid === '') return $();
    var s = String(lid);
    return $('#v2LabTbody tr[data-v2-lid]').filter(function() {
        return String($(this).attr('data-v2-lid')) === s;
    });
}

function v2LabGetRowIds() {
    var out = [];
    $('#v2LabTbody tr[data-v2-lid]').each(function() {
        out.push(String($(this).attr('data-v2-lid')));
    });
    return out;
}

function v2LabRenumberRows() {
    $('#v2LabTbody tr[data-v2-lid]').each(function(i) {
        $(this)
            .find('td.v2-lab-idx')
            .text(i + 1);
    });
}

function v2LabUpdateFooterSum(sum) {
    var s = typeof sum === 'number' && !isNaN(sum) ? sum.toFixed(2) : '0.00';
    $('#regActividadV2 .v2-lab-foot-sum').text(s);
}

function v2LabUpdateStatusCount() {
    var n = $('#v2LabTbody tr[data-v2-lid]').length;
    var $st = $('#v2LabStatus');
    if (!$st.length) return;
    if (!n) {
        $st.text('Sin labores');
    } else {
        $st.text('Mostrando 1 - ' + n + ' de ' + n);
    }
}

function v2LabClear() {
    $('#v2LabTbody').empty();
    v2LabUpdateFooterSum(0);
    v2LabUpdateStatusCount();
}

function v2LabRowReadMerged(lid) {
    var $tr = v2LabRow$(lid);
    if (!$tr.length) return {};
    function vin(sel) {
        var $e = $tr.find(sel);
        if (!$e.length) return '';
        var v = $e.val();
        return v != null ? String(v) : '';
    }
    var lidStr = String(lid);
    return {
        index: lidStr,
        Lab_Des: vin('#' + lidStr + '_Lab_Des'),
        Lab_Cod: vin('#' + lidStr + '_Lab_Cod'),
        Tpg_Des: vin('#' + lidStr + '_Tpg_Des'),
        Det_Fec: vin('#' + lidStr + '_Det_Fec'),
        Det_Obs: vin('#' + lidStr + '_Det_Obs'),
        Lab_Val: vin('#' + lidStr + '_Lab_Val'),
        Det_Can: vin('#' + lidStr + '_Det_Can'),
        Total: vin('#' + lidStr + '_Total'),
        Per_Cod: vin('#' + lidStr + '_Per_Cod'),
        Personal: vin('#' + lidStr + '_Personal'),
        _v2_emp_rid: vin('#' + lidStr + '__v2_emp_rid')
    };
}

function v2LabApplyLaborFromDialog(rowId, row) {
    var lid = String(rowId);
    var $tr = v2LabRow$(lid);
    if (!$tr.length || !row) return;
    if (row.Lab_Des != null) $tr.find('#' + lid + '_Lab_Des').val(String(row.Lab_Des));
    if (row.Lab_Cod != null) $tr.find('#' + lid + '_Lab_Cod').val(String(row.Lab_Cod));
    if (row.Tpg_Des != null) $tr.find('#' + lid + '_Tpg_Des').val(String(row.Tpg_Des));
    if (row.Lab_Val != null) $tr.find('#' + lid + '_Lab_Val').val(String(row.Lab_Val));
    $tr.find('#' + lid + '_Det_Can').val('');
    $tr.find('#' + lid + '_Total').val('');
}

/** Asegura que el datepicker quede por encima de cabeceras sticky y paneles. */
function v2BumpDatepickerZIndex() {
    [0, 50, 150].forEach(function(ms) {
        setTimeout(function() {
            var $d = $('#ui-datepicker-div');
            if ($d.length) {
                $d.css('z-index', '30000');
            }
        }, ms);
    });
}

/** Aplica labor elegida (di&aacute;logo jqGrid o autocompletar) en fila nativa Registrar. */
function v2SelectLaborNativeReg(idNat, row) {
    if (!row) return false;
    if (!g_v2SelectedEmpRowId) {
        $.alert('Seleccione un empleado en la lista izquierda.', null, 'remove');
        return false;
    }
    var trabNat = v2GetEmpMeta(g_v2SelectedEmpRowId);
    if (trabNat['Per_Cod'] === '') {
        $.alert('Debe Seleccionar un trabajador previamente!<br/>Revise los datos.', null, 'remove');
        return false;
    }
    var parametro = '_Det_Can';
    row['Per_Cod'] = trabNat['Per_Cod'];
    row['Personal'] = trabNat['Personal'];
    row['_v2_emp_rid'] = v2EmpDomRowIdFromAny(g_v2SelectedEmpRowId);
    v2LabApplyLaborFromDialog(idNat, row);
    var $dfNat = $('#' + idNat + '_Det_Fec');
    if ($dfNat.length && !$dfNat.hasClass('hasDatepicker')) {
        $dfNat.createDatePickers({ clean: true });
    }
    var selNat = $('#frm_alt_actividad').find('#Pec_Cod').find('option:selected');
    $dfNat.dateLimits(selNat.data('inicio'), selNat.data('fin'));
    $dfNat.datepicker('option', 'beforeShow', function() {
        v2BumpDatepickerZIndex();
    });
    $('#laboresDialog').dialog('close');
    $('#tableActividad')
        .find('#' + idNat + parametro)
        .off('change.v2labSell')
        .on('change.v2labSell', function() {
            makeCalculation(0);
            v2RefreshTotalesEmpleados();
        })
        .trigger('change');
    v2EnsureTrailingEmptyLabRowReg();
    setTimeout(function() {
        $('#' + idNat + '_Det_Can').trigger('focus');
    }, 0);
    return false;
}

/** Aplica labor elegida en fila nativa Modificar. */
function v2SelectLaborNativeMod(idModNat, row) {
    if (!row) return false;
    if (!g_modV2SelectedEmpRowId) {
        $.alert('Seleccione un empleado en la lista izquierda.', null, 'remove');
        return false;
    }
    var trabMod = v2ModGetEmpMeta(g_modV2SelectedEmpRowId);
    if (trabMod.Per_Cod === '') {
        $.alert('Debe Seleccionar un trabajador previamente!<br/>Revise los datos.', null, 'remove');
        return false;
    }
    row.Per_Cod = trabMod.Per_Cod;
    row.Personal = trabMod.Personal;
    row._v2_emp_rid = v2ModEmpDomRowIdFromAny(g_modV2SelectedEmpRowId);
    v2ModLabApplyLaborFromDialog(idModNat, row);
    var $dfMod = $('#' + idModNat + '_Det_Fec');
    if ($dfMod.length && !$dfMod.hasClass('hasDatepicker')) {
        $dfMod.createDatePickers({ clean: true });
    }
    var selMod = $('#modq_Pec_Cod').find('option:selected');
    $dfMod
        .dateLimits(selMod.data('inicio'), selMod.data('fin'))
        .datepicker('option', 'beforeShow', function() {
            v2BumpDatepickerZIndex();
        });
    $('#laboresDialog').dialog('close');
    v2ModLabRow$(idModNat)
        .find('#' + idModNat + '_Det_Can')
        .off('change.v2modlabSell')
        .on('change.v2modlabSell', function() {
            v2ModMakeCalculation();
            v2ModRefreshTotalesEmpleados();
        })
        .trigger('change');
    v2ModMarkCurrentEmpLaboresDirty();
    v2ModEnsureTrailingEmptyLabRowMod();
    setTimeout(function() {
        $('#' + idModNat + '_Det_Can').trigger('focus');
    }, 0);
    return false;
}

/** URL del front actual (mismo patr&oacute;n que UrlSaveJson en jqgrid5.php). */
function v2LaborSuggestGetUrl() {
    if (typeof UrlSaveJson !== 'undefined' && UrlSaveJson) return UrlSaveJson;
    if (typeof $ !== 'undefined' && $.isFunction($.getUrlSaveJson)) return $.getUrlSaveJson();
    return '';
}

/** Cat&aacute;logo de labores en memoria (filtrado local = respuesta inmediata). */
var v2LaborCatalog = null;
var v2LaborCatalogLoad = null;
var v2LaborSuggestXhr = null;
var v2LaborSuggestAjaxCache = {};
var V2_LABOR_SUGGEST_MAX = 40;
/** Evita respuestas obsoletas del autocompletar (peticiones superpuestas). */
var v2LaborAcSourceSeq = 0;
var v2LaborAcOpenGen = 0;
var v2LaborAcSuppressUntil = 0;

/** Hay modal/di&aacute;logo del sistema visible (MENSAJE DEL SISTEMA, etc.). */
function v2LaborAcSystemModalVisible() {
    return (
        $('.ui-dialog:visible').length > 0 ||
        $('.ui-widget-overlay:visible').length > 0
    );
}

/** No abrir ni reabrir el men&uacute; mientras haya modal o supresi&oacute;n activa. */
function v2LaborAcShouldShow() {
    if (v2LaborAcSystemModalVisible()) return false;
    if (Date.now() < v2LaborAcSuppressUntil) return false;
    return true;
}

function v2LaborAcSuppressOpen(ms) {
    v2LaborAcOpenGen++;
    v2LaborAcSuppressUntil = Date.now() + (ms || 800);
    v2LaborAcCloseAll();
}

function v2LaborNormTerm(term) {
    return $.trim(term != null ? String(term) : '').toLowerCase();
}

function v2LaborDesOfRow(it) {
    if (!it) return '';
    if (it.Lab_Des != null) return String(it.Lab_Des);
    if (it.lab_des != null) return String(it.lab_des);
    return '';
}

function v2LaborSetCatalog(rows) {
    v2LaborCatalog = rows && rows.length ? rows.slice(0) : [];
    v2LaborSuggestAjaxCache = {};
}

function v2LaborFilterCatalog(term, max) {
    if (!v2LaborCatalog || !v2LaborCatalog.length) return [];
    max = max || V2_LABOR_SUGGEST_MAX;
    var t = v2LaborNormTerm(term);
    var out = [];
    var i, des;
    for (i = 0; i < v2LaborCatalog.length && out.length < max; i++) {
        des = v2LaborDesOfRow(v2LaborCatalog[i]);
        if (!t || des.toLowerCase().indexOf(t) >= 0) {
            out.push(v2LaborCatalog[i]);
        }
    }
    return out;
}

/** Precarga labores activas una vez (mismo endpoint que el di&aacute;logo de mantenimiento). */
function v2LaborEnsureCatalog(done, fail) {
    if (v2LaborCatalog !== null) {
        if ($.isFunction(done)) done(v2LaborCatalog);
        return;
    }
    if (v2LaborCatalogLoad) {
        v2LaborCatalogLoad.done(function() {
            if ($.isFunction(done)) done(v2LaborCatalog || []);
        });
        if ($.isFunction(fail)) v2LaborCatalogLoad.fail(fail);
        return;
    }
    var url = v2LaborSuggestGetUrl();
    if (!url) {
        if ($.isFunction(fail)) fail();
        return;
    }
    v2LaborCatalogLoad = $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        global: false,
        cache: true,
        data: { laborAjax: true }
    })
        .done(function(res) {
            v2LaborSetCatalog(res && res.success && res.listLab ? res.listLab : []);
            if ($.isFunction(done)) done(v2LaborCatalog);
            if (v2LaborAcShouldShow()) {
                $('.v2-lab-des-input').each(function() {
                    var $f = $(this);
                    if ($f.length && document.activeElement === this) {
                        v2LaborAcForceOpen($f, $.trim($f.val()));
                    }
                });
            }
        })
        .fail(function() {
            v2LaborCatalog = null;
            if ($.isFunction(fail)) fail();
        })
        .always(function() {
            v2LaborCatalogLoad = null;
        });
}

/**
 * Consulta sugerencias: filtra en memoria si hay cat&aacute;logo; si no, LIKE al servidor (con cach&eacute; y abort).
 */
function v2LaborSuggestQuery(term, done, fail) {
    if (v2LaborCatalog !== null) {
        if ($.isFunction(done)) {
            done({ success: true, listLab: v2LaborFilterCatalog(term, V2_LABOR_SUGGEST_MAX) });
        }
        return;
    }
    var norm = v2LaborNormTerm(term);
    if (v2LaborSuggestAjaxCache.hasOwnProperty(norm)) {
        if ($.isFunction(done)) done(v2LaborSuggestAjaxCache[norm]);
        return;
    }
    var url = v2LaborSuggestGetUrl();
    if (!url) {
        if ($.isFunction(fail)) fail();
        return;
    }
    if (v2LaborSuggestXhr && v2LaborSuggestXhr.readyState !== 4) {
        try {
            v2LaborSuggestXhr.abort();
        } catch (eAb) {}
    }
    v2LaborSuggestXhr = $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        global: false,
        cache: false,
        data: { laborSuggestAjax: true, q: term },
        success: function(res) {
            v2LaborSuggestAjaxCache[norm] = res;
            if ($.isFunction(done)) done(res);
        },
        error: function(xhr, status) {
            if (status === 'abort') {
                if ($.isFunction(fail)) fail({ aborted: true });
                return;
            }
            if ($.isFunction(fail)) fail();
        }
    });
}

/** Entrega filas al widget jQuery UI (siempre debe llamarse response una vez por b&uacute;squeda). */
function v2LaborAcDeliver(term, response) {
    var rows;
    if (v2LaborCatalog !== null) {
        rows = v2LaborFilterCatalog(term, V2_LABOR_SUGGEST_MAX);
        response($.map(rows, v2LaborSuggestMapRow));
        return;
    }
    v2LaborSuggestQuery(
        term,
        function(res) {
            if (!res || !res.success) {
                response([]);
                return;
            }
            response($.map(res.listLab || [], v2LaborSuggestMapRow));
        },
        function() {
            response([]);
        }
    );
}

function v2LaborAcSource(request, response) {
    var $in = $(this);
    if ($in.length && !v2LaborAcEmpReady($in)) {
        response([]);
        return;
    }
    var term = $.trim(request.term);
    if (v2LaborCatalog !== null) {
        v2LaborAcDeliver(term, response);
        return;
    }
    var responded = false;
    function replyOnce(rows) {
        if (responded) return;
        responded = true;
        if (!rows || !rows.length) {
            response([]);
            return;
        }
        response($.map(rows, v2LaborSuggestMapRow));
    }
    v2LaborEnsureCatalog(
        function() {
            replyOnce(v2LaborFilterCatalog(term, V2_LABOR_SUGGEST_MAX));
        },
        function() {
            replyOnce([]);
        }
    );
}

/**
 * Abre el men&uacute; de labores (clic, foco o Sel. empleado). No depende de search('') de jQuery UI.
 */
function v2LaborAcForceOpen($in, term) {
    if (!$in || !$in.length) return;
    if (!v2LaborAcEmpReady($in)) return;
    if (!v2LaborAcShouldShow()) {
        v2LaborAcCloseAll();
        return;
    }
    term = $.trim(term != null ? String(term) : '');
    var gen = ++v2LaborAcOpenGen;

    function paint(attempt) {
        attempt = attempt || 0;
        if (gen !== v2LaborAcOpenGen) return;
        if (!v2LaborAcShouldShow()) {
            v2LaborAcCloseAll();
            return;
        }
        if (!$in.length) return;
        var inst = $in.data('ui-autocomplete');
        if (!inst) {
            if (attempt < 8) {
                setTimeout(function() {
                    paint(attempt + 1);
                }, 50);
            }
            return;
        }
        if (v2LaborCatalog === null) return;
        var items = $.map(v2LaborFilterCatalog(term, V2_LABOR_SUGGEST_MAX), v2LaborSuggestMapRow);
        if (!items.length) return;

        inst.term = term;
        inst.cancelSearch = false;

        if ($.isFunction(inst._suggest)) {
            inst._suggest(items);
        } else if ($.isFunction(inst._response)) {
            inst._response(items);
        }

        var $widget = $in.autocomplete('widget');
        if ($widget && $widget.length) {
            $widget.addClass('v2-lab-ac-menu').css('display', 'block').show();
        }
        if ($.isFunction(inst._resizeMenu)) {
            inst._resizeMenu();
        }
        if ($.isFunction(inst._position)) {
            inst._position();
        }
        if ($.isFunction(inst._trigger)) {
            inst._trigger('open');
        }
        v2LaborAcOpenMenu($in);
    }

    function runOpens() {
        if (!v2LaborAcShouldShow()) return;
        paint(0);
        setTimeout(function() {
            if (gen === v2LaborAcOpenGen) paint(1);
        }, 60);
        setTimeout(function() {
            if (gen === v2LaborAcOpenGen) paint(2);
        }, 140);
    }

    if (v2LaborCatalog !== null) {
        runOpens();
        return;
    }
    v2LaborEnsureCatalog(function() {
        runOpens();
    });
}

function v2LaborAcShowMenu($in, term) {
    v2LaborAcForceOpen($in, term);
}

/** Activa input Labor: foco + apertura del men&uacute; (tras elegir empleado, etc.). */
function v2LaborAcActivateInput($in) {
    if (!$in || !$in.length) return;
    setTimeout(function() {
        if (!$in.length) return;
        try {
            if ($in[0] && typeof $in[0].focus === 'function') {
                $in[0].focus();
            }
        } catch (eF) {}
        v2LaborAcForceOpen($in, $.trim($in.val()));
    }, 40);
}

/** Fuerza b&uacute;squeda/autocompletar al escribir (filtra por texto). */
function v2LaborAcTriggerSearch($in) {
    if (!$in || !$in.length) return;
    if (!v2LaborAcEmpReady($in)) return;
    var term = $.trim($in.val());
    if (v2LaborCatalog !== null) {
        v2LaborAcForceOpen($in, term);
        return;
    }
    v2LaborEnsureCatalog(function() {
        v2LaborAcForceOpen($in, term);
    });
}

/* Por encima de cabeceras sticky (~6), por debajo de .ui-dialog (~100+). */
var V2_LABOR_AC_Z = 90;

/** Cierra todos los men&uacute;s de autocompletar de labor abiertos. */
function v2LaborAcCloseAll() {
    $('.v2-lab-des-input').each(function() {
        var $in = $(this);
        if (!$in.data('ui-autocomplete')) return;
        try {
            $in.autocomplete('close');
        } catch (eC) {}
    });
    $('.ui-autocomplete.v2-lab-ac-menu:visible').hide();
}

function v2LaborAcEmpReady($in) {
    if ($('#regActividadV2').length && $in.closest('#regActividadV2').length) {
        return !!g_v2SelectedEmpRowId;
    }
    if ($('#modRegActividadV2').length && $in.closest('#modRegActividadV2').length) {
        return !!g_modV2SelectedEmpRowId;
    }
    return true;
}

/** Evita que el listado de labores tape di&aacute;logos del sistema ($.alert, etc.). */
function v2LaborAcInitGlobalHandlers() {
    if (window._v2LaborAcHandlersInit) return;
    window._v2LaborAcHandlersInit = true;
    $(document).on('dialogopen.v2labac dialogfocus.v2labac', function() {
        v2LaborAcSuppressOpen(1200);
    });
    /* Clic o foco en cualquier campo Labor (incluye filas nuevas tras Sel. empleado). */
    $(document)
        .off('focusin.v2labacopen mousedown.v2labacopen', '.v2-lab-des-input')
        .on('focusin.v2labacopen mousedown.v2labacopen', '.v2-lab-des-input', function() {
            var $in = $(this);
            if (!v2LaborAcEmpReady($in)) return;
            if (!v2LaborAcShouldShow()) return;
            setTimeout(function() {
                if (!$in.length || !v2LaborAcShouldShow()) return;
                v2LaborAcForceOpen($in, $.trim($in.val()));
            }, 0);
        });
    if ($.isFunction($.alert) && !$.alert._v2LabAcPatch) {
        var _alertOrig = $.alert;
        $.alert = function(message, action, icon) {
            v2LaborAcSuppressOpen(1500);
            return _alertOrig.apply(this, arguments);
        };
        $.alert._v2LabAcPatch = true;
    }
}

function v2LaborAcOpenMenu($in) {
    if (!v2LaborAcShouldShow()) {
        v2LaborAcCloseAll();
        return;
    }
    var $w = $in.autocomplete('widget');
    $w.addClass('v2-lab-ac-menu');
    var iw = $in.outerWidth() || 200;
    var menuW = Math.min(Math.max(iw + 12, 260), 420);
    $w.css({
        minWidth: '0',
        width: menuW + 'px',
        maxWidth: '420px',
        boxSizing: 'border-box',
        zIndex: V2_LABOR_AC_Z
    });
}

function v2LaborAcBindFocusOpen($in, ns) {
    function onActivate() {
        if (!v2LaborAcEmpReady($in) || !v2LaborAcShouldShow()) {
            v2LaborAcCloseAll();
            return;
        }
        v2LaborAcForceOpen($in, $.trim($in.val()));
    }
    $in.on('focusin.' + ns + ' mousedown.' + ns + ' click.' + ns, onActivate);
}

/** Al escribir: reabre el men&uacute; aunque falle el disparo interno de jQuery UI. */
function v2LaborAcBindInputSearch($in, ns) {
    var timerKey = 'v2labAcTmr';
    $in.on('input.' + ns + 'search keyup.' + ns + 'search compositionend.' + ns + 'search', function(ev) {
        if (ev && (ev.key === 'Escape' || ev.keyCode === 27)) return;
        if (!v2LaborAcEmpReady($in)) return;
        var prev = $in.data(timerKey);
        if (prev) clearTimeout(prev);
        $in.data(
            timerKey,
            setTimeout(function() {
                v2LaborEnsureCatalog(function() {
                    v2LaborAcTriggerSearch($in);
                });
            }, 30)
        );
    });
}

/** Normaliza una fila del servidor para autocompletar (nombre y precio en columnas separadas). */
function v2LaborSuggestMapRow(it) {
    var des =
        it.Lab_Des != null
            ? String(it.Lab_Des)
            : it.lab_des != null
              ? String(it.lab_des)
              : '';
    var lvRaw = it.Lab_Val != null ? it.Lab_Val : it.lab_val != null ? it.lab_val : '';
    var lv = lvRaw !== '' && lvRaw != null ? String(lvRaw).replace(/^\s+|\s+$/g, '') : '';
    var puDisp = lv !== '' ? 'P.U. ' + lv : '-';
    return $.extend(true, {}, it, {
        label: des,
        value: des,
        _v2_nom: des,
        _v2_pu: puDisp
    });
}

/** Render de cada item: 2 columnas (labor | P. unitario), compatible jQuery UI 1.11. */
function v2LaborAcAttachTwoColRender($in) {
    var w = $in.data('ui-autocomplete');
    if (!w) return;
    w._renderItem = function(ul, item) {
        var nom = item._v2_nom != null ? String(item._v2_nom) : String(item.label || item.value || '');
        var pu = item._v2_pu != null ? String(item._v2_pu) : '';
        var $a = $('<a/>')
            .attr('href', '#')
            .addClass('v2-lab-ac-a')
            .css({ display: 'block', padding: '4px 10px', lineHeight: '1.35', outline: 'none' });
        var $row = $('<span/>').css({ display: 'table', width: '100%', tableLayout: 'fixed' });
        $row.append(
            $('<span/>').css({
                display: 'table-cell',
                width: '72%',
                verticalAlign: 'middle',
                paddingRight: '12px',
                overflow: 'hidden',
                textOverflow: 'ellipsis',
                whiteSpace: 'nowrap'
            }).text(nom)
        );
        $row.append(
            $('<span/>').css({
                display: 'table-cell',
                width: '28%',
                verticalAlign: 'middle',
                textAlign: 'right',
                whiteSpace: 'nowrap',
                fontWeight: '600',
                color: '#2c5282'
            }).text(pu)
        );
        $a.append($row);
        return $('<li/>')
            .addClass('v2-lab-ac-li')
            .data('ui-autocomplete-item', item)
            .data('item.autocomplete', item)
            .append($a)
            .appendTo(ul);
    };
}

function v2LabBindLaborAutocomplete(lid) {
    if (!v2LabIsNative() || !$.fn.autocomplete) return;
    var lidStr = String(lid);
    var $in = $('#' + lidStr + '_Lab_Des');
    if (!$in.length) return;
    try {
        if ($in.data('ui-autocomplete')) {
            $in.autocomplete('destroy');
        }
    } catch (e0) {}
    $in.off('.v2labac');
    $in.autocomplete({
        minLength: 0,
        delay: 0,
        autoFocus: true,
        appendTo: $('body'),
        position: { my: 'left top', at: 'left bottom', collision: 'flip' },
        open: function() {
            v2LaborAcOpenMenu($(this));
        },
        source: v2LaborAcSource,
        focus: function(ev) {
            ev.preventDefault();
        },
        select: function(ev, ui) {
            ev.preventDefault();
            if (!ui.item) return false;
            v2SelectLaborNativeReg(lidStr, $.extend({}, ui.item));
            return false;
        }
    });
    v2LaborAcAttachTwoColRender($in);
    v2LaborAcBindFocusOpen($in, 'v2labac');
    v2LaborAcBindInputSearch($in, 'v2labac');
    v2LaborEnsureCatalog();
    $in.on('blur.v2labac', function() {
        var t = $.trim($in.val());
        var tm = $in.data('v2labAcTmr');
        if (tm) clearTimeout(tm);
        if (t === '') {
            var $tr = v2LabRow$(lidStr);
            $tr.find('#' + lidStr + '_Lab_Cod').val('');
            $tr.find('#' + lidStr + '_Tpg_Des').val('');
            $tr.find('#' + lidStr + '_Lab_Val').val('');
            $tr.find('#' + lidStr + '_Det_Can').val('');
            $tr.find('#' + lidStr + '_Total').val('');
            makeCalculation(0);
            v2RefreshTotalesEmpleados();
            v2EnsureTrailingEmptyLabRowReg();
        }
    });
}

function v2ModBindLaborAutocomplete(lid) {
    if (!v2ModLabIsNative() || !$.fn.autocomplete) return;
    var lidStr = String(lid);
    var $in = $('#' + lidStr + '_Lab_Des');
    if (!$in.length) return;
    try {
        if ($in.data('ui-autocomplete')) {
            $in.autocomplete('destroy');
        }
    } catch (e1) {}
    $in.off('.v2modlabac');
    $in.autocomplete({
        minLength: 0,
        delay: 0,
        autoFocus: true,
        appendTo: $('body'),
        position: { my: 'left top', at: 'left bottom', collision: 'flip' },
        open: function() {
            v2LaborAcOpenMenu($(this));
        },
        source: v2LaborAcSource,
        focus: function(ev) {
            ev.preventDefault();
        },
        select: function(ev, ui) {
            ev.preventDefault();
            if (!ui.item) return false;
            v2SelectLaborNativeMod(lidStr, $.extend({}, ui.item));
            return false;
        }
    });
    v2LaborAcAttachTwoColRender($in);
    v2LaborAcBindFocusOpen($in, 'v2modlabac');
    v2LaborAcBindInputSearch($in, 'v2modlabac');
    v2LaborEnsureCatalog();
    $in.on('blur.v2modlabac', function() {
        var t = $.trim($in.val());
        var tm = $in.data('v2labAcTmr');
        if (tm) clearTimeout(tm);
        if (t === '') {
            var $tr = v2ModLabRow$(lidStr);
            $tr.find('#' + lidStr + '_Lab_Cod').val('');
            $tr.find('#' + lidStr + '_Tpg_Des').val('');
            $tr.find('#' + lidStr + '_Lab_Val').val('');
            $tr.find('#' + lidStr + '_Det_Can').val('');
            $tr.find('#' + lidStr + '_Total').val('');
            v2ModMakeCalculation();
            v2ModRefreshTotalesEmpleados();
            v2ModMarkCurrentEmpLaboresDirty();
            v2ModEnsureTrailingEmptyLabRowMod();
        }
    });
}

function v2LabBindRowEvents(lid) {
    var $tr = v2LabRow$(lid);
    if (!$tr.length) return;
    var sel_fecha = $('#frm_alt_actividad')
        .find('#Pec_Cod')
        .find('option:selected');
    $('#' + lid + '_Det_Fec').createDatePickers({ clean: true });
    $('#' + lid + '_Det_Fec')
        .dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'))
        .datepicker('option', 'beforeShow', function() {
            v2BumpDatepickerZIndex();
        })
        .datepicker('option', 'onSelect', function() {
            v2EnsureTrailingEmptyLabRowReg();
        });
    $tr.find('#' + lid + '_Det_Can')
        .off('change.v2labdc')
        .on('change.v2labdc', function() {
            var vlInput = validaDecimal($tr.find('#' + lid + '_Det_Can').val());
            if (vlInput) {
                $.alert('El valor de la cantidad debe ser mayor que 0');
            }
            makeCalculation(0);
            v2RefreshTotalesEmpleados();
            if (vlInput) {
                $tr.find('#' + lid + '_Det_Can').focus();
            }
            v2EnsureTrailingEmptyLabRowReg();
        });
    $tr.find('#' + lid + '_Det_Obs')
        .off('input.v2labtrail change.v2labtrail')
        .on('input.v2labtrail change.v2labtrail', function() {
            v2EnsureTrailingEmptyLabRowReg();
        });
    v2LabBindLaborAutocomplete(lid);
}

function v2LabAppendRow(lidStr, rec) {
    var lid = String(lidStr);
    rec = rec || {};
    var empRid = v2EmpDomRowIdFromAny(g_v2SelectedEmpRowId || rec._v2_emp_rid || '');
    var $tr = $('<tr class="v2-lab-row"/>').attr('data-v2-lid', lid);
    if (rec._v2_emp_rid != null && String(rec._v2_emp_rid) !== '') {
        $tr.attr('data-v2-emp-lrid', String(rec._v2_emp_rid));
    }

    $tr.append($('<td class="text-center v2-lab-idx"/>').text(''));

    var $labCell = $('<td/>');
    $labCell.append(
        $('<input type="text" class="form-control input-xs v2-lab-des-input" autocomplete="off"/>')
            .attr({ id: lid + '_Lab_Des', name: lid + '_Lab_Des', placeholder: 'Escriba para buscar labor...' })
            .val(rec.Lab_Des != null ? String(rec.Lab_Des) : '')
    );
    $labCell.append(
        $('<input type="hidden"/>')
            .attr({ id: lid + '_index', name: lid + '_index' })
            .val(lid)
    );
    $labCell.append(
        $('<input type="hidden"/>')
            .attr({ id: lid + '_Lab_Cod', name: lid + '_Lab_Cod' })
            .val(rec.Lab_Cod != null ? String(rec.Lab_Cod) : '')
    );
    $labCell.append(
        $('<input type="hidden"/>')
            .attr({ id: lid + '_Per_Cod', name: lid + '_Per_Cod' })
            .val(rec.Per_Cod != null ? String(rec.Per_Cod) : '')
    );
    $labCell.append(
        $('<input type="hidden"/>')
            .attr({ id: lid + '_Personal', name: lid + '_Personal' })
            .val(rec.Personal != null ? String(rec.Personal) : '')
    );
    $labCell.append(
        $('<input type="hidden"/>')
            .attr({ id: lid + '__v2_emp_rid', name: lid + '__v2_emp_rid' })
            .val(
                rec._v2_emp_rid != null && String(rec._v2_emp_rid) !== ''
                    ? String(rec._v2_emp_rid)
                    : empRid || ''
            )
    );
    $tr.append($labCell);

    $tr.append(
        $('<td/>').append(
            $('<input type="text" class="form-control input-xs"/>')
                .attr({ id: lid + '_Tpg_Des', name: lid + '_Tpg_Des', readonly: 'readonly', tabindex: -1 })
                .val(rec.Tpg_Des != null ? String(rec.Tpg_Des) : '')
        )
    );
    $tr.append(
        $('<td/>').append(
            $('<input type="text" class="form-control input-xs datepickers"/>')
                .attr({ id: lid + '_Det_Fec', name: lid + '_Det_Fec' })
                .val(
                    (function() {
                        var raw = rec.Det_Fec != null ? String(rec.Det_Fec).replace(/^\s+|\s+$/g, '') : '';
                        return raw !== '' ? v2DetFecToInput(rec.Det_Fec) : v2GetActFecRegVal();
                    })()
                )
        )
    );
    $tr.append(
        $('<td/>').append(
            $('<input type="text" class="form-control input-xs"/>')
                .attr({ id: lid + '_Det_Obs', name: lid + '_Det_Obs' })
                .val(rec.Det_Obs != null ? String(rec.Det_Obs) : '')
        )
    );
    $tr.append(
        $('<td/>').append(
            $('<input type="text" class="form-control input-xs"/>')
                .attr({ id: lid + '_Lab_Val', name: lid + '_Lab_Val', readonly: 'readonly', tabindex: -1 })
                .val(rec.Lab_Val != null ? String(rec.Lab_Val) : '')
        )
    );
    $tr.append(
        $('<td/>').append(
            $('<input type="text" class="form-control input-xs"/>')
                .attr({ id: lid + '_Det_Can', name: lid + '_Det_Can' })
                .val(rec.Det_Can != null ? String(rec.Det_Can) : '')
        )
    );
    $tr.append(
        $('<td class="text-right"/>').append(
            $('<input type="text" class="form-control input-xs text-right"/>')
                .attr({ id: lid + '_Total', name: lid + '_Total', readonly: 'readonly', tabindex: -1 })
                .val(rec.Total != null ? String(rec.Total) : '')
        )
    );
    $tr.append(
        $('<td class="text-center"/>').append(
            v2GridActionBtn('delete', 'Eliminar labor').addClass('v2-btn-quitar-lab').attr('tabindex', -1)
        )
    );

    $('#v2LabTbody').append($tr);
    v2LabRenumberRows();
    v2LabBindRowEvents(lid);
    $tr.find('#' + lid + '_Det_Can').trigger('change');
    v2LabUpdateStatusCount();
    return $tr;
}

function v2InitLabNativeTable() {
    var $w = $('#tableActividad');
    if (!$w.length || !$('#v2LabTbody').length) return;
    $w.off('click.v2labq').on('click.v2labq', '.v2-btn-quitar-lab', function(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        var lid = $(this)
            .closest('tr[data-v2-lid]')
            .attr('data-v2-lid');
        if (lid) {
            quitarActividad({ id: lid });
        }
    });
    $('#btn_agr')
        .off('click.v2labagr')
        .on('click.v2labagr', function(e) {
            e.preventDefault();
            v2AddLaborFila();
        });
    v2LabUpdateStatusCount();
}

/** Id de fila empleado (tabla nativa: data-v2-rid en &lt;tr&gt;). */
function v2EmpDomRowIdFromAny(rowid) {
    if (rowid == null || rowid === '') return String(rowid || '');
    var s = String(rowid);
    if (!$('#v2EmpTbody').length) return s;
    var $tr = v2EmpNativeRow$(s);
    if ($tr.length) return String($tr.attr('data-v2-rid'));
    return s;
}

/** Corrige _v2_emp_rid en memoria si qued&oacute; desalineado (sesiones anteriores); solo si el Per_Cod coincide con un &uacute;nico empleado en la grilla. */
function v2NormalizarRidsLineasEmp() {
    if (!$('#tableEmpRegAct').length) return;
    var ids = v2EmpGetRowIds();
    if (!ids || !ids.length) return;
    g_v2Lineas.forEach(function(l) {
        if (v2LaborLineaVacia(l)) return;
        var leRaw = l._v2_emp_rid != null ? String(v2CleanVal(l._v2_emp_rid)) : '';
        if (leRaw === '') return;
        var le = v2EmpDomRowIdFromAny(leRaw);
        var ok = false;
        var i;
        for (i = 0; i < ids.length; i++) {
            if (String(ids[i]) === le) {
                ok = true;
                break;
            }
        }
        if (ok) {
            l._v2_emp_rid = le;
            return;
        }
        var pc = v2CleanVal(l.Per_Cod);
        if (!pc) return;
        var hit = [];
        for (i = 0; i < ids.length; i++) {
            if (v2CleanVal(v2GetEmpMeta(ids[i]).Per_Cod) === pc) hit.push(String(ids[i]));
        }
        if (hit.length === 1) l._v2_emp_rid = hit[0];
    });
}

function v2GetEmpMeta(rowid) {
    if (rowid == null || rowid === '') return { Per_Cod: '', Personal: '' };
    var sid = v2EmpDomRowIdFromAny(rowid);
    var m = g_v2EmpMeta[sid] || g_v2EmpMeta[String(rowid)];
    var pc = '';
    var nom = '';
    if (m) {
        pc = m.Per_Cod != null ? String(m.Per_Cod) : '';
        nom = m.Personal != null ? String(m.Personal) : '';
    }
    var $tr = v2EmpNativeRow$(sid);
    if ($tr.length) {
        if (!pc) pc = v2CleanVal($tr.attr('data-per-cod'));
        if (!nom) nom = v2CleanVal($tr.find('.v2-emp-nombre').text());
    }
    return { Per_Cod: pc, Personal: nom };
}

/** Texto limpio desde getRowData (evita HTML en celdas ocultas) */
function v2CleanVal(v) {
    if (v == null || v === '') return '';
    var s = String(v);
    if (/<[^>]+>/.test(s)) {
        var tmp = $('<div/>');
        tmp.html(s);
        s = tmp.text() || s.replace(/<[^>]*>/g, '');
    }
    return $.trim(s);
}

/** Normaliza fecha de detalle (SQL/ISO) a yyyy-mm-dd para el input del datepicker. */
function v2DetFecToInput(v) {
    if (v == null || v === '') return '';
    var t = String(v).trim();
    var m = t.match(/^(\d{4}-\d{2}-\d{2})/);
    if (m) return m[1];
    return t.length > 10 ? t.substring(0, 10) : t;
}

/** Fecha de cabecera en Registrar v2 (yyyy-mm-dd). */
function v2GetActFecRegVal() {
    var $f = $('#frm_alt_actividad #Act_Fec');
    if (!$f.length) return '';
    return v2DetFecToInput($f.val());
}

/** T&iacute;tulo de la tabla de labores (Registrar): muestra el nombre del trabajador. */
function v2UpdateLaboresCaptionReg(nombre) {
    var $cap = $('#tableActividad .v2-lab-caption');
    if (!$cap.length) return;
    var nom = nombre != null && String(nombre).replace(/\s+/g, '') !== '' ? v2CleanVal(nombre) : '';
    $cap.text(nom ? 'LABORES DEL EMPLEADO ' + nom : 'LABORES DEL EMPLEADO SELECCIONADO');
}

/** T&iacute;tulo de la tabla de labores (Modificar): muestra el nombre del trabajador. */
function v2UpdateLaboresCaptionMod(nombre) {
    var $cap = $('#modTableActividad .v2-lab-caption');
    if (!$cap.length) return;
    var nom = nombre != null && String(nombre).replace(/\s+/g, '') !== '' ? v2CleanVal(nombre) : '';
    $cap.text(nom ? 'LABORES DEL EMPLEADO ' + nom : 'LABORES DEL EMPLEADO SELECCIONADO');
}

/**
 * Enfoca el campo Labor: prioriza la fila borrador vac&iacute;a (de abajo hacia arriba).
 * @param {function} getRowIds devuelve array de ids de fila (p. ej. v2LabGetRowIds)
 */
function v2FocusLaborInputInGrid(getRowIds) {
    if (!$.isFunction(getRowIds)) return;
    var ids = getRowIds();
    if (!ids || !ids.length) return;
    var focusLid = null;
    var i, lid, t;
    for (i = ids.length - 1; i >= 0; i--) {
        lid = ids[i];
        t = $.trim($('#' + lid + '_Lab_Des').val());
        if (t === '') {
            focusLid = lid;
            break;
        }
    }
    if (!focusLid) focusLid = ids[ids.length - 1];
    var $lab = $('#' + focusLid + '_Lab_Des');
    if (!$lab.length) return;
    v2LaborAcActivateInput($lab);
}

/** Ajusta el scroll del panel de empleados (.v2-emp-scroll) para que la fila sea visible. */
function v2ScrollNativeEmpRowIntoView($tr) {
    if (!$tr || !$tr.length) return;
    var el = $tr[0];
    setTimeout(function() {
        if (el && typeof el.scrollIntoView === 'function') {
            el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }, 0);
}

/** L&iacute;nea de labor asociada a la fila de empleado (rowid jqGrid); sin _v2_emp_rid se usa Per_Cod (datos antiguos). */
function v2LineBelongsToEmp(l, empGridRid) {
    var er = v2EmpDomRowIdFromAny(empGridRid);
    var le = l._v2_emp_rid != null ? String(v2CleanVal(l._v2_emp_rid)) : '';
    if (le !== '') return v2EmpDomRowIdFromAny(le) === er;
    var meta = v2GetEmpMeta(er);
    var p = String(meta.Per_Cod || '');
    return p !== '' && String(v2CleanVal(l.Per_Cod)) === p;
}

/** Fila de labor sin datos &uacute;tiles: no se persiste al cambiar de empleado ni se repuebla. */
function v2LaborLineaVacia(rda) {
    if (!rda) return true;
    var ld = v2CleanVal(rda.Lab_Des);
    var lc = v2CleanVal(rda.Lab_Cod);
    var tv = v2CleanVal(rda.Total);
    var t = parseFloat(tv);
    if (!isNaN(t) && t > 0) return false;
    if (lc !== '' || ld !== '') return false;
    return true;
}

/** Une getRowData con inputs en edici&oacute;n (getRowData a menudo devuelve celdas vac&iacute;as en columnas formatter). */
function v2ActividadRowMerged($g, rid) {
    if ($g && $g.length && $g.attr('id') === 'tableActividad' && v2LabIsNative()) {
        return v2LabRowReadMerged(rid);
    }
    var idName = $g.pk() || 'index';
    var rda = $.extend(true, {}, $g.jqGrid('getRowData', rid));
    if (!$.vv(rda[idName])) rda[idName] = rid;
    var jqId =
        typeof $ !== 'undefined' && $.jgrid && $.jgrid.jqID ? $.jgrid.jqID(String(rid)) : String(rid).replace(/([ #;?%&,.+*~\':"!^$[\]()=>|\/@])/g, '\\$1');
    var $tr = v2DetailRow$($g, rid);
    function pick(col, suffix) {
        var suf = suffix != null ? suffix : '_' + col;
        var $el = $('#' + jqId + suf);
        if (!$el.length && $tr.length) {
            $el = $tr.find('[id$="' + suf + '"]');
        }
        if ($el.length && ($el.is('input') || $el.is('select') || $el.is('textarea'))) {
            var v = $el.val();
            if (v != null && String(v) !== '') rda[col] = v;
        }
    }
    pick('Lab_Des', '_Lab_Des');
    pick('Lab_Cod', '_Lab_Cod');
    pick('Tpg_Des', '_Tpg_Des');
    pick('Det_Fec', '_Det_Fec');
    pick('Det_Obs', '_Det_Obs');
    pick('Lab_Val', '_Lab_Val');
    pick('Det_Can', '_Det_Can');
    pick('Total', '_Total');
    pick('Per_Cod', '_Per_Cod');
    pick('Personal', '_Personal');
    pick('_v2_emp_rid', '_v2_emp_rid');
    return rda;
}

function v2DetailRow$($grid, rowid) {
    return v2EmpRow$($grid, rowid);
}

function v2NextLineIndex() {
    var max = 0;
    var n;
    g_v2Lineas.forEach(function(l) {
        n = parseInt(l.index, 10);
        if (!isNaN(n) && n > max) max = n;
    });
    if (v2LabIsNative()) {
        v2LabGetRowIds().forEach(function(id) {
            n = parseInt(id, 10);
            if (!isNaN(n) && n > max) max = n;
        });
    } else {
        $('#tableActividad').jqGrid('getDataIDs').forEach(function(id) {
            n = parseInt(id, 10);
            if (!isNaN(n) && n > max) max = n;
        });
    }
    return max + 1;
}

/** Mantiene exactamente una fila borrador vac&iacute;a al final (Registrar v2). */
function v2EnsureTrailingEmptyLabRowReg() {
    if (!v2LabIsNative() || !g_v2SelectedEmpRowId) return;
    if (g_v2LabTrailSuppress > 0) return;
    var ids = v2LabGetRowIds();
    while (ids.length >= 2) {
        var p = v2LabRowReadMerged(ids[ids.length - 2]);
        var q = v2LabRowReadMerged(ids[ids.length - 1]);
        if (v2LaborLineaVacia(p) && v2LaborLineaVacia(q)) {
            v2LabRow$(ids[ids.length - 1]).remove();
            v2LabRenumberRows();
            v2LabUpdateStatusCount();
            ids = v2LabGetRowIds();
        } else {
            break;
        }
    }
    var meta = v2GetEmpMeta(g_v2SelectedEmpRowId);
    var empRid = v2EmpDomRowIdFromAny(g_v2SelectedEmpRowId);
    var blankRec = {
        index: '',
        _v2_emp_rid: empRid,
        Per_Cod: meta.Per_Cod,
        Personal: meta.Personal,
        Lab_Cod: '',
        Lab_Des: '',
        Tpg_Des: '',
        Det_Fec: v2GetActFecRegVal(),
        Det_Obs: '',
        Lab_Val: '',
        Det_Can: '',
        Total: ''
    };
    if (!ids.length) {
        g_v2LabTrailSuppress++;
        try {
            var nid0 = String(v2NextLineIndex());
            v2LabAppendRow(nid0, $.extend({}, blankRec, { index: nid0 }));
        } finally {
            g_v2LabTrailSuppress--;
        }
        makeCalculation(0);
        v2RefreshTotalesEmpleados();
        return;
    }
    var lastId = ids[ids.length - 1];
    if (!v2LaborLineaVacia(v2LabRowReadMerged(lastId))) {
        g_v2LabTrailSuppress++;
        try {
            var nid1 = String(v2NextLineIndex());
            v2LabAppendRow(nid1, $.extend({}, blankRec, { index: nid1 }));
        } finally {
            g_v2LabTrailSuppress--;
        }
        makeCalculation(0);
        v2RefreshTotalesEmpleados();
    }
}

/** Mantiene una fila borrador vac&iacute;a al final (Modificar v2). */
function v2ModEnsureTrailingEmptyLabRowMod() {
    if (!v2ModLabIsNative() || !g_modV2SelectedEmpRowId) return;
    if (g_v2ModLabDirtySuppress > 0) return;
    var ids = v2ModLabGetRowIds();
    while (ids.length >= 2) {
        var p = v2ModLabRowReadMerged(ids[ids.length - 2]);
        var q = v2ModLabRowReadMerged(ids[ids.length - 1]);
        if (v2LaborLineaVacia(p) && v2LaborLineaVacia(q)) {
            v2ModLabRow$(ids[ids.length - 1]).remove();
            v2ModLabRenumberRows();
            v2ModLabUpdateStatusCount();
            ids = v2ModLabGetRowIds();
        } else {
            break;
        }
    }
    var meta = v2ModGetEmpMeta(g_modV2SelectedEmpRowId);
    var empRid = v2ModEmpDomRowIdFromAny(g_modV2SelectedEmpRowId);
    var blankRec = {
        index: '',
        _v2_emp_rid: empRid,
        Per_Cod: meta.Per_Cod,
        Personal: meta.Personal,
        Lab_Cod: '',
        Lab_Des: '',
        Tpg_Des: '',
        Det_Fec: v2ModGetUltimaDetFecEmpleado(empRid),
        Det_Obs: '',
        Lab_Val: '',
        Det_Can: '',
        Total: ''
    };
    if (!ids.length) {
        g_v2ModLabDirtySuppress++;
        try {
            var mid0 = String(v2ModNextLineIndex());
            v2ModLabAppendRow(mid0, $.extend({}, blankRec, { index: mid0 }));
        } finally {
            g_v2ModLabDirtySuppress--;
        }
        v2ModMakeCalculation();
        return;
    }
    var lastId = ids[ids.length - 1];
    if (!v2LaborLineaVacia(v2ModLabRowReadMerged(lastId))) {
        g_v2ModLabDirtySuppress++;
        try {
            var mid1 = String(v2ModNextLineIndex());
            v2ModLabAppendRow(mid1, $.extend({}, blankRec, { index: mid1 }));
        } finally {
            g_v2ModLabDirtySuppress--;
        }
        v2ModMakeCalculation();
    }
}

function v2SyncCurrentDetailToStore() {
    if (!$('#regActividadV2').length || !g_v2SelectedEmpRowId) return;
    var empRid = v2EmpDomRowIdFromAny(g_v2SelectedEmpRowId);
    var meta = v2GetEmpMeta(g_v2SelectedEmpRowId);
    var perCod = meta.Per_Cod;
    var personal = meta.Personal;
    var $g = $('#tableActividad');
    if ($g[0] && $g[0].grid && $.isFunction($g.stopGridEdit)) {
        $g.stopGridEdit();
    }
    var batch = [];
    if (v2LabIsNative()) {
        v2LabGetRowIds().forEach(function(rid) {
            var rda = v2LabRowReadMerged(rid);
            if (v2LaborLineaVacia(rda)) return;
            rda._v2_emp_rid = empRid;
            if (perCod) {
                rda.Per_Cod = perCod;
                rda.Personal = personal;
            }
            batch.push(rda);
        });
    } else {
        $g.jqGrid('getDataIDs').forEach(function(rid) {
            var rda = v2ActividadRowMerged($g, rid);
            if (v2LaborLineaVacia(rda)) return;
            rda._v2_emp_rid = empRid;
            if (perCod) {
                rda.Per_Cod = perCod;
                rda.Personal = personal;
            }
            batch.push(rda);
        });
    }
    g_v2Lineas = g_v2Lineas.filter(function(l) {
        var le = l._v2_emp_rid != null ? String(v2CleanVal(l._v2_emp_rid)) : '';
        if (le !== '') return v2EmpDomRowIdFromAny(le) !== empRid;
        if (perCod) return String(v2CleanVal(l.Per_Cod)) !== String(perCod);
        return true;
    });
    batch.forEach(function(b) {
        g_v2Lineas.push(b);
    });
}

function v2RefreshTotalesEmpleados() {
    if (!$('#regActividadV2').length) return;
    var selRid = g_v2SelectedEmpRowId ? v2EmpDomRowIdFromAny(g_v2SelectedEmpRowId) : '';
    v2EmpGetRowIds().forEach(function(rid) {
        var er = v2EmpDomRowIdFromAny(rid);
        var sum = 0;
        if (selRid && er === selRid) {
            if (v2LabIsNative()) {
                v2LabGetRowIds().forEach(function(lrid) {
                    var tv = $('#' + lrid + '_Total').val();
                    var t = parseFloat(tv);
                    if (isNaN(t) || t <= 0) return;
                    sum += t;
                });
            } else {
                $('#tableActividad').jqGrid('getDataIDs').forEach(function(lrid) {
                    var tv = $('#' + lrid + '_Total').val();
                    if (tv === undefined || tv === '') tv = $('#tableActividad').jqGrid('getCell', lrid, 'Total');
                    var t = parseFloat(tv);
                    if (isNaN(t) || t <= 0) return;
                    sum += t;
                });
            }
        } else {
            g_v2Lineas.forEach(function(l) {
                if (v2LaborLineaVacia(l)) return;
                if (!v2LineBelongsToEmp(l, er)) return;
                var t = parseFloat(l.Total);
                if (!isNaN(t)) sum += t;
            });
        }
        v2EmpNativeRow$(er)
            .find('.v2-emp-total-cell')
            .text(sum.toFixed(2));
    });
    v2EmpUpdateGrandTotalLabores();
    if (g_v2SelectedEmpRowId) {
        setTimeout(function() {
            v2ApplyEmpHighlight(g_v2SelectedEmpRowId);
            setTimeout(function() {
                v2StripEmpGridFondoFilas();
            }, 0);
        }, 0);
    } else {
        setTimeout(function() {
            v2StripEmpGridFondoFilas();
        }, 0);
    }
}

function v2EmpRow$($grid, rowid) {
    if (rowid == null || rowid === '') return $();
    var s = String(rowid);
    if ($grid && $grid.length && $grid.attr('id') === 'tableActividad' && $('#v2LabTbody').length) {
        return v2LabRow$(s);
    }
    if ($grid && $grid.length && $grid.attr('id') === 'tableEmpRegAct' && $('#v2EmpTbody').length) {
        return v2EmpNativeRow$(s);
    }
    var $r = $grid.find('tr.jqgrow[id="' + s.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"]');
    if ($r.length) return $r;
    return $grid.find('tr.jqgrow').filter(function() {
        return String(this.id) === s;
    });
}

function v2ApplyEmpHighlight(rowid) {
    $('#v2EmpTbody tr.v2-emp-row').removeClass('v2-emp-sel');
    if (rowid == null || rowid === '') return;
    var canon = v2EmpDomRowIdFromAny(rowid);
    v2EmpNativeRow$(canon).addClass('v2-emp-sel');
}

/** Reservado: la lista de empleados ya no usa jqGrid. */
function v2StripEmpGridFondoFilas() {}

function v2PoblarDetalleDesdeLineas(empGridRid) {
    var er = v2EmpDomRowIdFromAny(empGridRid);
    var $this = $('#tableActividad');
    var lines = g_v2Lineas.filter(function(l) {
        return v2LineBelongsToEmp(l, er) && !v2LaborLineaVacia(l);
    });
    if (v2LabIsNative()) {
        g_v2LabTrailSuppress++;
        try {
            v2LabClear();
            lines.forEach(function(rec) {
                if (!rec._v2_emp_rid) rec._v2_emp_rid = er;
                var id =
                    rec.index != null && String(rec.index) !== ''
                        ? String(rec.index)
                        : String(v2NextLineIndex());
                v2LabAppendRow(id, rec);
            });
            makeCalculation(0);
        } finally {
            g_v2LabTrailSuppress--;
        }
        v2EnsureTrailingEmptyLabRowReg();
        return;
    }
    $this.clearGrid();
    var sel_fecha = $('#frm_alt_actividad').find('#Pec_Cod').find('option:selected');
    var idName = $this.pk() || 'index';
    var gridRowIds = [];
    lines.forEach(function(rec) {
        if (!rec._v2_emp_rid) rec._v2_emp_rid = er;
        var id = rec[idName] != null && String(rec[idName]) !== '' ? rec[idName] : rec.index;
        gridRowIds.push(id);
        $this.jqGrid('addRowData', id, rec);
        $this.jqGrid('editRow', id);
        $.createDatePickers('#' + id + '_Det_Fec');
        $('#' + id + '_Det_Fec').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
        var $tr = v2DetailRow$($this, id);
        $tr.find('#' + id + '_Det_Can').on('change', function() {
            var vlInput = validaDecimal($tr.find('#' + id + '_Det_Can').val());
            if (vlInput) {
                $.alert('El valor de la cantidad debe ser mayor que 0');
            }
            makeCalculation(0);
            v2RefreshTotalesEmpleados();
            if (vlInput) {
                $tr.find('#' + id + '_Det_Can').focus();
            }
        }).trigger('change');
    });
    if (gridRowIds.length) {
        var lastGridId = gridRowIds[gridRowIds.length - 1];
        setTimeout(function() {
            $('#' + lastGridId + '_Lab_Des').trigger('focus');
        }, 0);
    }
}

function v2SwitchEmpleado(rowid) {
    if (!$('#regActividadV2').length) return;
    v2NormalizarRidsLineasEmp();
    var next = v2EmpDomRowIdFromAny(rowid);
    /** Siempre volcar el detalle actual a g_v2Lineas antes de repoblar (tambi&eacute;n al pulsar Sel. otra vez en la misma fila). */
    if (g_v2SelectedEmpRowId) {
        v2SyncCurrentDetailToStore();
    }
    g_v2SelectedEmpRowId = next;
    v2PoblarDetalleDesdeLineas(g_v2SelectedEmpRowId);
    v2ApplyEmpHighlight(g_v2SelectedEmpRowId);
    v2RefreshTotalesEmpleados();
    v2UpdateLaboresCaptionReg(v2GetEmpMeta(next).Personal);
    v2ScrollNativeEmpRowIntoView(v2EmpNativeRow$(next));
    v2StripEmpGridFondoFilas();
    if (v2LabIsNative()) {
        v2FocusLaborInputInGrid(v2LabGetRowIds);
    }
}

function v2AbrirBuscarEmpleado() {
    if ($('#regActividadV2').length && !v2RegRequiereFincaSemana()) return;
    g_modEmpLookupMode = false;
    g_v2EmpPickMode = true;
    $('#personalDialog').dialog('open');
}

/** Fecha local YYYY-MM-DD */
function v2FormatYMD(d) {
    if (!(d instanceof Date) || isNaN(d.getTime())) return '';
    var y = d.getFullYear(),
        m = d.getMonth() + 1,
        day = d.getDate();
    return y + '-' + (m < 10 ? '0' : '') + m + '-' + (day < 10 ? '0' : '') + day;
}

/**
 * Rango de la semana N dentro del per&iacute;odo (N=1 desde Pec_Fei, bloques de 7 d&iacute;as, tope Pec_Fef).
 * @param {jQuery} $pecSel select de per&iacute;odo
 * @param {jQuery} $semSel select de semana
 */
function v2RangoSemanaDesdeSelects($pecSel, $semSel) {
    if (!$pecSel || !$pecSel.length || !$semSel || !$semSel.length) return null;
    var $p = $pecSel.find('option:selected');
    var ini = $p.attr('data-inicio') || $p.data('inicio');
    var fin = $p.attr('data-fin') || $p.data('fin');
    var sem = parseInt($semSel.val(), 10) || 0;
    if (!ini || !fin || sem < 1) return null;
    var d0 = new Date(String(ini).replace(/-/g, '/'));
    var pEnd = new Date(String(fin).replace(/-/g, '/'));
    if (isNaN(d0.getTime()) || isNaN(pEnd.getTime())) return null;
    var dStart = new Date(d0.getFullYear(), d0.getMonth(), d0.getDate() + (sem - 1) * 7);
    var dEnd = new Date(dStart.getFullYear(), dStart.getMonth(), dStart.getDate() + 6);
    if (dEnd > pEnd) {
        dEnd = new Date(pEnd.getFullYear(), pEnd.getMonth(), pEnd.getDate());
    }
    if (dStart > pEnd) return null;
    return { fei: v2FormatYMD(dStart), fef: v2FormatYMD(dEnd) };
}

/** Rango semana en Registrar. */
function v2RangoSemanaPeriodo() {
    var $f = $('#frm_alt_actividad');
    return v2RangoSemanaDesdeSelects($f.find('#Pec_Cod'), $f.find('#Act_Sem'));
}

/** Rango semana en Modificar (per&iacute;odo/finca/semana de la consulta). */
function v2ModRangoSemanaPeriodo() {
    return v2RangoSemanaDesdeSelects($('#modq_Pec_Cod'), $('#modq_Act_Sem'));
}

function v2RegFincaSemanaSeleccionadas() {
    var $f = $('#frm_alt_actividad');
    if (!$f.length) return false;
    var fnc = parseInt($f.find('#Fnc_Cod_D').val(), 10) || 0;
    var sem = parseInt($f.find('#Act_Sem').val(), 10) || 0;
    return fnc > 0 && sem > 0;
}

function v2ModFincaSemanaSeleccionadas() {
    var fnc = parseInt($('#modq_Fnc_Cod').val(), 10) || 0;
    var sem = parseInt($('#modq_Act_Sem').val(), 10) || 0;
    return fnc > 0 && sem > 0;
}

function v2AlertRequiereFincaSemana() {
    $.alert('Seleccione <strong>finca</strong> y <strong>semana</strong> antes de continuar.', null, 'remove');
}

function v2RegRequiereFincaSemana() {
    if (v2RegFincaSemanaSeleccionadas()) return true;
    v2AlertRequiereFincaSemana();
    return false;
}

function v2ModRequiereFincaSemana() {
    if (v2ModFincaSemanaSeleccionadas()) return true;
    v2AlertRequiereFincaSemana();
    return false;
}

function v2ToggleEmpActionButtons($btns, ok) {
    $btns.prop('disabled', !ok);
    $btns.each(function() {
        var $b = $(this);
        if (!ok) {
            if ($b.data('v2-title') === undefined) {
                $b.data('v2-title', $b.attr('title') || '');
            }
            $b.attr('title', 'Seleccione finca y semana');
        } else {
            var t = $b.data('v2-title');
            if (t !== undefined && t !== '') {
                $b.attr('title', t);
            } else if (t === '') {
                $b.removeAttr('title');
            }
        }
    });
}

function v2UpdateRegEmpActionButtons() {
    if (!$('#regActividadV2').length) return;
    v2ToggleEmpActionButtons($('#btn_agr_emp, #btn_emp_por_area'), v2RegFincaSemanaSeleccionadas());
}

function v2UpdateModEmpActionButtons() {
    if (!$('#modRegActividadV2').length) return;
    v2ToggleEmpActionButtons($('#btn_mod_agr_emp, #btn_mod_emp_por_area'), v2ModFincaSemanaSeleccionadas());
}

/** Carga masiva de empleados por &aacute;rea y semana (contrato vigente en ese rango). */
function v2CargarEmpleadosPorArea() {
    if (!$('#regActividadV2').length) return;
    if (!v2RegRequiereFincaSemana()) return;
    if (g_v2SelectedEmpRowId) {
        v2SyncCurrentDetailToStore();
    }
    var are = parseInt($('#Are_Cod_Lab').val(), 10) || 0;
    var rango = v2RangoSemanaPeriodo();
    if (are <= 0) {
        $.alert('Seleccione un &aacute;rea.', null, 'remove');
        return;
    }
    if (!rango) {
        $.alert('Seleccione per&iacute;odo y <strong>semana</strong> v&aacute;lidos antes de listar empleados.', null, 'remove');
        return;
    }
    $.getDataJson(
        '',
        { personalPorAreaAjax: true, Are_Cod: are, Sem_Fei: rango.fei, Sem_Fef: rango.fef },
        function(resp) {
            if (!resp || !resp.success) {
                $.alert((resp && resp.message) || 'No se pudo obtener el listado.', null, 'remove');
                return;
            }
            var list = resp.empleados || [];
            if (!list.length) {
                $.alert('No hay empleados con contrato vigente en esa semana para el &aacute;rea elegida.', null, 'remove');
                return;
            }
            var idsE = v2EmpGetRowIds();
            var exist = {};
            var xi;
            for (xi = 0; xi < idsE.length; xi++) {
                exist[String(v2GetEmpMeta(idsE[xi]).Per_Cod)] = true;
            }
            var agregados = 0;
            var omitidos = 0;
            var firstNid = null;
            var i;
            for (i = 0; i < list.length; i++) {
                var row = list[i];
                var pc = row.Per_Cod != null ? String(row.Per_Cod) : '';
                if (!pc) continue;
                if (exist[pc]) {
                    omitidos++;
                    continue;
                }
                exist[pc] = true;
                var nidStr = v2EmpAppendRow(pc, row.Personal, '0.00');
                if (firstNid === null) firstNid = nidStr;
                agregados++;
            }
            v2RefreshTotalesEmpleados();
            if (firstNid !== null && agregados > 0) {
                v2SwitchEmpleado(firstNid);
            }
            var msg = 'Se agregaron ' + agregados + ' empleado(s).';
            if (omitidos > 0) msg += ' ' + omitidos + ' ya estaban en la lista.';
            $.alert(msg, null, 'remove');
        },
        function() {
            $.alert('Error al consultar empleados por &aacute;rea.', null, 'remove');
        }
    );
}

/** Inicializa el di&aacute;logo flotante para elegir &aacute;rea al listar empleados (Modificar). */
function v2ModInitListarEmpAreaDialog() {
    var $d = $('#modListarEmpAreaDialog');
    if (!$d.length || $d.data('v2ListarEmpDlg')) return;
    $d.data('v2ListarEmpDlg', true);
    if ($.fn.createDialog) {
        $d.createDialog({ height: 220, width: 440, icon: 'glyphicon glyphicon-list' });
    } else if ($.fn.dialog) {
        $d.dialog({ modal: true, width: 440, autoOpen: false, resizable: false });
    }
    $('#btn_mod_listar_emp_ok')
        .off('click.v2modlistdlg')
        .on('click.v2modlistdlg', function() {
            var are = parseInt($('#mod_listar_emp_Are_Cod').val(), 10) || 0;
            if (are <= 0) {
                $.alert('Seleccione un &aacute;rea.', null, 'remove');
                return;
            }
            $d.dialog('close');
            v2ModCargarEmpleadosPorArea(are);
        });
    $('#btn_mod_listar_emp_cancel')
        .off('click.v2modlistdlg')
        .on('click.v2modlistdlg', function() {
            $d.dialog('close');
        });
}

function v2ModAbrirDialogListarEmpleadosPorArea() {
    if (!$('#modRegActividadV2').length) return;
    if (!v2ModRequiereFincaSemana()) return;
    if (!g_modActividadCab) {
        $.alert('Primero pulse <b>Buscar</b> para cargar la actividad.', null, 'remove');
        return;
    }
    v2ModInitListarEmpAreaDialog();
    $('#modListarEmpAreaDialog').dialog('open');
    setTimeout(function() {
        $('#mod_listar_emp_Are_Cod').trigger('focus');
    }, 0);
}

/** Carga masiva de empleados por &aacute;rea en Modificar (misma l&oacute;gica que Registrar). */
function v2ModCargarEmpleadosPorArea(areCod) {
    if (!$('#modRegActividadV2').length) return;
    if (!v2ModRequiereFincaSemana()) return;
    if (!g_modActividadCab) {
        $.alert('Primero pulse <b>Buscar</b> para cargar la actividad.', null, 'remove');
        return;
    }
    if (g_modV2SelectedEmpRowId) {
        v2ModSyncCurrentDetailToStore();
    }
    var are = parseInt(areCod, 10) || parseInt($('#mod_listar_emp_Are_Cod').val(), 10) || 0;
    var rango = v2ModRangoSemanaPeriodo();
    if (are <= 0) {
        $.alert('Seleccione un &aacute;rea.', null, 'remove');
        return;
    }
    if (!rango) {
        $.alert('Verifique per&iacute;odo y semana de la actividad cargada.', null, 'remove');
        return;
    }
    $.getDataJson(
        '',
        { personalPorAreaAjax: true, Are_Cod: are, Sem_Fei: rango.fei, Sem_Fef: rango.fef },
        function(resp) {
            if (!resp || !resp.success) {
                $.alert((resp && resp.message) || 'No se pudo obtener el listado.', null, 'remove');
                return;
            }
            var list = resp.empleados || [];
            if (!list.length) {
                $.alert('No hay empleados con contrato vigente en esa semana para el &aacute;rea elegida.', null, 'remove');
                return;
            }
            var exist = {};
            var xi;
            v2ModEmpGetRowIds().forEach(function(rid) {
                exist[String(v2ModGetEmpMeta(rid).Per_Cod)] = true;
            });
            var agregados = 0;
            var omitidos = 0;
            var firstNid = null;
            var i;
            for (i = 0; i < list.length; i++) {
                var row = list[i];
                var pc = row.Per_Cod != null ? String(row.Per_Cod) : '';
                if (!pc) continue;
                if (exist[pc]) {
                    omitidos++;
                    continue;
                }
                exist[pc] = true;
                var nidStr = v2ModEmpAppendRow(pc, row.Personal, '0.00');
                if (firstNid === null) firstNid = nidStr;
                agregados++;
            }
            v2ModRefreshTotalesEmpleados();
            if (firstNid !== null && agregados > 0) {
                v2ModSwitchEmpleado(firstNid);
            }
            var msg = 'Se agregaron ' + agregados + ' empleado(s) a la actividad.';
            if (omitidos > 0) msg += ' ' + omitidos + ' ya estaban en la lista.';
            $.alert(msg, null, 'remove');
        },
        function() {
            $.alert('Error al consultar empleados por &aacute;rea.', null, 'remove');
        }
    );
}

function v2AddLaborFila() {
    if (!$('#regActividadV2').length) return;
    if (!g_v2SelectedEmpRowId) {
        $.alert('Seleccione un empleado en la lista izquierda (o agregue uno con <b>Agregar empleado</b>).', null, 'remove');
        return;
    }
    if (v2LabIsNative()) {
        esCrear = true;
        var meta = v2GetEmpMeta(g_v2SelectedEmpRowId);
        var id = String(v2NextLineIndex());
        v2LabAppendRow(id, {
            index: id,
            _v2_emp_rid: v2EmpDomRowIdFromAny(g_v2SelectedEmpRowId),
            Per_Cod: meta.Per_Cod,
            Personal: meta.Personal,
            Lab_Cod: '',
            Lab_Des: '',
            Tpg_Des: '',
            Det_Fec: v2GetActFecRegVal(),
            Det_Obs: '',
            Lab_Val: '',
            Det_Can: '',
            Total: ''
        });
        v2LabRow$(id)
            .find('#' + id + '_Lab_Des')
            .focus();
        makeCalculation(0);
        v2RefreshTotalesEmpleados();
        return;
    }
    esCrear = true;
    var meta = v2GetEmpMeta(g_v2SelectedEmpRowId);
    var $this = $('#tableActividad');
    var campoGrid = '_Det_Can';
    var fecha = '_Det_Fec';
    var $form = 'frm_alt_actividad';
    var sel_fecha = $('#' + $form).find('#Pec_Cod').find('option:selected');
    var id = v2NextLineIndex();
    $this.jqGrid('addRowData', id, {
        index: id,
        _v2_emp_rid: v2EmpDomRowIdFromAny(g_v2SelectedEmpRowId),
        Per_Cod: meta.Per_Cod,
        Personal: meta.Personal,
        Lab_Cod: '',
        Lab_Des: '',
        Tpg_Des: '',
        Det_Fec: '',
        Det_Obs: '',
        Lab_Val: '',
        Det_Can: '',
        Total: ''
    });
    $this.jqGrid('editRow', id);
    $.createDatePickers('#' + id + fecha);
    var $tr = v2DetailRow$($this, id);
    $tr.find('#' + id + '_Lab_Des').focus();
    $('#' + id + fecha).dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
    $tr.find('#' + id + campoGrid).on('change', function() {
        var vlInput = validaDecimal($tr.find('#' + id + campoGrid).val());
        if (vlInput) {
            $.alert('El valor de la cantidad debe ser mayor que 0');
        }
        makeCalculation(0);
        v2RefreshTotalesEmpleados();
        $tr.find('#' + id + campoGrid).focus();
    }).trigger('change');
}

function v2ButtonRowId(row, $g) {
    if (!row) return null;
    if (row.id != null && String(row.id) !== '') return String(row.id);
    if ($g && $g.length && $g[0].p && $g[0].p.keyName) {
        var kn = $g[0].p.keyName;
        if (row[kn] != null && String(row[kn]) !== '') return String(row[kn]);
    }
    if (row.index != null && String(row.index) !== '') return String(row.index);
    if (row.v2_row_id != null && String(row.v2_row_id) !== '') return String(row.v2_row_id);
    return null;
}

function v2QuitarEmpleadoPorDomRid(ridDom) {
    var rid = v2EmpDomRowIdFromAny(ridDom);
    if (!rid) return;
    $.createDialogConfirm('&iquest;Quitar este empleado y todas sus labores de este registro?', null, function() {
        v2SyncCurrentDetailToStore();
        var meta = v2GetEmpMeta(rid);
        var pcFilt = meta.Per_Cod || v2CleanVal(v2EmpNativeRow$(rid).attr('data-per-cod'));
        g_v2Lineas = g_v2Lineas.filter(function(l) {
            var le = l._v2_emp_rid != null ? String(v2CleanVal(l._v2_emp_rid)) : '';
            if (le !== '') return v2EmpDomRowIdFromAny(le) !== rid;
            return String(v2CleanVal(l.Per_Cod)) !== String(v2CleanVal(pcFilt));
        });
        delete g_v2EmpMeta[rid];
        v2EmpNativeRow$(rid).remove();
        v2EmpRenumberRows();
        v2EmpUpdateFooterCount();
        if (g_v2SelectedEmpRowId && v2EmpDomRowIdFromAny(g_v2SelectedEmpRowId) === rid) {
            g_v2SelectedEmpRowId = null;
            v2UpdateLaboresCaptionReg('');
            if (v2LabIsNative()) {
                v2LabClear();
            } else {
                $('#tableActividad').clearGrid();
            }
        }
        v2RefreshTotalesEmpleados();
    });
}

/** Compatibilidad con gridButton (row puede venir mal serializado). Preferir v2QuitarEmpleadoPorDomRid desde el DOM. */
function v2QuitarEmpleado(row) {
    if (!row) return;
    var rid = v2ButtonRowId(row, $('#tableEmpRegAct'));
    if (!rid && row.id != null && String(row.id) !== '') rid = String(row.id);
    if (!rid && row.v2_row_id != null && String(row.v2_row_id) !== '') rid = String(row.v2_row_id);
    v2QuitarEmpleadoPorDomRid(rid);
}

/** Limpia la pesta&ntilde;a Registrar (v2) sin recargar la p&aacute;gina. */
function v2RegCancelarReset() {
    if (!$('#regActividadV2').length) return;
    g_v2Lineas = [];
    g_v2SelectedEmpRowId = null;
    v2EmpClearAll();
    v2LabClear();
    $('#v2EmpTbody tr.v2-emp-row').removeClass('v2-emp-sel');

    var $f = $('#frm_alt_actividad');
    if (!$f.length) return;

    if ($f[0].reset) {
        $f[0].reset();
    }
    $f.find('#prefijo').text('');

    var $fec = $f.find('#Act_Fec');
    if ($fec.length && $.datepicker && $.datepicker.formatDate) {
        $fec.val($.datepicker.formatDate('yy-mm-dd', new Date()));
    }
    var sel_fecha = $f.find('#Pec_Cod').find('option:selected');
    if ($fec.length && sel_fecha.length) {
        $fec.dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
    }
    if ($fec.length) {
        $fec.datepicker('option', 'beforeShow', function() {
            v2BumpDatepickerZIndex();
        });
    }

    v2RefrescarSelectSemanas();
    v2RefreshTotalesEmpleados();
}

/** Cancelar: di&aacute;logo del sistema; solo reinicia la pesta&ntilde;a correspondiente (sin reload). */
function v2LaboresCancelReload(tabPanelId) {
    $.createDialogConfirm(
        '&iquest;Est&aacute; seguro que desea Cancelar? Se borrar&aacute;n todas las actividades asignadas.',
        null,
        function() {
            if (tabPanelId === 'tabs-2') {
                v2RegCancelarReset();
            } else if (tabPanelId === 'tabs-3') {
                v2ModResetPaneles();
                setTimeout(function() {
                    if (typeof v2ModReflowLabPanel === 'function') {
                        v2ModReflowLabPanel();
                    }
                }, 0);
            }
        }
    );
}

function initRegistroActividadV2() {
    v2InitEmpNativeTable();
    v2InitLabNativeTable();

    $('#frm_alt_actividad')
        .find('#Fnc_Cod_D, #Act_Sem')
        .off('change.v2empbtn')
        .on('change.v2empbtn', function() {
            v2UpdateRegEmpActionButtons();
        });
    v2UpdateRegEmpActionButtons();

    $('#btn_emp_por_area').off('click.v2area').on('click.v2area', function() {
        v2CargarEmpleadosPorArea();
    });

    $(document)
        .off('focus.v2dpz mousedown.v2dpz', '#regActividadV2 input.hasDatepicker, #frm_alt_actividad #Act_Fec.hasDatepicker')
        .on('focus.v2dpz mousedown.v2dpz', '#regActividadV2 input.hasDatepicker, #frm_alt_actividad #Act_Fec.hasDatepicker', function() {
            v2BumpDatepickerZIndex();
        });
}

/** Pantalla v2 (registro/modificar): no precargar cat&aacute;logo completo de labores al abrir (mejora tiempo en producci&oacute;n). */
function v2IsLaboresV2OnlyPage() {
    return ($('#regActividadV2').length || $('#modRegActividadV2').length) && !$('#detaLabores').length;
}

$(function() {
    searchLaborPago();
    if (!v2IsLaboresV2OnlyPage()) {
        refreshData();
    }
    $('#tabsLabores').createTabs({
        heightStyle: 'auto',
        activate: function(event, ui) {
            var pid = ui.newPanel.attr('id');
            if (pid === 'tabs-3') {
                esMod = true;
                esCrear = false;
                setTimeout(function() {
                    v2ModReflowLabPanel();
                    v2ScheduleSyncModqMatchSelectWidth();
                }, 0);
            } else if (pid === 'tabs-2') {
                esMod = false;
            }
        }
    });
    $('#btn_can_act_reg')
        .off('click.v2cancel')
        .on('click.v2cancel', function() {
            v2LaboresCancelReload('tabs-2');
        });
    $('#btn_can_act_mod')
        .off('click.v2cancel')
        .on('click.v2cancel', function() {
            v2LaboresCancelReload('tabs-3');
        });
    if ($('#Cod_Bus').length) {
        $('#Cod_Bus').createChosen('input-xs');
    }
    if ($('#Tpg_Cod').length) {
        $('#Tpg_Cod').createChosen('input-xs');
    }
    $.createDateRange('#Fec_Ini', '#Fec_Fin');
    // arrayDetalle = async() => { arrayD = await viewActividadAll(); }
    //arrayDetalle();
    $.createDatePickers('#Act_Fec');
    generarSemanas();
    if ($('#regActividadV2').length) {
        v2RefrescarSelectSemanas();
    }
    if ($('#regActividadV2').length || $('#modRegActividadV2').length) {
        v2LaborAcInitGlobalHandlers();
    }
    //busquedaInicial();
    var sel_fecha = $("#Pec_Cod").find('option:selected');
    $('#Act_Fec').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
    $('#Act_Fec').datepicker('option', 'beforeShow', function() {
        v2BumpDatepickerZIndex();
    });
    $('#Act_Fec').datepicker('option', 'onSelect', function() {
        $(this).trigger('change');
    });

    $(document).on('change', '#frm_alt_actividad #Act_Fec', function() {
        if ($('#regActividadV2').length) {
            var semActual = v2ObtenerSemanaActual();
            var $sel = $('#frm_alt_actividad #Act_Sem');
            if ($sel.length && $sel.find('option[value="' + semActual + '"]').length) {
                $sel.val(String(semActual));
                if (typeof verificaExistente === 'function') {
                    verificaExistente();
                }
            }
        }
    });

    getFincas();
    var opts = {
        height: 75,
        colModel: [
            { label: 'C�d.Int.', name: 'Lab_Cod', key: true, width: 15, align: 'center', hidden: false },
            { label: 'Descripci&oacute;n ', name: 'Lab_Des', width: 45, align: 'left' },
            { label: 'Unidad ', name: 'Tpg_Des', width: 20, align: 'left' },
            { label: 'Valor ', name: 'Lab_Val', width: 15, align: 'center' },
            {
                name: 'update',
                label: '<i class="glyphicon glyphicon-pencil"></i>',
                width: 10,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: updLabor,
                    icon: 'pencil',
                    type: 'info',
                    title: 'Actualizar Labor',
                    data: function(o) {
                        return o;
                    }
                },
                resizable: false
            },
            {
                name: 'delete',
                label: '<i class="glyphicon glyphicon-trash"></i>',
                width: 10,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: {
                    action: delLabor,
                    icon: 'trash',
                    type: 'danger',
                    title: 'Anular Labor',
                    data: function(o) {
                        return o;
                    }
                },
                resizable: false
            }
        ]
    };
    var optsFink = {
        height: 75,
        colModel: [
            { label: 'C�d.Int.', name: 'Fnc_Cod', key: true, width: 15, align: 'center', hidden: false },
            { label: 'Descripci&oacute;n ', name: 'Fnc_Des', width: 45, align: 'left' },
            { label: 'Direcci&oacute;n ', name: 'Fnc_Dir', width: 20, align: 'left' },
            { label: 'Hect&aacute;reas ', name: 'Fnc_Hec', width: 15, align: 'center' }
        ]
    };

    if ($('#unidadDialog').length) {
        $('#unidadDialog').createDialog({ height: 135, width: 400, icon: 'glyphicon glyphicon-plus' });
    }
    if ($('#laborDialog').length) {
        $('#laborDialog').createDialog({ height: 200, width: 420, icon: 'pencil' });
    }
    if ($('#dialogMapeoSector').length > 0) {
        $('#dialogMapeoSector').createDialog({ height: 560, width: 860, icon: 'glyphicon glyphicon-map-marker', resizable: true });
    }

    if ($('#detaLabores').length > 0)
        $('#detaLabores').createGrid(
            $.extend(opts, {
                height: 'auto',
                width: 550,
                responsive: false,
                caption: null,
                rownumbers: false
            }),
            true
        );
    if ($('#detaFincas').length > 0)
        $('#detaFincas').createGrid(
            $.extend(optsFink, {
                height: 'auto',
                width: 550,
                responsive: false,
                caption: null,
                rownumbers: false
            }),
            true
        );


    if ($('#regActividadV2').length) {
        initRegistroActividadV2();
    } else {
    var grid = $('#tableActividad');
    grid
        .createGrid({
                caption: 'REGISTRO DE ACTIVIDADES',
                height: '350',
                colModel: [{
                        name: 'index',
                        label: 'Index',
                        width: 20,
                        sorttype: 'int',
                        align: 'center',
                        hidden: true
                    },
                    { label: 'Cod', name: 'Act_Cod', key: true, hidden: true },
                    {
                        label: '<span class="required"></span> Trabajador',
                        name: 'Personal',
                        width: 45,
                        align: 'center',
                        title: true,
                        formatter: 'input2',
                        formatoptions: {
                            id: '1',
                            title: 'Buscar Trabajador',
                            action: 'abrirDialogPersonal',
                            data: function(o) {
                                return o;
                            }
                        },
                        resizable: false
                    },
                    {
                        label: '<span class="required"></span>Labor',
                        name: 'Lab_Des',
                        width: 30,
                        align: 'center',
                        title: true,
                        formatter: 'input2',
                        formatoptions: {
                            id: '1',
                            title: 'Buscar Labor',
                            action: 'abrirDialogLabor',
                            data: function(o) {
                                return o;
                            }
                        },
                        resizable: false
                    },
                    {
                        name: 'Lab_Cod',
                        hidden: true,
                        formatter: 'input3',
                        formatoptions: { id: '3', attr: '' }
                    },
                    {
                        name: 'Per_Cod',
                        hidden: true,
                        formatter: 'input3',
                        formatoptions: { id: '3', attr: '' }
                    },
                    {
                        label: 'Unidad',
                        name: 'Tpg_Des',
                        width: 20,
                        align: 'center',
                        title: false,
                        formatter: 'input3',
                        formatoptions: { id: '3', attr: '' }
                    }, {
                        label: '<span class="required"></span> Fecha',
                        name: 'Det_Fec',
                        width: 15,
                        align: "center",
                        title: false,
                        formatter: 'input2',
                        formatoptions: { id: '2', attr: '' }
                    },
                    {
                        label: '<span class="required"></span> Observaci&oacute;n',
                        name: 'Det_Obs',
                        width: 50,
                        align: 'center',
                        title: false,
                        formatter: 'input2',
                        formatoptions: { id: '2', attr: '' }
                    },
                    {
                        label: 'P. Unitario',
                        name: 'Lab_Val',
                        width: 15,
                        align: 'center',
                        title: false,
                        formatter: 'input4',
                        formatoptions: { id: '2', attr: '' }
                    },
                    {
                        label: '<span class="required"></span> Cantidad',
                        name: 'Det_Can',
                        width: 15,
                        align: 'center',
                        title: false,
                        formatter: 'inputN',
                        formatoptions: { id: '2', attr: '' }
                    },
                    {
                        label: 'Total',
                        name: 'Total',
                        width: 15,
                        align: 'right',
                        title: false,
                        formatter: 'input4',
                        formatoptions: { id: '2', attr: '' }
                    },

                    {
                        name: 'delete',
                        label: '<i class="glyphicon glyphicon-remove"></i>',
                        width: 10,
                        align: 'center',
                        viewable: false,
                        formatter: 'gridButton',
                        formatoptions: {
                            action: quitarActividad,
                            /*conditional: function(o) { console.log(o); return o.tarja === 'n' && o.Prh_Est !== 'I'; },*/
                            icon: 'remove',
                            type: 'danger',
                            title: 'Eliminar Item',
                            data: function(o) {
                                return o;
                            }
                        },
                        resizable: false
                    }
                ],
                footerrow: true,
                loadComplete: function () { $(this).setGridSummary(['Total'],{Det_Can: '<div style="text-align:right;">Total:</div>'}); },
                pgbuttons: false,
                pgtext: null,
                beforeSelectRow: function(rowid, e) {
                    return false;
                }
            },
            true,
            '#tableActividadPager', { view: false, refresh: false }
        )
        .gridButtonAdd({
            caption: 'Agregar Trabajador',
            id: 'btn_agr',
            buttonicon: 'glyphicon glyphicon-plus',
            title: 'Agregar',
            onClickButton: function() {
                agregarFila(0);
            }
        });
    }
    // Grid de la modificaci�n tableActividadMod tableActividadModPager
    $('#tableActividadMod').createGrid({
                caption: '*REGISTRO DE ACTIVIDADES',
                height: '350',
                colModel: [
                    { name: 'index', label: 'Index', width: 20, sorttype: 'int', align: 'center', hidden: true },
                    { label: 'Cod', name: 'Act_Cod', key: true, hidden: true },
                    { label: 'Det_Cod', name: 'Det_Cod', key: true, hidden: true },
                    {
                        label: '<span class="required"></span> Trabajador',
                        name: 'Personal',
                        width: 45,
                        align: 'center',
                        title: true,
                        formatter: 'input2',
                        formatoptions: {
                            id: '1',
                            title: 'Buscar Trabajador',
                            action: 'abrirDialogPersonal',
                            data: function(o) {
                                return o;
                            }
                        },
                        resizable: false
                    },
                    {
                        label: '<span class="required"></span>Labor',
                        name: 'Lab_Des',
                        width: 30,
                        align: 'center',
                        title: true,
                        formatter: 'input2',
                        formatoptions: {
                            id: '1',
                            title: 'Buscar Labor',
                            action: 'abrirDialogLabor',
                            data: function(o) {
                                return o;
                            }
                        },
                        resizable: false
                    },
                    {
                        name: 'Lab_Cod',
                        hidden: true,
                        formatter: 'input3',
                        formatoptions: { id: '3', attr: '' }
                    },
                    {
                        name: 'Per_Cod',
                        hidden: true,
                        formatter: 'input3',
                        formatoptions: { id: '3', attr: '' }
                    },
                    {
                        label: 'Unidad',
                        name: 'Tpg_Des',
                        width: 20,
                        align: 'center',
                        title: false,
                        formatter: 'input3',
                        formatoptions: { id: '3', attr: '' }
                    }, {
                        label: '<span class="required"></span> Fecha',
                        name: 'Det_Fec_Mod',
                        width: 20,
                        align: "center",
                        title: false,
                        formatter: 'input2',
                        formatoptions: { id: '2', attr: '' }
                    },
                    {
                        label: '<span class="required"></span> Observaci&oacute;n',
                        name: 'Det_Obs',
                        width: 45,
                        align: 'center',
                        title: false,
                        formatter: 'input2',
                        formatoptions: { id: '2', attr: '' }
                    },
                    { label: 'P. Unitario', name: 'Lab_Val', width: 15, align: 'center', title: false, formatter: 'input4', formatoptions: { id: '2', attr: '' } },
                    {
                        label: '<span class="required"></span> Cantidad',
                        name: 'Det_Can_Mod',
                        width: 15,
                        align: 'center',
                        title: false,
                        formatter: 'inputN',
                        formatoptions: { id: '2', attr: '' }
                    },
                    {
                        label: 'Total',
                        name: 'Total',
                        width: 15,
                        align: 'right',
                        title: false,
                        formatter: 'input4',
                        formatoptions: { id: '2', attr: '' }
                    },

                    {
                        name: 'delete',
                        label: '<i class="glyphicon glyphicon-remove"></i>',
                        width: 10,
                        align: 'center',
                        viewable: false,
                        formatter: 'gridButton',
                        formatoptions: {
                            action: quitarActividadMod,
                            /*conditional: function(o) { console.log(o); return o.tarja === 'n' && o.Prh_Est !== 'I'; },*/
                            icon: 'remove',
                            type: 'danger',
                            title: 'Eliminar Item',
                            data: function(o) {
                                return o;
                            }
                        },
                        resizable: false
                    }
                ],footerrow: true,
                loadComplete: function () { $(this).setGridSummary(['Total']); },
                pgbuttons: false,
                rowNum: 10000,
                pgtext: null,
                beforeSelectRow: function(rowid, e) {
                    return false;
                }
            },
            true,
            '#tableActividadModPager', { view: false, refresh: false }
        )
        .gridButtonAdd({
            caption: 'Agregar Trabajador',
            id: 'btn_agr',
            buttonicon: 'glyphicon glyphicon-plus',
            title: 'Agregar',
            onClickButton: function() {
                agregarFila(1);
            }
        });

    //Tabla modificar actividades
    var gridModAct = $('#consultarGrid');
    if (gridModAct.length) {
    gridModAct.createGrid({
        height: 300,
        datatype: "local",
        regional: 'es',
        shrinkToFit: true,
        colModel: [
            { label: 'C�d.Int.', name: 'Act_Cod', width: 10, key: true, hidden: false, align: "center", viewable: true },
            { name: "Fnc_Cod", hidden: true },
            { label: 'Trabajador', name: 'personal', width: 55, align: "center" },
            { label: 'Finca', name: 'Fnc_Des', width: 55, align: "center" },
            { label: 'Fecha', name: 'Act_Fec', width: 50, align: "left" },
            { label: 'Semana', name: 'Semana', width: 55, align: "center" },
            { label: $.createIcon('info-sign'), name: 'actInfo', align: "center", width: 7, viewable: false, formatter: 'gridButton', formatoptions: { action: viewInfo, icon: 'info-sign', type: 'info', title: 'Info' }, title: false, resizable: false },
            { label: $.createIcon('glyphicon glyphicon-pencil'), name: 'actEdt', align: "center", viewable: false, width: 7, formatter: 'gridButton', formatoptions: { action: editActividad, icon: 'glyphicon glyphicon-pencil', type: 'success', title: 'Modificar Activiad', resizable: false } }


        ],
        pager: "#cgPager",
        rownumbers: true,
        rowNum: 10000,
        gridview: false,
        viewrecords: false,
        footerrow: false,
        userDataOnFooter: false,
        loadComplete: function(data) {
            if ($('#rad_ba1').length) {
                busquedaInicial();
            }
        }

    }, false, "#cgPager", { view: false, refresh: false });
        $('#consultarGrid').closest('.v2-consultar-grid-host').css('min-height', '');
        $('#gbox_consultarGrid').css('min-height', '');
    }


    $.fn.fmatter.input2 = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        //console.log(opts);
        if (set['id'] === '1') {
            el = $(
                '<div class="input-group input-group-xs ret"><input type="text" id="' +
                opts['rowId'] + '_' +
                opts['colModel']['name'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs ' +
                set['class'] +
                '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' +
                set['title'] +
                '" onclick="' +
                set['action'] +
                '(' +
                opts['rowId'] +
                ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'
            );
        } else {
            el = $(
                '<input type="text" id="' +
                opts['rowId'] +
                '_' +
                opts['colModel']['name'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs" ' +
                set['attr'] +
                '/>'
            );
        }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input2.unformat = function(cv, opts, cObjt) {
        return $(cObjt).find(':input').val();
    };
    $.fn.fmatter.inputN = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        //console.log(opts);
        if (set['id'] === '1') {
            el = $(
                '<div class="input-group input-group-xs ret"><input type="number" step="0.01" min="0" id="' +
                opts['rowId'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs ' +
                set['class'] +
                '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' +
                set['title'] +
                '" onclick="' +
                set['action'] +
                '(' +
                opts['rowId'] +
                ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'
            );
        } else {
            el = $(
                '<input type="number"  style="text-align: right;" step="0.01" pattern="^\d+(?:\.\d{1,3})?$" min="0" id="' +
                opts['rowId'] +
                '_' +
                opts['colModel']['name'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs" ' +
                set['attr'] +
                '/>'
            );
        }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.inputN.unformat = function(cv, opts, cObjt) {
        return $(cObjt).find(':input').val();
    };

    $.fn.fmatter.input3 = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        //console.log(opts);
        if (set['id'] === '2') {
            el = $(
                '<div class="input-group input-group-xs ret"><input type="text" id="' +
                opts['rowId'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs ' +
                set['class'] +
                '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' +
                set['title'] +
                '" onclick="' +
                set['action'] +
                '(' +
                opts['rowId'] +
                ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'
            );
        } else {
            el = $(
                '<input type="text" style="text-align: center;" id="' +
                opts['rowId'] +
                '_' +
                opts['colModel']['name'] +
                '" name="' +
                opts['colModel']['name'] +
                '" class="form-control input-xs ret2" ' +
                set['attr'] +
                'readonly/>'
            );
        }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input3.unformat = function(cv, opts, cObjt) {
        return $(cObjt).find(':input').val();
    };

    $.fn.fmatter.input4 = function(cv, opts, cObjt) {
        var set = opts['colModel']['formatoptions'],
            el;
        //console.log(opts);
        if (set['id'] === '1') { el = $('<div class="input-group input-group-xs ret"><input type="text" id="' + opts['rowId'] + '" class="form-control input-xs ' + set['class'] + '"/><span class="input-group-btn"><button class="btn btn-info" type="button" title="' + set['title'] + '" onclick="' + set['action'] + '(' + opts['rowId'] + ')"><span class="glyphicon glyphicon-search"></span></button></span></div>'); } else { el = $('<input type="text"   style="text-align: right;" id="' + opts['rowId'] + '_' + opts['colModel']['name'] + '" name="' + opts['colModel']['name'] + '" class="form-control input-xs" ' + set['attr'] + 'readonly />'); }
        return el.prop('outerHTML');
    };
    $.fn.fmatter.input4.unformat = function(cv, opts, cObjt) { return $(cObjt).find(':input').val(); };
    //agregarFila(0);


    if ($('#dialogInfo').length > 0)
        $('#dialogInfo').createDialog({ height: 140, width: 600, noTitleStuff: false, noBorder: true });
});
if ($('#laboresDialog').length > 0) {
    $.createSearchDialog(
        'laboresDialog', [{
                label: 'C&oacute;d.Int.',
                name: 'Lab_Cod',
                key: true,
                width: 15,
                align: 'center',
                hidden: true
            },
            { label: 'Descripc�on', name: 'Lab_Des', width: 100 },
            { label: 'Unidad', name: 'Tpg_Des', width: 60 },
            { label: 'P.Unitario', name: 'Lab_Val', width: 50 },
            {
                label: '&nbsp;',
                name: 'act1',
                width: 20,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: { action: selectLabor }
            }
        ],
        null,
        null,
        null, { headertitles: true }, {
            title: 'Labores',
            options: [
                { label: '&nbsp;&nbsp;Drescripci&oacute;n&nbsp;&nbsp;', value: 'd' },
                { label: '&nbsp;&nbsp;Unidad&nbsp;&nbsp;', value: 'c' }
            ]
        }
    );
}

if ($('#personalDialog').length > 0) {
    $.createSearchDialog(
        '#personalDialog', [{
                label: 'C&oacute;d.Int.',
                name: 'Per_Cod',
                key: true,
                width: 15,
                align: 'center',
                hidden: true
            },
            { label: 'C�dula/RUC', name: 'Prs_Ced', width: 50 },
            { label: 'Personal', name: 'Personal', width: 100 },
            { label: 'Direcc.', name: 'Prs_Dir', width: 60 },
            {
                label: '&nbsp;',
                name: 'act1',
                width: 20,
                align: 'center',
                viewable: false,
                formatter: 'gridButton',
                formatoptions: { action: selectPersonal }
            }
        ],
        null,
        null,
        null, { headertitles: true }, {
            title: 'Personal',
            options: [
                { label: '&nbsp;&nbsp;Apellido/Nombre&nbsp;&nbsp;', value: 'd' },
                { label: '&nbsp;&nbsp;C&eacute;dula/R.U.C&nbsp;&nbsp;', value: 'c' }
            ]
        }
    );
}


function saveData(formulario, accion, dialogo) {
    var data = $('#' + formulario).getData('save');
    var vfG = false,
        selectSemana = '',
        selectFinca = '',
        responsable = '',
        cambio = '',
        indiceAct = 0;
    var shortDateFormat = 'yy-mm-dd';
    data[accion] = true;
    if (dialogo === 'actividad') {
        if (formulario === "frm_alt_actividad") {
            if ($('#regActividadV2').length) {
                v2NormalizarRidsLineasEmp();
                v2SyncCurrentDetailToStore();
                data['actividades'] = g_v2Lineas.filter(function(l) {
                    return !v2LaborLineaVacia(l);
                });
            } else {
                data['actividades'] = $('#tableActividad').getGridBatch();
            }
            selectSemana = $('#' + formulario).find('#Act_Sem').find('option:selected');
            selectFinca = $('#' + formulario).find('#Fnc_Cod_D').find('option:selected');
            responsable = $('#' + formulario).find('#Act_Res').val();
        }
        if (formulario === "frm_mod_act_edi") {
            data['actividades'] = $('#tableActividadMod').getGridBatch();
            selectSemana = $('#' + formulario).find('#Act_Sem').find('option:selected');
            selectFinca = $('#' + formulario).find('#Fnc_Cod').find('option:selected');
            responsable = $('#' + formulario).find('#Act_Res').val();
            cambio = true;
        }
        if (formulario === 'frm_mod_actividad' && $('#modRegActividadV2').length) {
            if (!g_modActividadCab || !v2ModCabActCod()) {
                $.alert('Busque una actividad (per&iacute;odo, finca y semana) antes de guardar.', null, 'remove');
                return false;
            }
            v2ModNormalizarRidsLineasEmp();
            v2ModSyncCurrentDetailToStore();
            var cabM = g_modActividadCab;
            data['Act_Cod'] = v2ModCabActCod();
            data['Pec_Cod'] = $('#modq_Pec_Cod').val();
            data['Fnc_Cod'] = $('#modq_Fnc_Cod').val();
            data['Act_Sem'] = $('#modq_Act_Sem').val();
            data['Act_Fec_Mod'] =
                cabM.Act_Fec != null && cabM.Act_Fec !== ''
                    ? String(cabM.Act_Fec)
                    : cabM.act_fec != null
                      ? String(cabM.act_fec)
                      : '';
            data['Act_Res'] = $.trim($('#modq_Act_Res').val() || '');
            if (data['Act_Res'] === '' && cabM) {
                data['Act_Res'] =
                    cabM.Act_Res != null && cabM.Act_Res !== ''
                        ? String(cabM.Act_Res)
                        : cabM.act_res != null
                          ? String(cabM.act_res)
                          : '';
            }
            data['actividades'] = g_modV2Lineas.filter(function(l) {
                return !v2LaborLineaVacia(l);
            }).map(function(l) {
                return {
                    Lab_Cod: l.Lab_Cod,
                    Lab_Des: l.Lab_Des,
                    Lab_Val: l.Lab_Val,
                    Tpg_Des: l.Tpg_Des,
                    Det_Obs: l.Det_Obs,
                    Det_Fec_Mod: l.Det_Fec,
                    Det_Can_Mod: l.Det_Can,
                    Per_Cod: l.Per_Cod,
                    Personal: l.Personal,
                    Total: l.Total,
                    index: l.index
                };
            });
            selectSemana = $('#modq_Act_Sem').find('option:selected');
            selectFinca = $('#modq_Fnc_Cod').find('option:selected');
            responsable = data['Act_Res'];
            cambio = true;
        }

        //console.log(responsable);
        if ((selectSemana.val()) * 1 <= 0) { $.alert('Debe Seleccionar una semana!<br/>Revise los datos.', null, 'remove'); return false; }
        if ((selectFinca.val()) * 1 <= 0) { $.alert('Debe Seleccionar una finca!<br/>Revise los datos.', null, 'remove'); return false; }
        if (responsable === '') { $.alert('Asignar un responsable!<br/>Revise los datos.', null, 'remove'); return false; }
        console.log(data['actividades']);
        $.each(data['actividades'], function(pos, valor) {
            //console.log(valor['Lab_Cod']);
            //console.log(valor['Lab_Des']);
            //console.log(valor['Lab_Val']);
            //console.log(valor['Per_Cod']);
            //console.log(valor['Personal']);
            //console.log(valor['Total']);
            //console.log(valor['Tpg_Des']);


            if (valor['Lab_Cod'] === '' || valor['Lab_Des'] === '' || valor['Lab_Val'] === '' || valor['Per_Cod'] === '' || valor['Personal'] === '' || valor['Total'] === '' || valor['Tpg_Des'] === '' || parseFloat(valor['Total']) * 1 === 0) {
                if (formulario === "frm_mod_act_edi") { index = $("#tableActividadMod").jqGrid('getInd', valor['index']); }
                if (formulario === 'frm_mod_actividad' && $('#modRegActividadV2').length) {
                    index = valor['index'];
                }
                if (formulario === "frm_alt_actividad") {
                    if ($('#regActividadV2').length) {
                        index = valor['index'];
                    } else {
                        index = $("#tableActividad").jqGrid('getInd', valor['index']);
                    }
                }

                indiceAct = index;
                vfG = true;
                return false;
            }
        });
        if (vfG) {
            $.alert('Verifique la informaci�n en la fila: ' + indiceAct);
            return false;
            vfG = false;
        }
        if ((data['actividades'].length) < 1) { $.alert('Debe existir al menos un registro en registro de actividades..!!'); return false; }


    }
    $.arraySpliceFields(data['actividades'], ['index', 'Personal', 'Lab_Des', 'delete', '_v2_emp_rid']);
    $.createDialogConfirm('�Est&aacute; seguro que desea guardar los cambios?', null, function() {
        $.saveDataJson('', data, function(resp) {
            if (resp['success']) {
                console.log(resp);
                if (dialogo === 'unidad') {
                    searchLaborPago(); /*$('.select_unidad').append($('<option>', { value: resp['tipoPago']['Tpg_Cod'], text: resp['tipoPago']['Tpg_Des'] }));*/
                }
                if (formulario === 'frmFinca') {
                    $('.select_finca').append($('<option>', { value: resp['finca']['Fnc_Cod'], text: resp['finca']['Fnc_Des'] }));
                }
                $('#' + formulario)[0].reset();
                $('#' + dialogo + 'Dialog').dialog('close');
                $('.select_unidad option:selected').removeAttr('selected');
                $('#Tpg_Cod').val('').trigger('chosen:updated');
                $('input:text[name=Act_Fec]').val($.datepicker.formatDate(shortDateFormat, new Date()));
                if ($('#regActividadV2').length && v2LabIsNative()) {
                    v2LabClear();
                } else {
                    $('#tableActividad').clearGrid();
                }
                $('#tableActividadMod').clearGrid();
                if ($('#regActividadV2').length) {
                    v2EmpClearAll();
                    g_v2Lineas = [];
                    g_v2EmpMeta = {};
                    g_v2SelectedEmpRowId = null;
                }
                if ($('#modRegActividadV2').length) {
                    v2ModResetPaneles();
                    g_modV2Lineas = [];
                    g_modV2EmpMeta = {};
                    g_modV2SelectedEmpRowId = null;
                }
                if (cambio) { $('#divEdic').moveComp('#tab3').updateGridsSizes(); }
                $.alert('La transaccii&oacute;n se realizo con exito.');
                //$("#Tpg_Cod").find('option').removeAttr("selected");
                //$("#Tpg_Cod").val([]);
                refreshData();
                return false;
            }
        });
    });
}



function v2LaborPrefetchCatalogFromAjax() {
    return new Promise(function(resolve, reject) {
        $.getDataJson(
            '',
            { laborAjax: true },
            function(resultado) {
                if (resultado.listLab && resultado.listLab.length) {
                    v2LaborSetCatalog(resultado.listLab);
                } else {
                    v2LaborSetCatalog([]);
                }
                resolve(resultado);
                return false;
            },
            function(err) {
                reject(err);
                return false;
            }
        );
    });
}

function refreshData() {
    var jobs = [];
    if ($('#detaLabores').length) {
        $('#detaLabores').clearGrid();
        jobs.push(loadDataTable());
    } else {
        jobs.push(v2LaborPrefetchCatalogFromAjax());
    }
    if ($('#detaFincas').length) {
        $('#detaFincas').clearGrid();
        jobs.push(loadDataFincas());
    }
    if (jobs.length) Promise.all(jobs);
}

function abrirDialogPersonal(personal) {
    $('#personalDialog').dialog('open');
    //console.log('personal', personal);
    $('#CodFormBus').val(personal);
}

function abrirDialogLabor(labor) {
    //console.log(labor);
    var id = labor;
    var lidStr = String(labor);
    if ($('#regActividadV2').length && v2LabIsNative() && v2LabRow$(lidStr).length) {
        if (!g_v2SelectedEmpRowId) {
            $.alert('Seleccione un empleado en la lista de la izquierda (fila resaltada).', null, 'remove');
            return;
        }
        var $ld = v2LabRow$(lidStr).find('#' + lidStr + '_Lab_Des');
        setTimeout(function() {
            $ld.trigger('focus');
        }, 0);
        return;
    }
    if ($('#modRegActividadV2').length && v2ModLabIsNative() && v2ModLabRow$(lidStr).length) {
        if (!g_modV2SelectedEmpRowId) {
            $.alert('Seleccione un empleado en la lista de la izquierda (fila resaltada).', null, 'remove');
            return;
        }
        var $ldM = v2ModLabRow$(lidStr).find('#' + lidStr + '_Lab_Des');
        setTimeout(function() {
            $ldM.trigger('focus');
        }, 0);
        return;
    }
    if ($('#regActividadV2').length) {
        if (!g_v2SelectedEmpRowId) {
            $.alert('Seleccione un empleado en la lista de la izquierda (fila resaltada).', null, 'remove');
            return;
        }
        if (id > 0) {
            $('#laboresDialog').dialog('open');
            $('#CodFormBusLab').val(labor);
        }
        return;
    }
    if (id > 0) {
        $('#laboresDialog').dialog('open');
        var trabajador_data = $('#tableActividad').jqGrid('getRowData', id);
        //console.log(trabajador_data);
        $('#CodFormBusLab').val(labor);

    } else {
        $.alert('Debe seleccionar un Trabajador antes.!!');
    }


}

function agregarFila(aux) {

    if (aux > 0) {
        esMod = true;
        var $this = $('#tableActividadMod');
        var campoGrid = '_Det_Can_Mod';
        var fecha = '_Det_Fec_Mod';
        var $form = 'frm_mod_act_edi';
    } else {
        if ($('#regActividadV2').length && v2LabIsNative()) {
            v2AddLaborFila();
            return;
        }
        esCrear = true;
        var $this = $('#tableActividad');
        var campoGrid = '_Det_Can';
        var fecha = '_Det_Fec'
        var $form = 'frm_alt_actividad';
    }
    var sel_fecha = $('#' + $form).find("#Pec_Cod").find('option:selected');
    var id = $this.nextIndex();
    $this.jqGrid('addRowData', id, { index: id });
    $this.jqGrid('editRow', id);
    $.createDatePickers('#' + id + fecha);
    $this.find('tr#' + id).find('#' + id + '_Personal').focus();
    $('#' + id + fecha).dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
    $this.find('tr#' + id).find('#' + id + campoGrid).on('change', function() {
        //console.log('entro en el change');
        var vlInput = validaDecimal($this.find('tr#' + id).find('#' + id + campoGrid).val());
        if (vlInput) {
            $.alert('El valor de la cantidad debe ser mayor que 0');
        }
        makeCalculation(0);
        $this.find('tr#' + id).find('#' + id + campoGrid).focus();

    }).trigger('change');

}

function validaDecimal(valor) {
    var verifica = false;
    if (parseFloat(valor) * 1 <= 0) {
        verifica = true;
    }
    return verifica;
}

function quitarActividad(row) {
    var rid = v2ButtonRowId(row, $('#tableActividad'));
    if (!rid && row && row.id != null && String(row.id) !== '') rid = String(row.id);
    if (!rid) return;
    $.createDialogConfirm('Desea Eliminar el item seleccionado..!!', null, function() {
        if (v2ModLabIsNative() && v2ModLabRow$(rid).length) {
            v2ModLabRow$(rid).remove();
            v2ModLabRenumberRows();
            v2ModLabUpdateStatusCount();
            v2ModMakeCalculation();
            v2ModSyncCurrentDetailToStore();
            v2ModRefreshTotalesEmpleados();
            v2ModMarkCurrentEmpLaboresDirty();
            v2ModEnsureTrailingEmptyLabRowMod();
        } else if (v2LabIsNative()) {
            v2LabRow$(rid).remove();
            v2LabRenumberRows();
            v2LabUpdateStatusCount();
            makeCalculation(0);
            v2SyncCurrentDetailToStore();
            v2RefreshTotalesEmpleados();
            v2EnsureTrailingEmptyLabRowReg();
        } else {
            $('#tableActividad').jqGrid('delRowData', rid);
            if ($('#regActividadV2').length) {
                v2SyncCurrentDetailToStore();
                v2RefreshTotalesEmpleados();
            }
        }
    });
}

function quitarActividadMod(row) {
    $.createDialogConfirm('Desea Eliminar el item seleccionado..!!', null, function() {
        $('#tableActividadMod').jqGrid('delRowData', row.id);
    });
}

function delLabor(row) {
    $.createDialogConfirm('Desea Eliminar la labor seleccionada..!!', null, function() {
        $.saveDataJson("", { elimLabor: true, Lab_Cod: row['Lab_Cod'] }, (respuesta) => {
            if (respuesta['success']) {
                $('#detaLabores').jqGrid('delRowData', row.id);
                //$('#formDatosLabor').updateGridsSizes();
                $('#detaLabores').trigger("reloadGrid");
                $.alert('La transacci&oacute;n se realizo con exito.');
                return false;
            }
        });

    });

}

function updLabor(row) {
    $("#Lab_Cod_Upd").val(row.id);
    $('#laborDialog').dialog('open');
    $('#Lab_Des_Upd').val(row.Lab_Des);
    $('#Tpg_Cod_Id').val(row.Tpg_Cod);
    $('#Lab_Val_Upd').val(row.Lab_Val);
}


/**
 * Obtiene el número de semana actual (1-53) basado en la fecha seleccionada en el formulario o en la fecha de hoy.
 */
function v2ObtenerSemanaActual() {
    var sem = 0;
    var fecVal = $('#frm_alt_actividad #Act_Fec').val();
    if (typeof moment !== 'undefined') {
        if (fecVal && moment(fecVal, 'YYYY-MM-DD', true).isValid()) {
            sem = moment(fecVal, 'YYYY-MM-DD').isoWeek();
        } else {
            sem = moment().isoWeek();
        }
    }
    if (!sem || isNaN(sem) || sem < 1) {
        var d = fecVal ? new Date(String(fecVal).replace(/-/g, '/')) : new Date();
        if (isNaN(d.getTime())) d = new Date();
        var target = new Date(d.valueOf());
        var dayNr = (d.getDay() + 6) % 7; // Lunes = 0, Domingo = 6
        target.setDate(target.getDate() - dayNr + 3); // Jueves ISO
        var firstThursday = target.valueOf();
        target.setMonth(0, 1);
        if (target.getDay() !== 4) {
            target.setMonth(0, 1 + ((4 - target.getDay()) + 7) % 7);
        }
        sem = 1 + Math.ceil((firstThursday - target) / 604800000);
    }
    if (sem < 1) sem = 1;
    if (sem > 53) sem = 53;
    return sem;
}

function generarSemanas() {
    const contenido = 'Semana ';
    var semActual = v2ObtenerSemanaActual();
    $('.select_semna').each(function() {
        var $el = $(this);
        if ($('#regActividadV2').length && $el.closest('#frm_alt_actividad').length && $el.attr('id') === 'Act_Sem') {
            return;
        }
        for (var i = 1; i < 53; i++) {
            $el.append($('<option>', { value: i, text: contenido + i }));
        }
        if ((!$el.val() || $el.val() === '0') && $el.find('option[value="' + semActual + '"]').length) {
            $el.val(String(semActual));
        }
    });
}

/** Reconstruye el combo Semana del registro v2: solo semanas sin actividad para per&iacute;odo + finca. Carga la semana actual por defecto. */
function v2ConstruirOpcionesSemanasRegistro(ocupadasNums) {
    var $sel = $('#frm_alt_actividad #Act_Sem');
    if (!$sel.length) return;
    var prevVal = $sel.val();
    var contenido = 'Semana ';
    $sel.find('option').not('[value="0"]').remove();
    var map = {};
    if (ocupadasNums && ocupadasNums.length) {
        var j;
        for (j = 0; j < ocupadasNums.length; j++) {
            map[parseInt(ocupadasNums[j], 10)] = true;
        }
    }
    var w;
    for (w = 1; w < 53; w++) {
        if (map[w]) continue;
        $sel.append($('<option>', { value: w, text: contenido + w }));
    }
    if (prevVal && prevVal !== '0' && $sel.find('option[value="' + prevVal + '"]').length) {
        $sel.val(prevVal);
    } else {
        var semActual = v2ObtenerSemanaActual();
        if ($sel.find('option[value="' + semActual + '"]').length) {
            $sel.val(String(semActual));
        } else {
            $sel.val('0');
        }
    }
}

function v2RefrescarSelectSemanas() {
    if (!$('#regActividadV2').length) return;
    var pec = $('#frm_alt_actividad #Pec_Cod').val();
    var fnc = $('#frm_alt_actividad #Fnc_Cod_D').val();
    if (!pec || parseInt(pec, 10) <= 0 || !fnc || parseInt(fnc, 10) <= 0) {
        v2ConstruirOpcionesSemanasRegistro(null);
        verificaExistente();
        return;
    }
    $.getDataJson(
        '',
        { semanasOcupadasAjax: true, Pec_Cod: pec, Fnc_Cod: fnc },
        function(res) {
            if (!res || !res.success) {
                v2ConstruirOpcionesSemanasRegistro(null);
            } else {
                v2ConstruirOpcionesSemanasRegistro(res.ocupadas || []);
            }
            $('#frm_alt_actividad').find('#Act_Sem').fieldValid(true);
            $('#frm_alt_actividad').find('#Fnc_Cod_D').fieldValid(true);
            $('#frm_alt_actividad').find('#btn_gua_act').removeAttr('disabled');
            verificaExistente();
        },
        function() {
            v2ConstruirOpcionesSemanasRegistro(null);
            verificaExistente();
        }
    );
}

function v2OnFincaRegistroActividadChanged() {
    v2RefrescarSelectSemanas();
    v2UpdateRegEmpActionButtons();
}

function getFincas() {
    return new Promise((resolve, reject) => {
        var $needAjax = $('.select_finca').filter(function() {
            return $(this).find('option').length <= 1;
        });
        function afterFincas(resultado) {
            if ($('#regActividadV2').length) {
                v2RefrescarSelectSemanas();
            }
            if ($('#modq_Pec_Cod').length) {
                v2InitModificarActividadV2();
            }
            resolve(resultado || { success: true });
        }
        if (!$needAjax.length) {
            afterFincas({ success: true, listaFincas: [] });
            return;
        }
        $.getDataJson('', { fincasAjax: true }, (resultado) => {
            if (resultado.listaFincas) {
                $needAjax.each(function() {
                    var $sel = $(this);
                    resultado.listaFincas.forEach(function(valor) {
                        if (!$sel.find('option[value="' + valor.Fnc_Cod + '"]').length) {
                            $sel.append($('<option>', { value: valor.Fnc_Cod, text: valor.Fnc_Des }));
                        }
                    });
                });
            }
            afterFincas(resultado);
        }, (err) => {
            reject(err);
        });
    });
}

function loadDataTable() {
    if (!$('#detaLabores').length) {
        return v2LaborPrefetchCatalogFromAjax();
    }
    var next = $('#detaLabores').jqGrid('getCol', 'index', false, 'max');
    next = isNaN(next) ? 1 : next + 1;

    return new Promise((resolve, reject) => {
        //
        $.getDataJson(
            '', { laborAjax: true },
            function(resultado) {
                if (resultado.listLab && resultado.listLab.length > 0) {
                    v2LaborSetCatalog(resultado.listLab);
                    resolve($('#detaLabores').setRows(resultado.listLab));
                } else {
                    v2LaborSetCatalog([]);
                    resolve($('#detaLabores').jqGrid('addRowData', next, $.extend({ index: next, Lab_Des: 'No se Encontraron Registros' }), 'last'));
                }

            },
            function(err) {
                reject(err);
            }
        );
    });
}

function loadDataFincas() {
    if (!$('#detaFincas').length) {
        return Promise.resolve();
    }
    var next = $('#detaFincas').jqGrid('getCol', 'index', false, 'max');
    next = isNaN(next) ? 1 : next + 1;
    return new Promise((resolve, reject) => {
        $.getDataJson(
            '', { fincasAjax: true },
            function(resultado) {
                if (resultado.listaFincas.length > 0) {
                    resolve($('#detaFincas').setRows(resultado.listaFincas));
                } else {
                    resolve($('#detaFincas').jqGrid('addRowData', next, $.extend({ index: next, Fnc_Des: 'No se Encontraron Registros' }), 'last'));
                }
            },
            function(err) {
                reject(err);
            }
        );
    });
}

function validarUnidad() {
    var inputValor = $('#Tpg_Des').val().replace(/ /g, '');
    if (inputValor.length > 0) {
        $.getDataJson('', { verificaDesc: true, Tpg_Des: inputValor }, function(resultado) {
            if (resultado.tipPagoDesc.length > 0) {
                $('#Tpg_Des').fieldValid(false, 'El nombre ' + inputValor + ' ya se encuentra registrado');
                $('#btn_gua').attr('disabled', 'disabled');
                $('#Tpg_Des').val('');
            } else {
                $('#Tpg_Des').fieldValid(true);
                $('#btn_gua').removeAttr('disabled');
            }
        });
    } else {
        $('#Tpg_Des').fieldValid(false, 'Escriba una Descripci�n del Registro');
    }
}

function verificaExistente() {
    v2UpdateRegEmpActionButtons();
    var sel_semana = $('#frm_alt_actividad').find("#Act_Sem").find('option:selected');
    var sel_finca = $('#frm_alt_actividad').find("#Fnc_Cod_D").find('option:selected');
    var sel_periodo = $('#frm_alt_actividad').find("#Pec_Cod").find('option:selected');
    if (sel_semana.val() > 0 && sel_finca.val() > 0) {
        $.getDataJson('', { verificaFincaSemana: true, Fnc_Cod: sel_finca.val(), Act_Sem: sel_semana.val() , Pec_Cod: sel_periodo.val() }, (resultado) => {
            if (resultado.fincaSemana.length > 0) {
                $('#frm_alt_actividad').find('#Act_Sem').fieldValid(false, 'La <u>Semana ' + resultado.fincaSemana['0']['Act_Sem'] + '</u> ya se encuentra registrada');
                $('#frm_alt_actividad').find('#Fnc_Cod_D').fieldValid(false, 'La finca <u>' + resultado.fincaSemana['0']['Fnc_Des'] + '</u> ya se encuentra registrada con la <u>Semana ' + resultado.fincaSemana['0']['Act_Sem'] + '</u>');
                $('#frm_alt_actividad').find('#btn_gua_act').attr('disabled', 'disabled');
                $('.select_semna option:selected').removeAttr('selected');
                $('.select_finca option:selected').removeAttr('selected');
                //
            } else {
                $('#frm_alt_actividad').find('#Act_Sem').fieldValid(true);
                $('#frm_alt_actividad').find('#Fnc_Cod_D').fieldValid(true);
                $('#frm_alt_actividad').find('#btn_gua_act').removeAttr('disabled');
            }
        });
    }
}


function searchLaborPago() {
    $.getDataJson('', { buscarLaborPago: true }, (respuesta) => {
        $('.select_unidad').empty();
        $('.select_unidad').val([]);
        $('.select_unidad').append($('<option>', { value: '', text: 'Seleccione....' }));
        if (respuesta.tipPago.length > 0) {
            respuesta.tipPago.forEach((resp) => {
                $('.select_unidad').append(
                    $('<option>', { value: resp['Tpg_Cod'], text: resp['Tpg_Des'] })
                );
            });
        }
        $('.select_unidad').trigger('chosen:updated');
    });
}

function selectPersonal(row) {
    if ($('#frm_mod_actividad').length && typeof g_modEmpLookupMode !== 'undefined' && g_modEmpLookupMode) {
        g_modEmpLookupMode = false;
        var pcL = row.Per_Cod != null ? row.Per_Cod : row.per_cod;
        var nmL =
            row.Personal != null && row.Personal !== ''
                ? String(row.Personal)
                : row.personal != null && row.personal !== ''
                  ? String(row.personal)
                  : '';
        $('#modq_lookup_Per_Cod').val(pcL != null ? String(pcL) : '');
        $('#modq_lookup_emp_nombre').val(nmL);
        $('#modq_match_panel').hide();
        $('#personalDialog').dialog('close');
        return false;
    }
    if ($('#modRegActividadV2').length && g_modV2EmpPickMode) {
        g_modV2EmpPickMode = false;
        var dupM = false;
        var idsM = v2ModEmpGetRowIds();
        var xm;
        for (xm = 0; xm < idsM.length; xm++) {
            if (String(v2ModGetEmpMeta(idsM[xm]).Per_Cod) === String(v2CleanVal(row.Per_Cod))) {
                dupM = true;
                break;
            }
        }
        if (dupM) {
            $.alert('Ese empleado ya est&aacute; en el listado.', null, 'remove');
            $('#personalDialog').dialog('close');
            return false;
        }
        var nidMod = v2ModEmpAppendRow(row.Per_Cod, row.Personal, '0.00');
        $('#personalDialog').dialog('close');
        v2ModSwitchEmpleado(nidMod);
        return false;
    }
    if ($('#regActividadV2').length && g_v2EmpPickMode) {
        g_v2EmpPickMode = false;
        var dup = false;
        var idsE = v2EmpGetRowIds();
        for (var xi = 0; xi < idsE.length; xi++) {
            var rpc = v2GetEmpMeta(idsE[xi]).Per_Cod;
            if (String(rpc) === String(v2CleanVal(row.Per_Cod))) {
                dup = true;
                break;
            }
        }
        if (dup) {
            $.alert('Ese empleado ya est&aacute; en el listado.', null, 'remove');
            $('#personalDialog').dialog('close');
            return false;
        }
        var nidStr = v2EmpAppendRow(row.Per_Cod, row.Personal, '0.00');
        $('#personalDialog').dialog('close');
        v2SwitchEmpleado(nidStr);
        return false;
    }
    //console.log('Modificar: ', esMod); console.log('Es crear:', esCrear);  console.log(row);
    if (esMod && !esCrear || !esMod && !esCrear) {
        //esMod = false;
        nameGrid = 'tableActividadMod';
        nameForm = 'frm_mod_act_edi';
        parametro = '_Det_Can_Mod';


    }
    if (!esMod && esCrear) {
        //esCrear = false;
        nameGrid = 'tableActividad';
        nameForm = 'frm_alt_actividad';
        parametro = '_Det_Can';
    }

    var ids = $('#' + nameGrid).jqGrid('getDataIDs');
    var datose = $('#' + nameGrid).jqGrid('getRowData');

    var data = { 'items': $('#' + nameGrid).getGridBatch() };

    var change = true;
    if (change) {
        var id = $('#CodFormBus').val();
        $('#' + nameGrid).changeRow($('#CodFormBus').val(), row);
        $('#' + nameGrid).find('tr#' + id).setData(row, false);
        $('#' + nameGrid).find('tr#' + id + parametro).val('');
        $('#' + nameGrid).find('tr#' + id + '_Total').val('');
        $('#personalDialog').dialog('close');
        $.createDatePickers('#' + id + '_Det_Fec_Mod');
        /* if (nameGrid === 'tableActividadMod') {
            $('#' + nameGrid).find('#' + id + parametro).on('change', function() {
                console.log('entro en el onchange select PErsonal');
                $('#btn_guardado').prop('disabled', false);
            }).trigger('change');

        } */
    }
    //$('#tableActividad').changeRow($('#CodFormBus').val(), row); //$('#tableActividad').find('tr#' + id).setData(row, false);//$('#' + id + '_Det_Can').val('');  //$('#' + id + '_Total').val('');  //$('#personalDialog').dialog('close');
}

function selectLabor(row) {
    var busLabId = $('#CodFormBusLab').val();
    if ($('#regActividadV2').length && v2LabIsNative() && v2LabRow$(busLabId).length) {
        return v2SelectLaborNativeReg(busLabId, row);
    }
    if ($('#modRegActividadV2').length && v2ModLabIsNative() && v2ModLabRow$(busLabId).length) {
        return v2SelectLaborNativeMod(busLabId, row);
    }
    //console.log('Modificar: ', esMod); console.log('Es crear:', esCrear);  console.log(row);
    if (esMod && !esCrear || !esMod && !esCrear) {
        esMod = false;
        nameGrid = 'tableActividadMod';
        nameForm = 'frm_mod_act_edi';
        parametro = '_Det_Can_Mod';


    }
    if (!esMod && esCrear) {
        esCrear = false;
        nameGrid = 'tableActividad';
        nameForm = 'frm_alt_actividad';
        parametro = '_Det_Can';
    }
    var ids = $('#' + nameGrid).jqGrid('getDataIDs');

    var datose = $('#' + nameGrid).jqGrid('getRowData');

    var data = { 'items': $('#' + nameGrid).getGridBatch() };

    var change = true;

    if (change) {
        var id = $('#CodFormBusLab').val();

        var trabajador_data;
        if ($('#regActividadV2').length && nameGrid === 'tableActividad') {
            if (!g_v2SelectedEmpRowId) {
                $('#laboresDialog').dialog('close');
                $.alert('Seleccione un empleado en la lista izquierda.', null, 'remove');
                return false;
            }
            trabajador_data = v2GetEmpMeta(g_v2SelectedEmpRowId);
        } else {
            trabajador_data = $('#' + nameGrid).jqGrid('getRowData', id);
        }
        if (trabajador_data['Per_Cod'] === '') {
            $('#laboresDialog').dialog('close');
            $.alert('Debe Seleccionar un trabajador previamente!<br/>Revise los datos.', null, 'remove');
            return false;
        } else {
            row['Per_Cod'] = trabajador_data['Per_Cod'];
            row['Personal'] = trabajador_data['Personal'];
            if (nameGrid === 'tableActividad' && $('#regActividadV2').length) {
                row['_v2_emp_rid'] = v2EmpDomRowIdFromAny(g_v2SelectedEmpRowId);
            }
            //console.log(row);
            $('#' + nameGrid).changeRow($('#CodFormBusLab').val(), row);
            $('#' + nameGrid).find('tr#' + id).setData(row, false);
            $('#' + nameGrid).find('tr#' + id + parametro).val('');
            $('#' + nameGrid).find('tr#' + id + '_Total').val('');
            if (nameGrid === 'tableActividadMod') {
                $.createDatePickers('#' + id + '_Det_Fec_Mod');
            } else if (nameGrid === 'tableActividad') {
                $.createDatePickers('#' + id + '_Det_Fec');
            }
            $('#laboresDialog').dialog('close');
            if (nameGrid === 'tableActividadMod') {

                $('#' + nameGrid).find('#' + id + parametro).on('change', function() {
                    //console.log('entro en el onchange select PErsonal');
                    /* var vlInput = validaDecimal($('#' + nameGrid).find('#' + id + parametro).val());
                    if (vlInput) {
                        $.alert('El valor de la cantidad debe ser mayor que 0');
                    } */
                    makeCalculation(1);
                    $('#btn_guardado').prop('disabled', false);
                }).trigger('change');

            }
            if (nameGrid === 'tableActividad' && $('#regActividadV2').length) {
                $('#' + nameGrid).find('#' + id + parametro).on('change', function() {
                    makeCalculation(0);
                    v2RefreshTotalesEmpleados();
                }).trigger('change');
            }

        }

    }



    //console.log(row);
    //$('#tableActividad').changeRow($('#CodFormBusLab').val(), row);
    //$('#tableActividad').find('tr#' + id).setData(row, false);
    // $('#' + id + '_Det_Can').val('');
    //$('#' + id + '_Total').val('');
    //$('#laboresDialog').dialog('close');
}

function makeCalculation(aux) {
    if (aux > 0) {
        //tableActividadMod
        var gridAct = $('#tableActividadMod');
        var ids = $('#tableActividadMod').jqGrid('getDataIDs');
        var datos = $('#tableActividadMod').jqGrid('getRowData');
        var campo = "Det_Can_Mod";
    } else {
        if ($('#regActividadV2').length && v2LabIsNative()) {
            v2LabGetRowIds().forEach(function(lid) {
                var $tr = v2LabRow$(lid);
                var can = $.trim($tr.find('#' + lid + '_Det_Can').val());
                var lv = $.trim($tr.find('#' + lid + '_Lab_Val').val());
                if (can !== '' && lv !== '') {
                    var tot = (parseFloat(can) || 0) * (parseFloat(lv) || 0);
                    $tr.find('#' + lid + '_Total').val(tot.toFixed(2));
                } else {
                    $tr.find('#' + lid + '_Total').val('');
                }
            });
            var sum = 0;
            v2LabGetRowIds().forEach(function(lid) {
                var t = parseFloat($('#' + lid + '_Total').val());
                if (!isNaN(t)) sum += t;
            });
            v2LabUpdateFooterSum(sum);
            $('#_Total').val(sum.toFixed(2));
            v2RefreshTotalesEmpleados();
        }
        if (typeof v2ModLabIsNative === 'function' && v2ModLabIsNative()) {
            v2ModMakeCalculation();
        }
        if ($('#regActividadV2').length && v2LabIsNative()) {
            return;
        }
        if (typeof v2ModLabIsNative === 'function' && v2ModLabIsNative()) {
            return;
        }
        var gridAct = $('#tableActividad');
        var ids = $('#tableActividad').jqGrid('getDataIDs');
        var datos = $('#tableActividad').jqGrid('getRowData');
        var campo = "Det_Can";
    }

    for (var i = 0; i < datos.length; i++) {
        var columna = gridAct.jqGrid('getCell', ids[i], 'index');
        var valorId = datos[i]['index'];
        if (datos[i]['' + campo] != '' && datos[i]['Lab_Val'] !== '') {
            //console.log(datos[i]['' + campo] * 1);
            //console.log(parseFloat(datos[i]['Lab_Val']));
            datos[i]['Total'] = (parseFloat(datos[i]['' + campo]) * 1) * (parseFloat(datos[i]['Lab_Val']) * 1);
            gridAct.find('#' + valorId + '_Total').val(parseFloat(datos[i]['Total']).toFixed(2));
        }
    }

    // Calcular el nuevo total general
    var sum = gridAct.jqGrid('getCol', 'Total', false, 'sum');
    // Actualizar el valor de la columna "Total" en el pie del jqGrid
    $('td[aria-describedby="tableActividad_Total"]:last').text(sum);
    $('#_Total').val(sum.toFixed(2));
    if (aux === 0 && $('#regActividadV2').length) {
        v2RefreshTotalesEmpleados();
    }

}
/**
 * start de methods for modificar actividades
 */
function editActividad(row) {
    var gridAct = $('#tableActividadMod');

    arrayD.length = 0;
    const detMod = async() => { arrayD = await doSomethingAsync(row); }
    detMod().then(() => {
        $("#loader").show();

        $('#tab3').moveComp('#divEdic').updateGridsSizes();

        $('#divEdic').setData(row);
        $("#loader").show();
        var sel_fecha = $('#frm_mod_act_edi').find("#Pec_Cod").find('option:selected');
        $('#Act_Fec_Mod').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
        $.createDatePickers('#Act_Fec_Mod', '#Det_Fec_Mod');
        $('#Act_Fec_Mod').val(row['Act_Fec']);
        $('#Det_Fec_Mod').val(row['Det_Fec']);
        $('#Act_Fec_Mod').trigger('change');
        detalleMod(arrayD);
        $("#loader").hide();
        // Calcular el nuevo total general
        var sum = gridAct.jqGrid('getCol', 'Total', false, 'sum');
        // Actualizar el valor de la columna "Total" en el pie del jqGrid
        $('#_Total').val(sum.toFixed(2));
    });
}

//View info
function viewInfo(row) {
    //console.log(row);
    $('#dialogInfo').setData(row);
    $('#dialogInfo').dialog('open');
}

//hide
function clearDocument() {
    $('#divEdic').hide();
}
async function doSomethingAsync(row) {
    let result = await viewActividad(row);
    return result;
}

async function detalleMod(row) {
    //tableActividadMod
    //tableActividadModPager
    var gridAct = $('#tableActividadMod');
    $('#tableActividadMod').clearGrid(true);
    var total = 0;
    //const detMod = await viewActividad(row);
    //const detMod = await doSomethingAsync(row);

    var sel_fecha = $('#frm_mod_act_edi').find("#Pec_Cod").find('option:selected');
    $("#loader").show();
    //detMod
    row.forEach((respuesta) => {

        total = ((respuesta['Det_Val'] * 1) * (respuesta['Det_Can'] * 1));
        //console.log(respuesta);
        var next = $("#tableActividadMod").jqGrid('getCol', 'index', false, 'max');
        next = (isNaN(next) ? 1 : next + 1);
        $("#tableActividadMod").jqGrid('addRowData', next, $.extend(respuesta, { index: next, Det_Cod: respuesta['Det_Cod'], Det_Fec_Mod: respuesta['Det_Fec'], Act_Cod: respuesta['Act_Cod'], Lab_Cod: respuesta['Lab_Cod'], Per_Cod: respuesta['Per_Cod'], Det_Can_Mod: parseFloat(respuesta['Det_Can']).toFixed(2), Det_Obs: respuesta['Det_Obs'], Total: parseFloat(total).toFixed(2) }), 'last');
        $.createDatePickers('#' + next + '_Det_Fec_Mod');
        $('#' + next + '_Det_Fec_Mod').dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'));
        $('#divEdic').updateGridsSizes();

        $("#tableActividadMod").find('#' + next + "_Personal").val(respuesta['personal']);
        $("#tableActividadMod").find('#' + next + "_Lab_Des").val(respuesta['Lab_Des']);
        $("#tableActividadMod").find('#' + next + "_Tpg_Des").val(respuesta['Tpg_Des']);
        $("#tableActividadMod").find('#' + next + "_Det_Fec_Mod").val(respuesta['Det_Fec']);
        $("#tableActividadMod").find('#' + next + "_Det_Obs").val(respuesta['Det_Obs']);
        $("#tableActividadMod").find('#' + next + "_Lab_Val").val(respuesta['Lab_Val']);
        $("#tableActividadMod").find('#' + next + "_Lab_Cod").val(respuesta['Lab_Cod']);
        $("#tableActividadMod").find('#' + next + "_Per_Cod").val(respuesta['Per_Cod']);
        $("#tableActividadMod").find('#' + next + "_Det_Can_Mod").val(respuesta['Det_Can_Mod']);
        $("#tableActividadMod").find('#' + next + "_Total").val(respuesta['Total']);
        $('#tableActividadMod').find('#' + next + '_Det_Can_Mod').on('change', function() {
            //console.log('entro en el onchange del modificar');
            var vlInput = validaDecimal($('#tableActividadMod').find('#' + next + '_Det_Can_Mod').val());
            if (vlInput) {
                $.alert('El valor de la cantidad debe ser mayor que 0');
            }
            makeCalculation(1);
            $('#btn_guardado').prop('disabled', false);
        }).trigger('change');



        //console.log(respuesta);
    });
    if (row.length > 0) { $("#loader").hide(); }

    // Calcular el nuevo total general
    var sum = gridAct.jqGrid('getCol', 'Total', false, 'sum');
    // Actualizar el valor de la columna "Total" en el pie del jqGrid
    $('#_Total').val(sum);
}


//Muestra el detalle apartir del Cod de la cabecera
function viewActividad(row) {
    //console.log(row);
    return new Promise((resolve, reject) => {
        $.getDataJson("", { searchDetActivi: true, Act_Cod: row['Act_Cod'] }, (result) => {
            resolve(result.detalleAct);
        }, (err) => {
            reject(err);
        });
    });


}
//Cargar todo el detalle existente
function viewActividadAll() {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { searchAllDetail: true }, (result) => {
            resolve(result.allDetail);
        }, (err) => {
            reject(err);
        });
    })
}



function verficarSemana() {
    var verifica = false;
    //console.log(verifica);
    var semana = $('#inlineRadio1').val();

    if ($("#inlineRadio1").prop('checked') && $("#rad_ba1").prop('checked')) {
        //$('#frm_mod_actividad').find('#search').attr('disabled', 'disabled');
        $('#selectSelmana').show();
    }
    if ($("#inlineRadio2").prop('checked')) {
        $('#frm_mod_actividad').find('#Cod_Bus').removeAttr('disabled');
        $('#selectSelmana').hide();
    }
}
async function sFinca() {
    const fnks = await allFincas();
    fnks.forEach((respuesta) => {
        $('.select_search').append(
            $('<option>', { value: respuesta['Fnc_Des'], text: respuesta['Fnc_Des'] })
        );
    });
    $('.select_search').trigger('chosen:updated');
}
async function sTrabajadores() {
    const trbjs = await allTrabajadores();
    trbjs.forEach((respuesta) => {
        $('.select_search').append(
            $('<option>', { value: respuesta['Personal'], text: respuesta['Personal'] })
        );
    });
    $('.select_search').trigger('chosen:updated');


}

function busquedaInicial() {
    if ($("#rad_ba1").prop('checked')) {
        sFinca();

    }
    if ($("#rad_ba2").prop('checked')) {
        //console.log('lola2');
        sTrabajadores();
    }
}
$("#rad_ba3").on('click', function() {
    $('#divFecha').show();
    $('#Fec_Ini').removeAttr('disabled');
    $('#Fec_Fin').removeAttr('disabled');
    $('#frm_mod_actividad').find('#Cod_Bus').attr('disabled', 'disabled');
    $('.select_search').empty();
    sFinca();
});
$("#rad_ba2").on('click', function() {
    //console.log('lolita');
    //$('#frm_mod_actividad').find('#Cod_Bus').attr('disabled', 'disabled');
    $('#divFecha').hide();
    $('#Fec_Ini').attr('disabled', 'disabled');
    $('#Fec_Fin').attr('disabled', 'disabled');
    $('#frm_mod_actividad').find('#Cod_Bus').removeAttr('disabled');
    //$('.select_search option:selected').removeAttr('selected');
    //$('.select_search').val([])
    //$('.select_search').empty();
    //$('.select_search').val('').trigger('chosen:updated');
    limpiarBusq();
    sTrabajadores();
});
$("#rad_ba1").on('click', function() {
    //console.log('lola');
    $('#divFecha').hide();
    $('#Fec_Ini').attr('disabled', 'disabled');
    $('#Fec_Fin').attr('disabled', 'disabled');
    $('#frm_mod_actividad').find('#Cod_Bus').removeAttr('disabled');
    //$('.select_search').val([])
    //$('.select_search').empty();
    limpiarBusq();
    sFinca();
});

$("#Pec_Cod").on('change', function() {
    var sel_fecha = $(this).find('option:selected');
    fechas(sel_fecha.data('inicio'), sel_fecha.data('fin'), sel_fecha.data('placod'));
    $('#Act_Fec').trigger('change');
    if ($('#regActividadV2').length) {
        v2RefrescarSelectSemanas();
    }
});

//Funcion para setear el datepicker al periodo seleccionado
function fechas(inicio, fin, placod) {
    $('#Act_Fec').dateLimits(inicio, fin);
}

/** Semanas con actividad ya registrada (misma l&oacute;gica que ocupadas en registro) para el combo Modificar */
function v2ModRefrescarSemanas(done) {
    var $sel = $('#modq_Act_Sem');
    if (!$sel.length) return;
    var pec = $('#modq_Pec_Cod').val();
    var fnc = $('#modq_Fnc_Cod').val();
    $sel.find('option').not('[value="0"]').remove();
    if (!pec || parseInt(pec, 10) <= 0 || !fnc || parseInt(fnc, 10) <= 0) {
        if (typeof done === 'function') done();
        v2UpdateModEmpActionButtons();
        return;
    }
    $.getDataJson(
        '',
        { semanasOcupadasAjax: true, Pec_Cod: pec, Fnc_Cod: fnc },
        function(res) {
            var ocup = res && res.success && res.ocupadas ? res.ocupadas : [];
            var contenido = 'Semana ';
            var k;
            for (k = 0; k < ocup.length; k++) {
                var wn = parseInt(ocup[k], 10);
                if (wn > 0) {
                    $sel.append($('<option>', { value: wn, text: contenido + wn }));
                }
            }
            var semActual = v2ObtenerSemanaActual();
            if ((!$sel.val() || $sel.val() === '0') && $sel.find('option[value="' + semActual + '"]').length) {
                $sel.val(String(semActual));
            }
            if (typeof done === 'function') done();
            v2UpdateModEmpActionButtons();
            return false;
        },
        function() {
            if (typeof done === 'function') done();
            v2UpdateModEmpActionButtons();
        }
    );
}

function v2ModRowMatchFncSem(row) {
    if (!row) return { fnc: '', sem: '' };
    var fnc = row.Fnc_Cod != null ? row.Fnc_Cod : row.fnc_cod;
    var sem = row.Act_Sem != null ? row.Act_Sem : row.act_sem;
    return { fnc: fnc != null ? String(fnc) : '', sem: sem != null ? String(sem) : '' };
}

var v2ModqMatchSyncTimer = null;

/** Ancho del combo = solo el campo nombre (termina donde empieza &laquo;Elegir empleado&raquo;; el botón queda alineado debajo). */
function v2SyncModqMatchSelectWidth() {
    var $inp = $('#modq_lookup_emp_nombre');
    var $sel = $('#modq_actividad_match');
    var $row = $sel.closest('.v2-modq-match-row');
    if (!$inp.length || !$sel.length || !$row.length) return;
    if (!$('#modq_match_panel').is(':visible')) return;
    if (!$inp[0].getBoundingClientRect) return;
    var rIn = $inp[0].getBoundingClientRect();
    var w = Math.round(rIn.width);
    if (w < 80) return;
    var rowW = $row.outerWidth();
    var btnBlk =
        $('#btn_mod_cargar_coincidencia')
            .closest('.input-group-btn')
            .outerWidth(true) || 0;
    var maxSel = Math.max(80, Math.floor(rowW - btnBlk - 4));
    if (w > maxSel) w = maxSel;
    $sel.css({ width: w + 'px', maxWidth: w + 'px', flexBasis: w + 'px' });
}

function v2ScheduleSyncModqMatchSelectWidth() {
    clearTimeout(v2ModqMatchSyncTimer);
    v2ModqMatchSyncTimer = setTimeout(v2SyncModqMatchSelectWidth, 48);
}

function v2ModApplyFincaSemanaFromMatchRow(row, runBuscar) {
    var o = v2ModRowMatchFncSem(row);
    if (!o.fnc || parseInt(o.fnc, 10) <= 0) {
        $.alert('La actividad encontrada no tiene finca v&aacute;lida.', null, 'remove');
        return;
    }
    $('#modq_Fnc_Cod').val(o.fnc).trigger('change');
    v2ModRefrescarSemanas(function() {
        if (o.sem && $('#modq_Act_Sem').find('option[value="' + o.sem + '"]').length) {
            $('#modq_Act_Sem').val(o.sem);
        } else if (o.sem) {
            $('#modq_Act_Sem').append($('<option>', { value: o.sem, text: 'Semana ' + o.sem }));
            $('#modq_Act_Sem').val(o.sem);
        }
        v2UpdateModEmpActionButtons();
        $('#modq_match_panel').hide();
        if (runBuscar) {
            var perBusq = $('#modq_lookup_Per_Cod').val();
            v2ModBuscarActividad(perBusq);
        }
    });
}

function v2ModBuscarActividadesPorEmpleado() {
    if (!$('#modq_Pec_Cod').length) return;
    var pec = $('#modq_Pec_Cod').val();
    var per = $('#modq_lookup_Per_Cod').val();
    if (!pec || parseInt(pec, 10) <= 0) {
        $.alert('Seleccione el per&iacute;odo contable.', null, 'remove');
        return;
    }
    if (!per || parseInt(per, 10) <= 0) {
        $.alert('Pulse <b>Elegir empleado</b> y seleccione al trabajador en el listado.', null, 'remove');
        return;
    }
    $('#modq_match_panel').hide();
    $('#modV2EmpStatus').text('Buscando actividades...');
    $.getDataJson(
        '',
        { modificarActividadesPorEmpleado: true, Pec_Cod: pec, Per_Cod: per },
        function(res) {
            if (!res || !res.success) {
                $.alert((res && res.message) || 'No se pudo consultar.', null, 'remove');
                $('#modV2EmpStatus').text('Error en consulta.');
                return false;
            }
            var m = res.matches || [];
            if (!m.length) {
                $.alert('No hay actividades registradas para ese empleado en este per&iacute;odo.', null, 'remove');
                $('#modV2EmpStatus').text('Sin coincidencias.');
                return false;
            }
            if (m.length === 1) {
                v2ModApplyFincaSemanaFromMatchRow(m[0], true);
                return false;
            }
            var $ms = $('#modq_actividad_match');
            $ms.empty();
            var j;
            for (j = 0; j < m.length; j++) {
                var r = m[j];
                var o = v2ModRowMatchFncSem(r);
                var fdes = r.Fnc_Des != null ? String(r.Fnc_Des) : r.fnc_des != null ? String(r.fnc_des) : 'Finca';
                var ac = r.Act_Cod != null ? String(r.Act_Cod) : r.act_cod != null ? String(r.act_cod) : '';
                var lbl = fdes + ' - Sem. ' + o.sem + (ac ? ' (Act.' + ac + ')' : '');
                $ms.append($('<option>', { value: String(j), text: lbl }));
            }
            $ms.data('v2Matches', m);
            $('#modq_match_panel').show();
            v2ScheduleSyncModqMatchSelectWidth();
            setTimeout(v2SyncModqMatchSelectWidth, 180);
            $('#modV2EmpStatus').text(m.length + ' coincidencias: elija finca/semana y pulse Cargar.');
            return false;
        },
        function() {
            $.alert('Error al buscar por empleado.', null, 'remove');
            $('#modV2EmpStatus').text('Error al consultar.');
        }
    );
}

/** Act_Cod de la cabecera cargada (MySQL puede devolver claves en min&uacute;sculas). */
function v2ModCabActCod() {
    if (!g_modActividadCab) return '';
    var cab = g_modActividadCab;
    if (cab.Act_Cod != null && cab.Act_Cod !== '') return String(cab.Act_Cod);
    if (cab.act_cod != null && cab.act_cod !== '') return String(cab.act_cod);
    return '';
}

function v2ModDetVal(row, names) {
    if (!row) return '';
    var i;
    for (i = 0; i < names.length; i++) {
        var n = names[i];
        if (Object.prototype.hasOwnProperty.call(row, n) && row[n] != null && row[n] !== '') {
            return row[n];
        }
    }
    return '';
}

/** Rellena mayordomo / prefijo desde la cabecera cargada (Modificar v2). */
function v2ModFillMayordomoFromCab() {
    if (!$('#modq_Act_Res').length) return;
    if (!g_modActividadCab) {
        $('#modq_Act_Res').val('');
        return;
    }
    var cab = g_modActividadCab;
    var res = v2ModDetVal(cab, ['Act_Res', 'act_res']);
    $('#modq_Act_Res').val(res != null && res !== '' ? String(res) : '');
    var $pfReg = $('#frm_alt_actividad').find('#prefijo');
    if (!$pfReg.length) {
        $pfReg = $('#prefijo').first();
    }
    if ($pfReg.length) {
        $('#modq_prefijo').text($.trim($pfReg.text()));
    }
}

function v2ModUpdateEmpGrandTotal() {
    var sum = 0;
    $('#modV2EmpTbody tr.v2-mod-emp-row').each(function() {
        var t = parseFloat($.trim($(this).find('.v2-mod-emp-total-cell').first().text()), 10);
        if (!isNaN(t)) sum += t;
    });
    var $g = $('#modV2EmpGrandTotal');
    if ($g.length) $g.text(sum.toFixed(2));
}

function v2ModLabIsNative() {
    return $('#modV2LabTbody').length > 0 && g_modActividadCab != null;
}

function v2ModEmpNativeRow$(rowid) {
    if (rowid == null || rowid === '') return $();
    var s = String(rowid);
    return $('#modV2EmpTbody tr[data-v2-rid]').filter(function() {
        return String($(this).attr('data-v2-rid')) === s;
    });
}

function v2ModEmpDomRowIdFromAny(rowid) {
    if (rowid == null || rowid === '') return String(rowid || '');
    var s = String(rowid);
    if (!$('#modV2EmpTbody').length) return s;
    var $tr = v2ModEmpNativeRow$(s);
    if ($tr.length) return String($tr.attr('data-v2-rid'));
    return s;
}

function v2ModEmpGetRowIds() {
    var out = [];
    $('#modV2EmpTbody tr[data-v2-rid]').each(function() {
        out.push(String($(this).attr('data-v2-rid')));
    });
    return out;
}

function v2ModEmpAllocRowId() {
    var max = 0;
    $('#modV2EmpTbody tr[data-v2-rid]').each(function() {
        var n = parseInt($(this).attr('data-v2-rid'), 10);
        if (!isNaN(n) && n > max) max = n;
    });
    return String(max + 1);
}

function v2ModGetEmpMeta(rowid) {
    if (rowid == null || rowid === '') return { Per_Cod: '', Personal: '' };
    var sid = v2ModEmpDomRowIdFromAny(rowid);
    var m = g_modV2EmpMeta[sid] || g_modV2EmpMeta[String(rowid)];
    var pc = '';
    var nom = '';
    if (m) {
        pc = m.Per_Cod != null ? String(m.Per_Cod) : '';
        nom = m.Personal != null ? String(m.Personal) : '';
    }
    var $tr = v2ModEmpNativeRow$(sid);
    if ($tr.length) {
        if (!pc) pc = v2CleanVal($tr.attr('data-per-cod'));
        if (!nom) nom = v2CleanVal($tr.find('.v2-mod-emp-nombre').text());
    }
    return { Per_Cod: pc, Personal: nom };
}

function v2ModLineBelongsToEmp(l, empGridRid) {
    var er = v2ModEmpDomRowIdFromAny(empGridRid);
    var le = l._v2_emp_rid != null ? String(v2CleanVal(l._v2_emp_rid)) : '';
    if (le !== '') return v2ModEmpDomRowIdFromAny(le) === er;
    var meta = v2ModGetEmpMeta(er);
    var p = String(meta.Per_Cod || '');
    return p !== '' && String(v2CleanVal(l.Per_Cod)) === p;
}

/** Ultima fecha de labores del empleado en Modificar (orden de filas; g_modV2Lineas + grilla visible). */
function v2ModGetUltimaDetFecEmpleado(empRid) {
    var er = empRid != null ? v2ModEmpDomRowIdFromAny(empRid) : '';
    if (!er) return '';
    var ultima = '';
    function consider(fec) {
        var ymd = v2DetFecToInput(fec);
        if (ymd) ultima = ymd;
    }
    g_modV2Lineas.forEach(function(l) {
        if (!v2ModLineBelongsToEmp(l, er)) return;
        if (v2LaborLineaVacia(l)) return;
        consider(l.Det_Fec);
    });
    if (g_modV2SelectedEmpRowId && v2ModEmpDomRowIdFromAny(g_modV2SelectedEmpRowId) === er) {
        v2ModLabGetRowIds().forEach(function(lrid) {
            var r = v2ModLabRowReadMerged(lrid);
            if (v2LaborLineaVacia(r)) return;
            consider(r.Det_Fec);
        });
    }
    return ultima;
}

function v2ModEmpAppendRow(perCod, personal, totalStr) {
    var nidStr = v2ModEmpAllocRowId();
    g_modV2EmpMeta[nidStr] = {
        Per_Cod: String(perCod != null ? perCod : ''),
        Personal: personal != null ? String(personal) : ''
    };
    var $tr = $('<tr class="v2-mod-emp-row"/>')
        .attr('data-v2-rid', nidStr)
        .attr('data-per-cod', String(perCod != null ? perCod : ''));
    $tr.append($('<td class="v2-col-idx text-center"/>'));
    $tr.append($('<td class="v2-mod-emp-nombre"/>').text(personal != null ? personal : ''));
    $tr.append(
        $('<td class="v2-mod-emp-total-cell text-right"/>').text(totalStr != null ? String(totalStr) : '0.00')
    );
    $tr.append(
        $('<td class="text-center"/>').append(
            v2GridActionBtn('edit', 'Ver / editar labores de este empleado').addClass('v2-btn-sel-emp')
        )
    );
    $tr.append(
        $('<td class="text-center"/>').append(
            v2GridActionBtn('delete', 'Quitar empleado y sus labores').addClass('v2-btn-quitar-emp')
        )
    );
    $('#modV2EmpTbody').append($tr);
    v2ModEmpRenumberRows();
    v2ModEmpUpdateFooterCount();
    return nidStr;
}

function v2ModEmpRenumberRows() {
    $('#modV2EmpTbody tr[data-v2-rid]').each(function(i) {
        $(this)
            .find('td.v2-col-idx')
            .text(i + 1);
    });
}

function v2ModEmpUpdateFooterCount() {
    var n = $('#modV2EmpTbody tr[data-v2-rid]').length;
    var $st = $('#modV2EmpStatus');
    if (!$st.length) return;
    if (n === 0) {
        $st.text('Sin empleados');
    } else {
        $st.text('Mostrando 1 - ' + n + ' de ' + n);
    }
    v2ModUpdateEmpGrandTotal();
}

function v2ModLabRow$(lid) {
    if (lid == null || lid === '') return $();
    var s = String(lid);
    return $('#modV2LabTbody tr[data-v2-lid]').filter(function() {
        return String($(this).attr('data-v2-lid')) === s;
    });
}

function v2ModLabGetRowIds() {
    var out = [];
    $('#modV2LabTbody tr[data-v2-lid]').each(function() {
        out.push(String($(this).attr('data-v2-lid')));
    });
    return out;
}

function v2ModLabRenumberRows() {
    $('#modV2LabTbody tr[data-v2-lid]').each(function(i) {
        $(this)
            .find('td.v2-lab-idx')
            .text(i + 1);
    });
}

function v2ModLabUpdateFooterSum(sum) {
    var s = typeof sum === 'number' && !isNaN(sum) ? sum.toFixed(2) : '0.00';
    $('#modRegActividadV2 .v2-mod-lab-foot-sum').text(s);
}

function v2ModLabUpdateStatusCount() {
    var n = $('#modV2LabTbody tr[data-v2-lid]').length;
    var $st = $('#modV2LabStatus');
    if (!$st.length) return;
    if (!n) {
        $st.text('Sin labores');
    } else {
        $st.text('Mostrando 1 - ' + n + ' de ' + n);
    }
}

function v2ModLabClear() {
    $('#modV2LabTbody').empty();
    v2ModLabUpdateFooterSum(0);
    v2ModLabUpdateStatusCount();
}

function v2ModLabRowReadMerged(lid) {
    var $tr = v2ModLabRow$(lid);
    if (!$tr.length) return {};
    function vin(sel) {
        var $e = $tr.find(sel);
        if (!$e.length) return '';
        var v = $e.val();
        return v != null ? String(v) : '';
    }
    var lidStr = String(lid);
    return {
        index: lidStr,
        Lab_Des: vin('#' + lidStr + '_Lab_Des'),
        Lab_Cod: vin('#' + lidStr + '_Lab_Cod'),
        Tpg_Des: vin('#' + lidStr + '_Tpg_Des'),
        Det_Fec: vin('#' + lidStr + '_Det_Fec'),
        Det_Obs: vin('#' + lidStr + '_Det_Obs'),
        Lab_Val: vin('#' + lidStr + '_Lab_Val'),
        Det_Can: vin('#' + lidStr + '_Det_Can'),
        Total: vin('#' + lidStr + '_Total'),
        Per_Cod: vin('#' + lidStr + '_Per_Cod'),
        Personal: vin('#' + lidStr + '_Personal'),
        _v2_emp_rid: vin('#' + lidStr + '__v2_emp_rid')
    };
}

function v2ModLabApplyLaborFromDialog(rowId, row) {
    var lid = String(rowId);
    var $tr = v2ModLabRow$(lid);
    if (!$tr.length || !row) return;
    if (row.Lab_Des != null) $tr.find('#' + lid + '_Lab_Des').val(String(row.Lab_Des));
    if (row.Lab_Cod != null) $tr.find('#' + lid + '_Lab_Cod').val(String(row.Lab_Cod));
    if (row.Tpg_Des != null) $tr.find('#' + lid + '_Tpg_Des').val(String(row.Tpg_Des));
    if (row.Lab_Val != null) $tr.find('#' + lid + '_Lab_Val').val(String(row.Lab_Val));
    $tr.find('#' + lid + '_Det_Can').val('');
    $tr.find('#' + lid + '_Total').val('');
}

function v2ModNextLineIndex() {
    var max = 0;
    var n;
    g_modV2Lineas.forEach(function(l) {
        n = parseInt(String(l.index).replace(/^m/, ''), 10);
        if (!isNaN(n) && n > max) max = n;
    });
    v2ModLabGetRowIds().forEach(function(id) {
        n = parseInt(String(id).replace(/^m/, ''), 10);
        if (!isNaN(n) && n > max) max = n;
    });
    return 'm' + (max + 1);
}

function v2ModLabBindRowEvents(lid) {
    var $tr = v2ModLabRow$(lid);
    if (!$tr.length) return;
    var sel_fecha = $('#modq_Pec_Cod').find('option:selected');
    $('#' + lid + '_Det_Fec').createDatePickers({ clean: true });
    $('#' + lid + '_Det_Fec')
        .dateLimits(sel_fecha.data('inicio'), sel_fecha.data('fin'))
        .datepicker('option', 'beforeShow', function() {
            v2BumpDatepickerZIndex();
        })
        .datepicker('option', 'onSelect', function() {
            v2ModMarkCurrentEmpLaboresDirty();
            v2ModEnsureTrailingEmptyLabRowMod();
        });
    $tr.find('#' + lid + '_Det_Can')
        .off('change.v2modlabdc')
        .on('change.v2modlabdc', function() {
            var vlInput = validaDecimal($tr.find('#' + lid + '_Det_Can').val());
            if (vlInput) {
                $.alert('El valor de la cantidad debe ser mayor que 0');
            }
            v2ModMakeCalculation();
            v2ModRefreshTotalesEmpleados();
            v2ModMarkCurrentEmpLaboresDirty();
            v2ModEnsureTrailingEmptyLabRowMod();
        });
    $tr.find('#' + lid + '_Det_Obs')
        .off('input.v2moddirty change.v2moddirty')
        .on('input.v2moddirty change.v2moddirty', function() {
            v2ModMarkCurrentEmpLaboresDirty();
            v2ModEnsureTrailingEmptyLabRowMod();
        });
    $tr.find('#' + lid + '_Det_Fec')
        .off('change.v2moddirty')
        .on('change.v2moddirty', function() {
            v2ModMarkCurrentEmpLaboresDirty();
            v2ModEnsureTrailingEmptyLabRowMod();
        });
    v2ModBindLaborAutocomplete(lid);
}

function v2ModLabAppendRow(lidStr, rec) {
    var lid = String(lidStr);
    rec = rec || {};
    var empRid = v2ModEmpDomRowIdFromAny(g_modV2SelectedEmpRowId || rec._v2_emp_rid || '');
    var $tr = $('<tr class="v2-lab-row"/>').attr('data-v2-lid', lid);
    if (rec._v2_emp_rid != null && String(rec._v2_emp_rid) !== '') {
        $tr.attr('data-v2-emp-lrid', String(rec._v2_emp_rid));
    }
    $tr.append($('<td class="text-center v2-lab-idx"/>').text(''));
    var $labCell = $('<td/>');
    $labCell.append(
        $('<input type="text" class="form-control input-xs v2-lab-des-input" autocomplete="off"/>')
            .attr({ id: lid + '_Lab_Des', name: lid + '_Lab_Des', placeholder: 'Escriba para buscar labor...' })
            .val(rec.Lab_Des != null ? String(rec.Lab_Des) : '')
    );
    $labCell.append($('<input type="hidden"/>').attr({ id: lid + '_index', name: lid + '_index' }).val(lid));
    $labCell.append(
        $('<input type="hidden"/>')
            .attr({ id: lid + '_Lab_Cod', name: lid + '_Lab_Cod' })
            .val(rec.Lab_Cod != null ? String(rec.Lab_Cod) : '')
    );
    $labCell.append(
        $('<input type="hidden"/>')
            .attr({ id: lid + '_Per_Cod', name: lid + '_Per_Cod' })
            .val(rec.Per_Cod != null ? String(rec.Per_Cod) : '')
    );
    $labCell.append(
        $('<input type="hidden"/>')
            .attr({ id: lid + '_Personal', name: lid + '_Personal' })
            .val(rec.Personal != null ? String(rec.Personal) : '')
    );
    $labCell.append(
        $('<input type="hidden"/>')
            .attr({ id: lid + '__v2_emp_rid', name: lid + '__v2_emp_rid' })
            .val(
                rec._v2_emp_rid != null && String(rec._v2_emp_rid) !== ''
                    ? String(rec._v2_emp_rid)
                    : empRid || ''
            )
    );
    $tr.append($labCell);
    $tr.append(
        $('<td/>').append(
            $('<input type="text" class="form-control input-xs"/>')
                .attr({ id: lid + '_Tpg_Des', name: lid + '_Tpg_Des', readonly: 'readonly', tabindex: -1 })
                .val(rec.Tpg_Des != null ? String(rec.Tpg_Des) : '')
        )
    );
    $tr.append(
        $('<td/>').append(
            $('<input type="text" class="form-control input-xs datepickers"/>')
                .attr({ id: lid + '_Det_Fec', name: lid + '_Det_Fec' })
                .val(
                    (function() {
                        var raw = rec.Det_Fec != null ? String(rec.Det_Fec).replace(/^\s+|\s+$/g, '') : '';
                        return raw !== '' ? v2DetFecToInput(rec.Det_Fec) : v2ModGetUltimaDetFecEmpleado(empRid);
                    })()
                )
        )
    );
    $tr.append(
        $('<td/>').append(
            $('<input type="text" class="form-control input-xs"/>')
                .attr({ id: lid + '_Det_Obs', name: lid + '_Det_Obs' })
                .val(rec.Det_Obs != null ? String(rec.Det_Obs) : '')
        )
    );
    $tr.append(
        $('<td/>').append(
            $('<input type="text" class="form-control input-xs"/>')
                .attr({ id: lid + '_Lab_Val', name: lid + '_Lab_Val', readonly: 'readonly', tabindex: -1 })
                .val(rec.Lab_Val != null ? String(rec.Lab_Val) : '')
        )
    );
    $tr.append(
        $('<td/>').append(
            $('<input type="text" class="form-control input-xs"/>')
                .attr({ id: lid + '_Det_Can', name: lid + '_Det_Can' })
                .val(rec.Det_Can != null ? String(rec.Det_Can) : '')
        )
    );
    $tr.append(
        $('<td class="text-right"/>').append(
            $('<input type="text" class="form-control input-xs text-right"/>')
                .attr({ id: lid + '_Total', name: lid + '_Total', readonly: 'readonly', tabindex: -1 })
                .val(rec.Total != null ? String(rec.Total) : '')
        )
    );
    $tr.append(
        $('<td class="text-center"/>').append(
            v2GridActionBtn('delete', 'Eliminar labor').addClass('v2-btn-quitar-lab').attr('tabindex', -1)
        )
    );
    $('#modV2LabTbody').append($tr);
    v2ModLabRenumberRows();
    g_v2ModLabDirtySuppress++;
    try {
        v2ModLabBindRowEvents(lid);
        $tr.find('#' + lid + '_Det_Can').trigger('change');
    } finally {
        g_v2ModLabDirtySuppress--;
    }
    v2ModLabUpdateStatusCount();
    return $tr;
}

function v2ModMakeCalculation() {
    v2ModLabGetRowIds().forEach(function(lid) {
        var $tr = v2ModLabRow$(lid);
        var can = $.trim($tr.find('#' + lid + '_Det_Can').val());
        var lv = $.trim($tr.find('#' + lid + '_Lab_Val').val());
        if (can !== '' && lv !== '') {
            var tot = (parseFloat(can) || 0) * (parseFloat(lv) || 0);
            $tr.find('#' + lid + '_Total').val(tot.toFixed(2));
        } else {
            $tr.find('#' + lid + '_Total').val('');
        }
    });
    var sum = 0;
    v2ModLabGetRowIds().forEach(function(lid) {
        var t = parseFloat($('#' + lid + '_Total').val());
        if (!isNaN(t)) sum += t;
    });
    v2ModLabUpdateFooterSum(sum);
    v2ModRefreshTotalesEmpleados();
}

function v2ModSyncCurrentDetailToStore() {
    if (!$('#modRegActividadV2').length || !g_modV2SelectedEmpRowId) return;
    var empRid = v2ModEmpDomRowIdFromAny(g_modV2SelectedEmpRowId);
    var meta = v2ModGetEmpMeta(g_modV2SelectedEmpRowId);
    var perCod = meta.Per_Cod;
    var personal = meta.Personal;
    var batch = [];
    v2ModLabGetRowIds().forEach(function(rid) {
        var rda = v2ModLabRowReadMerged(rid);
        if (v2LaborLineaVacia(rda)) return;
        rda._v2_emp_rid = empRid;
        if (perCod) {
            rda.Per_Cod = perCod;
            rda.Personal = personal;
        }
        batch.push(rda);
    });
    g_modV2Lineas = g_modV2Lineas.filter(function(l) {
        var le = l._v2_emp_rid != null ? String(v2CleanVal(l._v2_emp_rid)) : '';
        if (le !== '') return v2ModEmpDomRowIdFromAny(le) !== empRid;
        if (perCod) return String(v2CleanVal(l.Per_Cod)) !== String(perCod);
        return true;
    });
    batch.forEach(function(b) {
        g_modV2Lineas.push(b);
    });
}

function v2ModPoblarDetalleDesdeLineas(empGridRid) {
    var er = v2ModEmpDomRowIdFromAny(empGridRid);
    var lines = g_modV2Lineas.filter(function(l) {
        return v2ModLineBelongsToEmp(l, er) && !v2LaborLineaVacia(l);
    });
    g_v2ModLabDirtySuppress++;
    try {
        v2ModLabClear();
        lines.forEach(function(rec) {
            if (!rec._v2_emp_rid) rec._v2_emp_rid = er;
            var id =
                rec.index != null && String(rec.index) !== ''
                    ? String(rec.index)
                    : String(v2ModNextLineIndex());
            v2ModLabAppendRow(id, rec);
        });
        v2ModMakeCalculation();
    } finally {
        g_v2ModLabDirtySuppress--;
    }
    v2ModEnsureTrailingEmptyLabRowMod();
    if (g_modV2SelectedEmpRowId) {
        v2ModSyncEmpDirtyFromBaseline(g_modV2SelectedEmpRowId);
    }
}

function v2ModClearAllEmpDirtyRows() {
    $('#modV2EmpTbody tr.v2-mod-emp-row').removeClass('v2-mod-emp-dirty');
}

/** Normaliza una l&iacute;nea de labor para comparar con el baseline (Modificar). */
function v2ModNormalizeLabLineForCompare(l) {
    if (!l) return null;
    var can = parseFloat(String(v2CleanVal(l.Det_Can) || '0').replace(',', '.'));
    var lv = parseFloat(String(v2CleanVal(l.Lab_Val) || '0').replace(',', '.'));
    if (isNaN(can)) can = 0;
    if (isNaN(lv)) lv = 0;
    return {
        Lab_Cod: v2CleanVal(l.Lab_Cod),
        Det_Fec: v2DetFecToInput(l.Det_Fec),
        Det_Obs: v2CleanVal(l.Det_Obs),
        Lab_Val: lv.toFixed(4),
        Det_Can: can.toFixed(4)
    };
}

function v2ModLabFingerprint(lines) {
    var arr = [];
    var i;
    for (i = 0; i < lines.length; i++) {
        var n = v2ModNormalizeLabLineForCompare(lines[i]);
        if (n) arr.push(n);
    }
    arr.sort(function(a, b) {
        var ka = [a.Lab_Cod, a.Det_Fec, a.Det_Obs, a.Det_Can, a.Lab_Val].join('\t');
        var kb = [b.Lab_Cod, b.Det_Fec, b.Det_Obs, b.Det_Can, b.Lab_Val].join('\t');
        if (ka < kb) return -1;
        if (ka > kb) return 1;
        return 0;
    });
    return JSON.stringify(arr);
}

function v2ModCollectEmpLabLines(empRid, preferDom) {
    var er = v2ModEmpDomRowIdFromAny(empRid);
    var out = [];
    var useDom =
        preferDom !== false &&
        g_modV2SelectedEmpRowId &&
        v2ModEmpDomRowIdFromAny(g_modV2SelectedEmpRowId) === er &&
        $('#modRegActividadV2').length;
    if (useDom) {
        v2ModLabGetRowIds().forEach(function(lrid) {
            var r = v2ModLabRowReadMerged(lrid);
            if (v2LaborLineaVacia(r)) return;
            out.push(r);
        });
        return out;
    }
    g_modV2Lineas.forEach(function(l) {
        if (!v2ModLineBelongsToEmp(l, er)) return;
        if (v2LaborLineaVacia(l)) return;
        out.push(l);
    });
    return out;
}

/** Amarillo solo si las labores del empleado difieren del detalle cargado al pulsar Buscar. */
function v2ModSyncEmpDirtyFromBaseline(empRid) {
    var er = v2ModEmpDomRowIdFromAny(empRid || g_modV2SelectedEmpRowId);
    if (!er || !$('#modRegActividadV2').length) return;
    var $tr = v2ModEmpNativeRow$(er);
    if (!$tr.length) return;
    var cur = v2ModCollectEmpLabLines(er, true);
    var base = [];
    g_modV2LineasBaseline.forEach(function(l) {
        if (!v2ModLineBelongsToEmp(l, er)) return;
        if (v2LaborLineaVacia(l)) return;
        base.push(l);
    });
    var dirty = v2ModLabFingerprint(cur) !== v2ModLabFingerprint(base);
    if (dirty) {
        $tr.addClass('v2-mod-emp-dirty');
    } else {
        $tr.removeClass('v2-mod-emp-dirty');
    }
}

function v2ModReconcileAllEmpDirtyFlags() {
    if (!$('#modRegActividadV2').length) return;
    v2ModEmpGetRowIds().forEach(function(rid) {
        v2ModSyncEmpDirtyFromBaseline(rid);
    });
}

function v2ModMarkCurrentEmpLaboresDirty() {
    if (g_v2ModLabDirtySuppress > 0) return;
    v2ModSyncEmpDirtyFromBaseline(g_modV2SelectedEmpRowId);
}

function v2ModApplyEmpHighlight(rowid) {
    $('#modV2EmpTbody tr.v2-mod-emp-row').removeClass('v2-mod-emp-sel');
    if (rowid == null || rowid === '') return;
    v2ModEmpNativeRow$(v2ModEmpDomRowIdFromAny(rowid)).addClass('v2-mod-emp-sel');
}

function v2ModRefreshTotalesEmpleados() {
    if (!$('#modRegActividadV2').length) return;
    var selRid = g_modV2SelectedEmpRowId ? v2ModEmpDomRowIdFromAny(g_modV2SelectedEmpRowId) : '';
    var pcToRid = {};
    var empIds = v2ModEmpGetRowIds();
    var ix;
    for (ix = 0; ix < empIds.length; ix++) {
        var ridOne = empIds[ix];
        var pcOne = v2CleanVal(v2ModGetEmpMeta(ridOne).Per_Cod);
        if (pcOne) pcToRid[String(pcOne)] = v2ModEmpDomRowIdFromAny(ridOne);
    }
    var sums = {};
    g_modV2Lineas.forEach(function(l) {
        if (v2LaborLineaVacia(l)) return;
        var er = '';
        if (l._v2_emp_rid != null && String(v2CleanVal(l._v2_emp_rid)) !== '') {
            er = v2ModEmpDomRowIdFromAny(l._v2_emp_rid);
        } else {
            var pcc = String(v2CleanVal(l.Per_Cod));
            if (pcc && pcToRid[pcc]) er = pcToRid[pcc];
        }
        if (!er) return;
        var t = parseFloat(String(l.Total || '').replace(',', '.'));
        if (isNaN(t)) return;
        sums[er] = (sums[er] || 0) + t;
    });
    if (selRid) {
        var sumDom = 0;
        v2ModLabGetRowIds().forEach(function(lrid) {
            var tv = $('#' + lrid + '_Total').val();
            var t = parseFloat(tv);
            if (isNaN(t) || t <= 0) return;
            sumDom += t;
        });
        sums[selRid] = sumDom;
    }
    for (ix = 0; ix < empIds.length; ix++) {
        var er = v2ModEmpDomRowIdFromAny(empIds[ix]);
        var s = sums[er];
        if (typeof s !== 'number' || isNaN(s)) s = 0;
        v2ModEmpNativeRow$(er)
            .find('.v2-mod-emp-total-cell')
            .text(s.toFixed(2));
    }
    v2ModUpdateEmpGrandTotal();
}

function v2ModSwitchEmpleado(rowid) {
    if (!$('#modRegActividadV2').length) return;
    v2ModNormalizarRidsLineasEmp();
    var next = v2ModEmpDomRowIdFromAny(rowid);
    if (g_modV2SelectedEmpRowId) {
        v2ModSyncCurrentDetailToStore();
        v2ModSyncEmpDirtyFromBaseline(g_modV2SelectedEmpRowId);
    }
    g_modV2SelectedEmpRowId = next;
    g_modPerSel = v2ModGetEmpMeta(next).Per_Cod;
    v2ModPoblarDetalleDesdeLineas(g_modV2SelectedEmpRowId);
    v2ModApplyEmpHighlight(g_modV2SelectedEmpRowId);
    v2ModSyncEmpDirtyFromBaseline(g_modV2SelectedEmpRowId);
    v2ModRefreshTotalesEmpleados();
    v2UpdateLaboresCaptionMod(v2ModGetEmpMeta(next).Personal);
    v2ScrollNativeEmpRowIntoView(v2ModEmpNativeRow$(next));
    v2ModReflowLabPanel();
    if (v2ModLabIsNative()) {
        v2FocusLaborInputInGrid(v2ModLabGetRowIds);
    }
}

/** Tras cambiar de empleado o dibujar labores, fuerza rec&aacute;lculo del alto del &aacute;rea scroll (pesta&ntilde;a + flex). */
function v2ModReflowLabPanel() {
    if (!$('#modRegActividadV2').length) return;
    var $sc = $('#modTableActividad .v2-lab-scroll');
    if (!$sc.length) return;
    var el = $sc[0];
    requestAnimationFrame(function() {
        void el.offsetHeight;
        var t = el.scrollTop;
        el.scrollTop = t;
    });
}

function v2ModNormalizarRidsLineasEmp() {
    if (!$('#modTableEmpRegAct').length) return;
    var ids = v2ModEmpGetRowIds();
    if (!ids || !ids.length) return;
    g_modV2Lineas.forEach(function(l) {
        if (v2LaborLineaVacia(l)) return;
        var leRaw = l._v2_emp_rid != null ? String(v2CleanVal(l._v2_emp_rid)) : '';
        if (leRaw === '') return;
        var le = v2ModEmpDomRowIdFromAny(leRaw);
        var ok = false;
        var i;
        for (i = 0; i < ids.length; i++) {
            if (String(ids[i]) === le) {
                ok = true;
                break;
            }
        }
        if (ok) {
            l._v2_emp_rid = le;
            return;
        }
        var pc = v2CleanVal(l.Per_Cod);
        if (!pc) return;
        var hit = [];
        for (i = 0; i < ids.length; i++) {
            if (v2CleanVal(v2ModGetEmpMeta(ids[i]).Per_Cod) === pc) hit.push(String(ids[i]));
        }
        if (hit.length === 1) l._v2_emp_rid = hit[0];
    });
}

function v2ModAbrirBuscarEmpleado() {
    if (!v2ModRequiereFincaSemana()) return;
    g_modEmpLookupMode = false;
    g_modV2EmpPickMode = true;
    $('#personalDialog').dialog('open');
}

/** Elegir empleado solo para localizar finca/semana (Modificar &gt; consulta), sin a&ntilde;adir fila al grid. */
function v2ModAbrirElegirEmpleadoConsulta() {
    g_modV2EmpPickMode = false;
    g_modEmpLookupMode = true;
    $('#personalDialog').dialog('open');
}

function v2ModAddLaborFila() {
    if (!$('#modRegActividadV2').length) return;
    if (!g_modV2SelectedEmpRowId) {
        $.alert('Seleccione un empleado en la lista izquierda (o agregue uno con <b>Agregar empleado</b>).', null, 'remove');
        return;
    }
    var meta = v2ModGetEmpMeta(g_modV2SelectedEmpRowId);
    var id = String(v2ModNextLineIndex());
    v2ModLabAppendRow(id, {
        index: id,
        _v2_emp_rid: v2ModEmpDomRowIdFromAny(g_modV2SelectedEmpRowId),
        Per_Cod: meta.Per_Cod,
        Personal: meta.Personal,
        Lab_Cod: '',
        Lab_Des: '',
        Tpg_Des: '',
        Det_Fec: v2ModGetUltimaDetFecEmpleado(g_modV2SelectedEmpRowId),
        Det_Obs: '',
        Lab_Val: '',
        Det_Can: '',
        Total: ''
    });
    v2ModLabRow$(id)
        .find('#' + id + '_Lab_Des')
        .focus();
    v2ModMakeCalculation();
    v2ModRefreshTotalesEmpleados();
    v2ModMarkCurrentEmpLaboresDirty();
}

function v2ModQuitarEmpleadoPorDomRid(ridDom) {
    var rid = v2ModEmpDomRowIdFromAny(ridDom);
    if (!rid) return;
    $.createDialogConfirm('&iquest;Quitar este empleado y todas sus labores de esta actividad?', null, function() {
        v2ModSyncCurrentDetailToStore();
        var meta = v2ModGetEmpMeta(rid);
        var pcFilt = meta.Per_Cod || v2CleanVal(v2ModEmpNativeRow$(rid).attr('data-per-cod'));
        g_modV2Lineas = g_modV2Lineas.filter(function(l) {
            if (v2ModLineBelongsToEmp(l, rid)) return false;
            if (pcFilt && String(v2CleanVal(l.Per_Cod)) === String(pcFilt)) return false;
            return true;
        });
        delete g_modV2EmpMeta[rid];
        v2ModEmpNativeRow$(rid).remove();
        if (g_modV2SelectedEmpRowId && v2ModEmpDomRowIdFromAny(g_modV2SelectedEmpRowId) === rid) {
            g_modV2SelectedEmpRowId = null;
            g_modPerSel = null;
            v2UpdateLaboresCaptionMod('');
            v2ModLabClear();
        }
        v2ModEmpRenumberRows();
        v2ModEmpUpdateFooterCount();
        v2ModRefreshTotalesEmpleados();
    });
}

function v2ModDetRowToLinea(r, empRid, lid) {
    var lv = v2ModDetVal(r, ['Det_Val', 'det_val', 'Lab_Val', 'lab_val']);
    var dc = v2ModDetVal(r, ['Det_Can', 'det_can']);
    var dv = parseFloat(String(lv || '0'), 10) || 0;
    var cn = parseFloat(String(dc || '0'), 10) || 0;
    return {
        index: String(lid),
        _v2_emp_rid: empRid || '',
        Per_Cod: String(v2ModDetVal(r, ['Per_Cod', 'per_cod']) || ''),
        Personal: String(v2ModDetVal(r, ['personal', 'Personal']) || ''),
        Lab_Cod: String(v2ModDetVal(r, ['Lab_Cod', 'lab_cod']) || ''),
        Lab_Des: String(v2ModDetVal(r, ['Lab_Des', 'lab_des']) || ''),
        Tpg_Des: String(v2ModDetVal(r, ['Tpg_Des', 'tpg_des']) || ''),
        Det_Fec: v2DetFecToInput(v2ModDetVal(r, ['Det_Fec', 'det_fec', 'Det_Fec_Mod', 'det_fec_mod']) || ''),
        Det_Obs: String(v2ModDetVal(r, ['Det_Obs', 'det_obs']) || ''),
        Lab_Val: String(lv || ''),
        Det_Can: String(dc || ''),
        Total: (dv * cn).toFixed(2)
    };
}

function v2ModInitEmpNativeTable() {
    var $w = $('#modTableEmpRegAct');
    if (!$w.length || !$('#modV2EmpTbody').length) return;
    $w.off('click.v2modrow');
    $w.off('click.v2modempnat').on('click.v2modempnat', '.v2-btn-sel-emp', function(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        var rid = $(this)
            .closest('tr[data-v2-rid]')
            .attr('data-v2-rid');
        if (rid) v2ModSwitchEmpleado(rid);
    });
    $w.off('click.v2modquitar').on('click.v2modquitar', '.v2-btn-quitar-emp', function(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        var rid = $(this)
            .closest('tr[data-v2-rid]')
            .attr('data-v2-rid');
        if (rid) v2ModQuitarEmpleadoPorDomRid(rid);
    });
    $('#modRegActividadV2')
        .off('click.v2modagr')
        .on('click.v2modagr', '#btn_mod_agr_emp', function() {
            v2ModAbrirBuscarEmpleado();
        });
    $('#modRegActividadV2')
        .off('click.v2modlistemp')
        .on('click.v2modlistemp', '#btn_mod_emp_por_area', function() {
            v2ModAbrirDialogListarEmpleadosPorArea();
        });
}

/** Descarta cambios en labores del empleado seleccionado y vuelve al detalle cargado en la &uacute;ltima b&uacute;squeda (solo esta persona). */
function v2ModRestaurarLaboresEmpleadoActual() {
    if (!$('#modRegActividadV2').length) return;
    if (!g_modV2SelectedEmpRowId) {
        $.alert('Seleccione un empleado en la lista izquierda.', null, 'remove');
        return;
    }
    if (!g_modV2LineasBaseline.length) {
        $.alert('No hay datos base para restaurar. Pulse <b>Buscar</b> para cargar la actividad desde el servidor.', null, 'remove');
        return;
    }
    v2ModSyncCurrentDetailToStore();
    var er = v2ModEmpDomRowIdFromAny(g_modV2SelectedEmpRowId);
    var restored = [];
    var ix;
    for (ix = 0; ix < g_modV2LineasBaseline.length; ix++) {
        var bl = g_modV2LineasBaseline[ix];
        if (v2ModLineBelongsToEmp(bl, er)) {
            restored.push($.extend(true, {}, bl));
        }
    }
    g_modV2Lineas = g_modV2Lineas.filter(function(l) {
        return !v2ModLineBelongsToEmp(l, er);
    });
    restored.forEach(function(r) {
        g_modV2Lineas.push(r);
    });
    v2ModPoblarDetalleDesdeLineas(er);
    v2ModRefreshTotalesEmpleados();
    v2ModEmpNativeRow$(er).removeClass('v2-mod-emp-dirty');
    v2ModEnsureTrailingEmptyLabRowMod();
}

function v2ModInitLabNativeTable() {
    var $w = $('#modTableActividad');
    var $modRoot = $('#modRegActividadV2');
    if (!$modRoot.length || !$w.length || !$('#modV2LabTbody').length) return;
    $w.off('click.v2modlabq').on('click.v2modlabq', '.v2-btn-quitar-lab', function(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        var lid = $(this)
            .closest('tr[data-v2-lid]')
            .attr('data-v2-lid');
        if (lid) quitarActividad({ id: lid });
    });
    $modRoot
        .off('click.v2modlabagr')
        .on('click.v2modlabagr', '#btn_mod_agr', function(e) {
            e.preventDefault();
            v2ModAddLaborFila();
        });
    $modRoot
        .off('click.v2modlabrst')
        .on('click.v2modlabrst', '#btn_mod_rst_lab', function(e) {
            e.preventDefault();
            v2ModRestaurarLaboresEmpleadoActual();
        });
    v2ModLabUpdateStatusCount();
}

function v2ModUpdateLabTotal() {
    var sum = 0;
    v2ModLabGetRowIds().forEach(function(lid) {
        var t = parseFloat($('#' + lid + '_Total').val());
        if (!isNaN(t)) sum += t;
    });
    v2ModLabUpdateFooterSum(sum);
}

function v2ModResetPaneles() {
    g_modActividadCab = null;
    g_modPerSel = null;
    g_modV2Lineas = [];
    g_modV2LineasBaseline = [];
    g_modV2EmpMeta = {};
    g_modV2SelectedEmpRowId = null;
    $('#modV2EmpTbody').empty();
    $('#modV2LabTbody').empty();
    v2ModUpdateEmpGrandTotal();
    v2ModUpdateLabTotal();
    v2ModLabUpdateStatusCount();
    v2ModEmpUpdateFooterCount();
    $('#modq_Act_Res').val('');
    $('#modq_prefijo').text('');
    $('#modq_lookup_Per_Cod').val('');
    $('#modq_lookup_emp_nombre').val('');
    $('#modq_actividad_match').empty().removeData('v2Matches');
    $('#modq_match_panel').hide();
    v2UpdateLaboresCaptionMod('');
}

/**
 * Carga actividad por finca/semana.
 * @param {string|number} [autoSelectPerCod] Si viene del flujo &laquo;Buscar por empleado&raquo;, al terminar selecciona esa fila en el listado.
 */
function v2ModBuscarActividad(autoSelectPerCod) {
    if (!$('#modq_Pec_Cod').length) return;
    var pec = $('#modq_Pec_Cod').val();
    var fnc = $('#modq_Fnc_Cod').val();
    var sem = $('#modq_Act_Sem').val();
    var perSelPreferido =
        autoSelectPerCod != null && String(autoSelectPerCod).replace(/\s/g, '') !== ''
            ? String(v2CleanVal(autoSelectPerCod))
            : '';
    if (!pec || parseInt(pec, 10) <= 0) {
        $.alert('Seleccione el per&iacute;odo.', null, 'remove');
        return;
    }
    if (!fnc || parseInt(fnc, 10) <= 0) {
        $.alert('Seleccione la finca.', null, 'remove');
        return;
    }
    if (!sem || parseInt(sem, 10) <= 0) {
        $.alert('Seleccione la semana (solo se listan semanas que ya tienen actividad en esa finca y per&iacute;odo).', null, 'remove');
        return;
    }
    $('#modV2EmpStatus').text('Cargando...');
    $.getDataJson(
        '',
        { modificarCargarActividadSemana: true, Pec_Cod: pec, Fnc_Cod: fnc, Act_Sem: sem },
        function(res) {
            if (!res || !res.success) {
                v2ModResetPaneles();
                var msg = res && res.message ? res.message : 'No se encontr&oacute; actividad.';
                $('#modV2EmpStatus').text(msg);
                $.alert(msg, null, 'remove');
                return;
            }
            g_modActividadCab = res.actividad;
            v2ModFillMayordomoFromCab();
            g_modV2Lineas = [];
            g_modV2LineasBaseline = [];
            g_modV2EmpMeta = {};
            g_modV2SelectedEmpRowId = null;
            g_modPerSel = null;
            $('#modV2EmpTbody').empty();
            $('#modV2LabTbody').empty();
            var emps = res.empleados || [];
            var empRidByPer = {};
            var i;
            for (i = 0; i < emps.length; i++) {
                var e = emps[i];
                var perC = e.Per_Cod != null ? e.Per_Cod : e.per_cod;
                var nom = e.personal != null ? String(e.personal) : '';
                var totE = e.total != null ? String(e.total) : '0.00';
                var nid = v2ModEmpAppendRow(perC, nom, totE);
                empRidByPer[String(perC)] = nid;
            }
            var ac = v2ModCabActCod();
            $.getDataJson(
                '',
                { searchDetActivi: true, Act_Cod: ac },
                function(dres) {
                    var rows = dres && dres.detalleAct ? dres.detalleAct : [];
                    var lineNum = 1;
                    var j;
                    for (j = 0; j < rows.length; j++) {
                        var r = rows[j];
                        var pc = String(v2ModDetVal(r, ['Per_Cod', 'per_cod']) || '');
                        var empRid = empRidByPer[pc] || '';
                        var lid = 'm' + lineNum;
                        lineNum++;
                        g_modV2Lineas.push(v2ModDetRowToLinea(r, empRid, lid));
                    }
                    g_modV2LineasBaseline = v2CloneLineasArray(g_modV2Lineas);
                    v2ModClearAllEmpDirtyRows();
                    v2ModEmpUpdateFooterCount();
                    var pickRid = null;
                    if (perSelPreferido && empRidByPer[perSelPreferido]) {
                        pickRid = empRidByPer[perSelPreferido];
                    }
                    if (!pickRid && perSelPreferido && /^\d+$/.test(String(perSelPreferido))) {
                        var pWant = parseInt(perSelPreferido, 10);
                        var altK;
                        for (altK in empRidByPer) {
                            if (Object.prototype.hasOwnProperty.call(empRidByPer, altK) && parseInt(altK, 10) === pWant) {
                                pickRid = empRidByPer[altK];
                                break;
                            }
                        }
                    }
                    if (!pickRid && emps.length) {
                        pickRid = v2ModEmpGetRowIds()[0];
                    }
                    if (pickRid) {
                        v2ModSwitchEmpleado(pickRid);
                    } else {
                        v2ModUpdateLabTotal();
                    }
                    return false;
                },
                function() {
                    $.alert('Error al cargar el detalle de labores.', null, 'remove');
                }
            );
            return false;
        },
        function() {
            v2ModResetPaneles();
            $('#modV2EmpStatus').text('Error al consultar.');
            $.alert('Error al cargar la actividad.', null, 'remove');
        }
    );
}

function v2ModBindControls() {
    $('#modq_Pec_Cod')
        .off('change.v2mod')
        .on('change.v2mod', function() {
            v2ModRefrescarSemanas();
        });
    $('#modq_Fnc_Cod')
        .off('change.v2mod')
        .on('change.v2mod', function() {
            v2ModRefrescarSemanas();
        });
    $('#modq_Act_Sem')
        .off('change.v2empbtn')
        .on('change.v2empbtn', function() {
            v2UpdateModEmpActionButtons();
        });
    $('#btn_mod_buscar_act')
        .off('click.v2mod')
        .on('click.v2mod', function() {
            v2ModBuscarActividad();
        });
    $('#btn_mod_elegir_empleado_busq')
        .off('click.v2mod')
        .on('click.v2mod', function() {
            v2ModAbrirElegirEmpleadoConsulta();
        });
    $('#btn_mod_buscar_por_empleado')
        .off('click.v2mod')
        .on('click.v2mod', function() {
            v2ModBuscarActividadesPorEmpleado();
        });
    $('#btn_mod_cargar_coincidencia')
        .off('click.v2mod')
        .on('click.v2mod', function() {
            var $ms = $('#modq_actividad_match');
            var m = $ms.data('v2Matches');
            if (!m || !m.length) {
                $.alert('No hay coincidencias cargadas. Pulse <b>Buscar actividades</b> primero.', null, 'remove');
                return;
            }
            var idx = parseInt($ms.val(), 10);
            if (isNaN(idx) || idx < 0 || idx >= m.length) {
                $.alert('Seleccione una opci&oacute;n del listado de coincidencias.', null, 'remove');
                return;
            }
            v2ModApplyFincaSemanaFromMatchRow(m[idx], true);
        });
    $('#btn_mod_gua_act')
        .off('click.v2modgua')
        .on('click.v2modgua', function() {
            saveData('frm_mod_actividad', 'saveModActividad', 'actividad');
        });
    v2ModInitEmpNativeTable();
    v2ModInitLabNativeTable();
    $(window)
        .off('resize.v2modqmatch')
        .on('resize.v2modqmatch', function() {
            v2ScheduleSyncModqMatchSelectWidth();
        });
}

function v2InitModificarActividadV2() {
    if (!$('#modq_Pec_Cod').length) return;
    v2ModBindControls();
    v2ModInitListarEmpAreaDialog();
    v2ModResetPaneles();
    v2ModRefrescarSemanas();
    v2UpdateModEmpActionButtons();
}

function limpiarBusq() {
    if (!$('.select_search').length) return;
    $('.select_search').val([]);
    $('.select_search').empty();
    $('.select_search').val('').trigger('chosen:updated');
}


// Promesa de fincas
function allFincas() {
    return new Promise((resolve, reject) => {
        $.getDataJson("", { fincasAjax: true }, (result) => {
            resolve(result.listaFincas);
        }, (err) => {
            reject(err);
        });
    });
}
//Promesa Trabajadores
function allTrabajadores() {
    return new Promise((resolve, reject) => {
        //
        $.getDataJson("", { trabajadoresAjax: true }, (result) => {
            resolve(result.listTrabajadores);
        }, (err) => {
            reject(err);
        });
    });
}



//Fin metodos para modificar actividades

// ==========================================
// Integracion de Mapeo Interactivo Relavera
// ==========================================
var modoMapeoActual = 'actividad'; // 'actividad', 'actividad_mod', 'ver', 'general'

function abrirModalMapeoSector(modo) {
    modoMapeoActual = modo || 'actividad';
    var url = '../../mapeo/FRONT/map_alt_mapeo_interactivo.php?modo=selector&ts=' + new Date().getTime();
    var curLat = '', curLng = '';
    if (modo === 'actividad') {
        var opt = $('#Fnc_Cod_D option:selected');
        curLat = opt.data('lat') || opt.attr('data-lat') || '';
        curLng = opt.data('lng') || opt.attr('data-lng') || '';
    } else if (modo === 'actividad_mod') {
        var opt = $('#modq_Fnc_Cod option:selected');
        curLat = opt.data('lat') || opt.attr('data-lat') || '';
        curLng = opt.data('lng') || opt.attr('data-lng') || '';
    }
    if (curLat && curLng) {
        url += '&lat=' + encodeURIComponent(curLat) + '&lng=' + encodeURIComponent(curLng) + '&zoom=20';
    }
    $('#dialogMapeoIndicacion').html('<i class="glyphicon glyphicon-info-sign text-info"></i> Modo Selecci&oacute;n: Haga clic en cualquier punto del mapa para capturar sus coordenadas, o elija un sector existente.');
    if ($('#dialogMapeoSector').length > 0) {
        $('#iframeMapeoSector').attr('src', url);
        $('#dialogMapeoSector').dialog('open');
    } else {
        window.open(url, '_blank');
    }
}

function abrirMapeoParaNuevaLocacion(modo) {
    abrirModalMapeoSector(modo);
}

function recargarSelectUbicaciones(callback) {
    $.getDataJson('', { fincasAjax: true }, function(resultado) {
        if (resultado && resultado.listaFincas) {
            $('.select_finca').each(function() {
                var $sel = $(this);
                var currentVal = $sel.val();
                $sel.find('option:not(:first)').remove();
                resultado.listaFincas.forEach(function(valor) {
                    var $opt = $('<option>', { value: valor.Fnc_Cod, text: valor.Fnc_Des });
                    if (valor.Fnc_Lat) $opt.attr('data-lat', valor.Fnc_Lat);
                    if (valor.Fnc_Lng) $opt.attr('data-lng', valor.Fnc_Lng);
                    $sel.append($opt);
                });
                if (currentVal) $sel.val(currentVal);
            });
            if (typeof callback === 'function') callback(resultado.listaFincas);
            $.alert('Lista de sectores actualizada (' + resultado.listaFincas.length + ' sectores disponibles).');
        }
    }, function(err) {
        $.alert('Error al recargar los sectores.');
    });
}

function abrirModalMapeoGeneral() {
    modoMapeoActual = 'general';
    var url = '../../mapeo/FRONT/map_alt_mapeo_interactivo.php?modo=selector&ts=' + new Date().getTime();
    $('#dialogMapeoIndicacion').html('<i class="glyphicon glyphicon-globe text-success"></i> Mapa Satelital HD de Sectores y Operaciones en Relavera El Tabl&oacute;n.');
    if ($('#dialogMapeoSector').length > 0) {
        $('#iframeMapeoSector').attr('src', url);
        $('#dialogMapeoSector').dialog('open');
    } else {
        window.open(url, '_blank');
    }
}

function verSectorEnMapa(lat, lng, nombreEnc, sectorId) {
    modoMapeoActual = 'ver';
    var nombre = sanitizarTexto(decodeURIComponent(nombreEnc || ''));
    var cod = sectorId || 0;
    var url = '../../mapeo/FRONT/map_alt_mapeo_interactivo.php?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng) + '&zoom=20&modo=ver&sector_id=' + encodeURIComponent(cod) + '&ts=' + new Date().getTime();
    $('#dialogMapeoIndicacion').html('<i class="glyphicon glyphicon-map-marker text-success"></i> Visualizando Sector: <b>' + escaparHTML(nombre || 'Georreferenciado') + '</b> (Lat: ' + parseFloat(lat).toFixed(5) + ', Lng: ' + parseFloat(lng).toFixed(5) + ')');
    if ($('#dialogMapeoSector').length > 0) {
        $('#iframeMapeoSector').attr('src', url);
        $('#dialogMapeoSector').dialog('open');
    } else {
        window.open(url, '_blank');
    }
}

function cerrarModalMapeoSector() {
    if ($('#dialogMapeoSector').length > 0) {
        $('#dialogMapeoSector').dialog('close');
    }
}

function recargarIframeMapeo() {
    var ifr = document.getElementById('iframeMapeoSector');
    if (ifr) ifr.src = ifr.src;
}

function sanitizarTexto(texto) {
    if (texto === undefined || texto === null) return '';
    return String(texto).replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function escaparHTML(texto) {
    if (texto === undefined || texto === null) return '';
    return String(texto)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Listener para eventos recibidos desde el mapa interactivo (iframe)
window.addEventListener('message', function(event) {
    if (!event.data || typeof event.data !== 'object') return;
    var d = event.data;

    if (d.type === 'SECTOR_SELECCIONADO') {
        var secId = d.id;
        var secNom = sanitizarTexto(d.nombre || '');
        if (modoMapeoActual === 'actividad') {
            if ($('#Fnc_Cod_D').length) {
                if (!$('#Fnc_Cod_D option[value="' + secId + '"]').length) {
                    $('#Fnc_Cod_D').append($('<option>', { value: secId, text: secNom }));
                }
                $('#Fnc_Cod_D').val(secId).trigger('change');
            }
        } else if (modoMapeoActual === 'actividad_mod') {
            if ($('#modq_Fnc_Cod').length) {
                if (!$('#modq_Fnc_Cod option[value="' + secId + '"]').length) {
                    $('#modq_Fnc_Cod').append($('<option>', { value: secId, text: secNom }));
                }
                $('#modq_Fnc_Cod').val(secId).trigger('change');
            }
        }
        if ($('#dialogMapeoSector').length > 0) {
            $('#dialogMapeoSector').dialog('close');
        }
        $.alert('Sector seleccionado: <b>' + escaparHTML(secNom) + '</b>');
    }

    if (d.type === 'SECTOR_CREADO') {
        recargarSelectUbicaciones(function(lista) {
            if (d.id) {
                if (modoMapeoActual === 'actividad' && $('#Fnc_Cod_D').length) {
                    $('#Fnc_Cod_D').val(d.id).trigger('change');
                } else if (modoMapeoActual === 'actividad_mod' && $('#modq_Fnc_Cod').length) {
                    $('#modq_Fnc_Cod').val(d.id).trigger('change');
                }
            }
        });
        if ($('#dialogMapeoSector').length > 0) {
            $('#dialogMapeoSector').dialog('close');
        }
        $.alert('Nuevo sector creado en el mapa y sincronizado en el listado.');
    }
});

