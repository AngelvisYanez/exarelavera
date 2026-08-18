<?php
/**
 * EXA Adquisiciones - Registro de Solicitudes
 * @author Oz <oz-agent@warp.dev>
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/adq_adquisiciones_log.php');

$obBD_conexion = new Class_Log_Conexion_Global($Ses_Dat_Dis);
$obBD_con1 = new adq_adquisiciones_log($obBD_conexion);
require_once('../LOGICA/wf_manager_log.php');
$wf_mgr = new wf_manager_log($Ses_Dat_Dis);

$ajax_save_solicitud = isset($_GET['ajax_save_solicitud']) ? $_GET['ajax_save_solicitud'] : (isset($_POST['ajax_save_solicitud']) ? $_POST['ajax_save_solicitud'] : null);
$ajax_save_borrador = isset($_GET['ajax_save_borrador']) ? $_GET['ajax_save_borrador'] : (isset($_POST['ajax_save_borrador']) ? $_POST['ajax_save_borrador'] : null);
$ajax_save_cotizaciones = isset($_GET['ajax_save_cotizaciones']) ? $_GET['ajax_save_cotizaciones'] : (isset($_POST['ajax_save_cotizaciones']) ? $_POST['ajax_save_cotizaciones'] : null);
$ajax_save_solicitud_corta = isset($_GET['ajax_save_solicitud_corta']) ? $_GET['ajax_save_solicitud_corta'] : (isset($_POST['ajax_save_solicitud_corta']) ? $_POST['ajax_save_solicitud_corta'] : null);
$ajax_completar_solicitud = isset($_GET['ajax_completar_solicitud']) ? $_GET['ajax_completar_solicitud'] : (isset($_POST['ajax_completar_solicitud']) ? $_POST['ajax_completar_solicitud'] : null);
$ajax_get_decisiones_flujo = isset($_GET['ajax_get_decisiones_flujo']) ? $_GET['ajax_get_decisiones_flujo'] : null;
$ajax_get_seleccion_usuarios_flujo = isset($_GET['ajax_get_seleccion_usuarios_flujo']) ? $_GET['ajax_get_seleccion_usuarios_flujo'] : null;
$ajax_get_trq_details = isset($_GET['ajax_get_trq_details']) ? $_GET['ajax_get_trq_details'] : null;
$ajax_get_borrador = isset($_GET['ajax_get_borrador']) ? $_GET['ajax_get_borrador'] : null;
$ajax_get_solicitud_cot = isset($_GET['ajax_get_solicitud_cot']) ? $_GET['ajax_get_solicitud_cot'] : null;
$ajax_search_proveedores = isset($_GET['ajax_search_proveedores']) ? $_GET['ajax_search_proveedores'] : null;
$ajax_lookup_proveedor = isset($_GET['ajax_lookup_proveedor']) ? $_GET['ajax_lookup_proveedor'] : null;
$ajax_save_proveedor = isset($_GET['ajax_save_proveedor'])
    ? $_GET['ajax_save_proveedor']
    : (isset($_POST['ajax_save_proveedor']) ? $_POST['ajax_save_proveedor'] : null);
$ajax_get_form = isset($_GET['ajax_get_form']) ? $_GET['ajax_get_form'] : null;

// Ensures solo cuando hacen falta (escritura o render del formulario).
$necesita_ensure_schema = (
    isset($ajax_save_solicitud) || isset($ajax_save_borrador) || isset($ajax_save_cotizaciones)
    || isset($ajax_save_solicitud_corta) || isset($ajax_completar_solicitud) || isset($ajax_get_form)
    || isset($ajax_get_borrador) || isset($ajax_get_solicitud_cot) || isset($ajax_save_proveedor)
    || isset($ajax_get_seleccion_usuarios_flujo)
);
if ($necesita_ensure_schema) {
    $obBD_con1->ensureSolicitudTituloColumn();
    $obBD_con1->ensureDecisionValsTable();
    $obBD_con1->ensureSolicitudRubrosTable();
    $obBD_con1->ensureCotizacionesSchema();
    $wf_mgr->ensureNotificationSchema();
}

function adq_validar_y_guardar_pdf_cot($tmp_name, $original_name, $target_dir, $rel_dir) {
    if (empty($tmp_name) || !is_uploaded_file($tmp_name)) {
        return null;
    }
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        return null;
    }
    $head = @file_get_contents($tmp_name, false, null, 0, 5);
    if ($head === false || strpos($head, '%PDF') !== 0) {
        return null;
    }
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);
            $mimes_validos = array('application/pdf', 'application/x-pdf', 'application/octet-stream', 'application/download');
            if ($mime && !in_array($mime, $mimes_validos, true)) {
                return null;
            }
        }
    }
    $unique_name = 'cot_' . uniqid() . '.pdf';
    $target_file = rtrim($target_dir, '/\\') . DIRECTORY_SEPARATOR . $unique_name;
    if (!move_uploaded_file($tmp_name, $target_file)) {
        return null;
    }
    return rtrim(str_replace('\\', '/', (string)$rel_dir), '/') . '/' . $unique_name;
}

function adq_procesar_pdfs_multiples($files_field, $key, $target_dir, $rel_dir) {
    $guardados = array();
    if (!isset($files_field['name'][$key])) {
        return $guardados;
    }
    $names = $files_field['name'][$key];
    if (!is_array($names)) {
        $names = array($names);
    }
    foreach ($names as $i => $name) {
        if ($name === '' || $name === null) {
            continue;
        }
        $error = is_array($files_field['error'][$key]) ? $files_field['error'][$key][$i] : $files_field['error'][$key];
        if ($error != 0) {
            continue;
        }
        $tmp_name = is_array($files_field['tmp_name'][$key]) ? $files_field['tmp_name'][$key][$i] : $files_field['tmp_name'][$key];
        $ruta = adq_validar_y_guardar_pdf_cot($tmp_name, $name, $target_dir, $rel_dir);
        if ($ruta !== null) {
            $guardados[] = $ruta;
        }
    }
    return $guardados;
}

function adq_normalizar_cot_adjuntos_existentes(&$cotizaciones_existentes, $files_existentes, $target_dir, $obBD_con1, $rel_dir) {
    if (!is_array($cotizaciones_existentes)) {
        return;
    }
    foreach ($cotizaciones_existentes as $sco_cod => &$cot) {
        $paths = array();
        if (!empty($cot['Cot_Adj_Keep'])) {
            $kept = is_array($cot['Cot_Adj_Keep']) ? $cot['Cot_Adj_Keep'] : array($cot['Cot_Adj_Keep']);
            foreach ($kept as $path) {
                $path = trim((string)$path);
                if ($path !== '' && strpos($path, '..') === false) {
                    $paths[] = $path;
                }
            }
        }
        if (!empty($files_existentes)) {
            $nuevos = adq_procesar_pdfs_multiples($files_existentes, $sco_cod, $target_dir, $rel_dir);
            if (!empty($nuevos)) {
                $paths = array_merge($paths, $nuevos);
            }
        }
        $cot['Cot_Adj'] = $obBD_con1->encodeCotAdjuntos($paths);
        unset($cot['Cot_Adj_Keep']);
    }
    unset($cot);
}

function adq_to_utf8($s) {
    $s = (string)$s;
    if ($s === '') {
        return '';
    }
    if (function_exists('mb_check_encoding') && mb_check_encoding($s, 'UTF-8')) {
        return $s;
    }
    return utf8_encode($s);
}

function adq_h($s) {
    return htmlspecialchars(adq_to_utf8($s), ENT_QUOTES, 'UTF-8');
}

function adq_cmp_cla_nat($a, $b) {
    $pa = ($a === '') ? array() : explode('.', $a);
    $pb = ($b === '') ? array() : explode('.', $b);
    $n = max(count($pa), count($pb));
    for ($i = 0; $i < $n; $i++) {
        $va = isset($pa[$i]) ? intval($pa[$i]) : -1;
        $vb = isset($pb[$i]) ? intval($pb[$i]) : -1;
        if ($va !== $vb) {
            return ($va < $vb) ? -1 : 1;
        }
    }
    return 0;
}

function adq_render_tabla_rubros($rubros, $seleccionados = array()) {
    $seleccionados = array_map('intval', is_array($seleccionados) ? $seleccionados : array());
    $hay = is_array($rubros) && count($rubros) > 0;
    $proyectos = array();
    if ($hay) {
        usort($rubros, function($a, $b) {
            $cmp = adq_cmp_cla_nat(isset($a['Ppa_Cla']) ? $a['Ppa_Cla'] : '', isset($b['Ppa_Cla']) ? $b['Ppa_Cla'] : '');
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcasecmp(isset($a['Pdp_Rubro']) ? $a['Pdp_Rubro'] : '', isset($b['Pdp_Rubro']) ? $b['Pdp_Rubro'] : '');
        });
        foreach ($rubros as $rb) {
            $pc = isset($rb['Pro_Cod']) ? (string)$rb['Pro_Cod'] : '';
            if ($pc === '' || isset($proyectos[$pc])) {
                continue;
            }
            $nom = trim((isset($rb['Pro_Ide']) && $rb['Pro_Ide'] !== '' ? $rb['Pro_Ide'] . ' — ' : '') . (isset($rb['Pro_Nom']) ? $rb['Pro_Nom'] : ''));
            $proyectos[$pc] = ($nom !== '') ? adq_to_utf8($nom) : ('Proyecto ' . $pc);
        }
        natcasesort($proyectos);
    }
    ?>
    <div class="adq-rubros-box">
        <label class="form-label fw-bold mb-1">Tipo de rubro presupuestario</label>
        <p class="text-muted small mb-2">Opcional. Abra el selector para marcar uno o más rubros del presupuesto.</p>
        <?php if (!$hay) { ?>
            <div class="alert alert-warning py-2 px-3 mb-0" style="font-size:13px;">No hay rubros de proyecto en <strong>pre_proyecto_detalles</strong> para esta empresa. Cárguelos en Presupuesto &gt; Proyectos (el presupuesto corporativo por partida no alcanza).</div>
        <?php } else { ?>
            <div class="d-flex align-items-center flex-wrap" style="gap:10px;">
                <button type="button" class="btn btn-primary btn-sm" id="btnAbrirRubrosPpto">
                    <i class="bi bi-ui-checks"></i> Seleccionar rubros
                </button>
                <span class="text-muted small text-nowrap" id="lblRubrosSel">0 seleccionados</span>
            </div>
            <div id="resumenRubrosPpto" class="adq-rubros-resumen"></div>
            <div id="rubrosHidden"></div>
            <div class="modal fade" id="mdlRubrosPpto" tabindex="-1" role="dialog" aria-labelledby="mdlRubrosPptoLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document" style="width:92%;max-width:920px;margin:30px auto;">
                    <div class="modal-content adq-rubros-modal">
                        <div class="modal-header adq-rubros-modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color:#fff;opacity:.9;text-shadow:none;"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="mdlRubrosPptoLabel"><i class="bi bi-list-check"></i> Seleccionar tipo de rubro</h4>
                        </div>
                        <div class="modal-body">
                            <div class="adq-rubros-filtros">
                                <div class="adq-rubros-filtro">
                                    <label for="selProyectoRubros">Proyecto</label>
                                    <select id="selProyectoRubros" class="form-control">
                                        <option value="">[Seleccione un proyecto]</option>
                                        <?php foreach ($proyectos as $pc => $pnom) { ?>
                                            <option value="<?php echo adq_h($pc); ?>"<?php echo (count($proyectos) === 1) ? ' selected' : ''; ?>><?php echo adq_h($pnom); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="adq-rubros-filtro">
                                    <label for="filtroRubrosPpto">Buscar</label>
                                    <input type="search" class="form-control input-sm" id="filtroRubrosPpto" placeholder="Clasificación, partida o rubro..." autocomplete="off">
                                </div>
                            </div>
                            <div id="adqRubrosHintProyecto" class="text-muted small" style="margin-bottom:8px;">Seleccione un proyecto para ver sus rubros en árbol.</div>
                            <div class="table-responsive adq-rubros-scroll">
                                <table class="table table-sm table-hover mb-0" id="tblRubrosPpto">
                                    <thead>
                                        <tr>
                                            <th style="width:36px;"></th>
                                            <th>Clasificación / detalle</th>
                                            <th>Rubro</th>
                                            <th class="text-end">Presup. anual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $last_pro = null;
                                    $last_g = null;
                                    $last_s = null;
                                    foreach ($rubros as $rb) {
                                        $pdp = intval($rb['Pdp_Cod']);
                                        $pro = isset($rb['Pro_Cod']) ? (string)$rb['Pro_Cod'] : '';
                                        $cla = isset($rb['Ppa_Cla']) ? trim($rb['Ppa_Cla']) : '';
                                        $grupo = isset($rb['grupo_cod']) ? $rb['grupo_cod'] : '';
                                        $sub = isset($rb['subgrupo_cod']) ? $rb['subgrupo_cod'] : '';
                                        $gdes = adq_to_utf8(isset($rb['grupo_des']) ? $rb['grupo_des'] : '');
                                        $sdes = adq_to_utf8(isset($rb['subgrupo_des']) ? $rb['subgrupo_des'] : '');
                                        $chk = in_array($pdp, $seleccionados, true) ? ' checked' : '';
                                        $proy_nom = adq_to_utf8(trim((isset($rb['Pro_Ide']) && $rb['Pro_Ide'] !== '' ? $rb['Pro_Ide'] . ' ' : '') . (isset($rb['Pro_Nom']) ? $rb['Pro_Nom'] : '')));
                                        $rubro = adq_to_utf8(isset($rb['Pdp_Rubro']) ? $rb['Pdp_Rubro'] : '');
                                        $partida = adq_to_utf8(isset($rb['Ppa_Des']) ? $rb['Ppa_Des'] : '');
                                        $monto = isset($rb['Pdp_PreAnual']) ? floatval($rb['Pdp_PreAnual']) : 0;
                                        if ($grupo !== '' && ($last_pro !== $pro || $last_g !== $grupo)) {
                                            $gtxt = $grupo . ($gdes !== '' ? ' — ' . $gdes : '');
                                            ?>
                                        <tr class="adq-rubro-grupo" data-pro="<?php echo adq_h($pro); ?>" data-grupo="<?php echo adq_h($grupo); ?>">
                                            <td></td>
                                            <td colspan="3"><i class="bi bi-folder2-open"></i> <?php echo adq_h($gtxt); ?></td>
                                        </tr>
                                            <?php
                                        }
                                        if ($sub !== '' && ($last_pro !== $pro || $last_s !== $sub)) {
                                            $stxt = $sub . ($sdes !== '' ? ' — ' . $sdes : '');
                                            ?>
                                        <tr class="adq-rubro-sub" data-pro="<?php echo adq_h($pro); ?>" data-grupo="<?php echo adq_h($grupo); ?>" data-sub="<?php echo adq_h($sub); ?>">
                                            <td></td>
                                            <td colspan="3"><i class="bi bi-diagram-3"></i> <?php echo adq_h($stxt); ?></td>
                                        </tr>
                                            <?php
                                        }
                                        $last_pro = $pro;
                                        $last_g = $grupo;
                                        $last_s = $sub;
                                        $search_src = $cla . ' ' . $rubro . ' ' . $partida . ' ' . $proy_nom;
                                        $search = function_exists('mb_strtolower') ? mb_strtolower($search_src, 'UTF-8') : strtolower($search_src);
                                        ?>
                                        <tr class="adq-rubro-row" data-pdp="<?php echo $pdp; ?>" data-pro="<?php echo adq_h($pro); ?>" data-grupo="<?php echo adq_h($grupo); ?>" data-sub="<?php echo adq_h($sub); ?>" data-rubro="<?php echo adq_h($rubro); ?>" data-search="<?php echo adq_h($search); ?>">
                                            <td>
                                                <input type="checkbox" class="form-check-input chk-rubro-ppto" value="<?php echo $pdp; ?>"<?php echo $chk; ?>>
                                            </td>
                                            <td>
                                                <span class="adq-rubro-cla"><?php echo adq_h($cla); ?></span>
                                                <?php if ($partida !== '') { ?>
                                                    <span class="adq-rubro-part"><?php echo adq_h($partida); ?></span>
                                                <?php } ?>
                                            </td>
                                            <td><?php echo adq_h($rubro); ?></td>
                                            <td class="text-end"><?php echo number_format($monto, 2, '.', ','); ?></td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <span class="text-muted small pull-left" id="lblRubrosSelModal" style="margin-top:8px;">0 seleccionados</span>
                            <button type="button" class="btn btn-default" id="btnCancelarRubrosPpto">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="btnConfirmarRubrosPpto"><i class="bi bi-check2"></i> Confirmar selección</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            (function() {
                if (typeof jQuery === 'undefined') {
                    return;
                }
                var $ = jQuery;
                var snapRubros = [];

                function adqCollectRubros() {
                    var ids = [];
                    var seen = {};
                    $('.chk-rubro-ppto:checked, #rubrosHidden input[name="rubros[]"]').each(function() {
                        var v = parseInt($(this).val(), 10);
                        if (v > 0 && !seen[v]) {
                            seen[v] = true;
                            ids.push(v);
                        }
                    });
                    return ids;
                }
                function adqHayRubrosDisponibles() {
                    return $('#tblRubrosPpto tbody tr.adq-rubro-row').length > 0;
                }
                function adqValidarRubros() {
                    return true;
                }
                function adqSyncRubrosHidden() {
                    var $h = $('#rubrosHidden');
                    if (!$h.length) {
                        return;
                    }
                    $h.empty();
                    var ids = adqCollectRubros();
                    for (var i = 0; i < ids.length; i++) {
                        $h.append($('<input type="hidden" name="rubros[]">').val(ids[i]));
                    }
                    var n = ids.length;
                    var txt = n + (n === 1 ? ' seleccionado' : ' seleccionados');
                    $('#lblRubrosSel, #lblRubrosSelModal').text(txt);
                    var $res = $('#resumenRubrosPpto');
                    $res.empty();
                    $('.chk-rubro-ppto:checked').each(function() {
                        var nom = $(this).closest('tr').attr('data-rubro') || $(this).val();
                        $res.append($('<span class="adq-rubro-chip"></span>').text(nom));
                    });
                }
                function adqAplicarRubros(ids) {
                    var set = {};
                    (ids || []).forEach(function(id) {
                        set[parseInt(id, 10)] = true;
                    });
                    $('.chk-rubro-ppto').each(function() {
                        $(this).prop('checked', !!set[parseInt($(this).val(), 10)]);
                    });
                    adqSyncRubrosHidden();
                }
                function adqCerrarModalRubros() {
                    var $mdl = $('#mdlRubrosPpto');
                    if ($mdl.data('bs.modal')) {
                        $mdl.modal('hide');
                    } else {
                        $mdl.hide().removeClass('in');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                    }
                }
                function adqFiltrarRubrosModal() {
                    var pro = String($('#selProyectoRubros').val() || '');
                    var q = String($('#filtroRubrosPpto').val() || '').toLowerCase();
                    var $hint = $('#adqRubrosHintProyecto');
                    if (!pro) {
                        $('#tblRubrosPpto tbody tr').hide();
                        $hint.show();
                        return;
                    }
                    $hint.hide();
                    $('#tblRubrosPpto tbody tr.adq-rubro-row').each(function() {
                        var $tr = $(this);
                        var okPro = String($tr.attr('data-pro') || '') === pro;
                        var txt = String($tr.attr('data-search') || $tr.text() || '').toLowerCase();
                        var okQ = !q || txt.indexOf(q) !== -1;
                        $tr.toggle(okPro && okQ);
                    });
                    $('#tblRubrosPpto tbody tr.adq-rubro-grupo, #tblRubrosPpto tbody tr.adq-rubro-sub').each(function() {
                        var $h = $(this);
                        if (String($h.attr('data-pro') || '') !== pro) {
                            $h.hide();
                            return;
                        }
                        var vis = 0;
                        if ($h.hasClass('adq-rubro-grupo')) {
                            var g = String($h.attr('data-grupo') || '');
                            vis = $h.nextAll('tr.adq-rubro-row').filter(function() {
                                var $r = $(this);
                                return $r.is(':visible') && String($r.attr('data-pro') || '') === pro && String($r.attr('data-grupo') || '') === g;
                            }).length;
                        } else {
                            var s = String($h.attr('data-sub') || '');
                            vis = $h.nextAll('tr.adq-rubro-row').filter(function() {
                                var $r = $(this);
                                return $r.is(':visible') && String($r.attr('data-pro') || '') === pro && String($r.attr('data-sub') || '') === s;
                            }).length;
                        }
                        $h.toggle(vis > 0);
                    });
                }
                window.adqCollectRubros = adqCollectRubros;
                window.adqValidarRubros = adqValidarRubros;
                window.adqAplicarRubros = adqAplicarRubros;

                $('body > #mdlRubrosPpto').not($('.adq-rubros-box #mdlRubrosPpto')).remove();
                var $mdl = $('#mdlRubrosPpto');
                if ($mdl.length && $mdl.parent()[0] !== document.body) {
                    $mdl.appendTo(document.body);
                }

                $(document).off('.adqRubrosPpto');
                $(document).on('click.adqRubrosPpto', '#btnAbrirRubrosPpto', function(e) {
                    e.preventDefault();
                    snapRubros = adqCollectRubros();
                    $('#filtroRubrosPpto').val('');
                    adqFiltrarRubrosModal();
                    adqSyncRubrosHidden();
                    $mdl.appendTo(document.body);
                    if (typeof $mdl.modal === 'function') {
                        $mdl.modal({ backdrop: true, keyboard: true, show: true });
                    } else {
                        $mdl.addClass('in').css('display', 'block');
                        if (!$('.modal-backdrop').length) {
                            $('<div class="modal-backdrop fade in"></div>').appendTo(document.body);
                        }
                        $('body').addClass('modal-open');
                    }
                });
                $(document).on('click.adqRubrosPpto', '#btnConfirmarRubrosPpto', function() {
                    adqSyncRubrosHidden();
                    adqCerrarModalRubros();
                });
                $(document).on('click.adqRubrosPpto', '#btnCancelarRubrosPpto, #mdlRubrosPpto [data-dismiss="modal"]', function() {
                    adqAplicarRubros(snapRubros);
                    adqCerrarModalRubros();
                });
                $(document).on('change.adqRubrosPpto', '.chk-rubro-ppto', adqSyncRubrosHidden);
                $(document).on('click.adqRubrosPpto', '#mdlRubrosPpto tr.adq-rubro-row', function(e) {
                    if ($(e.target).is('input, label, a, button')) {
                        return;
                    }
                    var $chk = $(this).find('.chk-rubro-ppto');
                    $chk.prop('checked', !$chk.prop('checked')).trigger('change');
                });
                $(document).on('change.adqRubrosPpto', '#selProyectoRubros', adqFiltrarRubrosModal);
                $(document).on('input.adqRubrosPpto', '#filtroRubrosPpto', adqFiltrarRubrosModal);
                $(document).on('shown.bs.modal.adqRubrosPpto', '#mdlRubrosPpto', function() {
                    $('.modal-backdrop').last().addClass('adq-rubros-backdrop').css('z-index', 20050);
                    adqFiltrarRubrosModal();
                    $('#selProyectoRubros').focus();
                });
                adqFiltrarRubrosModal();
                adqSyncRubrosHidden();
            })();
            </script>
        <?php } ?>
    </div>
    <style>
        .adq-rubros-resumen {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }
        .adq-rubro-chip {
            display: inline-block;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            border-radius: 999px;
            padding: 2px 10px;
            font-size: 12px;
        }
        #mdlRubrosPpto {
            z-index: 20060;
        }
        .modal-backdrop.adq-rubros-backdrop {
            z-index: 20050;
        }
        .adq-rubros-modal {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
        }
        .adq-rubros-modal-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            color: #fff;
            border: 0;
        }
        .adq-rubros-modal-header .modal-title {
            font-size: 16px;
            font-weight: 700;
        }
        .adq-rubros-filtros {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .adq-rubros-filtro {
            flex: 1;
            min-width: 200px;
        }
        .adq-rubros-filtro label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 4px;
        }
        tr.adq-rubro-grupo td {
            background: #1e3a5f;
            color: #fff;
            font-weight: 700;
            font-size: 12.5px;
        }
        tr.adq-rubro-sub td {
            background: #e2e8f0;
            color: #0f172a;
            font-weight: 600;
            font-size: 12.5px;
            padding-left: 22px !important;
        }
        tr.adq-rubro-row td:nth-child(2) {
            padding-left: 36px;
        }
        .adq-rubro-cla {
            font-family: ui-monospace, Consolas, monospace;
            font-size: 11px;
            color: #64748b;
            margin-right: 8px;
        }
        .adq-rubro-part {
            color: #334155;
        }
        .adq-rubros-scroll {
            max-height: 52vh;
            overflow: auto;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            background: #fff;
        }
        #tblRubrosPpto thead th {
            position: sticky;
            top: 0;
            background: #f1f5f9;
            z-index: 1;
            font-size: 12px;
            white-space: nowrap;
            color: #334155;
        }
        #tblRubrosPpto tbody td {
            font-size: 12.5px;
            vertical-align: middle;
        }
        tr.adq-rubro-row { cursor: pointer; }
        tr.adq-rubro-row:has(.chk-rubro-ppto:checked) {
            background: #eff6ff;
        }
    </style>
    <?php
}

// Verificar acceso a la ventana 'bandeja' y pestaña 'crear_solicitud'
if (!$wf_mgr->verificarAccesoVentana('bandeja', 'crear_solicitud')) {
    if (isset($ajax_save_solicitud) || isset($ajax_save_borrador) || isset($ajax_save_cotizaciones) || isset($ajax_save_solicitud_corta) || isset($ajax_completar_solicitud) || isset($ajax_get_trq_details) || isset($ajax_get_borrador) || isset($ajax_get_solicitud_cot) || isset($ajax_search_proveedores) || isset($ajax_lookup_proveedor) || isset($ajax_save_proveedor) || isset($ajax_get_decisiones_flujo) || isset($ajax_get_seleccion_usuarios_flujo)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Acceso denegado. No tiene permisos para realizar esta acción.'));
        exit;
    } else {
        echo "<div class='alert alert-danger m-3'>Acceso denegado. No tiene permisos para ver esta ventana.</div>";
        exit;
    }
}

// Redirección segura para navegación directa del navegador (no AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['ajax_get_form']) && !isset($_GET['ajax_get_trq_details']) && !isset($_GET['ajax_get_borrador']) && !isset($_GET['ajax_get_solicitud_cot']) && !isset($_GET['ajax_search_proveedores']) && !isset($_GET['ajax_lookup_proveedor']) && !isset($_GET['ajax_save_proveedor']) && !isset($_GET['ajax_save_solicitud']) && !isset($_GET['ajax_save_borrador']) && !isset($_GET['ajax_save_cotizaciones']) && !isset($_GET['ajax_save_solicitud_corta']) && !isset($_GET['ajax_completar_solicitud']) && !isset($_GET['ajax_get_decisiones_flujo']) && !isset($_GET['ajax_get_seleccion_usuarios_flujo'])) {
    header("Location: adq_bandeja.php?tab=crear_solicitud");
    exit;
}

// Manejo de llamadas AJAX
if (isset($ajax_get_decisiones_flujo)) {
    $trq_cod = intval(isset($_GET['trq_cod']) ? $_GET['trq_cod'] : 0);
    $obBD_con1->echoJson($wf_mgr->obtenerDecisionesFlujoPorTipo($trq_cod, intval($Ses_Emp_Cod)));
    exit;
}

if (isset($ajax_get_seleccion_usuarios_flujo)) {
    $trq_cod = intval(isset($_GET['trq_cod']) ? $_GET['trq_cod'] : 0);
    $sol_cod = intval(isset($_GET['sol_cod']) ? $_GET['sol_cod'] : 0);
    $obBD_con1->echoJson($wf_mgr->obtenerSeleccionUsuariosFlujoPorTipo($trq_cod, intval($Ses_Emp_Cod), $sol_cod));
    exit;
}

if (isset($ajax_save_solicitud_corta)) {
    $_POST['Emp_Cod'] = $Ses_Emp_Cod;
    $_POST['Suc_Cod'] = $Ses_Suc_Cod;
    $resCorta = $obBD_con1->registrarSolicitudCorta($_POST);
    // Responder al navegador; notificaciones (WhatsApp/correo) van despues.
    if (session_id() !== '') {
        session_write_close();
    }
    @ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($resCorta);
    if (function_exists('flush')) {
        @flush();
    }
    if (!empty($resCorta['success']) && class_exists('wf_manager_log') && wf_manager_log::hayNotificacionesPendientes()) {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        ignore_user_abort(true);
        ob_start();
        try {
            $wf_mgr->flushPendingNotificaciones();
        } catch (Exception $e) {
        }
        @ob_end_clean();
    }
    exit;
}

if (isset($ajax_save_solicitud) || isset($ajax_save_borrador) || isset($ajax_save_cotizaciones) || isset($ajax_completar_solicitud)) {
    $items = array();
    if (isset($_POST['items'])) {
        $items = $_POST['items'];
    }

    $cotizaciones = array();
    if (isset($_POST['cotizaciones'])) {
        $cotizaciones = $_POST['cotizaciones'];
    }

    $cotizaciones_existentes = array();
    if (isset($_POST['cotizaciones_existentes'])) {
        $cotizaciones_existentes = $_POST['cotizaciones_existentes'];
    }

    $cot_eliminar = array();
    if (isset($_POST['cot_eliminar'])) {
        $cot_eliminar = is_array($_POST['cot_eliminar']) ? $_POST['cot_eliminar'] : array($_POST['cot_eliminar']);
    }

    $sol_cod_files = isset($_POST['Sol_Cod']) ? intval($_POST['Sol_Cod']) : 0;
    $sol_tit_hint = isset($_POST['Sol_Tit']) ? trim((string)$_POST['Sol_Tit']) : '';
    try {
        $dir_info = $obBD_con1->asegurarDirectorioDocumentosSolicitud($sol_cod_files, $sol_tit_hint);
    } catch (Exception $e) {
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
        exit;
    }
    $target_dir = $dir_info['abs'] . '/';
    $rel_dir = $dir_info['rel'];

    if (isset($_FILES['cotizacion_archivos'])) {
        $names = $_FILES['cotizacion_archivos']['name'];
        if (is_array($names)) {
            foreach ($names as $cot_idx => $dummy) {
                $nuevos = adq_procesar_pdfs_multiples($_FILES['cotizacion_archivos'], $cot_idx, $target_dir, $rel_dir);
                if (!empty($nuevos)) {
                    if (!isset($cotizaciones[$cot_idx])) {
                        $cotizaciones[$cot_idx] = array();
                    }
                    $actuales = $obBD_con1->parseCotAdjuntos(isset($cotizaciones[$cot_idx]['Cot_Adj']) ? $cotizaciones[$cot_idx]['Cot_Adj'] : '');
                    $cotizaciones[$cot_idx]['Cot_Adj'] = $obBD_con1->encodeCotAdjuntos(array_merge($actuales, $nuevos));
                }
            }
        }
    }

    if (!empty($cotizaciones_existentes)) {
        $files_existentes = isset($_FILES['cotizacion_archivos_existentes']) ? $_FILES['cotizacion_archivos_existentes'] : null;
        adq_normalizar_cot_adjuntos_existentes($cotizaciones_existentes, $files_existentes, $target_dir, $obBD_con1, $rel_dir);
    }

    $adjuntos_nuevos = array();
    if (isset($_POST['adjuntos']) && is_array($_POST['adjuntos'])) {
        foreach ($_POST['adjuntos'] as $idx => $adj) {
            $des = isset($adj['Sad_Des']) ? trim($adj['Sad_Des']) : '';
            $ruta = null;
            if (isset($_FILES['adjunto_archivos']['name'][$idx]) && $_FILES['adjunto_archivos']['name'][$idx] !== '') {
                $ruta = adq_validar_y_guardar_pdf_cot(
                    $_FILES['adjunto_archivos']['tmp_name'][$idx],
                    $_FILES['adjunto_archivos']['name'][$idx],
                    $target_dir,
                    $rel_dir
                );
            }
            if ($ruta) {
                $adjuntos_nuevos[] = array('Sad_Des' => $des, 'Sad_Adj' => $ruta);
            }
        }
    }

    $adjuntos_existentes = array();
    if (isset($_POST['adjuntos_existentes']) && is_array($_POST['adjuntos_existentes'])) {
        $adjuntos_existentes = $_POST['adjuntos_existentes'];
        foreach ($adjuntos_existentes as $sad_cod => &$adj_ex) {
            if (isset($_FILES['adjunto_archivos_existentes']['name'][$sad_cod]) && $_FILES['adjunto_archivos_existentes']['name'][$sad_cod] !== '') {
                $ruta = adq_validar_y_guardar_pdf_cot(
                    $_FILES['adjunto_archivos_existentes']['tmp_name'][$sad_cod],
                    $_FILES['adjunto_archivos_existentes']['name'][$sad_cod],
                    $target_dir,
                    $rel_dir
                );
                if ($ruta) {
                    $adj_ex['Sad_Adj'] = $ruta;
                }
            }
        }
        unset($adj_ex);
    }

    $adj_eliminar = array();
    if (isset($_POST['adj_eliminar'])) {
        $adj_eliminar = is_array($_POST['adj_eliminar']) ? $_POST['adj_eliminar'] : array($_POST['adj_eliminar']);
    }

    $_POST['Emp_Cod'] = $Ses_Emp_Cod;
    $_POST['Suc_Cod'] = $Ses_Suc_Cod;

    if (isset($ajax_save_cotizaciones)) {
        $sol_cod = intval(isset($_POST['Sol_Cod']) ? $_POST['Sol_Cod'] : 0);
        $resp = $obBD_con1->guardarCotizacionesEtapa($sol_cod, $cotizaciones, $cotizaciones_existentes, $cot_eliminar);
    } elseif (isset($ajax_completar_solicitud)) {
        $resp = $obBD_con1->completarSolicitudNodo($_POST, $items, $cotizaciones, $cotizaciones_existentes, $cot_eliminar, $adjuntos_nuevos, $adjuntos_existentes, $adj_eliminar);
    } elseif (isset($ajax_save_borrador)) {
        $resp = $obBD_con1->guardarBorrador($_POST, $items, $cotizaciones, $cotizaciones_existentes, $cot_eliminar, $adjuntos_nuevos, $adjuntos_existentes, $adj_eliminar);
    } else {
        $resp = $obBD_con1->guardarSolicitud($_POST, $items, $cotizaciones, $cotizaciones_existentes, $cot_eliminar, $adjuntos_nuevos, $adjuntos_existentes, $adj_eliminar);
    }
    // Responder al navegador; notificaciones (WhatsApp/correo) van despues sin contaminar el JSON.
    if (!empty($resp['success']) && class_exists('wf_manager_log') && wf_manager_log::hayNotificacionesPendientes()) {
        if (session_id() !== '') {
            session_write_close();
        }
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($resp);
        if (function_exists('flush')) {
            @flush();
        }
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        ignore_user_abort(true);
        ob_start();
        try {
            $wf_mgr->flushPendingNotificaciones();
        } catch (Exception $e) {
            // No contaminar la respuesta ya enviada.
        }
        @ob_end_clean();
        exit;
    }
    $obBD_con1->echoJson($resp);
    exit;
}

if (isset($ajax_get_borrador)) {
    $sol_cod = intval($_GET['sol_cod']);
    $usu_sol = isset($Ses_Usu_Cod) ? intval($Ses_Usu_Cod) : 0;
    $por_nodo = !empty($_GET['por_nodo']);
    $resp = $obBD_con1->obtenerBorradorParaEdicion($sol_cod, $Ses_Emp_Cod, $usu_sol, $por_nodo);
    $obBD_con1->echoJson($resp);
    exit;
}

if (isset($ajax_get_solicitud_cot)) {
    $sol_cod = intval($_GET['sol_cod']);
    $usu_cod = isset($Ses_Usu_Cod) ? intval($Ses_Usu_Cod) : 0;
    $resp = $obBD_con1->obtenerSolicitudParaCotizaciones($sol_cod, $Ses_Emp_Cod, $usu_cod);
    $obBD_con1->echoJson($resp);
    exit;
}

/**
 * Mapea datos del modal de proveedor tomando como fuente principal la tabla persona.
 */
function adq_mapearDatosPersonaProveedor($row) {
    $prs_ced = trim($row['Prs_Ced']);
    $prs_ape = trim(isset($row['Prs_Ape']) ? $row['Prs_Ape'] : '');
    $prs_nom = trim(isset($row['Prs_Nom']) ? $row['Prs_Nom'] : '');

    if (strlen($prs_ced) === 10) {
        $razon_social = trim($prs_ape . ' ' . $prs_nom);
    } else {
        $razon_social = $prs_ape !== '' ? $prs_ape : $prs_nom;
    }
    if ($razon_social === '') {
        $razon_social = $prs_ape;
    }

    $prs_cor = trim(isset($row['Prs_Cor']) ? $row['Prs_Cor'] : '');
    $prs_tel = trim(isset($row['Prs_Tel']) ? $row['Prs_Tel'] : '');
    if ($prs_tel === '' && !empty($row['Prs_Cel'])) {
        $prs_tel = trim($row['Prs_Cel']);
    }

    $prv_com = !empty($row['Prv_Com']) ? trim($row['Prv_Com']) : '';
    $prv_cod = !empty($row['Prv_Cod']) ? intval($row['Prv_Cod']) : null;

    $nombre_completo = $razon_social;
    if ($prv_com !== '') {
        $nombre_completo .= ' (' . $prv_com . ')';
    }
    $nombre_completo .= ' - RUC: ' . $prs_ced;

    return array(
        'Prv_Cod' => $prv_cod,
        'Prs_Cod' => !empty($row['Prs_Cod']) ? intval($row['Prs_Cod']) : null,
        'Prs_Ced' => $prs_ced,
        'Prs_Ape' => $razon_social,
        'Prs_Nom' => $prs_nom,
        'Prs_Dir' => trim(isset($row['Prs_Dir']) ? $row['Prs_Dir'] : ''),
        'Prv_Com' => $prv_com,
        'Prv_Cor' => $prs_cor,
        'Prv_Tel' => $prs_tel,
        'text' => $nombre_completo
    );
}

/**
 * Consulta persona por cedula/RUC y, si aplica, el proveedor de la empresa actual.
 */
function adq_buscarPersonaProveedorPorCedula($obBD_con1, $obBD_conexion, $prs_ced, $emp_cod) {
    $prs_ced = mysqli_real_escape_string($obBD_conexion->conexion, trim($prs_ced));
    $emp_cod = intval($emp_cod);
    if ($prs_ced === '') {
        return null;
    }

    return $obBD_con1->getRowConsultaSql(
        "SELECT per.Prs_Cod, per.Prs_Ced, per.Prs_Nom, per.Prs_Ape, per.Prs_Dir, per.Prs_Cor, per.Prs_Tel, per.Prs_Cel,
                per.Ide_Cod, per.Ciu_Cod,
                p.Prv_Cod, p.Prv_Com
         FROM persona per
         LEFT JOIN proveedore p ON p.Prs_Cod = per.Prs_Cod AND p.Emp_Cod = $emp_cod
         WHERE per.Prs_Ced = '$prs_ced'
         LIMIT 1;",
        $obBD_conexion
    );
}

if (isset($ajax_get_trq_details)) {
    $trq_cod = intval($_GET['trq_cod']);
    $trq = $obBD_con1->getRowConsultaSql("SELECT * FROM adq_tipos_requerimientos WHERE Trq_Cod = $trq_cod;", $obBD_conexion);
    $obBD_con1->echoJson(array('success' => true, 'data' => $trq));
    exit;
}

if (isset($ajax_search_proveedores)) {
    $search = isset($_GET['q']) ? mysqli_real_escape_string($obBD_conexion->conexion, $_GET['q']) : '';
    $sql = "SELECT p.Prv_Cod, per.Prs_Ced, per.Prs_Nom, per.Prs_Ape, p.Prv_Com 
            FROM proveedore p
            INNER JOIN persona per ON per.Prs_Cod = p.Prs_Cod
            WHERE p.Emp_Cod = $Ses_Emp_Cod 
              AND (per.Prs_Ced LIKE '%$search%' OR per.Prs_Ape LIKE '%$search%' OR per.Prs_Nom LIKE '%$search%' OR p.Prv_Com LIKE '%$search%')
            ORDER BY per.Prs_Ape, per.Prs_Nom
            LIMIT 30;";
    $rows = $obBD_con1->getArrayConsultaSql($sql, $obBD_conexion);
    
    $results = array();
    foreach ($rows as $r) {
        $nombre = trim($r['Prs_Ape'] . ' ' . $r['Prs_Nom']);
        if (!empty($r['Prv_Com'])) {
            $nombre .= " (" . $r['Prv_Com'] . ")";
        }
        $nombre .= " - RUC: " . $r['Prs_Ced'];
        
        $results[] = array(
            'id' => $r['Prv_Cod'],
            'text' => $nombre
        );
    }
    $obBD_con1->echoJson($results);
    exit;
}

if (isset($ajax_lookup_proveedor)) {
    $prs_ced = isset($_GET['Prs_Ced']) ? trim($_GET['Prs_Ced']) : '';

    if ($prs_ced === '') {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'Ingrese un RUC o Cedula.'));
        exit;
    }

    $row = adq_buscarPersonaProveedorPorCedula($obBD_con1, $obBD_conexion, $prs_ced, $Ses_Emp_Cod);

    if (empty($row)) {
        $obBD_con1->echoJson(array(
            'success' => true,
            'existe' => false,
            'proveedor_existe' => false
        ));
        exit;
    }

    $data = adq_mapearDatosPersonaProveedor($row);

    $obBD_con1->echoJson(array(
        'success' => true,
        'existe' => true,
        'proveedor_existe' => !empty($data['Prv_Cod']),
        'data' => $data
    ));
    exit;
}

if (isset($ajax_save_proveedor)) {
    $prs_ced = mysqli_real_escape_string($obBD_conexion->conexion, trim($_POST['Prs_Ced']));
    $prs_ape = mysqli_real_escape_string($obBD_conexion->conexion, trim($_POST['Prs_Ape']));
    $prv_com = mysqli_real_escape_string($obBD_conexion->conexion, trim($_POST['Prv_Com']));
    $prv_cor = mysqli_real_escape_string($obBD_conexion->conexion, trim($_POST['Prv_Cor']));
    $prv_tel = mysqli_real_escape_string($obBD_conexion->conexion, trim($_POST['Prv_Tel']));

    if (empty($prs_ced) || empty($prs_ape)) {
        $obBD_con1->echoJson(array('success' => false, 'message' => 'El RUC/Cédula y la Razón Social son obligatorios.'));
        exit;
    }

    // Si la persona ya es proveedor en esta empresa, devolverlo sin duplicar
    $row_exist = adq_buscarPersonaProveedorPorCedula($obBD_con1, $obBD_conexion, $prs_ced, $Ses_Emp_Cod);
    if (!empty($row_exist) && !empty($row_exist['Prv_Cod'])) {
        $data_exist = adq_mapearDatosPersonaProveedor($row_exist);
        $obBD_con1->echoJson(array(
            'success' => true,
            'id' => intval($data_exist['Prv_Cod']),
            'text' => $data_exist['text'],
            'existente' => true
        ));
        exit;
    }

    // Obtener un Ciu_Cod válido
    $row_ciu = $obBD_con1->getRowConsultaSql("SELECT Ciu_Cod FROM ciudad LIMIT 1;", $obBD_conexion);
    $ciu_cod = $row_ciu ? intval($row_ciu['Ciu_Cod']) : 217;

    $ide_cod = (strlen($prs_ced) === 10) ? 2 : 1;
    $prv_tic = (strlen($prs_ced) === 10) ? 'N' : 'J';

    // Verificar si ya existe la persona en el sistema
    $exist_pers = adq_buscarPersonaProveedorPorCedula($obBD_con1, $obBD_conexion, $prs_ced, $Ses_Emp_Cod);

    $obBD_con1->inicio_transaccion($obBD_conexion);
    try {
        if (!empty($exist_pers['Prs_Cod'])) {
            $prs_cod = intval($exist_pers['Prs_Cod']);
            // Actualizar datos de persona
            $sql_upd_pers = "UPDATE persona SET Prs_Ape = '$prs_ape', Prs_Cor = '$prv_cor', Prs_Tel = '$prv_tel' WHERE Prs_Cod = $prs_cod;";
            $obBD_con1->grabarv_registros($sql_upd_pers, $obBD_conexion);
        } else {
            // Insertar persona
            $sql_ins_pers = "INSERT INTO persona (Prs_Ced, Prs_Nom, Prs_Ape, Prs_Sex, Prs_Dir, Prs_Tel, Prs_Cel, Ciu_Cod, Ide_Cod, Prs_Cor, Prs_Est) 
                             VALUES ('$prs_ced', '', '$prs_ape', 'M', 'S/N', '$prv_tel', '', $ciu_cod, $ide_cod, '$prv_cor', 'A');";
            $obBD_con1->grabarv_registros($sql_ins_pers, $obBD_conexion);
            $prs_cod = $obBD_con1->insercionid($obBD_conexion);
        }

        // Insertar proveedor
        $final_com = !empty($prv_com) ? $prv_com : $prs_ape;
        $sql_ins_prv = "INSERT INTO proveedore (Emp_Cod, Prs_Cod, Prv_Com, Prv_Tic, Prv_Tel, Prv_Cor, Prv_Esp, Prv_Con, Prv_Reg, Prv_Ris, Prv_Gct, Prv_Rim_Emp, Prv_Rim_Np, Prv_Ag_Ret, Prv_Est) 
                        VALUES ($Ses_Emp_Cod, $prs_cod, '$final_com', '$prv_tic', '$prv_tel', '$prv_cor', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'A');";
        $obBD_con1->grabarv_registros($sql_ins_prv, $obBD_conexion);
        $prv_cod = $obBD_con1->insercionid($obBD_conexion);

        $obBD_con1->commit_nomsn($obBD_conexion);

        $row_new = adq_buscarPersonaProveedorPorCedula($obBD_con1, $obBD_conexion, $prs_ced, $Ses_Emp_Cod);
        $data_new = adq_mapearDatosPersonaProveedor($row_new);

        $obBD_con1->echoJson(array(
            'success' => true,
            'id' => $prv_cod,
            'text' => $data_new['text']
        ));
    } catch (Exception $e) {
        $obBD_con1->rollBack_nomsn($obBD_conexion);
        $obBD_con1->echoJson(array('success' => false, 'message' => $e->getMessage()));
    }
    exit;
}

// Cargar catálogos iniciales (filtrados por permiso de crear en nodo INICIO)
$tipos_req = $wf_mgr->listarTiposRequerimientoParaCrear($Ses_Emp_Cod);
$centros_costo = $obBD_con1->getArrayConsultaSql("SELECT DISTINCT Dep_Cdc AS Cdc_Cod FROM departamen WHERE Emp_Cod = $Ses_Emp_Cod AND Dep_Cdc IS NOT NULL AND Dep_Cdc <> '';", $obBD_conexion);
$rubros_ppto = $obBD_con1->listarRubrosPresupuestoDisponibles(intval($Ses_Emp_Cod));

if (isset($ajax_get_form)) {
    header('Content-Type: text/html; charset=UTF-8');
    $modo_form = isset($_GET['modo']) ? trim($_GET['modo']) : 'corto';
    $sol_form_cod = isset($_GET['sol_cod']) ? intval($_GET['sol_cod']) : 0;
    $adq_ocultar_cotizaciones = false;
    $adq_cot_edit = 1;
    $adq_cot_sel = 1;
    if ($modo_form === 'completar' && $sol_form_cod > 0) {
        $ins_form = $obBD_con1->getRowConsultaSql(
            "SELECT i.Ins_Cod
             FROM wf_instancias i
             WHERE i.Ins_Ent_Typ = 'adq_solicitudes' AND i.Ins_Ent_Cod = $sol_form_cod AND i.Ins_Est = 'P'
             ORDER BY i.Ins_Cod DESC LIMIT 1;",
            $obBD_conexion
        );
        if (!empty($ins_form['Ins_Cod'])) {
            $adq_cot_edit = ($wf_mgr->resolverNodCotEditInstancia(intval($ins_form['Ins_Cod'])) === 1) ? 1 : 0;
            $adq_cot_sel = ($wf_mgr->resolverNodCotSelInstancia(intval($ins_form['Ins_Cod'])) === 1) ? 1 : 0;
            if ($adq_cot_edit !== 1 && $adq_cot_sel !== 1) {
                $adq_ocultar_cotizaciones = true;
            }
        }
    }
    if ($modo_form !== 'completar') {
        ?>
        <div class="adq-step-card" style="max-width:980px;margin:24px auto;background:#fff;border:1px solid #dbe4ee;border-radius:14px;padding:28px;box-shadow:0 8px 28px rgba(15,43,70,.08);">
            <div class="d-flex align-items-center mb-4" style="gap:14px;">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white" style="width:46px;height:46px;font-size:21px;"><i class="bi bi-file-earmark-plus"></i></span>
                <div>
                    <h4 class="fw-bold text-primary mb-1">Crear solicitud</h4>
                    <p class="text-muted mb-0">Registre los datos iniciales. El responsable de la primera etapa completará la información técnica y comercial.</p>
                </div>
            </div>
            <form id="frmSolicitudCorta" autocomplete="off">
                <div class="mb-4">
                    <label class="form-label fw-bold" for="Sol_Tit_Corto">Nombre de la solicitud *</label>
                    <input type="text" class="form-control" id="Sol_Tit_Corto" name="Sol_Tit" maxlength="255" required placeholder="EJ. ADQUISICIÓN DE EQUIPOS PARA LABORATORIO" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase();">
                    <small class="text-muted">Use un nombre breve y claro que identifique la necesidad. Se registra siempre en mayúsculas.</small>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold" for="Trq_Cod_Corto">Tipo de requerimiento *</label>
                    <select class="form-control" id="Trq_Cod_Corto" name="Trq_Cod" required style="width: 100%;">
                        <option value="">[Seleccione un Tipo]</option>
                        <?php foreach ($tipos_req as $tr) { ?>
                            <option value="<?php echo intval($tr['Trq_Cod']); ?>"><?php echo htmlspecialchars($tr['Trq_Des'] . ' — ' . $tr['Wfm_Nom'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>
                    <small class="text-muted">Se listan todos los tipos Activos. Puede escribir para buscar. El tipo determina el flujo y el responsable del primer nodo.</small>
                </div>
                <div class="mb-4">
                    <?php adq_render_tabla_rubros($rubros_ppto); ?>
                </div>
                <div id="panelValorEstimadoCorta" class="mb-4" style="display:none;">
                    <label class="form-label fw-bold" for="Sol_Val_Est_Corto">Valor estimado *</label>
                    <input type="number" class="form-control" id="Sol_Val_Est_Corto" min="0.01" step="0.01" placeholder="00.00">
                    <small class="text-muted">Ingrese el monto estimado de la solicitud.</small>
                </div>
                <div id="panelDecisionesCorta" class="mb-4" style="display:none;">
                    <div class="p-3 rounded border" style="background:#f8fafc; border-color:#cbd5e1 !important;">
                        <h6 class="fw-bold text-primary mb-1" style="font-size:13px;"><i class="bi bi-signpost-split"></i> Valores para nodos de decisión</h6>
                        <p class="text-muted small mb-3 mb-md-2">Este flujo tiene ramas condicionales. Indique los valores para determinar qué tareas se ejecutarán.</p>
                        <div id="camposDecisionesCorta" class="row g-2"></div>
                        <div id="previewRamasCorta" class="mt-3"></div>
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4" id="btnCrearSolicitudCorta"><i class="bi bi-arrow-right-circle"></i> Registrar para iniciar los procesos</button>
                </div>
            </form>
        </div>
        <div id="adqLoaderRegistro" aria-live="polite" aria-busy="false" style="display:none;position:fixed;inset:0;z-index:4000;background:rgba(15,23,42,0.55);align-items:center;justify-content:center;padding:20px;">
            <div style="background:#ffffff;border-radius:12px;padding:28px 36px;text-align:center;box-shadow:0 20px 40px rgba(15,23,42,0.25);min-width:260px;max-width:360px;">
                <div class="spinner-border text-primary" role="status" style="width:2.6rem;height:2.6rem;"></div>
                <div id="adqLoaderRegistroTitulo" style="margin-top:16px;font-size:16px;font-weight:700;color:#0f172a;">Registrando solicitud...</div>
                <div id="adqLoaderRegistroDetalle" style="margin-top:6px;font-size:13px;color:#64748b;">Por favor espere mientras se guarda el registro.</div>
            </div>
        </div>
        <script>
        (function() {
            var decisionesCacheCorta = { decisiones: [], campos: [] };

            function evalCondicionLocal(cond, valores) {
                if (!cond || !cond.campo) return true;
                var campo = cond.campo === 'Dep_Cod' ? 'Dep_Sol' : cond.campo;
                var real = valores[campo];
                if (real === undefined || real === null || real === '') return false;
                var op = cond.operador || '=';
                var esperado = cond.valor;
                var nReal = parseFloat(real);
                var nEsp = parseFloat(esperado);
                var numOk = !isNaN(nReal) && !isNaN(nEsp);
                switch (op) {
                    case '>': return numOk ? nReal > nEsp : false;
                    case '<': return numOk ? nReal < nEsp : false;
                    case '>=': return numOk ? nReal >= nEsp : false;
                    case '<=': return numOk ? nReal <= nEsp : false;
                    case '!=': return String(real) !== String(esperado);
                    case '=':
                    default: return String(real) === String(esperado);
                }
            }

            function leerValoresDecisionCorta() {
                var vals = {};
                $('#camposDecisionesCorta [data-decision-campo]').each(function() {
                    vals[$(this).data('decision-campo')] = $(this).val();
                });
                return vals;
            }

            function actualizarPreviewRamasCorta() {
                var $box = $('#previewRamasCorta').empty();
                if (!decisionesCacheCorta.decisiones || !decisionesCacheCorta.decisiones.length) return;
                var vals = leerValoresDecisionCorta();
                var html = '<div class="small"><strong>Ruta estimada:</strong><ul class="mb-0 mt-1">';
                decisionesCacheCorta.decisiones.forEach(function(dec) {
                    var elegida = null;
                    var defecto = null;
                    (dec.ramas || []).forEach(function(r) {
                        if (parseInt(r.es_default, 10) === 1) {
                            defecto = r;
                            return;
                        }
                        if (!elegida && evalCondicionLocal(r.condicion, vals)) {
                            elegida = r;
                        }
                    });
                    var rama = elegida || defecto;
                    var destino = rama ? (rama.Destino_Nom || ('Nodo ' + rama.Nod_Des)) : '(sin destino)';
                    var texto = rama ? rama.texto : 'Sin coincidencia';
                    html += '<li><span class="fw-bold">' + $('<div>').text(dec.Nod_Nom).html() + '</span>: ' +
                        $('<div>').text(texto).html() + ' → <em>' + $('<div>').text(destino).html() + '</em></li>';
                });
                html += '</ul></div>';
                $box.html(html);
            }

            function renderCamposDecisionCorta(info) {
                decisionesCacheCorta = info || { decisiones: [], campos: [] };
                var $panel = $('#panelDecisionesCorta');
                var $campos = $('#camposDecisionesCorta').empty();
                if (!$('#Trq_Cod_Corto').val()) {
                    $panel.hide();
                    $('#panelValorEstimadoCorta').hide();
                    $('#previewRamasCorta').empty();
                    return;
                }
                if (!info || !info.decisiones || !info.decisiones.length) {
                    $panel.hide();
                    $('#panelValorEstimadoCorta').show();
                    $('#previewRamasCorta').empty();
                    return;
                }
                $('#panelValorEstimadoCorta').hide();
                var campos = (info && info.campos ? info.campos.slice() : []);
                var tieneValorEstimado = campos.some(function(c) {
                    return c.campo === 'Sol_Val_Est';
                });
                if (!tieneValorEstimado) {
                    campos.unshift({
                        campo: 'Sol_Val_Est',
                        etiqueta: 'Valor estimado / monto',
                        tipo: 'number',
                        opciones: []
                    });
                }
                campos.forEach(function(c) {
                    if (c.campo === 'Sol_Cod' || c.campo === 'Trq_Cod') return;
                    var $col = $('<div class="col-md-6"></div>');
                    var $lab = $('<label class="form-label small fw-bold"></label>').text(c.etiqueta + ' *');
                    var $input;
                    if (c.opciones && c.opciones.length) {
                        $input = $('<select class="form-control form-control-sm" required></select>');
                        $input.append('<option value="">[Seleccione]</option>');
                        c.opciones.forEach(function(op) {
                            $input.append($('<option></option>').val(op).text(op));
                        });
                    } else {
                        $input = $('<input class="form-control form-control-sm" required />');
                        $input.attr('type', c.tipo === 'number' ? 'number' : 'text');
                        if (c.tipo === 'number') {
                            $input.attr({ step: '0.01', min: '0' });
                            if (c.campo === 'Sol_Val_Est') {
                                $input.attr('min', '0.01');
                                $input.attr('placeholder', '00.00');
                            }
                        }
                    }
                    $input.attr({
                        name: 'decision_vals[' + c.campo + ']',
                        'data-decision-campo': c.campo,
                        id: 'dec_corta_' + c.campo
                    });
                    $input.on('input change', actualizarPreviewRamasCorta);
                    $col.append($lab).append($input);
                    $campos.append($col);
                });
                // Mostrar resumen de ramas configuradas
                if (info.decisiones && info.decisiones.length) {
                    var resumen = '<div class="mt-2 small text-muted">Nodos de decisión en el flujo: ';
                    resumen += info.decisiones.map(function(d) { return d.Nod_Nom; }).join(', ');
                    resumen += '</div>';
                    $campos.append('<div class="col-12">' + resumen + '</div>');
                }
                $panel.show();
                actualizarPreviewRamasCorta();
            }

            $('#Trq_Cod_Corto').off('change.adqDec').on('change.adqDec', function() {
                var trq = $(this).val();
                if (!trq) {
                    renderCamposDecisionCorta({ decisiones: [], campos: [] });
                    return;
                }
                $.getJSON('adq_solicitud.php', { ajax_get_decisiones_flujo: 1, trq_cod: trq }, function(res) {
                    renderCamposDecisionCorta(res && res.success ? res : { decisiones: [], campos: [] });
                }).fail(function() {
                    renderCamposDecisionCorta({ decisiones: [], campos: [] });
                });
            });

            if (typeof $.fn.select2 === 'function' && $('#Trq_Cod_Corto').length) {
                if ($('#Trq_Cod_Corto').hasClass('select2-hidden-accessible')) {
                    $('#Trq_Cod_Corto').select2('destroy');
                }
                $('#Trq_Cod_Corto').select2({
                    placeholder: '[Seleccione un Tipo]',
                    allowClear: true,
                    width: '100%',
                    language: {
                        noResults: function() { return 'Sin resultados'; },
                        searching: function() { return 'Buscando...'; }
                    }
                });
            }

            $('#frmSolicitudCorta').off('submit.adqCorta').on('submit.adqCorta', function(e) {
                e.preventDefault();
                const titulo = $('#Sol_Tit_Corto').val().trim().toUpperCase();
                $('#Sol_Tit_Corto').val(titulo);
                const tipo = $('#Trq_Cod_Corto').val();
                if (!titulo || !tipo) {
                    alert('Ingrese el nombre y seleccione el tipo de requerimiento.');
                    return;
                }
                var payload = { Sol_Tit: titulo, Trq_Cod: tipo, decision_vals: {} };
                var faltante = null;
                $('#camposDecisionesCorta [data-decision-campo]').each(function() {
                    var campo = $(this).data('decision-campo');
                    var val = $(this).val();
                    if (val === '' || val === null) {
                        faltante = campo;
                        return false;
                    }
                    payload.decision_vals[campo] = val;
                });
                if (faltante) {
                    alert('Complete todos los valores de decisión del flujo antes de registrar.');
                    return;
                }
                if (!decisionesCacheCorta.decisiones || !decisionesCacheCorta.decisiones.length) {
                    payload.decision_vals.Sol_Val_Est = $('#Sol_Val_Est_Corto').val();
                }
                if ((parseFloat(payload.decision_vals.Sol_Val_Est) || 0) <= 0) {
                    alert('Debe ingresar un valor estimado mayor que cero.');
                    return;
                }
                var rubrosSel = [];
                if (typeof window.adqCollectRubros === 'function') {
                    rubrosSel = window.adqCollectRubros() || [];
                }
                if (!rubrosSel.length) {
                    $('.chk-rubro-ppto:checked, #rubrosHidden input[name="rubros[]"]').each(function() {
                        var v = parseInt($(this).val(), 10);
                        if (v > 0 && rubrosSel.indexOf(v) === -1) {
                            rubrosSel.push(v);
                        }
                    });
                }
                payload.rubros = rubrosSel;
                const $btn = $('#btnCrearSolicitudCorta');
                const $loader = $('#adqLoaderRegistro');
                if ($loader.length && $loader.parent()[0] !== document.body) {
                    $loader.appendTo(document.body);
                }
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Registrando...');
                $('#adqLoaderRegistroTitulo').text('Registrando solicitud...');
                $('#adqLoaderRegistroDetalle').text('Por favor espere mientras se guarda el registro.');
                $loader.css('display', 'flex').attr('aria-busy', 'true');
                $.post('adq_solicitud.php?ajax_save_solicitud_corta=1', payload, function(res) {
                    if (res.success) {
                        $('#adqLoaderRegistroTitulo').text('Solicitud registrada');
                        $('#adqLoaderRegistroDetalle').text('Redirigiendo a Mis solicitudes...');
                        window.location.href = 'adq_bandeja.php?tab=mis_solicitudes';
                    } else {
                        $loader.hide().attr('aria-busy', 'false');
                        alert('No se pudo registrar: ' + (res.message || 'Error desconocido'));
                        $btn.prop('disabled', false).html('<i class="bi bi-arrow-right-circle"></i> Registrar para iniciar los procesos');
                    }
                }, 'json').fail(function() {
                    $loader.hide().attr('aria-busy', 'false');
                    alert('Error de comunicación al registrar la solicitud.');
                    $btn.prop('disabled', false).html('<i class="bi bi-arrow-right-circle"></i> Registrar para iniciar los procesos');
                });
            });
        })();
        </script>
        <?php
        exit;
    }
    ?>
    <style>
        /* Estilos de mejora visual para el formulario de creación */
        .adq-step-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 24px 28px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            position: relative;
        }
        .adq-step-badge {
            position: absolute;
            top: -12px;
            left: 20px;
            background: #1e40af;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 2px 5px rgba(30,64,175,0.3);
        }
        .adq-step-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin-top: 8px;
            margin-bottom: 20px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 10px;
        }
        .adq-step-title i {
            color: #3b82f6;
            margin-right: 6px;
        }
        .adq-step-title-note {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            margin-left: 8px;
        }
        .form-label-req {
            font-weight: 700;
            font-size: 12px;
            color: #0f172a;
            margin-bottom: 8px;
            padding: 0;
            display: block;
        }
        .form-label-req.small {
            font-size: 11px;
            color: #1e293b;
            font-weight: 600;
        }
        .adq-solicitud-form .form-check-label {
            color: #1e293b;
            font-weight: 600;
        }
        .adq-field-hint {
            font-size: 11px;
            color: #334155 !important;
            display: block;
            margin-top: 4px;
            line-height: 1.35;
        }
        .adq-modal-label {
            font-size: 11px;
            margin-bottom: 4px;
        }
        .adq-field-block {
            margin-bottom: 22px;
        }
        .adq-step-card > .row > [class*="col-"] {
            padding-left: 14px;
            padding-right: 14px;
        }
        .adq-resp-summary {
            margin-top: 22px;
            border: 1px solid #c7d7ea;
            border-radius: 12px;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
            overflow: hidden;
        }
        .adq-resp-summary-inner {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
        }
        .adq-resp-summary-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #1e40af;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .adq-resp-summary-text {
            flex: 1;
            min-width: 0;
        }
        .adq-resp-summary-text strong {
            display: block;
            color: #0f172a;
            font-size: 13px;
        }
        .adq-resp-summary-text span {
            color: #475569;
            font-size: 12px;
            line-height: 1.35;
        }
        .adq-resp-modal {
            border: none;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.22);
        }
        .adq-resp-modal-header {
            background: linear-gradient(135deg, #0f2b46 0%, #1e40af 100%);
            color: #fff;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }
        .adq-resp-modal-kicker {
            font-size: 10px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.8;
            margin: 0;
        }
        .adq-resp-modal-header .modal-title {
            font-size: 16px;
            color: #fff;
        }
        .adq-resp-modal-sub {
            margin-top: 3px;
            font-size: 11px;
            opacity: 0.9;
            max-width: 540px;
        }
        .adq-resp-close {
            background: rgba(255,255,255,0.12);
            border: none;
            color: #fff;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            font-size: 20px;
            line-height: 1;
            opacity: 0.95;
        }
        .adq-resp-modal-body {
            background: #f8fafc;
            padding: 10px 12px;
            max-height: 58vh;
            overflow-y: auto;
        }
        .adq-resp-modal-footer {
            background: #fff;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
        }
        .adq-nodo-usu-card {
            background: #fff;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 8px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .adq-nodo-usu-card.is-complete {
            border-color: #86efac;
            box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.15);
        }
        .adq-nodo-usu-card.is-missing {
            border-color: #f87171;
            box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.2);
            background: #fff7f7;
        }
        .adq-nodo-usu-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 0;
            padding-bottom: 6px;
            border-bottom: 1px solid #eef2f7;
        }
        .adq-nodo-usu-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.25;
            margin: 0;
        }
        .adq-nodo-usu-subtitle {
            display: block;
            margin-top: 1px;
            font-size: 10px;
            font-weight: 600;
            color: #64748b;
        }
        .adq-nodo-usu-badge {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            background: #e2e8f0;
            color: #334155;
            border-radius: 999px;
            padding: 2px 7px;
            flex-shrink: 0;
        }
        .adq-nodo-usu-options {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 8px;
            padding-top: 0;
        }
        .adq-nodo-usu-option {
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #dbe4ee;
            border-radius: 6px;
            padding: 6px 10px;
            background: #fff;
            cursor: pointer;
            margin: 0;
            transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        }
        .adq-nodo-usu-option:hover {
            border-color: #93c5fd;
            background: #f8fbff;
        }
        .adq-nodo-usu-option.is-selected {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.18);
        }
        .adq-nodo-usu-option input[type="checkbox"] {
            margin: 0;
            width: 14px;
            height: 14px;
            flex-shrink: 0;
            cursor: pointer;
        }
        .adq-nodo-usu-option span {
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.25;
        }
        .adq-resp-empty {
            text-align: center;
            padding: 28px 16px;
            color: #475569;
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
        }
        .adq-form-fields-stack {
            clear: both;
        }
        .adq-form-fields-stack::before,
        .adq-form-fields-stack::after {
            content: '';
            display: table;
            clear: both;
        }
        #divJustificacionComercial,
        #divDescripcionDetallada {
            min-width: 0;
        }
        .adq-row-textareas textarea.form-control-adq {
            width: 100%;
            min-height: 96px;
            resize: vertical;
        }
        #divProveedorSugerido {
            clear: both;
        }
        .adq-trq-select option {
            font-size: 13px;
        }
        .form-control-adq {
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            font-size: 13px;
            line-height: 1.4;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        select.form-control-adq {
            min-height: 42px;
            height: auto;
            padding: 10px 32px 10px 14px;
            line-height: 1.4;
        }
        .adq-trq-select {
            width: 100%;
            max-width: 100%;
        }
        .adq-row-general > .adq-field-block {
            min-width: 0;
        }
        .adq-row-general select.form-control-adq {
            width: 100%;
            max-width: 100%;
        }
        .form-control-adq:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
            outline: none;
        }
        .table-items-adq {
            font-size: 12px;
        }
        .table-items-adq th {
            background-color: #f8fafc !important;
            color: #475569 !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.03em;
            padding: 12px 14px !important;
        }
        .table-items-adq td {
            padding: 10px 14px !important;
            vertical-align: middle !important;
        }
        .lbl-total-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 20px;
            display: inline-block;
        }
        .adq-solicitud-form {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 8px 6px 32px;
            box-sizing: border-box;
        }
        .adq-cot-headline {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 20px;
            padding: 16px 20px;
            background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
            border: 1px solid #bfdbfe;
            border-radius: 10px;
        }
        .adq-cot-headline .headline-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 4px;
        }
        .adq-cot-headline .headline-copy {
            font-size: 12px;
            color: #64748b;
            margin: 0;
        }
        .adq-cot-headline .headline-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #1d4ed8;
            color: #ffffff;
            font-size: 20px;
            box-shadow: 0 8px 18px rgba(29, 78, 216, 0.2);
            flex: 0 0 auto;
        }
        .adq-cot-card {
            position: relative;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 0;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
            overflow: hidden;
            height: 100%;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .adq-cot-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 6px 18px rgba(30, 64, 175, 0.1);
        }
        .adq-cot-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: #ffffff;
        }
        .adq-cot-card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 700;
            margin: 0;
        }
        .adq-cot-card-title i {
            font-size: 17px;
        }
        .adq-cot-card-body {
            padding: 18px 20px;
        }
        .adq-cot-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
            padding: 0;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .adq-cot-field {
            margin-bottom: 18px;
        }
        .adq-cot-control {
            min-height: 42px;
            font-size: 14px;
            border-radius: 8px;
            padding: 10px 14px;
        }
        .adq-cot-provider-row {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .adq-cot-provider-row .select-wrap {
            flex: 1 1 auto;
            min-width: 0;
            max-width: 100%;
        }
        .adq-cot-add-provider {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            margin: 0;
            padding: 0;
            font-size: 14px;
            align-self: center;
        }
        .adq-file-upload {
            position: relative;
        }
        .adq-file-upload input[type="file"] {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }
        .adq-file-drop {
            min-height: 68px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border: 2px dashed #93c5fd;
            border-radius: 10px;
            background: #eff6ff;
            color: #1e40af;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .adq-file-drop:hover {
            background: #dbeafe;
            border-color: #2563eb;
        }
        .adq-file-icon {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border-radius: 10px;
            color: #1d4ed8;
            font-size: 18px;
            flex: 0 0 auto;
        }
        .adq-file-main {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #1e3a8a;
        }
        .adq-file-name {
            display: block;
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .adq-cot-pdf-zone {
            width: 100%;
        }
        .adq-cot-pdf-row {
            display: flex;
            align-items: stretch;
            gap: 4px;
            margin-bottom: 0;
            flex: 0 0 auto;
        }
        .adq-cot-pdf-row .adq-file-upload {
            flex: 1;
            min-width: 0;
        }
        .adq-pdf-row-remove {
            color: #dc2626;
            padding: 8px 4px;
            line-height: 1;
        }
        .adq-pdf-guardado-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 10px;
            margin-bottom: 6px;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            background: #f8fafc;
            font-size: 12px;
        }
        .adq-pdf-guardado-item a {
            color: #1d4ed8;
            text-decoration: none;
            word-break: break-all;
        }
        .adq-pdf-guardado-item a:hover {
            text-decoration: underline;
        }
        .adq-pdf-guardado-remove {
            color: #dc2626;
            border: none;
            background: transparent;
            padding: 0 4px;
            line-height: 1;
        }
        .adq-cot-winner {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 14px 16px 14px 40px;
            margin-bottom: 0;
            pointer-events: auto;
        }
        .adq-trq-readonly {
            background-color: #f1f5f9 !important;
            color: #475569 !important;
            cursor: not-allowed;
        }
        .adq-cot-winner label {
            font-size: 13px;
            line-height: 1.35;
        }
        .adq-cot-remove {
            color: #ffffff;
            background: #dc2626;
            border: 1px solid #b91c1c;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            min-width: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            padding: 0;
            line-height: 1;
            opacity: 1;
            text-decoration: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            transition: background-color 0.2s, border-color 0.2s, transform 0.15s;
        }
        .adq-cot-remove i {
            font-size: 20px;
            line-height: 1;
        }
        .adq-cot-remove:hover,
        .adq-cot-remove:focus {
            color: #ffffff;
            background: #b91c1c;
            border-color: #991b1b;
            opacity: 1;
            text-decoration: none;
            transform: scale(1.05);
        }
        .adq-btn-add-cot {
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 700;
            border-color: #2563eb;
            color: #1d4ed8;
            background: #eff6ff;
        }
        .adq-btn-add-cot:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }
        .adq-cot-row-split {
            margin-left: -8px;
            margin-right: -8px;
        }
        .adq-cot-row-split > [class*="col-"] {
            padding-left: 8px;
            padding-right: 8px;
        }
        #cotizacionesList {
            display: flex;
            flex-direction: column;
            width: 100%;
            margin: 0;
            box-sizing: border-box;
            gap: 10px;
        }
        #cotizacionesList > .adq-cot-col {
            float: none;
            display: block;
            flex: 0 0 100%;
            max-width: 100%;
            width: 100%;
            min-width: 0;
            padding: 0;
            margin-bottom: 0;
            box-sizing: border-box;
        }
        #cotizacionesList > .adq-cot-col > .adq-cot-card {
            width: 100%;
        }
        .adq-cot-card-inline {
            padding: 10px 12px;
        }
        .adq-cot-card-inline.adq-cot-card-ganadora {
            border-color: #10b981;
            background-color: #f0fdf4;
            box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.15);
        }
        .adq-cot-inline-row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 10px 12px;
        }
        .adq-cot-main-row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 10px 12px;
        }
        .adq-cot-main-row .adq-cot-field {
            margin-bottom: 0;
        }
        .adq-cot-top-prov {
            flex: 1 1 100%;
            min-width: 240px;
        }
        .adq-cot-provider-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .adq-cot-provider-row .select-wrap {
            flex: 1 1 auto;
            min-width: 0;
        }
        .adq-proformas-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }
        .adq-proformas-head .adq-cot-label {
            margin-bottom: 0;
        }
        .adq-proformas-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }
        .adq-proforma-row {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
        }
        .adq-proforma-row.adq-proforma-ganadora {
            border-color: #86efac;
            background: #f0fdf4;
        }
        .adq-proforma-fields {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 10px 12px;
        }
        .adq-proforma-pdf {
            flex: 1 1 200px;
            min-width: 180px;
            max-width: 280px;
        }
        .adq-proforma-pdf .adq-file-upload,
        .adq-proforma-pdf .adq-cot-pdfs-inline {
            width: 100%;
        }
        .adq-proforma-val {
            flex: 0 0 110px;
            min-width: 100px;
        }
        .adq-proforma-iva {
            flex: 0 0 90px;
            min-width: 80px;
        }
        .adq-proforma-iva .adq-cot-iva-check {
            margin: 0;
            min-height: 34px;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 0;
        }
        .adq-proforma-total {
            flex: 0 0 110px;
            min-width: 100px;
        }
        .adq-cot-total-box {
            min-height: 34px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 4px 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            color: #166534;
            font-size: 13px;
        }
        .adq-proforma-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }
        .adq-proforma-jus {
            width: 100%;
            margin-top: 8px;
        }
        .adq-proforma-remove {
            color: #dc2626;
            padding: 4px 6px;
        }
        .adq-btn-add-pdf-cot {
            min-height: 34px;
            height: 34px;
            min-width: auto;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            white-space: nowrap;
            flex: 0 0 auto;
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            border: 1px solid #1e40af;
            color: #ffffff;
            box-shadow: 0 1px 4px rgba(37, 99, 235, 0.3);
        }
        .adq-btn-add-pdf-cot:hover,
        .adq-btn-add-pdf-cot:focus {
            background: linear-gradient(180deg, #1d4ed8 0%, #1e3a8a 100%);
            border-color: #1e3a8a;
            color: #ffffff;
        }
        .adq-cot-top-val {
            flex: 0 0 130px;
            min-width: 120px;
        }
        .adq-cot-top-jus {
            flex: 2 1 260px;
            min-width: 200px;
        }
        .adq-cot-top-actions {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 6px;
            padding-bottom: 2px;
        }
        .adq-cot-top-actions .adq-cot-winner {
            margin: 0;
            padding: 6px 8px 6px 28px;
            min-height: 36px;
        }
        .adq-cot-pdf-section {
            width: 100%;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
        }
        .adq-cot-pdf-section > .adq-cot-label {
            margin-bottom: 8px;
        }
        .adq-cot-pdf-strip {
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;
            gap: 8px;
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 4px;
            -webkit-overflow-scrolling: touch;
        }
        .adq-cot-pdf-strip .adq-cot-pdfs-guardados {
            display: flex;
            flex-wrap: nowrap;
            flex: 0 0 auto;
            gap: 8px;
            margin: 0;
        }
        .adq-cot-pdf-strip .adq-cot-pdf-zone {
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;
            gap: 8px;
            width: auto;
            flex: 0 0 auto;
            min-width: 0;
        }
        .adq-cot-pdf-strip .adq-cot-pdf-rows {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            align-items: stretch;
            flex: 0 0 auto;
        }
        .adq-cot-pdf-strip .adq-cot-pdf-row {
            flex: 0 0 180px;
            width: 180px;
            min-width: 180px;
            max-width: 180px;
            margin-bottom: 0;
        }
        .adq-cot-pdf-strip .adq-cot-pdf-row .adq-file-upload {
            width: 100%;
        }
        .adq-cot-pdf-strip .adq-btn-add-pdf-cot {
            align-self: center;
            margin-top: 0;
            min-height: 34px;
            height: 34px;
            min-width: auto;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            white-space: nowrap;
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            border: 1px solid #1e40af;
            color: #ffffff;
            box-shadow: 0 1px 4px rgba(37, 99, 235, 0.3);
            flex: 0 0 auto;
        }
        .adq-cot-pdf-strip .adq-btn-add-pdf-cot:hover,
        .adq-cot-pdf-strip .adq-btn-add-pdf-cot:focus {
            background: linear-gradient(180deg, #1d4ed8 0%, #1e3a8a 100%);
            border-color: #1e3a8a;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.45);
        }
        .adq-cot-pdf-strip .adq-btn-add-pdf-cot .bi {
            font-size: 13px;
            line-height: 1;
        }
        .adq-cot-pdf-strip .adq-btn-add-pdf-cot .adq-btn-add-pdf-label {
            white-space: nowrap;
        }
        .adq-cot-inline-prov {
            flex: 2 1 220px;
            min-width: 200px;
        }
        .adq-cot-inline-val {
            flex: 0 0 120px;
            min-width: 110px;
        }
        .adq-cot-inline-pdf {
            flex: 0 1 180px;
            min-width: 140px;
        }
        .adq-cot-inline-jus {
            flex: 2 1 220px;
            min-width: 180px;
        }
        .adq-cot-inline-actions {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 6px;
            padding-bottom: 2px;
        }
        .adq-cot-inline-actions .adq-cot-winner {
            margin: 0;
            padding: 6px 8px 6px 28px;
            min-height: 36px;
        }
        .adq-cot-pdfs-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            flex: 0 0 auto;
        }
        .adq-cot-pdf-btn {
            white-space: nowrap;
            padding: 4px 10px;
            font-size: 12px;
            margin: 0;
        }
        .adq-cot-pdf-compact .adq-file-drop {
            min-height: 38px;
            padding: 6px 10px;
            gap: 8px;
        }
        .adq-cot-pdf-compact .adq-file-icon {
            width: 28px;
            height: 28px;
            font-size: 14px;
        }
        .adq-cot-pdf-compact .adq-file-main {
            font-size: 12px;
        }
        .adq-cot-pdf-compact .adq-file-name {
            font-size: 11px;
        }
        .adq-file-upload-compact .adq-file-drop {
            min-height: 38px;
            padding: 6px 10px;
        }
        @media (max-width: 767px) {
            #cotizacionesList > .adq-cot-col {
                flex: 0 0 100%;
                max-width: 100%;
                width: 100%;
            }
            .adq-cot-top-prov,
            .adq-cot-top-val,
            .adq-cot-top-jus,
            .adq-cot-inline-prov,
            .adq-cot-inline-val,
            .adq-cot-inline-pdf,
            .adq-cot-inline-jus {
                flex: 1 1 100%;
                min-width: 0;
            }
        }
        .adq-cot-card .select2-container--default .select2-selection--single {
            height: 44px !important;
            border-radius: 8px;
        }
        .adq-cot-card .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
            padding: 0 36px 0 12px;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            display: block;
            box-sizing: border-box;
        }
        .adq-cot-card .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
            top: 1px;
            right: 4px;
        }
        .adq-solicitud-form .select2-container {
            width: 100% !important;
            max-width: 100%;
            box-sizing: border-box;
        }
        .select2-container--default .select2-selection--single {
            height: 44px !important;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
            padding: 0 36px 0 14px;
            font-size: 14px;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            display: block;
            box-sizing: border-box;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
            top: 1px;
            right: 4px;
        }
        .select2-container--default .select2-results__option {
            padding: 10px 14px;
            line-height: 1.4;
            white-space: normal;
            word-break: break-word;
        }
        .select2-dropdown.adq-select2-dropdown {
            border-color: #cbd5e1;
            box-sizing: border-box;
        }
        #divProveedorSugerido > div {
            max-width: 100%;
        }
        #divProveedorSugerido .select2-container {
            width: 100% !important;
            max-width: 100%;
        }
    </style>

    <div class="adq-solicitud-form">
        <div id="bannerEdicionBorrador" class="alert alert-info py-2 px-3 mb-3" style="display: none; font-size: 12px;">
            <i class="bi bi-pencil-square"></i> <strong>Completando borrador</strong> <span id="lblBorradorNum"></span>.
            Puede guardar sin cotizaciones y enviar a aprobacion cuando este listo.
        </div>
        <div id="bannerCompletarNodo" class="alert alert-primary py-2 px-3 mb-3" style="display: none; font-size: 12px;">
            <i class="bi bi-clipboard-check"></i> <strong>Tarea del nodo actual:</strong>
            complete toda la información de la solicitud. Al guardar permanece en esta etapa; luego use <strong>Resolver</strong> para continuar el proceso del nodo.
        </div>
        <div id="bannerEdicionObservada" class="alert alert-warning py-2 px-3 mb-3" style="display: none; font-size: 12px;">
            <i class="bi bi-exclamation-triangle-fill"></i> <strong>Solicitud observada</strong> <span id="lblObservadaNum"></span>.
            <span id="lblObservadaDetalle"></span>
            Corrija lo solicitado, vuelva a marcar la cotizacion ganadora si aplica, guarde los cambios y pulse <strong>Reenviar correccion</strong>.
        </div>
        <div id="bannerEdicionCotizaciones" class="alert alert-primary py-2 px-3 mb-3" style="display: none; font-size: 12px;">
            <i class="bi bi-file-earmark-pdf-fill"></i> <strong>Carga de cotizaciones</strong> <span id="lblCotizacionesNum"></span>.
            <span id="lblCotizacionesEtapa"></span>
            Solo puede agregar o modificar las cotizaciones/proformas; el resto de la solicitud es de solo lectura.
        </div>
        <input type="hidden" id="Sol_Modo_Edicion" value="">
        <form id="frmSolicitud" method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" id="Sol_Cod" name="Sol_Cod" value="">
            <div id="cotEliminarContainer"></div>
            
            <!-- PASO 1: Información General -->
            <div class="adq-step-card">
                <span class="adq-step-badge">Paso 1</span>
                <h5 class="adq-step-title"><i class="bi bi-info-circle-fill"></i> Información General de la Solicitud</h5>
                
                <div class="row g-3 adq-row-general">
                    <div class="col-12 adq-field-block">
                        <label class="form-label-req" for="Sol_Tit">Nombre de la solicitud *</label>
                        <input type="text" class="form-control form-control-adq" id="Sol_Tit" name="Sol_Tit" maxlength="255" required>
                    </div>
                    <!-- Tipo de Requerimiento -->
                    <div class="col-12 col-md-6 adq-field-block">
                        <label class="form-label-req" for="Trq_Cod">Tipo de Requerimiento *</label>
                        <select class="form-control form-control-adq adq-trq-select" id="Trq_Cod" name="Trq_Cod" required style="width: 100%;">
                            <option value="">[Seleccione un Tipo]</option>
                            <?php foreach ($tipos_req as $tr) {
                                $wfm_nom = htmlspecialchars($tr['Wfm_Nom'], ENT_QUOTES, 'UTF-8');
                                $trq_des = htmlspecialchars($tr['Trq_Des'], ENT_QUOTES, 'UTF-8');
                                ?>
                                <option value="<?php echo $tr['Trq_Cod']; ?>"><?php echo $trq_des; ?> — <?php echo $wfm_nom; ?></option>
                            <?php } ?>
                        </select>
                        <span class="adq-field-hint">Determina el flujo de aprobaciones que seguirá la solicitud.</span>
                    </div>

                    <div class="col-12 adq-field-block">
                        <?php adq_render_tabla_rubros($rubros_ppto); ?>
                    </div>

                    <!-- Prioridad -->
                    <div class="col-12 col-md-6 adq-field-block">
                        <label class="form-label-req" for="Sol_Pri">Prioridad de la Compra *</label>
                        <select class="form-control form-control-adq" id="Sol_Pri" name="Sol_Pri" required>
                            <option value="BAJA">Baja</option>
                            <option value="MEDIA" selected>Media</option>
                            <option value="ALTA">Alta</option>
                            <option value="URGENTE">Urgente</option>
                        </select>
                    </div>

                    <!-- Centro de costo (oculto) -->
                    <div class="col-12 col-md-4 adq-field-block" id="divCentroCosto" style="display: none;">
                        <label class="form-label-req" for="Cdc_Cod">Centro de Costo Imputable</label>
                        <select class="form-control form-control-adq" id="Cdc_Cod" name="Cdc_Cod">
                            <option value="">[Ninguno / No aplica]</option>
                            <?php foreach ($centros_costo as $cc) { ?>
                                <option value="<?php echo $cc['Cdc_Cod']; ?>"><?php echo $cc['Cdc_Cod']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Requisitos de esta solicitud -->
                    <div class="col-12 adq-field-block" id="divRequisitosSolicitud" style="display: none;">
                        <div class="p-4 rounded border" style="background: #f8fafc; border-color: #e2e8f0 !important;">
                            <h6 class="fw-bold text-primary mb-2" style="font-size: 13px;"><i class="bi bi-sliders"></i> Requisitos de esta solicitud</h6>
                            <p class="adq-field-hint mb-3">Se precargan desde el tipo seleccionado. Puede ajustarlos para este caso sin crear otro tipo de requerimiento.</p>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Req_Fac" name="Sol_Req_Fac" value="1" onchange="syncReqConfigFromForm()">
                                        <label class="form-check-label small" for="Sol_Req_Fac">Factura de compra al cierre</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Per_Cie" name="Sol_Per_Cie" value="1" onchange="syncReqConfigFromForm()">
                                        <label class="form-check-label small" for="Sol_Per_Cie">Permitir cierre parcial de items</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Req_Cot" name="Sol_Req_Cot" value="1" onchange="toggleMinCotizaciones(); syncReqConfigFromForm(); aplicarReglasCotizaciones();">
                                        <label class="form-check-label small" for="Sol_Req_Cot">Cotizaciones de sustento obligatorias</label>
                                    </div>
                                    <div class="ms-4 mb-2" id="divSolMinCot" style="display: none;">
                                        <label class="form-label-req small mb-1">Minimo de cotizaciones</label>
                                        <input type="number" class="form-control form-control-sm form-control-adq" id="Sol_Min_Cot" name="Sol_Min_Cot" min="1" value="1" style="width: 100px; background-color: #ffffff; cursor: not-allowed;" readonly title="Este valor lo define el tipo de requerimiento y no puede modificarse">
                                        <small class="text-muted d-block mt-1" style="font-size: 10px;"><i class="bi bi-lock-fill"></i> Definido por el tipo de requerimiento</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Req_Pre" name="Sol_Req_Pre" value="1" onchange="syncReqConfigFromForm()">
                                        <label class="form-check-label small" for="Sol_Req_Pre">Verificar disponibilidad presupuestaria</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Req_Adj" name="Sol_Req_Adj" value="1" onchange="syncReqConfigFromForm()">
                                        <label class="form-check-label small" for="Sol_Req_Adj">Archivos adjuntos de soporte (opcionales)</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Req_Pro" name="Sol_Req_Pro" value="1" onchange="toggleProveedorSugerido(); syncReqConfigFromForm();">
                                        <label class="form-check-label small" for="Sol_Req_Pro">Proveedor sugerido obligatorio</label>
                                    </div>
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="Sol_Define_Sla" name="Sol_Define_Sla" value="1" onchange="toggleSlaDias(); syncReqConfigFromForm();">
                                        <label class="form-check-label small" for="Sol_Define_Sla">Definir tiempo estimado (SLA)</label>
                                    </div>
                                    <div class="ms-4" id="divSolTiempoEst" style="display: none;">
                                        <label class="form-label-req small mb-1">Dias estimados de resolucion</label>
                                        <input type="number" class="form-control form-control-sm form-control-adq" id="Sol_Tiempo_Est" name="Sol_Tiempo_Est" min="1" style="width: 120px;" onchange="syncReqConfigFromForm()">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 adq-form-fields-stack">
                        <div class="row">
                            <div class="col-md-6 adq-field-block" id="divProveedorSugerido" style="display: none;">
                                <label class="form-label-req" for="Prv_Sug">Proveedor Sugerido *</label>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <div style="flex: 1; min-width: 0;">
                                        <select class="form-control select2-ajax" id="Prv_Sug" name="Prv_Sug" style="width: 100%;">
                                            <option value=""></option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-success" onclick="abrirModalNuevoProveedor('sugerido')" title="Agregar Nuevo Proveedor" style="height: 32px; padding: 0 10px; display: flex; align-items: center; justify-content: center; background-color: #10b981; border-color: #10b981; color: white; border-radius: 6px;"><i class="bi bi-plus-lg" style="font-size: 14px; font-weight: bold;"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 adq-row-textareas">
                            <div class="col-12 col-md-6 adq-field-block" id="divJustificacionComercial">
                                <label class="form-label-req" for="Sol_Jus">Justificación Comercial *</label>
                                <textarea class="form-control form-control-adq" id="Sol_Jus" name="Sol_Jus" rows="3" placeholder="Explique brevemente por qué es necesaria esta adquisición..." required></textarea>
                            </div>
                            <div class="col-12 col-md-6 adq-field-block" id="divDescripcionDetallada">
                                <label class="form-label-req" for="Sol_Det">Descripción Detallada del Pedido *</label>
                                <textarea class="form-control form-control-adq" id="Sol_Det" name="Sol_Det" rows="3" placeholder="Indique especificaciones técnicas, marcas, modelos o detalles específicos..." required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 1B: Valores de decisión del flujo -->
            <div class="adq-step-card" id="panelDecisionesCompleta" style="display:none;">
                <span class="adq-step-badge">Paso 1B</span>
                <h5 class="adq-step-title"><i class="bi bi-signpost-split"></i> Valores para nodos de decisión</h5>
                <p class="adq-field-hint mb-3">Indique los valores que definen qué rama del flujo se ejecutará (ej. monto &gt; 10000). Si el valor estimado se calcula con los ítems, se sincronizará automáticamente.</p>
                <div id="camposDecisionesCompleta" class="row g-3"></div>
                <div id="previewRamasCompleta" class="mt-3"></div>
            </div>

            <!-- PASO 2: Cotizaciones de Sustento -->
            <div class="adq-step-card" id="divCotizaciones" data-adq-cot-edit="<?php echo intval($adq_cot_edit); ?>" data-adq-cot-sel="<?php echo intval($adq_cot_sel); ?>"<?php echo !empty($adq_ocultar_cotizaciones) ? ' style="display:none;"' : ''; ?>>
                <span class="adq-step-badge">Paso 2</span>
                <h5 class="adq-step-title"><i class="bi bi-file-earmark-pdf-fill"></i> Sustento de Cotizaciones Físicas</h5>
                <div class="adq-cot-headline">
                    <div>
                        <div class="headline-title">Carga y compara las cotizaciones del requerimiento</div>
                        <p class="headline-copy">Registre proveedor, monto y el PDF de sustento (obligatorio). Marque la cotizacion ganadora cuando corresponda.</p>
                    </div>
                    <span class="headline-icon"><i class="bi bi-files"></i></span>
                </div>
                
                <!-- Estado 1: Inicial (Sin Tipo de Requerimiento seleccionado) -->
                <div id="cotizacionesStateInitial" class="text-center p-4" style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                    <i class="bi bi-file-earmark-lock text-muted" style="font-size: 28px;"></i>
                    <p class="text-muted fw-semibold mt-2 mb-0" style="font-size: 13px;">Debe seleccionar un Tipo de Requerimiento en el Paso 1 para configurar las cotizaciones de sustento.</p>
                </div>

                <!-- Estado 2: Tipo de Requerimiento seleccionado -->
                <div id="cotizacionesStateActive" style="display: none;">
                    <div id="cotizacionesAlert" class="alert p-3 mb-3" style="font-size: 12px; border-radius: 6px; line-height: 1.5;"></div>
                    <div id="cotizacionesList"></div>
                    <div class="mt-3" id="divBtnAddCot">
                        <button type="button" class="btn adq-btn-add-cot" onclick="agregarCotizacionHTML()"><i class="bi bi-plus-circle"></i> Anadir otra proforma</button>
                    </div>
                </div>
            </div>

            <!-- PASO 2B: Otros archivos PDF de soporte (opcionales) -->
            <div class="adq-step-card" id="divAdjuntosSolicitud">
                <span class="adq-step-badge">Paso 2B</span>
                <h5 class="adq-step-title"><i class="bi bi-paperclip"></i> Otros archivos PDF de soporte <span class="adq-step-title-note">(Opcional)</span></h5>
                <p class="adq-field-hint mb-3">Opcional: además de las proformas, puede adjuntar otros PDF (especificaciones, órdenes, memorandos, etc.) e indicar una descripción para cada uno. No es obligatorio para completar o enviar la solicitud.</p>
                <div id="adjuntosList"></div>
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="agregarAdjuntoSolicitud()"><i class="bi bi-plus-circle"></i> Agregar PDF</button>
                </div>
                <div id="adjEliminarContainer"></div>
            </div>

            <!-- PASO 3: Artículos o Servicios -->
            <div class="adq-step-card">
                <span class="adq-step-badge">Paso 3</span>
                <h5 class="adq-step-title">
                    <i class="bi bi-cart-fill"></i> Artículos / Servicios Requeridos
                    <span class="adq-step-title-note">(Debe registrar un valor estimado mayor que cero)</span>
                </h5>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-condensed table-items-adq" id="tblItems">
                        <thead>
                            <tr class="text-center">
                                <th style="width: 40px; text-align: center;">#</th>
                                <th>Artículo / Descripción Técnica</th>
                                <th style="width: 100px; text-align: center;">Cantidad</th>
                                <th style="width: 140px; text-align: right;">P. Unit. Est. ($)</th>
                                <th style="width: 100px; text-align: center;">IVA</th>
                                <th style="width: 140px; text-align: right;">Total Est. ($)</th>
                                <th style="width: 50px; text-align: center;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Las líneas se inyectan por JS -->
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="agregarLinea()"><i class="bi bi-plus-circle"></i> Agregar Ítem / Fila</button>
                    
                    <div class="lbl-total-box text-end">
                        <span class="text-muted small fw-bold d-block">VALOR TOTAL ESTIMADO</span>
                        <span class="fs-4 fw-bold text-success">$ <span id="lblTotalEstimado">0.00</span></span>
                        <input type="hidden" id="Sol_Val_Est" name="Sol_Val_Est" value="0.00">
                    </div>
                </div>

                <div id="panelSeleccionUsuariosNodos" class="adq-resp-summary" style="display: none;">
                    <div class="adq-resp-summary-inner">
                        <div class="adq-resp-summary-icon"><i class="bi bi-people-fill"></i></div>
                        <div class="adq-resp-summary-text">
                            <strong id="lblRespSummaryTitle">Asignación de responsables</strong>
                            <span id="lblRespSummaryDesc" class="d-block">Defina quién atenderá cada etapa del flujo.</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary fw-bold" id="btnAbrirModalResponsables" onclick="abrirModalSeleccionResponsables()">
                            <i class="bi bi-person-check"></i> Asignar responsables
                        </button>
                    </div>
                    <div id="nodoUsuariosHidden"></div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="d-flex justify-content-end gap-2 mt-4 mb-3" id="adqFormActionsDefault">
                <button type="button" class="btn btn-default" onclick="limpiarFormulario()"><i class="bi bi-trash"></i> Limpiar Todo</button>
                <button type="button" class="btn btn-outline-secondary fw-bold p-3 py-2" style="font-size: 14px;" onclick="guardarBorrador()"><i class="bi bi-save"></i> Guardar Borrador</button>
                <button type="submit" class="btn btn-success fw-bold p-3 py-2" id="btnEnviarSolicitud" style="font-size: 14px;"><i class="bi bi-send-check"></i> Enviar Solicitud a Aprobación</button>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4 mb-3" id="adqFormActionsObservada" style="display: none;">
                <button type="button" class="btn btn-outline-secondary fw-bold p-3 py-2" style="font-size: 14px;" onclick="guardarBorrador()"><i class="bi bi-save"></i> Guardar Corrección</button>
                <button type="button" class="btn btn-success fw-bold p-3 py-2" style="font-size: 14px;" onclick="reenviarCorreccionObservada()"><i class="bi bi-send-check"></i> Reenviar Corrección</button>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4 mb-3" id="adqFormActionsCotizaciones" style="display: none;">
                <button type="button" class="btn btn-outline-secondary fw-bold p-3 py-2" style="font-size: 14px;" onclick="volverAMisPendientes()"><i class="bi bi-arrow-left"></i> Volver</button>
                <button type="button" class="btn btn-primary fw-bold p-3 py-2" style="font-size: 14px;" id="btnGuardarCotizacionesEtapa" onclick="guardarCotizacionesEtapa()"><i class="bi bi-save"></i> Guardar Cotizaciones</button>
            </div>
        </form>

        <!-- MODAL SELECCIÓN RESPONSABLES POR NODO -->
        <div class="modal fade" id="mdlSeleccionResponsables" tabindex="-1" role="dialog" aria-labelledby="mdlSeleccionResponsablesLabel" aria-hidden="true" style="z-index: 1070;">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content adq-resp-modal">
                    <div class="adq-resp-modal-header">
                        <div>
                            <p class="adq-resp-modal-kicker mb-1">Workflow de adquisiciones</p>
                            <h4 class="modal-title fw-bold mb-0" id="mdlSeleccionResponsablesLabel">
                                <i class="bi bi-diagram-3"></i> Selección de responsables
                            </h4>
                            <p class="adq-resp-modal-sub mb-0">Es obligatorio elegir un responsable en cada etapa. Solo se listan nodos con más de un usuario asignado.</p>
                        </div>
                        <button type="button" class="adq-resp-close" data-dismiss="modal" aria-label="Cerrar" onclick="$('#mdlSeleccionResponsables').modal('hide')">&times;</button>
                    </div>
                    <div class="modal-body adq-resp-modal-body">
                        <div id="seleccionUsuariosNodosList"></div>
                        <div id="seleccionUsuariosNodosEmpty" class="adq-resp-empty" style="display:none;"></div>
                    </div>
                    <div class="modal-footer adq-resp-modal-footer">
                        <div class="adq-resp-progress small text-muted" id="lblRespProgress">0 / 0 etapas asignadas</div>
                        <div>
                            <button type="button" class="btn btn-default" onclick="$('#mdlSeleccionResponsables').modal('hide')">Cancelar</button>
                            <button type="button" class="btn btn-success fw-bold" id="btnConfirmarResponsables" onclick="confirmarSeleccionResponsables()">
                                <i class="bi bi-check2-circle"></i> Confirmar asignación
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL NUEVO PROVEEDOR -->
        <div class="modal fade" id="mdlNuevoProveedor" tabindex="-1" role="dialog" aria-labelledby="mdlNuevoProveedorLabel" aria-hidden="true" style="z-index: 1060;">
            <div class="modal-dialog" role="document">
                <div class="modal-content" style="border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <div class="modal-header bg-success text-white" style="padding: 12px 16px;">
                        <h5 class="modal-title fw-bold" id="mdlNuevoProveedorLabel" style="font-size: 14px; margin: 0;"><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Proveedor</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="$('#mdlNuevoProveedor').modal('hide')" style="background: none; border: none; color: #fff; font-size: 20px; line-height: 1; opacity: 0.8; padding: 0;">&times;</button>
                    </div>
                    <div class="modal-body" style="background-color: #f8fafc; padding: 16px;">
                        <form id="frmNuevoProveedor" onsubmit="guardarNuevoProveedor(event)">
                            <input type="hidden" id="prov_target_idx" value="">
                            <input type="hidden" id="new_Prv_Cod" name="Prv_Cod" value="">
                            <div class="mb-3">
                                <label class="form-label-req adq-modal-label">RUC / Cédula *</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control form-control-sm form-control-adq" id="new_Prs_Ced" name="Prs_Ced" required placeholder="Ej. 0999999999001 o 0999999999" maxlength="13" autocomplete="off" style="width: 100%;">
                                    <span class="input-group-text p-1 px-2" id="new_Prs_Ced_Est" title="Estado de validacion" style="min-width: 34px; justify-content: center;"></span>
                                </div>
                                <div id="new_Prv_LookupMsg" class="small mt-1" style="display: none;"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-req adq-modal-label">Razón Social *</label>
                                <input type="text" class="form-control form-control-sm form-control-adq" id="new_Prs_Ape" name="Prs_Ape" required placeholder="Ej. CORPORACION EXA S.A." style="width: 100%;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label-req adq-modal-label">Nombre Comercial</label>
                                <input type="text" class="form-control form-control-sm form-control-adq" id="new_Prv_Com" name="Prv_Com" placeholder="Ej. EXA" style="width: 100%;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label-req adq-modal-label">Correo Electrónico</label>
                                <input type="email" class="form-control form-control-sm form-control-adq" id="new_Prv_Cor" name="Prv_Cor" placeholder="Ej. info@exa.com" style="width: 100%;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label-req adq-modal-label">Teléfono</label>
                                <input type="text" class="form-control form-control-sm form-control-adq" id="new_Prv_Tel" name="Prv_Tel" placeholder="Ej. 042XXXXXX o 09XXXXXXXX" style="width: 100%;">
                            </div>
                            <div class="text-end mt-3" style="margin-top: 15px;">
                                <button type="button" class="btn btn-sm btn-default" onclick="$('#mdlNuevoProveedor').modal('hide')" style="margin-right: 5px;">Cancelar</button>
                                <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="bi bi-save"></i> Guardar Proveedor</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../../framework/plugins/cedulaRuc.js" charset="UTF-8"></script>
    <script src="../VALIDACIONES/adq_solicitud.js?v=20260818a" charset="UTF-8"></script>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Solicitud de Adquisición</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body class="bg-light py-4">
    <div class="container bg-white p-4 rounded shadow-sm" style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <h4 class="fw-bold text-primary m-0"><i class="bi bi-file-earmark-plus"></i> Registro de Adquisición</h4>
            <a href="adq_bandeja.php" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left"></i> Volver a mi Bandeja</a>
        </div>

        <form id="frmSolicitud" method="POST" enctype="multipart/form-data">
            <!-- El contenido de este formulario se maneja dinámicamente o por carga híbrida -->
        </form>
    </div>

    <!-- Script del validador de adquisición -->
    <script src="../../framework/plugins/cedulaRuc.js" charset="UTF-8"></script>
    <script src="../VALIDACIONES/adq_solicitud.js?v=20260818a" charset="UTF-8"></script>
</body>
</html>
