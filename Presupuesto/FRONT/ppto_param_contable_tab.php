<?php
/**
 * ppto_param_contable_tab.php
 * Pestana Parametrizacion Contable � diseno Cuadro presupuestario.
 * Requiere: $emp_filtro, $ani_filtro. Opcional: $tab_qs, $mes_acumulado, $anios_filtro.
 */
if (!isset($emp_filtro)) {
    $emp_filtro = 0;
}
if (!isset($ani_filtro)) {
    $ani_filtro = (int)date('Y');
}
if (!isset($mes_acumulado)) {
    $mes_acumulado = (int)date('n');
}
if (!isset($tab_qs) || $tab_qs === '') {
    $tab_qs = 'emp_cod=' . (int)$emp_filtro . '&amp;ani=' . (int)$ani_filtro
        . '&amp;ver=1&amp;mes=' . (int)$mes_acumulado;
}
$pc_anios_opts = array();
if (!empty($anios_filtro) && is_array($anios_filtro)) {
    $pc_anios_opts = $anios_filtro;
} elseif (!empty($anios) && is_array($anios)) {
    $pc_anios_opts = $anios;
} else {
    $y = (int)date('Y');
    for ($i = $y - 2; $i <= $y + 1; $i++) {
        $pc_anios_opts[] = $i;
    }
}
$pc_anio_origen_def = $ani_filtro > 2000 ? ($ani_filtro - 1) : ((int)date('Y') - 1);
$pc_mes_hasta_def = max(1, min(12, (int)$mes_acumulado));
$pc_nombre_mes_fn = function_exists('ppto_nombre_mes');
?>
<style>
/* --- Cuadro-like shell (Parametrizacion) --- */
.ppto-pc-wrap .exa-ppto-tab-intro { font-size: 12px; color: #718096; margin: 0 0 14px; line-height: 1.45; }
.ppto-pc-wrap .exa-ppto-cuadro-kpis { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
.ppto-pc-wrap .exa-ppto-cuadro-kpi {
    flex: 1; min-width: 130px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
    padding: 12px 14px; box-shadow: 0 1px 2px rgba(15,23,42,.04);
}
.ppto-pc-wrap .exa-ppto-cuadro-kpi .lbl {
    font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #718096; font-weight: 600; margin-bottom: 4px;
}
.ppto-pc-wrap .exa-ppto-cuadro-kpi .val { font-size: 18px; font-weight: 700; color: #1a365d; line-height: 1.2; }
.ppto-pc-wrap .exa-ppto-cuadro-kpi .val.val-mes { color: #276749; font-size: 16px; }
.ppto-pc-wrap .exa-ppto-cuadro-kpi .val-sub { font-size: 11px; color: #718096; font-weight: 500; margin-top: 2px; }
.ppto-pc-wrap .exa-ppto-escenario-box {
    margin: 0 0 16px; padding: 14px 16px;
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    border: 1px solid #cbd5e0; border-radius: 8px;
}
.ppto-pc-wrap .exa-ppto-escenario-box .esc-head {
    display: flex; align-items: baseline; justify-content: space-between; gap: 10px; flex-wrap: wrap;
}
.ppto-pc-wrap .exa-ppto-escenario-box h5 { margin: 0 0 6px; font-weight: 700; color: #2d3748; font-size: 14px; }
.ppto-pc-wrap .exa-ppto-escenario-box .esc-sub { font-size: 11px; color: #718096; }
.ppto-pc-wrap .exa-ppto-escenario-box .help { font-size: 11px; color: #4a5568; margin: 0 0 12px; line-height: 1.45; }
.ppto-pc-wrap .esc-selector { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
.ppto-pc-wrap .esc-btn {
    flex: 1; min-width: 180px; text-align: left; background: #fff;
    border: 2px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; cursor: pointer; transition: all .12s ease;
}
.ppto-pc-wrap .esc-btn:hover { border-color: #90cdf4; }
.ppto-pc-wrap .esc-btn.active { border-color: #3182ce; background: #ebf8ff; box-shadow: 0 2px 8px rgba(49,130,206,.15); }
.ppto-pc-wrap .esc-btn .esc-btn-t { display: block; font-size: 11px; font-weight: 700; color: #2d3748; margin-bottom: 4px; }
.ppto-pc-wrap .esc-btn .esc-btn-t i { margin-right: 4px; color: #3182ce; }
.ppto-pc-wrap .esc-btn .esc-btn-v { display: block; font-size: 16px; font-weight: 800; color: #1a365d; }
.ppto-pc-wrap .esc-btn.active .esc-btn-v { color: #2b6cb0; }
.ppto-pc-wrap .esc-btn .esc-btn-s { display: block; font-size: 9px; color: #718096; text-transform: uppercase; margin-top: 2px; }
.ppto-pc-wrap .esc-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px; }
.ppto-pc-wrap .esc-resumen { margin-top: 14px; padding-top: 14px; border-top: 1px solid #e2e8f0; }
.ppto-pc-wrap .esc-resumen h6 { margin: 0 0 8px; font-size: 12px; font-weight: 700; color: #2d3748; }
.ppto-pc-wrap .esc-resumen-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.ppto-pc-wrap .esc-resumen-table th,
.ppto-pc-wrap .esc-resumen-table td { padding: 8px 10px; border: 1px solid #e2e8f0; text-align: right; }
.ppto-pc-wrap .esc-resumen-table th:first-child,
.ppto-pc-wrap .esc-resumen-table td:first-child { text-align: left; font-weight: 600; color: #4a5568; }
.ppto-pc-wrap .esc-resumen-table th { background: #f7fafc; font-size: 10px; text-transform: uppercase; color: #718096; }
.ppto-pc-wrap .esc-resumen-table .eco-pos { color: #276749; font-weight: 700; }
.ppto-pc-wrap .exa-ppto-rubros-list-title { font-weight: 700; color: #1a365d; margin: 4px 0 10px; font-size: 14px; }
.ppto-pc-wrap .exa-ppto-cuadro-empty {
    padding: 28px 16px; text-align: center; color: #a0aec0;
    background: #f8fafc; border: 1px dashed #e2e8f0; border-radius: 8px; font-size: 12px;
}

/* Workspace */
.ppto-pc-layout { display:grid; grid-template-columns: minmax(280px, 34%) 1fr; gap:12px; margin-top:4px; }
@media (max-width: 991px) { .ppto-pc-layout { grid-template-columns: 1fr; } }
.ppto-pc-panel {
    border: 1px solid #e2e8f0; border-radius: 8px; background: #fff;
    min-height: 280px; box-shadow: 0 1px 2px rgba(15,23,42,.04); overflow: hidden;
}
.ppto-pc-panel-head {
    padding: 10px 12px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;
    display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;
}
.ppto-pc-panel-head h5 { margin: 0; font-size: 13px; font-weight: 700; color: #1a365d; }
.ppto-pc-panel-body { padding: 10px 12px; max-height: 520px; overflow: auto; }
.ppto-pc-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 10px; }
.ppto-pc-subtabs { display: inline-flex; gap: 4px; flex-wrap: wrap; }
.ppto-pc-subtabs .btn {
    background: #fff; border-color: #cbd5e0; color: #4a5568; font-weight: 600; font-size: 11px;
}
.ppto-pc-subtabs .btn:hover { background: #edf2f7; color: #2d3748; }
.ppto-pc-subtabs .btn.active,
.ppto-pc-subtabs .btn.btn-primary,
.ppto-pc-subtabs .btn.btn-primary.active {
    background: #2b6cb0 !important; border-color: #2b6cb0 !important; color: #fff !important;
}
.ppto-pc-tree-row {
    display: flex; align-items: flex-start; gap: 6px; padding: 5px 6px; border-radius: 4px;
    cursor: pointer; font-size: 12px; line-height: 1.35; border: 1px solid transparent;
}
.ppto-pc-tree-row:hover { background: #edf2f7; }
.ppto-pc-tree-row.active { background: #ebf8ff; border-color: #90cdf4; }
.ppto-pc-tree-row.is-grupo { cursor: pointer; color: #4a5568; font-weight: 600; }
.ppto-pc-tree-row.is-hidden { display: none !important; }
.ppto-pc-tree-toggle {
    flex: 0 0 18px; width: 18px; height: 18px; margin: 0; padding: 0; border: none;
    background: transparent; color: #4a5568; font-size: 11px; line-height: 18px;
    text-align: center; cursor: pointer; border-radius: 3px;
}
.ppto-pc-tree-toggle:hover { background: #e2e8f0; color: #1a365d; }
.ppto-pc-tree-toggle.is-leaf {
    visibility: hidden; pointer-events: none;
}
.ppto-pc-badge {
    display: inline-block; min-width: 18px; padding: 1px 6px; border-radius: 10px;
    font-size: 10px; font-weight: 700; text-align: center; color: #fff;
}
.ppto-pc-badge.ok { background: #38a169; }
.ppto-pc-badge.pend { background: #dd6b20; }
.ppto-pc-badge.grupo { display: none; }
.ppto-pc-empty { padding: 28px 12px; text-align: center; color: #718096; font-size: 12px; }
.ppto-pc-rubro-title { font-size: 14px; font-weight: 700; color: #1a365d; margin: 0 0 4px; }
.ppto-pc-rubro-meta { font-size: 11px; color: #718096; margin-bottom: 10px; }
.ppto-pc-check-lazy { font-size: 12px; color: #4a5568; margin: 0; display: inline-flex; align-items: center; gap: 6px; }
.ppto-pc-msg { margin: 8px 0; font-size: 12px; display: none; }
.ppto-pc-msg.show { display: block; }
.ppto-pc-audit-box ul { margin: 6px 0 0 18px; padding: 0; font-size: 12px; }
#modal_pc_agregar.exa-pre-modal-overlay,
#modal_pc_copiar.exa-pre-modal-overlay,
#modal_pc_sugerir.exa-pre-modal-overlay,
#modal_pc_asignar_pend.exa-pre-modal-overlay { z-index: 10050; }
#modal_pc_asignar_pend.exa-pre-modal-overlay {
    align-items: flex-start;
    justify-content: center;
    overflow-y: auto;
    padding: 12px 8px;
    box-sizing: border-box;
}
#modal_pc_asignar_pend .exa-pre-modal-box.ppto-pc-modal-asignar {
    width: 94%;
    max-width: 560px;
    max-height: calc(100vh - 24px);
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-sizing: border-box;
    padding-bottom: 12px;
}
#modal_pc_asignar_pend .ppto-pc-modal-asignar-body {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
#modal_pc_asignar_pend .ppto-pc-modal-asignar-foot {
    flex: 0 0 auto;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    background: #fff;
}
#pc_asignar_pend_tree {
    flex: 1 1 auto;
    min-height: 140px;
    max-height: none;
    overflow: auto;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    margin-top: 4px;
}
#pc_asignar_pend_tree .pc-apr-row {
    display: flex; align-items: flex-start; gap: 6px; padding: 6px 8px;
    border-bottom: 1px solid #edf2f7; font-size: 12px; margin: 0; cursor: pointer;
}
#pc_asignar_pend_tree .pc-apr-row:last-child { border-bottom: none; }
#pc_asignar_pend_tree .pc-apr-row.is-grupo { background: #f7fafc; font-weight: 600; color: #2d3748; }
#pc_asignar_pend_tree .pc-apr-row.is-detalle:hover { background: #ebf8ff; }
#pc_asignar_pend_tree .pc-apr-row.is-selected { background: #bee3f8; }
#pc_asignar_pend_tree .pc-apr-row.is-hidden { display: none !important; }
#pc_asignar_pend_tree .pc-apr-toggle {
    flex: 0 0 18px; width: 18px; border: none; background: transparent;
    color: #4a5568; font-size: 11px; padding: 0; cursor: pointer; text-align: center; line-height: 18px;
}
#pc_asignar_pend_tree .pc-apr-meta { color: #718096; font-size: 11px; font-weight: 400; }
#pc_asignar_pend_tree input[type=radio] { margin: 2px 0 0; }
#modal_pc_agregar.exa-pre-modal-overlay {
    align-items: flex-start;
    justify-content: center;
    overflow-y: auto;
    padding: 12px 8px;
    box-sizing: border-box;
}
#modal_pc_agregar .exa-pre-modal-box.ppto-pc-modal-agregar {
    width: 94%;
    max-width: 760px;
    max-height: calc(100vh - 24px);
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-sizing: border-box;
}
#modal_pc_agregar .ppto-pc-modal-agregar-body {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
#modal_pc_agregar .ppto-pc-modal-agregar-foot {
    flex: 0 0 auto;
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px solid #e2e8f0;
}
.ppto-pc-search-results {
    flex: 1 1 auto;
    min-height: 140px;
    max-height: none;
    overflow: auto;
    margin-top: 6px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
}
.ppto-pc-search-results label,
.ppto-pc-search-results .pc-cta-row-grupo {
    display: flex; gap: 8px; align-items: flex-start; padding: 7px 10px;
    border-bottom: 1px solid #edf2f7; font-size: 12px; margin: 0;
}
.ppto-pc-search-results label { cursor: pointer; }
.ppto-pc-search-results label:last-child,
.ppto-pc-search-results .pc-cta-row-grupo:last-child { border-bottom: none; }
.ppto-pc-search-results label.disabled { opacity: .65; cursor: not-allowed; background: #f7fafc; }
.ppto-pc-search-results label.is-propia { background: #ebf8ff; }
.ppto-pc-search-results label.is-ocupada { background: #fffaf0; }
.ppto-pc-search-results .pc-cta-row-grupo {
    background: #f7fafc; color: #2d3748; font-weight: 600; cursor: pointer;
    align-items: center;
}
.ppto-pc-search-results .pc-cta-row-grupo.is-collapsed + .pc-cta-child-hidden,
.ppto-pc-search-results .pc-cta-child-hidden { display: none !important; }
.ppto-pc-search-results .pc-cta-toggle {
    flex: 0 0 18px; width: 18px; border: none; background: transparent;
    color: #4a5568; font-size: 11px; padding: 0; cursor: pointer; text-align: center;
}
.ppto-pc-search-results .pc-cta-meta { color: #718096; font-size: 11px; margin-top: 2px; }
.ppto-pc-search-results .pc-cta-badge {
    display: inline-block; margin-left: 4px; padding: 1px 6px; border-radius: 3px;
    font-size: 10px; font-weight: 700; vertical-align: middle;
}
.ppto-pc-search-results .pc-cta-badge.libre { background: #c6f6d5; color: #276749; }
.ppto-pc-search-results .pc-cta-badge.propia { background: #bee3f8; color: #2a4365; }
.ppto-pc-search-results .pc-cta-badge.ocupada { background: #feebc8; color: #9c4221; }
.ppto-pc-search-results .pc-cta-badge.grupo { background: #edf2f7; color: #4a5568; }
.ppto-pc-busca-toolbar {
    display: grid; grid-template-columns: 1.1fr 1.4fr auto; gap: 8px; align-items: end; margin-bottom: 8px;
    flex: 0 0 auto;
}
.ppto-pc-busca-toolbar label { display: block; font-size: 11px; font-weight: 600; color: #4a5568; margin: 0 0 4px; }
.ppto-pc-busca-hint {
    font-size: 11px; color: #718096; margin: 0 0 8px; line-height: 1.4;
    background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px;
    flex: 0 0 auto;
}
.ppto-pc-busca-summary {
    font-size: 11px; color: #4a5568; margin: 0 0 6px; display: flex; gap: 10px; flex-wrap: wrap;
    flex: 0 0 auto;
}
@media (max-width: 700px) {
    .ppto-pc-busca-toolbar { grid-template-columns: 1fr; }
}
.ppto-pc-copiar-flow {
    display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap;
    padding: 16px 8px; background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin: 12px 0;
}
.ppto-pc-copiar-flow .pc-box {
    min-width: 140px; text-align: center; padding: 10px 12px; background: #fff;
    border: 1px solid #cbd5e0; border-radius: 8px; font-size: 12px; font-weight: 600;
}
#pc_tabla_pendientes tr.pc-row-grupo { background: #f7fafc; color: #4a5568; font-weight: 600; cursor: pointer; }
#pc_tabla_pendientes tr.pc-row-grupo td { font-weight: 600; }
#pc_tabla_pendientes tr.pc-row-asignada { background: #fffaf0; color: #718096; cursor: help; }
#pc_tabla_pendientes tr.pc-row-asignada .pc-pend-badge {
    display: inline-block; margin-left: 6px; padding: 1px 6px; border-radius: 3px;
    font-size: 10px; font-weight: 700; background: #feebc8; color: #9c4221; vertical-align: middle;
    cursor: help;
}
#pc_tabla_pendientes .pc-pend-toggle {
    display: inline-block; width: 18px; border: none; background: transparent;
    color: #4a5568; font-size: 11px; padding: 0; cursor: pointer; text-align: center; margin-right: 2px;
}
#pc_tabla_pendientes .pc-pend-toggle:hover { color: #1a365d; }
#pc_tabla_pendientes tr.is-hidden { display: none !important; }
.ppto-pc-tipo-badge {
    display: inline-block; min-width: 22px; padding: 1px 6px; border-radius: 3px;
    font-size: 10px; font-weight: 700; text-align: center;
}
.ppto-pc-tipo-badge.g { background: #edf2f7; color: #4a5568; }
.ppto-pc-tipo-badge.d { background: #ebf8ff; color: #2b6cb0; }
.ppto-pc-wrap .exa-adq-table > thead > tr > th {
    background: #f7fafc; font-size: 10px; text-transform: uppercase; letter-spacing: .03em; color: #718096;
}
</style>

<div class="ppto-pc-wrap" id="ppto_pc_root"
     data-emp="<?php echo (int)$emp_filtro; ?>"
     data-anio="<?php echo (int)$ani_filtro; ?>"
     data-mes="<?php echo (int)$pc_mes_hasta_def; ?>">

    <p class="exa-ppto-tab-intro">
        Mapee rubros presupuestarios <strong>Detalle</strong> con cuentas contables <strong>Detalle</strong>
        (una cuenta solo en un rubro). Luego sincronice el ejecutado desde mayores al ledger
        (<code>mayor_contable</code>) para alimentar
        <a href="?tab=1&amp;<?php echo $tab_qs; ?>">M&eacute;tricas</a> y el Dashboard.
        El periodo de sync usa el <strong>A&ntilde;o fiscal</strong> y el <strong>Periodo acumulado</strong> de los filtros de arriba
        (mes 1 hasta <?php echo $pc_nombre_mes_fn ? htmlspecialchars(ppto_nombre_mes($pc_mes_hasta_def)) : (int)$pc_mes_hasta_def; ?>).
    </p>

    <div id="pc_alert" class="alert ppto-pc-msg" role="alert"></div>

    <div class="exa-ppto-cuadro-kpis" id="pc_kpis">
        <div class="exa-ppto-cuadro-kpi">
            <div class="lbl">% Parametrizaci&oacute;n</div>
            <div class="val" id="pc_kpi_pct">-</div>
            <div class="val-sub" id="pc_kpi_plan">Plan: -</div>
        </div>
        <div class="exa-ppto-cuadro-kpi">
            <div class="lbl">Rubros parametrizados</div>
            <div class="val" id="pc_kpi_ok">-</div>
            <div class="val-sub" id="pc_kpi_detalle">de 0 detalle</div>
        </div>
        <div class="exa-ppto-cuadro-kpi">
            <div class="lbl">Rubros pendientes</div>
            <div class="val" id="pc_kpi_pend">-</div>
            <div class="val-sub">Sin cuentas</div>
        </div>
        <div class="exa-ppto-cuadro-kpi">
            <div class="lbl">Cuentas asignadas</div>
            <div class="val" id="pc_kpi_cta">-</div>
            <div class="val-sub" id="pc_kpi_sin">0 sin rubro</div>
        </div>
        <div class="exa-ppto-cuadro-kpi">
            <div class="lbl">Cuentas vinculadas</div>
            <div class="val" id="pc_ejec_kpi_mapeos">-</div>
            <div class="val-sub">Cuenta a rubro</div>
        </div>
        <div class="exa-ppto-cuadro-kpi">
            <div class="lbl">Ejecutado mayor</div>
            <div class="val val-mes" id="pc_ejec_kpi_monto">-</div>
            <div class="val-sub" id="pc_ejec_kpi_rango">Hasta periodo filtro</div>
        </div>
        <div class="exa-ppto-cuadro-kpi">
            <div class="lbl">Rubros con monto</div>
            <div class="val" id="pc_ejec_kpi_lineas">-</div>
            <div class="val-sub">En el periodo</div>
        </div>
    </div>

    <div class="exa-ppto-escenario-box" id="pc_bloque_ejecutado">
        <div class="esc-head">
            <h5><i class="bi bi-arrow-repeat"></i> Ejecutado desde mayores</h5>
            <span class="esc-sub"><a href="?tab=1&amp;<?php echo $tab_qs; ?>">Ir a M&eacute;tricas</a></span>
        </div>
        <p class="help">
            Suma asientos de cuentas mapeadas y escribe el ledger <code>mayor_contable</code>
            del a&ntilde;o / periodo acumulado de los filtros superiores.
            Si el rubro pertenece a un solo proyecto (ej. Relaves), el ejecutado se etiqueta con ese proyecto
            para que el Dashboard lo muestre al filtrarlo. Debe <strong>volver a Sincronizar</strong> tras mapear cuentas.
            Solo reemplaza filas <code>mayor_contable</code>; no toca compras u otros or&iacute;genes.
        </p>
        <div class="esc-selector">
            <button type="button" class="esc-btn active" id="pc_esc_param" data-esc="param">
                <span class="esc-btn-t"><i class="bi bi-share"></i> Parametrizaci&oacute;n</span>
                <span class="esc-btn-v" id="pc_esc_param_val">-</span>
                <span class="esc-btn-s">Cobertura rubros detalle</span>
            </button>
            <button type="button" class="esc-btn" id="pc_esc_preview" data-esc="preview">
                <span class="esc-btn-t"><i class="bi bi-eye"></i> Vista previa</span>
                <span class="esc-btn-v" id="pc_esc_preview_val">-</span>
                <span class="esc-btn-s">Ejecutado calculado</span>
            </button>
            <button type="button" class="esc-btn" id="pc_esc_sync" data-esc="sync">
                <span class="esc-btn-t"><i class="bi bi-hdd-network"></i> Sincronizar ledger</span>
                <span class="esc-btn-v" id="pc_esc_sync_val">Listo</span>
                <span class="esc-btn-s">Escribe mayor_contable</span>
            </button>
        </div>
        <div class="esc-actions">
            <button type="button" class="btn btn-default btn-sm" id="pc_ejec_preview">
                <i class="bi bi-eye"></i> Calcular vista previa
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="pc_ejec_sync">
                <i class="bi bi-arrow-repeat"></i> Sincronizar
            </button>
            <button type="button" class="btn btn-default btn-sm" id="pc_btn_copiar">
                <i class="bi bi-files"></i> Copiar parametrizaci&oacute;n
            </button>
            <button type="button" class="btn btn-default btn-sm" id="pc_btn_auditar">
                <i class="bi bi-shield-check"></i> Auditar
            </button>
        </div>
        <div class="esc-resumen" id="pc_ejec_resultado">
            <div class="exa-ppto-cuadro-empty">
                Pulse <strong>Calcular vista previa</strong> para ver el ejecutado por mes sin grabar.
            </div>
        </div>
    </div>

    <h5 class="exa-ppto-rubros-list-title">
        <i class="bi bi-diagram-3"></i> Centro de asignaci&oacute;n
    </h5>

    <div class="ppto-pc-toolbar">
        <div class="ppto-pc-subtabs" role="tablist">
            <button type="button" class="btn btn-default btn-sm active" data-pc-pane="asignaciones">Asignaciones</button>
            <button type="button" class="btn btn-default btn-sm" data-pc-pane="pendientes">Cuentas pendientes</button>
            <button type="button" class="btn btn-default btn-sm" data-pc-pane="mapa" id="pc_btn_mapa">Mapa Contable</button>
            <button type="button" class="btn btn-default btn-sm" data-pc-pane="auditoria">Auditor&iacute;a</button>
        </div>
        <select id="pc_filtro_arbol" class="form-control input-sm" style="width:auto;min-width:150px;">
            <option value="todos">Todos los rubros</option>
            <option value="pendientes">Solo pendientes</option>
            <option value="parametrizados">Solo parametrizados</option>
        </select>
        <label class="ppto-pc-check-lazy">
            <input type="checkbox" id="pc_lazy_mov" />
            Cargar mov. acumulado / ultimo mov.
        </label>
    </div>

    <div class="ppto-pc-pane" id="pc_pane_asignaciones">
        <div class="ppto-pc-layout">
            <div class="ppto-pc-panel">
                <div class="ppto-pc-panel-head">
                    <h5><i class="bi bi-diagram-3"></i> Rubros presupuestarios</h5>
                    <button type="button" class="btn btn-default btn-xs" id="pc_btn_reload_arbol" title="Recargar"><i class="bi bi-arrow-clockwise"></i></button>
                </div>
                <div class="ppto-pc-panel-body" id="pc_arbol">
                    <div class="ppto-pc-empty">Cargando arbol...</div>
                </div>
            </div>
            <div class="ppto-pc-panel">
                <div class="ppto-pc-panel-head">
                    <h5><i class="bi bi-journal-text"></i> Detalle del rubro</h5>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <button type="button" class="btn btn-default btn-xs" id="pc_btn_sugerir" disabled title="Propone coincidencias; no asigna automaticamente">
                            <i class="bi bi-lightbulb"></i> Sugerir cuentas
                        </button>
                        <button type="button" class="btn btn-primary btn-xs" id="pc_btn_agregar" disabled>
                            <i class="bi bi-plus-lg"></i> Agregar cuentas
                        </button>
                    </div>
                </div>
                <div class="ppto-pc-panel-body" id="pc_detalle">
                    <div class="ppto-pc-empty">Seleccione un rubro <strong>Detalle</strong> en el arbol.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="ppto-pc-pane" id="pc_pane_pendientes" style="display:none;">
        <p style="font-size:12px;color:#4a5568;margin:0 0 8px;line-height:1.4;">
            Pulse <strong>Asignar...</strong> en una cuenta libre y <strong>elija el rubro destino</strong> en el dialogo.
            No es necesario ir antes a la pesta�a Asignaciones.
        </p>
        <div id="pc_pend_alert" class="alert ppto-pc-msg" role="alert" style="margin:0 0 8px;"></div>
        <div class="ppto-pc-toolbar">
            <input type="text" id="pc_pend_q" class="form-control input-sm" placeholder="Buscar codigo o nombre..." style="max-width:260px;" />
            <select id="pc_pend_grupo" class="form-control input-sm" style="width:auto;min-width:220px;">
                <option value="todas">Grupo (balances): todos</option>
            </select>
            <button type="button" class="btn btn-default btn-sm" id="pc_btn_pend_buscar"><i class="bi bi-search"></i> Buscar</button>
        </div>
        <div class="exa-adq-table-wrap">
            <table class="table table-bordered table-hover exa-adq-table table-condensed" id="pc_tabla_pendientes">
                <thead>
                    <tr>
                        <th style="width:28px;"></th>
                        <th>Codigo</th>
                        <th>Cuenta</th>
                        <th>Naturaleza</th>
                        <th>Grupo</th>
                        <th style="width:110px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="6" class="text-muted text-center">Use Buscar para listar el plan.</td></tr>
                </tbody>
            </table>
        </div>
        <p style="font-size:11px;color:#718096;margin-top:6px;" id="pc_pend_footer">
            Grupos de <strong>Contabilidad &rsaquo; Configurar Balances</strong>. Solo cuentas <strong>Detalle</strong> se asignan.
        </p>
    </div>

    <div class="ppto-pc-pane" id="pc_pane_mapa" style="display:none;">
        <div class="ppto-pc-toolbar">
            <select id="pc_mapa_filtro" class="form-control input-sm" style="width:auto;">
                <option value="todos">Todos los rubros detalle</option>
                <option value="parametrizados">Solo parametrizados</option>
                <option value="pendientes">Solo pendientes</option>
            </select>
            <label class="ppto-pc-check-lazy">
                <input type="checkbox" id="pc_mapa_mov" checked />
                Incluir mov. acumulado / ultimo mov.
            </label>
            <button type="button" class="btn btn-default btn-sm" id="pc_btn_mapa_reload"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
            <button type="button" class="btn btn-success btn-sm" id="pc_btn_mapa_excel"><i class="bi bi-file-earmark-excel"></i> Exportar Excel</button>
        </div>
        <div id="pc_mapa_totales" style="font-size:12px;color:#4a5568;margin-bottom:8px;"></div>
        <div class="exa-adq-table-wrap">
            <table class="table table-bordered table-hover exa-adq-table table-condensed" id="pc_tabla_mapa">
                <thead>
                    <tr>
                        <th>Rubro</th>
                        <th>Estado</th>
                        <th>Cuenta(s)</th>
                        <th class="text-right">Mov. acum.</th>
                        <th>Ultimo mov.</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5" class="text-muted text-center">Pulse Actualizar para generar el mapa.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="ppto-pc-pane" id="pc_pane_auditoria" style="display:none;">
        <div class="ppto-pc-audit-box" id="pc_audit_resultado">
            <div class="exa-ppto-cuadro-empty">Pulse <strong>Auditar</strong> para revisar inconsistencias.</div>
        </div>
    </div>
</div>

<div id="modal_pc_agregar" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box ppto-pc-modal-agregar">
        <span class="exa-pre-modal-close" id="pc_modal_agregar_cerrar">&times;</span>
        <h3 class="exa-adq-section-title" style="flex:0 0 auto; margin-right:28px;">Agregar cuentas al rubro</h3>
        <div class="ppto-pc-modal-agregar-body">
            <p id="pc_modal_rubro_lbl" style="font-size:12px;color:#4a5568;margin:0 0 8px;flex:0 0 auto;"></p>
            <p class="ppto-pc-busca-hint">
                Navegue el <strong>plan de cuentas</strong> (grupos + detalle). Solo las cuentas
                <strong>Detalle</strong> se pueden asignar; los <strong>Grupos</strong> son guia.
                Ya parametrizadas quedan inactivas.
            </p>
            <div class="ppto-pc-busca-toolbar">
                <div>
                    <label for="pc_busca_grupo">Grupo del plan</label>
                    <select id="pc_busca_grupo" class="form-control input-sm">
                        <option value="todas">- Elija un grupo -</option>
                    </select>
                </div>
                <div>
                    <label for="pc_busca_q">Buscar</label>
                    <input type="text" id="pc_busca_q" class="form-control input-sm" placeholder="Ej: 5.2.2 o Software o sueldos" />
                </div>
                <div>
                    <label for="pc_busca_filtro">Mostrar</label>
                    <select id="pc_busca_filtro" class="form-control input-sm">
                        <option value="todas" selected>Todas (ocupadas inactivas)</option>
                        <option value="libres">Solo libres</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-bottom:6px;flex:0 0 auto;">
                <button type="button" class="btn btn-default btn-sm" id="pc_busca_btn"><i class="bi bi-search"></i> Buscar</button>
                <button type="button" class="btn btn-link btn-sm" id="pc_busca_limpiar" style="padding-left:0;">Limpiar</button>
            </div>
            <div class="ppto-pc-busca-summary" id="pc_busca_summary"></div>
            <div class="ppto-pc-search-results" id="pc_busca_results"></div>
        </div>
        <div class="exa-pre-form-actions ppto-pc-modal-agregar-foot" style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" class="btn btn-default btn-sm" id="pc_modal_agregar_cancel">Cancelar</button>
            <button type="button" class="btn btn-success btn-sm" id="pc_modal_agregar_ok">Asignar seleccionadas</button>
        </div>
    </div>
</div>

<div id="modal_pc_asignar_pend" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box ppto-pc-modal-asignar">
        <span class="exa-pre-modal-close" id="pc_modal_asignar_pend_cerrar">&times;</span>
        <h3 class="exa-adq-section-title" style="flex:0 0 auto; margin-right:28px;">Asignar cuenta a rubro</h3>
        <div class="ppto-pc-modal-asignar-body">
            <p id="pc_asignar_pend_cuenta" style="font-size:13px;font-weight:600;color:#1a365d;margin:0 0 8px;flex:0 0 auto;"></p>
            <p style="font-size:11px;color:#718096;margin:0 0 8px;flex:0 0 auto;">
                Navegue por <strong>grupos</strong> (expandir/contraer) y elija un <strong>rubro detalle</strong>.
                Tambien puede filtrar por codigo o nombre.
            </p>
            <div class="filter-group" style="margin-bottom:8px;flex:0 0 auto;">
                <label for="pc_asignar_pend_filtro" style="font-size:11px;font-weight:600;color:#4a5568;">Buscar rubro</label>
                <input type="text" id="pc_asignar_pend_filtro" class="form-control input-sm" placeholder="Ej: 01.01 o personal o senaletica" />
            </div>
            <div style="font-size:11px;font-weight:600;color:#4a5568;margin-bottom:2px;flex:0 0 auto;">Arbol de rubros</div>
            <div id="pc_asignar_pend_tree" role="listbox" aria-label="Rubros presupuestarios"></div>
            <div id="pc_asignar_pend_sel_lbl" style="font-size:11px;color:#2b6cb0;margin-top:6px;min-height:16px;flex:0 0 auto;"></div>
            <div id="pc_asignar_pend_alert" class="alert ppto-pc-msg" role="alert" style="margin:8px 0 0;flex:0 0 auto;"></div>
        </div>
        <div class="exa-pre-form-actions ppto-pc-modal-asignar-foot">
            <button type="button" class="btn btn-default btn-sm" id="pc_modal_asignar_pend_cancel">Cancelar</button>
            <button type="button" class="btn btn-success btn-sm" id="pc_modal_asignar_pend_ok">
                <i class="bi bi-link-45deg"></i> Asignar
            </button>
        </div>
    </div>
</div>

<div id="modal_pc_sugerir" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box" style="width:92%; max-width:720px;">
        <span class="exa-pre-modal-close" id="pc_modal_sugerir_cerrar">&times;</span>
        <h3 class="exa-adq-section-title">Sugerir cuentas</h3>
        <p id="pc_sugerir_rubro_lbl" style="font-size:12px;color:#4a5568;margin:0 0 6px;"></p>
        <p style="font-size:11px;color:#718096;margin:0 0 10px;">
            Propuestas por similitud de nombre/codigo. <strong>No asigna automaticamente</strong>; usted decide.
        </p>
        <div class="ppto-pc-search-results" id="pc_sugerir_results"></div>
        <div class="exa-pre-form-actions" style="margin-top:14px; display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" class="btn btn-default btn-sm" id="pc_modal_sugerir_cancel">Cancelar</button>
            <button type="button" class="btn btn-primary btn-sm" id="pc_modal_sugerir_ok">Asignar seleccionadas</button>
        </div>
    </div>
</div>

<div id="modal_pc_copiar" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box" style="width:92%; max-width:560px;">
        <span class="exa-pre-modal-close" id="pc_modal_copiar_cerrar">&times;</span>
        <h3 class="exa-adq-section-title">Copiar parametrizaci&oacute;n</h3>
        <p style="font-size:12px;color:#718096;">
            Copia el mapeo partida&ndash;cuenta de un a&ntilde;o origen al destino.
        </p>
        <div class="ppto-pc-copiar-flow">
            <div class="pc-box">
                Origen
                <select id="pc_copiar_origen" class="form-control input-sm" style="margin-top:6px;">
                    <?php foreach ($pc_anios_opts as $ya): ?>
                    <option value="<?php echo (int)$ya; ?>" <?php echo (int)$ya === (int)$pc_anio_origen_def ? 'selected' : ''; ?>>
                        <?php echo (int)$ya; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="font-size:20px;color:#718096;">&darr;</div>
            <div class="pc-box">
                Destino
                <select id="pc_copiar_destino" class="form-control input-sm" style="margin-top:6px;">
                    <?php foreach ($pc_anios_opts as $ya): ?>
                    <option value="<?php echo (int)$ya; ?>" <?php echo (int)$ya === (int)$ani_filtro ? 'selected' : ''; ?>>
                        <?php echo (int)$ya; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <label class="ppto-pc-check-lazy" style="margin-bottom:12px;">
            <input type="checkbox" id="pc_copiar_overwrite" />
            Sobreescribir si la cuenta ya esta en otro rubro del destino
        </label>
        <div class="exa-pre-form-actions" style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" class="btn btn-default btn-sm" id="pc_modal_copiar_cancel">Cancelar</button>
            <button type="button" class="btn btn-primary btn-sm" id="pc_modal_copiar_ok">
                <i class="bi bi-files"></i> Copiar
            </button>
        </div>
    </div>
</div>

<script src="../VALIDACIONES/ppto_param_contable_js.js"></script>
