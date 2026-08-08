<?php require_once('../../administrador/LOGICA/seguridad.php'); ?>

<!DOCTYPE html>

<html lang="es" class="exa-ui-fill-root">

<head>

    <meta charset="UTF-8">

    <title>Presupuesto proyectos - EXA</title>

    <?php require_once dirname(dirname(__DIR__)) . '/contabilidad/FRONT/con_model3_assets.php'; ?>

    <script src="../VALIDACIONES/ppto_format.js?v=20260808a"></script>

    <style>

        .exa-pre-form-panel label { display: block; font-size: 11px; font-weight: 600; margin-bottom: 4px; color: #4a5568; }

        .exa-pre-form-panel h5 { margin: 0 0 12px; font-weight: 700; color: #1a365d; }

        .exa-ppto-ton-base { background: #f0f7ff; border: 1px solid #bee3f8; border-radius: 6px; padding: 12px 14px; margin-bottom: 14px; }

        .exa-ppto-ton-base .help { font-size: 11px; color: #718096; margin: 0 0 10px; }

        .exa-ppto-readonly { background: #edf2f7 !important; cursor: default; }

        .exa-ppto-rubro-actions { display: inline-flex; flex-direction: row; flex-wrap: nowrap; align-items: center; justify-content: center; gap: 4px; white-space: nowrap; }
        .exa-ppto-rubro-actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            min-width: 30px;
            height: 26px;
            padding: 0;
            margin: 0;
            float: none;
            line-height: 1;
            box-sizing: border-box;
        }
        .exa-ppto-rubro-actions .btn i { font-size: 13px; line-height: 1; }
        #tblRubros td.exa-ppto-rubro-actions-cell { white-space: nowrap; text-align: center; vertical-align: middle; }
        .exa-ppto-pdf-box { margin-top: 12px; padding-top: 12px; border-top: 1px dashed #bee3f8; }
        .exa-ppto-pdf-preview { max-height: 320px; overflow: auto; font-size: 11px; }
        .exa-ppto-pdf-preview .pdf-preview-total-row td { background: #edf2f7 !important; border-top: 2px solid #cbd5e0; }
        .exa-ppto-pdf-preview .pdf-preview-total-mes { font-size: 10px; font-weight: 600; color: #718096; margin-top: 2px; }
        #modalPdfPreview .exa-pre-modal-box { width: 96%; max-width: 1280px; }
        #modalPdfPreview .modal-table th, #modalPdfPreview .modal-table td { font-size: 11px; padding: 6px 8px; white-space: nowrap; }
        #modalPdfPreview .pdf-preview-desc { min-width: 160px; font-size: 11px; }
        #modalPdfPreview .pdf-preview-factor { max-width: 90px; font-size: 11px; text-align: right; }
        #modalPdfPreview .pdf-preview-meses-global { max-width: 72px; font-size: 11px; text-align: right; margin: 4px auto 0; display: block; }
        #modalPdfPreview .pdf-preview-meses-val { font-weight: 600; color: #2d3748; }

        .exa-ppto-tab-intro { font-size: 12px; color: #718096; margin: 0 0 14px; }
        .exa-ppto-rubro-form-help { font-size: 11px; color: #718096; margin: 0 0 10px; }
        #modalEditRubro.exa-pre-modal-overlay { z-index: 10040; }
        #modalPartidaRubro.exa-pre-modal-overlay { z-index: 10060; }
        #msg.exa-ppto-toast-float {
            position: fixed;
            top: 18px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10120;
            min-width: 300px;
            max-width: min(560px, 92vw);
            margin: 0;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.18);
            text-align: center;
            pointer-events: none;
        }
        .exa-ppto-rubro-resumen { font-size: 11px; color: #4a5568; margin-top: 5px; line-height: 1.35; min-height: 16px; }
        .exa-ppto-rubro-resumen strong { color: #1a365d; }
        .exa-ppto-sel-with-btn { display: flex; gap: 4px; align-items: flex-start; }
        .exa-ppto-sel-with-btn select { flex: 1 1 auto; min-width: 0; }
        .exa-ppto-sel-with-btn .btn-add-partida {
            flex: 0 0 28px; width: 28px; height: 30px; padding: 0; font-weight: 700; line-height: 1;
            font-size: 16px; color: #2b6cb0; border-color: #bee3f8; background: #ebf8ff;
        }
        .exa-ppto-sel-with-btn .btn-add-partida:hover { background: #bee3f8; color: #1a365d; }
        .exa-ppto-rubro-form-row select.form-control { font-size: 12px; }
        .exa-ppto-rubros-list-title { font-weight: 700; color: #1a365d; margin: 20px 0 10px; font-size: 14px; }
        .exa-ppto-cuadro-kpis { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
        .exa-ppto-cuadro-kpi { flex: 1; min-width: 160px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
        .exa-ppto-cuadro-kpi .lbl { font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #718096; font-weight: 600; margin-bottom: 4px; }
        .exa-ppto-cuadro-kpi .val { font-size: 18px; font-weight: 700; color: #1a365d; }
        .exa-ppto-cuadro-kpi .val.val-mes { color: #276749; font-size: 16px; }
        .exa-ppto-cuadro-kpi .val-sub { font-size: 11px; color: #718096; font-weight: 500; margin-top: 2px; }
        .exa-ppto-cuadro-accordion { margin: 0; width: 100%; }
        /* Cuadro presupuestario: grilla unificada cabecera + filas grupo */
        .exa-ppto-cuadro-shell {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 8px;
            width: 100%;
        }
        .exa-ppto-cuadro-grid {
            --cuadro-grid-cols: minmax(280px, 1fr) 150px 136px 104px 114px 118px 142px 120px;
            display: grid;
            grid-template-columns: var(--cuadro-grid-cols);
            gap: 0 10px;
            align-items: center;
            padding: 0 14px;
            box-sizing: border-box;
            min-width: 980px;
            width: 100%;
        }
        .exa-ppto-cuadro-head-row {
            padding: 8px 0;
            margin-bottom: 8px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #718096;
            font-weight: 600;
            min-height: 38px;
        }
        .exa-ppto-cuadro-head-row .col-grupo { text-align: left; justify-self: stretch; padding-left: 12px; box-sizing: border-box; }
        .exa-ppto-cuadro-head-row .col-money { text-align: right; justify-self: stretch; }
        .exa-ppto-cuadro-head-row .col-ton { text-align: center; justify-self: stretch; }
        .exa-ppto-cuadro-head-row .col-pct-tope,
        .exa-ppto-cuadro-head-row .col-tope,
        .exa-ppto-cuadro-head-row .col-accion { text-align: center; justify-self: stretch; }
        .exa-ppto-cuadro-accordion > .panel { border: 1px solid #e2e8f0; border-radius: 8px !important; overflow: hidden; box-shadow: 0 1px 2px rgba(15,23,42,.04); margin-bottom: 8px !important; min-width: 980px; width: 100%; box-sizing: border-box; }
        .exa-ppto-cuadro-accordion > .panel > .panel-heading { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 0; }
        .exa-ppto-cuadro-accordion .cuadro-grupo-heading {
            padding: 7px 0;
            margin: 0;
            min-height: 0;
        }
        .exa-ppto-cuadro-accordion .cuadro-grupo-heading.is-open { background: #ebf8ff; }
        .exa-ppto-cuadro-accordion .cuadro-grupo-head {
            color: #1a365d;
            font-size: 13px;
            font-weight: 600;
            align-items: center;
            min-height: 42px;
        }
        .exa-ppto-cuadro-accordion .cuadro-grupo-toggle {
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            min-width: 0;
            align-self: center;
        }
        .exa-ppto-cuadro-accordion .cuadro-grupo-toggle:hover { color: #2c5282; }
        .exa-ppto-cuadro-accordion .cuadro-grupo-heading.is-open .cuadro-grupo-toggle.grupo-head-left { color: #2b6cb0; }
        .exa-ppto-cuadro-accordion .grupo-head-left {
            min-width: 0;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-start;
            text-align: left;
            padding-left: 12px;
            box-sizing: border-box;
        }
        .exa-ppto-cuadro-accordion .grupo-cod { display: inline-block; flex-shrink: 0; min-width: 34px; padding: 2px 8px; background: #ebf8ff; color: #2b6cb0; border-radius: 4px; font-size: 11px; font-weight: 700; text-align: center; }
        .exa-ppto-cuadro-accordion .grupo-nom { flex: 1 1 auto; font-weight: 600; color: #2d3748; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
        .exa-ppto-cuadro-accordion .grupo-metric {
            display: flex;
            align-items: center;
            min-width: 0;
            justify-self: stretch;
            align-self: center;
        }
        .exa-ppto-cuadro-accordion .grupo-metric.col-num {
            justify-content: flex-end;
            text-align: right;
            font-size: 14px;
            font-weight: 700;
            color: #1a365d;
            line-height: 1.2;
            white-space: nowrap;
        }
        .exa-ppto-cuadro-accordion .grupo-metric.col-num.val-mes { color: #276749; font-size: 13px; }
        /* === CUADRO_PARTIDA_FINAL_UI (reversible) START === */
        .exa-ppto-cuadro-accordion .grupo-metric.col-num.grupo-presup-dual {
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            gap: 1px;
            line-height: 1.15;
        }
        .exa-ppto-cuadro-accordion .grupo-presup-dual .presup-base {
            font-size: 10px;
            font-weight: 500;
            color: #a0aec0;
            text-decoration: line-through;
        }
        .exa-ppto-cuadro-accordion .grupo-presup-dual .presup-final {
            font-size: 14px;
            font-weight: 700;
            color: #276749;
        }
        .exa-ppto-cuadro-accordion .grupo-presup-dual.val-mes .presup-final { color: #276749; font-size: 13px; }
        .exa-ppto-cuadro-head-row .col-money.col-final-on { color: #276749; }
        /* === CUADRO_PARTIDA_FINAL_UI (reversible) END === */
        .exa-ppto-cuadro-accordion .grupo-metric.grupo-ton-val {
            justify-content: center;
            text-align: center;
            color: #2b6cb0;
            font-size: 13px;
        }
        .exa-ppto-cuadro-accordion .grupo-metric.grupo-ton-val.val-mes { color: #3182ce; }
        .exa-ppto-cuadro-accordion .grupo-col-pct {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            cursor: default;
            justify-self: stretch;
            align-self: center;
        }
        .exa-ppto-cuadro-accordion .grupo-col-pct > .text-muted {
            display: block;
            width: 100%;
            text-align: center;
        }
        .exa-ppto-cuadro-accordion .grupo-col-tope {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            line-height: 1.15;
            min-width: 0;
            cursor: default;
            justify-self: stretch;
            align-self: center;
            text-align: center;
        }
        .exa-ppto-cuadro-accordion .grupo-col-tope > .text-muted {
            display: block;
            width: 100%;
            text-align: center;
        }
        .exa-ppto-cuadro-accordion .grupo-tope-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            line-height: 1.15;
            width: 100%;
            text-align: center;
            gap: 2px;
        }
        .exa-ppto-cuadro-accordion .grupo-tope-val {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            white-space: nowrap;
            max-width: 100%;
            text-align: center;
        }
        .exa-ppto-cuadro-accordion .grupo-tope-usado {
            display: block;
            font-size: 10px;
            margin-top: 0;
            white-space: nowrap;
            text-align: center;
            width: 100%;
        }
        .exa-ppto-cuadro-accordion .grupo-head-right {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
            min-width: 0;
            justify-self: stretch;
            align-self: center;
            padding-right: 2px;
        }
        .exa-ppto-cuadro-accordion .btn-grupo-resumen {
            height: 26px;
            padding: 2px 7px;
            line-height: 1.2;
            flex-shrink: 0;
        }
        .exa-ppto-cuadro-accordion .btn-grupo-resumen i { font-size: 13px; }
        #modalGrupoResumen .exa-pre-modal-box { width: 96%; max-width: 1100px; }
        #modalGrupoResumen .grupo-resumen-kpi { display: flex; flex-wrap: wrap; gap: 10px; margin: 0 0 12px; }
        #modalGrupoResumen .grupo-resumen-kpi .item { flex: 1 1 140px; background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; }
        #modalGrupoResumen .grupo-resumen-kpi .lbl { display: block; font-size: 10px; text-transform: uppercase; color: #718096; font-weight: 600; }
        #modalGrupoResumen .grupo-resumen-kpi .val { display: block; font-size: 14px; font-weight: 700; color: #1a365d; margin-top: 2px; }
        #modalGrupoResumen .grupo-resumen-kpi .val.val-mes { color: #276749; }
        #modalGrupoResumen .grupo-resumen-kpi .val.val-ton { color: #2b6cb0; font-size: 13px; }
        #modalGrupoResumen .modal-table th, #modalGrupoResumen .modal-table td { font-size: 11px; padding: 6px 8px; }
        #modalGrupoResumen .grupo-resumen-subhead td { background: #edf2f7; font-weight: 600; color: #2d3748; }
        .exa-ppto-cuadro-accordion .grupo-meta .badge { background: #e2e8f0; color: #4a5568; font-weight: 600; font-size: 11px; }
        .exa-ppto-cuadro-accordion .grupo-meta i.bi-chevron-down { color: #a0aec0; font-size: 14px; transition: transform .2s; flex-shrink: 0; }
        .exa-ppto-cuadro-accordion .cuadro-grupo-heading.is-open .bi-chevron-down { transform: rotate(180deg); }
        .exa-ppto-cuadro-accordion .panel-body { padding: 0 14px 10px; background: #fff; }
        .exa-ppto-cuadro-accordion .panel-body .table-responsive { margin: 0; }
        .exa-ppto-cuadro-accordion .table { margin: 0; font-size: 13px; }
        .exa-ppto-cuadro-accordion .table > thead > tr > th,
        .exa-ppto-cuadro-accordion .table > tbody > tr > td,
        .exa-ppto-cuadro-accordion .table > tfoot > tr > td {
            padding: 11px 12px;
            vertical-align: middle;
        }
        .exa-ppto-cuadro-accordion .table > thead > tr > th { background: #f7fafc; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-transform: uppercase; letter-spacing: .03em; color: #718096; }
        .exa-ppto-cuadro-accordion .exa-ppto-subgrupo-head td { background: #edf2f7; border-top: 2px solid #cbd5e0; padding: 0; }
        .exa-ppto-cuadro-accordion .exa-ppto-subgrupo-head-inner { display: flex; align-items: center; gap: 10px; padding: 12px 14px; flex-wrap: wrap; }
        .exa-ppto-cuadro-accordion .subgrupo-cod { display: inline-block; flex-shrink: 0; padding: 2px 7px; background: #e6fffa; color: #234e52; border-radius: 4px; font-size: 10px; font-weight: 700; }
        .exa-ppto-cuadro-accordion .subgrupo-nom { flex: 1 1 200px; font-size: 12px; font-weight: 600; color: #2d3748; min-width: 0; }
        .exa-ppto-cuadro-accordion .subgrupo-metrics { display: flex; align-items: center; gap: 12px; flex-shrink: 0; font-size: 11px; color: #4a5568; margin-left: auto; }
        .exa-ppto-cuadro-accordion .subgrupo-metrics .badge { background: #fff; color: #4a5568; font-weight: 600; }
        .exa-ppto-cuadro-accordion .subgrupo-total { font-weight: 700; color: #1a365d; }
        .exa-ppto-cuadro-accordion .subgrupo-total-mes { font-weight: 700; color: #276749; }
        .exa-ppto-cuadro-accordion .subgrupo-ton { color: #2b6cb0; font-weight: 600; }
        .exa-ppto-cuadro-accordion .exa-ppto-subgrupo-foot td { background: #f8fafc; font-weight: 600; font-size: 11px; color: #4a5568; border-bottom: 1px solid #e2e8f0; }
        .exa-ppto-cuadro-accordion tr.exa-ppto-rubro-indent td:first-child { padding-left: 22px; }
        .exa-ppto-cuadro-empty { padding: 40px 20px; text-align: center; color: #a0aec0; background: #f8fafc; border: 1px dashed #e2e8f0; border-radius: 8px; }
        .exa-ppto-grupo-excedido > .panel-heading { background: #fff5f5 !important; border-bottom-color: #fc8181 !important; }
        .exa-ppto-grupo-excedido .grupo-pct-badge { background: #fed7d7; color: #c53030; }
        .exa-ppto-publicar-box {
            margin: 0 0 16px;
            padding: 14px 16px;
            background: linear-gradient(135deg, #f0fff4 0%, #ebf8ff 100%);
            border: 1px solid #9ae6b4;
            border-radius: 8px;
        }
        .exa-ppto-publicar-box h5 { margin: 0 0 8px; font-weight: 700; color: #22543d; font-size: 14px; }
        .exa-ppto-publicar-box .help { font-size: 11px; color: #4a5568; margin: 0 0 10px; line-height: 1.45; }
        .exa-ppto-publicar-meta { font-size: 11px; color: #276749; margin-bottom: 10px; }
        .exa-ppto-publicar-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .exa-ppto-escenario-box { margin: 0 0 16px; padding: 14px 16px; background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%); border: 1px solid #cbd5e0; border-radius: 8px; }
        .exa-ppto-escenario-box .esc-head { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .exa-ppto-escenario-box h5 { margin: 0 0 6px; font-weight: 700; color: #2d3748; font-size: 14px; }
        .exa-ppto-escenario-box .esc-sub { font-size: 11px; color: #718096; }
        .exa-ppto-escenario-box .help { font-size: 11px; color: #4a5568; margin: 0 0 12px; line-height: 1.45; }
        .esc-selector { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
        .esc-btn { flex: 1; min-width: 180px; text-align: left; background: #fff; border: 2px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; cursor: pointer; transition: all .12s ease; }
        .esc-btn:hover { border-color: #90cdf4; }
        .esc-btn.active { border-color: #3182ce; background: #ebf8ff; box-shadow: 0 2px 8px rgba(49,130,206,.15); }
        .esc-btn .esc-btn-t { display: block; font-size: 11px; font-weight: 700; color: #2d3748; margin-bottom: 4px; }
        .esc-btn .esc-btn-t i { margin-right: 4px; color: #3182ce; }
        .esc-btn .esc-btn-v { display: block; font-size: 16px; font-weight: 800; color: #1a365d; }
        .esc-btn.active .esc-btn-v { color: #2b6cb0; }
        .esc-actions { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .esc-note { font-size: 11px; color: #4a5568; }
        .esc-republicar { margin-top: 12px; border-top: 1px dashed #cbd5e0; padding-top: 8px; }
        .esc-republicar-toggle { font-size: 11px; color: #718096; font-weight: 600; text-decoration: none; }
        .esc-republicar-toggle:hover { color: #4a5568; }
        .esc-resumen { margin-top: 14px; padding-top: 14px; border-top: 1px solid #e2e8f0; }
        .esc-resumen h6 { margin: 0 0 6px; font-size: 12px; font-weight: 700; color: #2d3748; }
        .esc-resumen .help { margin-bottom: 10px; }
        .esc-resumen-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .esc-resumen-table th, .esc-resumen-table td { padding: 8px 10px; border: 1px solid #e2e8f0; text-align: right; }
        .esc-resumen-table th:first-child, .esc-resumen-table td:first-child { text-align: left; font-weight: 600; color: #4a5568; }
        .esc-resumen-table th { background: #f7fafc; font-size: 10px; text-transform: uppercase; color: #718096; }
        .esc-resumen-table .esc-res-col.active { background: #ebf8ff; }
        .esc-resumen-table .eco-pos { color: #276749; font-weight: 700; }
        .esc-resumen-table .eco-neg { color: #c53030; font-weight: 700; }
        .esc-btn .esc-btn-s { display: block; font-size: 9px; color: #718096; text-transform: uppercase; margin-top: 2px; }
        .exa-ppto-cuadro-periodo {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin: 0 0 12px;
            padding: 10px 12px;
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        .exa-ppto-cuadro-periodo .periodo-grp { display: inline-flex; align-items: center; gap: 6px; }
        .exa-ppto-cuadro-periodo .periodo-grp-proy select {
            min-width: 220px;
            max-width: 320px;
            font-size: 12px;
        }
        .exa-ppto-cuadro-periodo label { margin: 0; font-size: 11px; font-weight: 600; color: #4a5568; }
        .exa-ppto-cuadro-periodo .btn-group .btn { font-size: 11px; padding: 4px 10px; }
        .exa-ppto-cuadro-periodo .cuadro-vista-btn {
            background: #fff;
            border-color: #cbd5e0;
            color: #4a5568;
            font-weight: 600;
        }
        .exa-ppto-cuadro-periodo .cuadro-vista-btn:hover {
            background: #edf2f7;
            border-color: #a0aec0;
            color: #2d3748;
        }
        .exa-ppto-cuadro-periodo .cuadro-vista-btn.active,
        .exa-ppto-cuadro-periodo .cuadro-vista-btn.active:focus,
        .exa-ppto-cuadro-periodo .cuadro-vista-btn.active:hover {
            background: #2b6cb0 !important;
            border-color: #2b6cb0 !important;
            color: #fff !important;
            box-shadow: none;
            z-index: 2;
        }
        .exa-ppto-cuadro-periodo .periodo-lbl {
            margin-left: auto;
            font-size: 11px;
            color: #718096;
        }
        .exa-ppto-cuadro-periodo .periodo-lbl strong { color: #2d3748; }
        .exa-ppto-cuadro-periodo .periodo-actions {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .exa-ppto-ajuste-box {
            margin: 0 0 16px;
            padding: 16px 18px;
            background: linear-gradient(180deg, #fffdf7 0%, #fffaf0 100%);
            border: 1px solid #ecc94b;
            border-radius: 10px;
        }
        .exa-ppto-ajuste-box h5 {
            margin: 0 0 6px;
            font-size: 15px;
            font-weight: 700;
            color: #744210;
        }
        .exa-ppto-ajuste-box .ajuste-intro {
            margin: 0 0 14px;
            padding: 10px 12px;
            background: #fff;
            border: 1px solid #f6e05e;
            border-radius: 8px;
            font-size: 12px;
            color: #744210;
            line-height: 1.45;
        }
        .exa-ppto-ajuste-box .ajuste-intro strong { color: #5a3e0b; }
        .exa-ppto-ajuste-box .ajuste-formula {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 8px;
            background: #fefcbf;
            border-radius: 4px;
            font-family: Consolas, Monaco, monospace;
            font-size: 11px;
            font-weight: 600;
        }
        .exa-ppto-ajuste-box .ajuste-steps {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0 0 14px;
            padding: 0;
            list-style: none;
        }
        .exa-ppto-ajuste-box .ajuste-steps li {
            flex: 1;
            min-width: 140px;
            background: #fff;
            border: 1px solid #faf089;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 11px;
            color: #744210;
        }
        .exa-ppto-ajuste-box .ajuste-steps .step-n {
            display: inline-block;
            width: 20px;
            height: 20px;
            line-height: 20px;
            text-align: center;
            border-radius: 50%;
            background: #d69e2e;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            margin-right: 6px;
        }
        .exa-ppto-ajuste-box .ajuste-dual {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 12px;
        }
        .exa-ppto-ajuste-box .ajuste-card {
            flex: 1;
            min-width: 280px;
            background: #fff;
            border: 1px solid #f6e05e;
            border-radius: 8px;
            padding: 12px 14px;
        }
        .exa-ppto-ajuste-box .ajuste-card h6 {
            margin: 0 0 4px;
            font-size: 13px;
            font-weight: 700;
            color: #744210;
        }
        .exa-ppto-ajuste-box .ajuste-card .card-why {
            margin: 0 0 10px;
            font-size: 11px;
            color: #975a16;
            line-height: 1.35;
        }
        .exa-ppto-ajuste-box .ajuste-card .ajuste-field {
            margin-bottom: 8px;
        }
        .exa-ppto-ajuste-box .ajuste-card .ajuste-field label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            color: #744210;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .exa-ppto-ajuste-box .ajuste-card .field-help {
            display: block;
            margin-top: 2px;
            font-size: 10px;
            color: #a0aec0;
            line-height: 1.3;
        }
        .exa-ppto-ajuste-box .gad-timeline {
            margin: 8px 0 0;
            padding: 8px 10px;
            background: #fffff0;
            border-left: 3px solid #d69e2e;
            font-size: 11px;
            color: #744210;
            line-height: 1.4;
        }
        .exa-ppto-ajuste-box .ajuste-check-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            margin: 0 0 12px;
            padding: 10px 12px;
            background: #fff;
            border: 1px dashed #d69e2e;
            border-radius: 8px;
        }
        .exa-ppto-ajuste-box .ajuste-check-row label.chk-main {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: #744210;
            cursor: pointer;
        }
        .exa-ppto-ajuste-box .ajuste-check-row .chk-help {
            flex: 1;
            min-width: 200px;
            font-size: 11px;
            color: #975a16;
        }
        .exa-ppto-ajuste-box .ajuste-kpis-hero {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 0 0 10px;
        }
        .exa-ppto-ajuste-box .ajuste-kpi-hero {
            flex: 1;
            min-width: 160px;
            background: #744210;
            color: #fff;
            border-radius: 8px;
            padding: 12px 14px;
        }
        .exa-ppto-ajuste-box .ajuste-kpi-hero .lbl {
            font-size: 10px;
            text-transform: uppercase;
            opacity: .85;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .exa-ppto-ajuste-box .ajuste-kpi-hero .val {
            font-size: 18px;
            font-weight: 700;
        }
        .exa-ppto-ajuste-box .ajuste-kpi-hero.kpi-rest {
            background: #c05621;
        }
        .exa-ppto-ajuste-box .ajuste-kpi-hero.kpi-final {
            background: #276749;
        }
        .exa-ppto-ajuste-box .ajuste-kpi-hero.kpi-util {
            background: #2b6cb0;
        }
        .exa-ppto-ajuste-box .ajuste-kpis {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0 0 12px;
        }
        .exa-ppto-ajuste-box .ajuste-kpi {
            flex: 1;
            min-width: 100px;
            background: #fff;
            border: 1px solid #faf089;
            border-radius: 6px;
            padding: 8px 10px;
        }
        .exa-ppto-ajuste-box .ajuste-kpi .lbl { font-size: 9px; text-transform: uppercase; color: #975a16; font-weight: 600; }
        .exa-ppto-ajuste-box .ajuste-kpi .val { font-size: 13px; font-weight: 700; color: #744210; }
        .exa-ppto-ajuste-box .table { font-size: 11px; margin-bottom: 0; background: #fff; }
        .exa-ppto-ajuste-box .ajuste-actions { margin-top: 0; margin-bottom: 12px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .exa-ppto-ajuste-box .ajuste-hint { font-size: 11px; color: #975a16; margin: 0; }
        .exa-ppto-ajuste-box .ajuste-table-caption {
            margin: 0 0 6px;
            font-size: 12px;
            font-weight: 600;
            color: #744210;
        }
        .exa-ppto-ajuste-box .col-menos { color: #c53030; }
        .exa-ppto-ajuste-box .ajuste-dist-wrap {
            max-height: 320px;
            overflow: auto;
            border: 1px solid #f6e05e;
            border-radius: 6px;
            background: #fff;
        }
        .exa-ppto-ajuste-box #tblAjusteDist {
            margin-bottom: 0;
            border: none;
        }
        .exa-ppto-ajuste-box #tblAjusteDist thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #fefcbf;
            border-bottom: 1px solid #ecc94b;
            box-shadow: 0 1px 0 #ecc94b;
        }
        .exa-ppto-ajuste-box #tblAjusteDist tfoot td {
            position: sticky;
            bottom: 0;
            z-index: 2;
            background: #faf5e4;
            border-top: 2px solid #d69e2e;
            font-weight: 700;
            color: #744210;
            box-shadow: 0 -1px 0 #d69e2e;
        }
        .exa-ppto-ajuste-box details.ajuste-tech {
            margin-top: 8px;
            font-size: 11px;
            color: #975a16;
        }
        .exa-ppto-ajuste-box details.ajuste-tech summary {
            cursor: pointer;
            font-weight: 600;
        }
        #modalPublicarPreview .pub-kpi { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
        #modalPublicarPreview .pub-kpi .item { flex: 1; min-width: 140px; background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; }
        #modalPublicarPreview .pub-kpi .lbl { font-size: 10px; text-transform: uppercase; color: #718096; font-weight: 600; }
        #modalPublicarPreview .pub-kpi .val { font-size: 16px; font-weight: 700; color: #1a365d; }
        .grupo-pct-edit { width: 64px; min-width: 64px; flex: 0 0 64px; display: inline-block; text-align: right; padding: 2px 8px; height: 26px; font-size: 12px; -moz-appearance: textfield; appearance: textfield; }
        .grupo-pct-edit::-webkit-outer-spin-button,
        .grupo-pct-edit::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .grupo-meses-edit { width: 56px; display: inline-block; text-align: right; padding: 2px 6px; height: 24px; font-size: 11px; }
        .grupo-pct-wrap {
            display: inline-flex;
            align-items: stretch;
            justify-content: center;
            gap: 0;
            font-size: 11px;
            color: #4a5568;
            flex-shrink: 0;
            width: auto;
            max-width: 100%;
            border: 1px solid #cbd5e0;
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
        }
        .exa-ppto-cuadro-accordion .grupo-pct-wrap .grupo-pct-edit {
            border: none;
            border-radius: 0;
            box-shadow: none;
        }
        .exa-ppto-cuadro-accordion .grupo-pct-wrap .grupo-pct-edit:focus {
            outline: none;
            box-shadow: inset 0 0 0 1px #90cdf4;
        }
        .exa-ppto-cuadro-accordion .btn-save-grupo-pct {
            height: 26px;
            padding: 0 9px;
            line-height: 1;
            flex-shrink: 0;
            border: none;
            border-left: 1px solid #cbd5e0;
            border-radius: 0;
            background: #f7fafc;
            font-size: 11px;
            font-weight: 600;
        }
        .exa-ppto-cuadro-accordion .btn-save-grupo-pct:hover,
        .exa-ppto-cuadro-accordion .btn-save-grupo-pct:focus {
            background: #edf2f7;
            outline: none;
        }
        @media (max-width: 1280px) {
            .exa-ppto-cuadro-grid {
                --cuadro-grid-cols: minmax(240px, 1fr) 132px 122px 94px 104px 110px 132px 116px;
                min-width: 960px;
                padding: 0 12px;
            }
            .exa-ppto-cuadro-accordion > .panel { min-width: 960px; }
        }
        .grupo-meses-wrap { display: inline-flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 4px; font-size: 11px; color: #4a5568; max-width: 100%; margin-top: 2px; }
        .grupo-meses-label { font-size: 10px; color: #718096; }
        .grupo-pct-status { display: block; width: 100%; text-align: right; font-size: 10px; line-height: 1.25; }
        .grupo-pct-tope { font-size: 10px; color: #718096; }

    </style>

</head>

<body class="exa-ui-fill-root">

<div class="panel panel-main exa-ui-panel exa-ui-fill-page">

    <div class="panel-heading exa-header exa-header-flex">

        <h3 class="panel-title"><i class="bi bi-folder2-open"></i> Presupuesto proyectos</h3>

    </div>

    <div class="panel-body exa-body">

        <div class="exa-ui-page-view">

            <div id="msg" class="alert exa-ppto-toast-float" style="display:none;"></div>

            <ul class="nav nav-tabs exa-ui-nav-tabs" id="pptoProyTabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#tabProyectos" aria-controls="tabProyectos" role="tab" data-toggle="tab"><i class="bi bi-folder2-open"></i> Proyectos</a>
                </li>
                <li role="presentation">
                    <a href="#tabRubrosTon" aria-controls="tabRubrosTon" role="tab" data-toggle="tab"><i class="bi bi-sliders"></i> Rubros y toneladas</a>
                </li>
                <li role="presentation">
                    <a href="#tabCuadro" aria-controls="tabCuadro" role="tab" data-toggle="tab"><i class="bi bi-table"></i> Cuadro presupuestario</a>
                </li>
            </ul>

            <div class="tab-content exa-ui-tab-content panels-area">

            <div role="tabpanel" class="tab-pane active" id="tabProyectos">

            <div class="exa-pre-form-panel">

                <h5>Nuevo / Editar proyecto</h5>

                <input type="hidden" id="is_edit" value="0" />
                <input type="hidden" id="proy_id" value="" />

                <div class="row">

                    <div class="col-md-2"><label>Codigo</label><input id="Pro_Cod" class="form-control input-sm" placeholder="Ej. RCET-01" /></div>

                    <div class="col-md-4"><label>Nombre</label><input id="Pro_Nom" class="form-control input-sm" /></div>

                    <div class="col-md-2"><label>Estado</label><select id="Pro_Est" class="form-control input-sm"><option value="A">Activo</option><option value="I">Inactivo</option></select></div>

                    <div class="col-md-3"><label>Plantilla</label><select id="Plt_Cod" class="form-control input-sm"></select></div>

                    <div class="col-md-1" style="padding-top:22px;"><button id="btnSaveProy" class="btn btn-success btn-sm">Guardar</button></div>

                </div>

            </div>

            <div class="exa-adq-table-wrap">

                <table class="table table-bordered exa-adq-table" id="tblProy">

                    <thead><tr><th>Codigo</th><th>Nombre</th><th>Estado</th><th>Plantilla</th><th>Acciones</th></tr></thead>

                    <tbody></tbody>

                </table>

            </div>

            </div><!-- tab proyectos -->

            <div role="tabpanel" class="tab-pane" id="tabRubrosTon">

            <div class="exa-ppto-ton-base exa-pre-form-panel">

                <h5 style="margin-bottom:10px;">Base ingresos del proyecto</h5>

                <div class="row">

                    <div class="col-md-2"><label>Proyecto</label><select id="rub_proy_id" class="form-control input-sm"></select></div>

                    <input type="hidden" id="rub_ppe_id" value="" />

                    <div class="col-md-2"><label title="Ingresos y escenarios (3.500 &times; 30 d)">Ton ingresos (mes)</label><input id="pv_toneladas_base_mes" type="number" step="0.0001" class="form-control input-sm" placeholder="105000" title="No altera egresos del Excel" /></div>

                    <div class="col-md-2"><label title="Precio base/default de la version. Las proyecciones por ano se configuran en Cuadro &gt; Precios por ano">$/Ton con IVA (base)</label><input id="pv_tarifa_ton_iva" type="number" step="0.0001" class="form-control input-sm" value="3" title="Tarifa base de la version (fallback si un ano no tiene precio)" /></div>

                    <div class="col-md-1"><label>IVA div.</label><input id="pv_iva_divisor" type="number" step="0.01" class="form-control input-sm" value="1.15" title="Divisor IVA (1.15 = 15%)" /></div>

                    <div class="col-md-3" style="padding-top:22px;">

                        <button id="btnSaveTonBase" class="btn btn-primary btn-sm" type="button" title="Guarda ton ingresos, tarifa e IVA">Guardar base ingresos</button>

                    </div>

                </div>

                <div class="row" style="margin-top:10px;">

                    <div class="col-md-2"><label title="Egresos Excel / rubros driver (3.500 &times; 22 d)">Ton costo egreso (mes)</label><input id="pv_toneladas_costo_mes" type="number" step="0.0001" class="form-control input-sm" placeholder="77000" title="Base para $/Ton de rubros del cuadro" /></div>

                    <div class="col-md-4" style="padding-top:22px;">

                        <button id="btnAplicarTonRubros" class="btn btn-default btn-sm" type="button" title="Aplica ton costo: recalcula presupuesto Base PDF (ton x $/Ton). $/Ton queda fijo del Excel.">Aplicar ton costo a rubros</button>

                    </div>

                </div>

                <div class="exa-ppto-pdf-box">
                    <h5 style="margin:0 0 8px;font-size:13px;">Importar presupuesto (PDF o Excel)</h5>
                    <div class="row">
                        <div class="col-md-5">
                            <label>Archivo PDF / Excel / CSV</label>
                            <input type="file" id="pdf_import_file" accept=".pdf,.xlsx,.xls,.xlsm,.csv,application/pdf" class="form-control input-sm" title="Excel RCET (.xlsx) recomendado. PDF con texto seleccionable." />
                        </div>
                        <div class="col-md-3" style="padding-top:22px;">
                            <button id="btnParsePdf" class="btn btn-info btn-sm" type="button"><i class="bi bi-search"></i> Analizar</button>
                            <button id="btnImportPdf" class="btn btn-success btn-sm" type="button" style="display:none;"><i class="bi bi-upload"></i> Importar</button>
                        </div>
                        <div class="col-md-4" style="padding-top:22px;font-size:11px;color:#718096;" id="pdf_import_status"></div>
                    </div>
                </div>

            </div>



            <div class="exa-ppto-rubro-toolbar" style="margin:12px 0 16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <button type="button" id="btnAbrirAgregarRubro" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Agregar rubro</button>
            </div>

            <h5 class="exa-ppto-rubros-list-title">Listado de rubros</h5>

            <div class="exa-adq-table-wrap">

                <table class="table table-bordered table-hover exa-adq-table" id="tblRubros">

                    <thead><tr><th>Partida</th><th>Rubro</th><th title="Driver egresos Excel (3.500 &times; 22 d = 77.000)">Ton/mes costo</th><th>$/Ton anual</th><th>$/Ton mensual</th><th>Presup. anual</th><th>Presup. mensual</th><th style="width:88px;"></th></tr></thead>

                    <tbody></tbody>

                </table>

                <div id="tblRubrosPager" class="clearfix" style="display:none;margin:10px 0 0;font-size:12px;color:#4a5568;">
                    <span class="pager-info" style="margin-right:12px;"></span>
                    <span class="pager-page" style="margin-right:12px;"></span>
                    <button type="button" class="btn btn-default btn-xs btn-pager-prev">Anterior</button>
                    <button type="button" class="btn btn-default btn-xs btn-pager-next">Siguiente</button>
                </div>

            </div>

            </div><!-- tab rubros y toneladas -->

            <div role="tabpanel" class="tab-pane" id="tabCuadro">
                <div class="exa-ppto-cuadro-periodo" id="cuadroPeriodoBar">
                    <div class="periodo-grp periodo-grp-proy">
                        <label for="cuadro_proy_id">Proyecto</label>
                        <select id="cuadro_proy_id" class="form-control input-sm" title="Proyecto del cuadro presupuestario"></select>
                    </div>
                    <div class="periodo-grp">
                        <label>Vista</label>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-default cuadro-vista-btn active" data-vista="anual">Anual</button>
                            <button type="button" class="btn btn-default cuadro-vista-btn" data-vista="acumulado">Acumulado</button>
                            <button type="button" class="btn btn-default cuadro-vista-btn" data-vista="mes">Mes</button>
                        </div>
                    </div>
                    <div class="periodo-grp" id="cuadroMesWrap" style="display:none;">
                        <label for="cuadro_mes_sel" id="cuadroMesLbl">Hasta mes</label>
                        <select id="cuadro_mes_sel" class="form-control input-sm" style="width:120px;">
                            <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option>
                            <option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option>
                            <option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option>
                            <option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                    </div>
                    <div class="periodo-grp">
                        <label for="cuadro_anio_precio" title="$/Ton con IVA del a&ntilde;o">A&ntilde;o</label>
                        <select id="cuadro_anio_precio" class="form-control input-sm" style="width:120px;"></select>
                    </div>
                    <span class="periodo-lbl" id="cuadroPeriodoLbl"></span>
                    <span class="periodo-lbl" id="cuadroPrecioAnioLbl" style="margin-left:0;"></span>
                    <div class="periodo-actions">
                        <button type="button" class="btn btn-success btn-sm" id="btnExportCuadroExcel" title="Descargar cuadro presupuestario en Excel">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </button>
                    </div>
                </div>
                <div class="exa-ppto-cuadro-kpis">
                    <div class="exa-ppto-cuadro-kpi">
                        <div class="lbl">Grupos principales</div>
                        <div class="val" id="cuadroKpiGrupos">0</div>
                    </div>
                    <div class="exa-ppto-cuadro-kpi">
                        <div class="lbl">Rubros driver</div>
                        <div class="val" id="cuadroKpiRubros">0</div>
                    </div>
                    <div class="exa-ppto-cuadro-kpi">
                        <div class="lbl" title="Base ingresos: 3.500 &times; 30 d">Ton ingresos (mes)</div>
                        <div class="val" id="cuadroKpiTon">-</div>
                    </div>
                    <div class="exa-ppto-cuadro-kpi">
                        <div class="lbl" id="cuadroKpiTotalLbl">Presupuesto anual total</div>
                        <div class="val" id="cuadroKpiTotal">$0.00</div>
                    </div>
                    <div class="exa-ppto-cuadro-kpi">
                        <div class="lbl" id="cuadroKpiTotalMesLbl">Presupuesto mensual total</div>
                        <div class="val val-mes" id="cuadroKpiTotalMes">$0.00</div>
                        <div class="val-sub" id="cuadroKpiTotalMesSub">Anual / 12</div>
                    </div>
                    <div class="exa-ppto-cuadro-kpi">
                        <div class="lbl">$/Ton anual (proyecto)</div>
                        <div class="val" id="cuadroKpiTonAnual">-</div>
                    </div>
                    <div class="exa-ppto-cuadro-kpi">
                        <div class="lbl">$/Ton mensual (proyecto)</div>
                        <div class="val val-mes" id="cuadroKpiTonMes">-</div>
                        <div class="val-sub">Anual / 12</div>
                    </div>
                </div>

                <div class="exa-ppto-escenario-box" id="boxEscenarios">
                    <div class="esc-head">
                        <h5><i class="bi bi-calculator"></i> Escenarios de toneladas</h5>
                        <span class="esc-sub" id="escMesesRealInfo"></span>
                    </div>
                    <div class="esc-selector">
                        <button type="button" class="esc-btn active" data-esc="esperada">
                            <span class="esc-btn-t"><i class="bi bi-file-earmark-text"></i> Base PDF (esperada)</span>
                            <span class="esc-btn-v" id="escTotEsperada">-</span>
                            <span class="esc-btn-s">Gastos anuales</span>
                        </button>
                        <button type="button" class="esc-btn" data-esc="proyectada">
                            <span class="esc-btn-t"><i class="bi bi-graph-up-arrow"></i> Proyectada</span>
                            <span class="esc-btn-v" id="escTotProyectada">-</span>
                            <span class="esc-btn-s">Gastos anuales</span>
                        </button>
                        <button type="button" class="esc-btn" data-esc="real">
                            <span class="esc-btn-t"><i class="bi bi-check2-square"></i> Real (+proyectado)</span>
                            <span class="esc-btn-v" id="escTotReal">-</span>
                            <span class="esc-btn-s">Gastos anuales</span>
                        </button>
                    </div>
                    <div class="esc-resumen" id="escResumenEco">
                        <h6><i class="bi bi-pie-chart"></i> Ingresos vs gastos <span id="escResumenPeriodoTit">(anual)</span></h6>
                        <table class="esc-resumen-table">
                            <thead>
                                <tr>
                                    <th>Concepto</th>
                                    <th class="esc-res-col" data-esc="esperada">Base PDF</th>
                                    <th class="esc-res-col" data-esc="proyectada">Proyectada</th>
                                    <th class="esc-res-col" data-esc="real">Real</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td title="Toneladas para ingresos (base 30 d)" id="escTonRowLbl">Ton anual ingresos</td>
                                    <td class="esc-res-col" data-esc="esperada" id="escTonAn_esperada">-</td>
                                    <td class="esc-res-col" data-esc="proyectada" id="escTonAn_proyectada">-</td>
                                    <td class="esc-res-col" data-esc="real" id="escTonAn_real">-</td>
                                </tr>
                                <tr>
                                    <td>Ingresos</td>
                                    <td class="esc-res-col" data-esc="esperada" id="escIng_esperada">-</td>
                                    <td class="esc-res-col" data-esc="proyectada" id="escIng_proyectada">-</td>
                                    <td class="esc-res-col" data-esc="real" id="escIng_real">-</td>
                                </tr>
                                <tr>
                                    <td>Gastos presup.</td>
                                    <td class="esc-res-col" data-esc="esperada" id="escGas_esperada">-</td>
                                    <td class="esc-res-col" data-esc="proyectada" id="escGas_proyectada">-</td>
                                    <td class="esc-res-col" data-esc="real" id="escGas_real">-</td>
                                </tr>
                                <tr style="background:#f7fafc;">
                                    <td>Utilidad / P&eacute;rdida</td>
                                    <td class="esc-res-col" data-esc="esperada" id="escUtil_esperada">-</td>
                                    <td class="esc-res-col" data-esc="proyectada" id="escUtil_proyectada">-</td>
                                    <td class="esc-res-col" data-esc="real" id="escUtil_real">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="exa-ppto-ajuste-box" id="boxAjusteFinanciero">
                    <h5><i class="bi bi-bank"></i> Ajuste capital y GAD</h5>
                    <div class="ajuste-intro">
                        <div class="ajuste-formula">Final = Base - Capital - GAD</div>
                    </div>

                    <div class="ajuste-dual">
                        <div class="ajuste-card">
                            <h6><i class="bi bi-percent"></i> A. Capital</h6>
                            <div class="ajuste-field">
                                <label for="aj_capital_pct">% sobre ingresos</label>
                                <input type="number" step="0.0001" class="form-control input-sm" id="aj_capital_pct" value="11" />
                            </div>
                            <div class="ajuste-field">
                                <label for="aj_anio_precio">A&ntilde;o precio</label>
                                <input type="number" step="1" class="form-control input-sm" id="aj_anio_precio" value="" readonly title="Segun filtro Ano del cuadro" />
                            </div>
                        </div>
                        <div class="ajuste-card">
                            <h6><i class="bi bi-recycle"></i> B. GAD</h6>
                            <div class="ajuste-field">
                                <label for="aj_gad_objetivo">Compromiso ($)</label>
                                <input type="text" inputmode="decimal" class="form-control input-sm aj-money-input" id="aj_gad_objetivo" value="2,000,000.00" />
                            </div>
                            <div class="ajuste-field">
                                <label for="aj_gad_acum">Amortizado</label>
                                <input type="text" inputmode="decimal" class="form-control input-sm aj-money-input" id="aj_gad_acum" value="0.00" />
                            </div>
                            <div class="ajuste-field">
                                <label for="aj_gad_factor">Factor ($/t)</label>
                                <input type="number" step="0.000001" class="form-control input-sm" id="aj_gad_factor" value="0.1984" />
                            </div>
                            <div class="gad-timeline" id="ajGadTimeline">
                                Tramos: a&ntilde;os 1-4 y 5-8
                            </div>
                        </div>
                    </div>

                    <div class="ajuste-check-row">
                        <label class="chk-main checkbox-inline">
                            <input type="checkbox" id="aj_activo" /> Aplicar presupuesto neto en el cuadro
                        </label>
                        <span class="chk-help">
                            Usa partida final (base &minus; capital &minus; GAD) en el cuadro y en ingresos vs gastos.
                        </span>
                    </div>

                    <div class="ajuste-actions">
                        <button type="button" class="btn btn-default btn-sm" id="btnAjGuardarCfg"><i class="bi bi-save"></i> Guardar config</button>
                        <button type="button" class="btn btn-default btn-sm" id="btnAjPrecios"><i class="bi bi-calendar3"></i> Precios por a&ntilde;o</button>
                        <button type="button" class="btn btn-primary btn-sm" id="btnAjSimular"><i class="bi bi-calculator"></i> Simular</button>
                        <button type="button" class="btn btn-warning btn-sm" id="btnAjAplicar"><i class="bi bi-check2-circle"></i> Aplicar ajuste</button>
                        <button type="button" class="btn btn-link btn-sm" id="btnAjHistorial">Historial</button>
                    </div>

                    <div class="ajuste-kpis-hero" id="ajKpisHero">
                        <div class="ajuste-kpi-hero kpi-rest">
                            <div class="lbl">1) Deducci&oacute;n total del per&iacute;odo</div>
                            <div class="val" id="ajKpiRestaTot">-</div>
                        </div>
                        <div class="ajuste-kpi-hero kpi-final">
                            <div class="lbl">2) Presupuesto neto aplicable</div>
                            <div class="val" id="ajKpiGastoFin">-</div>
                        </div>
                        <div class="ajuste-kpi-hero kpi-util">
                            <div class="lbl">3) Utilidad del gestor (ingresos &minus; base)</div>
                            <div class="val" id="ajKpiUtil">-</div>
                        </div>
                    </div>

                    <div class="ajuste-kpis" id="ajKpis">
                        <div class="ajuste-kpi"><div class="lbl">Precio neto</div><div class="val" id="ajKpiNeto">-</div></div>
                        <div class="ajuste-kpi"><div class="lbl">Capital unitario ($/t)</div><div class="val" id="ajKpiCapTon">-</div></div>
                        <div class="ajuste-kpi"><div class="lbl">Capital del per&iacute;odo</div><div class="val" id="ajKpiCapTot">-</div></div>
                        <div class="ajuste-kpi"><div class="lbl">Recuperaci&oacute;n GAD del per&iacute;odo</div><div class="val" id="ajKpiGad">-</div></div>
                        <div class="ajuste-kpi"><div class="lbl">Saldo pendiente GAD</div><div class="val" id="ajKpiSaldo">-</div></div>
                    </div>

                    <p class="ajuste-table-caption">Distribuci&oacute;n por grupo seg&uacute;n participaci&oacute;n en el presupuesto base</p>
                    <div class="ajuste-dist-wrap">
                        <table class="table table-bordered table-condensed" id="tblAjusteDist">
                            <thead>
                                <tr>
                                    <th>Grupo</th>
                                    <th class="text-right" title="Presupuesto asignado por el municipio">Base asignada</th>
                                    <th class="text-right">Participaci&oacute;n %</th>
                                    <th class="text-right col-menos">&minus; Capital</th>
                                    <th class="text-right col-menos">&minus; GAD</th>
                                    <th class="text-right" title="Presupuesto neto aplicable">Partida final</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot id="tblAjusteDistFoot"></tfoot>
                        </table>
                    </div>
                    <details class="ajuste-tech">
                        <summary>Anexo: factores unitarios por tonelada ($/t)</summary>
                        <div class="table-responsive" style="max-height:220px;overflow:auto;margin-top:8px;">
                            <table class="table table-bordered table-condensed" id="tblAjusteDistTon">
                                <thead>
                                    <tr>
                                        <th>Grupo</th>
                                        <th class="text-right">Base $/t</th>
                                        <th class="text-right">Capital $/t</th>
                                        <th class="text-right">GAD $/t</th>
                                        <th class="text-right">Final $/t</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </details>
                </div>

                <div class="exa-ppto-cuadro-shell">
                <div id="rubrosCuadroHead" class="exa-ppto-cuadro-head-row exa-ppto-cuadro-grid" style="display:none;">
                    <span class="col-grupo">Grupo</span>
                    <span class="col-money" id="cuadroColPresupLbl">Presup. anual</span>
                    <span class="col-money" id="cuadroColPresupMesLbl">Presup. mensual</span>
                    <span class="col-ton">$/Ton anual</span>
                    <span class="col-ton">$/Ton mensual</span>
                    <span class="col-pct-tope">% Tope</span>
                    <span class="col-tope">Tope</span>
                    <span class="col-accion">Rubros/Acci&oacute;n</span>
                </div>
                <div id="rubrosCuadroAccordion" class="panel-group exa-ppto-cuadro-accordion" role="tablist" aria-multiselectable="true"></div>
                </div>
                <div id="rubrosCuadroEmpty" class="exa-ppto-cuadro-empty" style="display:none;">
                    <i class="bi bi-inbox" style="font-size:28px;display:block;margin-bottom:8px;"></i>
                    Seleccione proyecto en la pestana Rubros y toneladas, o agregue rubros alli.
                </div>

            </div><!-- tab cuadro -->

            </div><!-- tab-content -->

        </div>

    </div>

</div>

<div id="modalPublicarPreview" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box" style="width:720px;max-width:96%;">
        <span class="exa-pre-modal-close" id="btnClosePublicarModal">&times;</span>
        <h3 class="exa-adq-section-title" style="margin-top:0;">Vista previa - publicar presupuesto</h3>
        <p class="text-muted" style="font-size:11px;margin:0 0 12px;">Comparaci&oacute;n entre el vigente actual (cuadro) y el monto a publicar desde toneladas proyectadas.</p>
        <div class="pub-kpi">
            <div class="item"><div class="lbl">Vigente actual</div><div class="val" id="pubPrevVigente">$0.00</div></div>
            <div class="item"><div class="lbl">A publicar (proyectado)</div><div class="val" id="pubPrevNuevo" style="color:#276749;">$0.00</div></div>
            <div class="item"><div class="lbl">Diferencia</div><div class="val" id="pubPrevDelta">$0.00</div></div>
            <div class="item"><div class="lbl">Ton proyectada (a&ntilde;o)</div><div class="val" id="pubPrevTon">0</div></div>
        </div>
        <div id="pubPrevWarnings" class="alert alert-warning" style="display:none;font-size:11px;padding:8px 10px;"></div>
        <div class="exa-adq-table-wrap" style="max-height:280px;overflow:auto;">
            <table class="table table-condensed table-bordered exa-adq-table" style="font-size:11px;">
                <thead><tr><th>Partida</th><th>Rubro</th><th class="text-right">Vigente</th><th class="text-right">Publicar</th><th class="text-right">Delta</th></tr></thead>
                <tbody id="pubPrevTbody"></tbody>
            </table>
        </div>
        <div style="margin-top:12px;text-align:right;">
            <button type="button" class="btn btn-default btn-sm" id="btnCancelPublicarModal">Cerrar</button>
            <button type="button" class="btn btn-success btn-sm" id="btnConfirmPublicar"><i class="bi bi-check2"></i> Confirmar publicaci&oacute;n</button>
        </div>
    </div>
</div>

<div id="modalAjustePrecios" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box" style="width:520px;max-width:96%;">
        <span class="exa-pre-modal-close" id="btnCloseAjustePrecios">&times;</span>
        <h3 class="exa-adq-section-title" style="margin-top:0;">Proyecci&oacute;n de precios $/Ton con IVA</h3>
        <p class="text-muted" style="font-size:11px;">Si un a&ntilde;o no tiene precio, se usa la tarifa de la versi&oacute;n. El costo de capital se calcula sobre el precio neto del a&ntilde;o.</p>
        <div class="table-responsive">
            <table class="table table-bordered table-condensed" id="tblAjustePrecios">
                <thead><tr><th>A&ntilde;o</th><th>$/Ton con IVA</th><th></th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <div style="margin-top:8px;">
            <button type="button" class="btn btn-default btn-xs" id="btnAjAddPrecio">+ A&ntilde;o</button>
            <button type="button" class="btn btn-default btn-xs" id="btnAjSeedPrecios">Cargar ejemplo 8 a&ntilde;os</button>
        </div>
        <div style="margin-top:12px;text-align:right;">
            <button type="button" class="btn btn-default btn-sm" id="btnCancelAjustePrecios">Cerrar</button>
            <button type="button" class="btn btn-success btn-sm" id="btnSaveAjustePrecios">Guardar precios</button>
        </div>
    </div>
</div>

<div id="modalAjusteHistorial" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box" style="width:860px;max-width:96%;">
        <span class="exa-pre-modal-close" id="btnCloseAjusteHistorial">&times;</span>
        <h3 class="exa-adq-section-title" style="margin-top:0;">Historial de ajustes financieros</h3>
        <div class="table-responsive" style="max-height:360px;overflow:auto;">
            <table class="table table-bordered table-condensed" id="tblAjusteHistorial">
                <thead>
                    <tr>
                        <th>ID</th><th>Fecha</th><th>Usuario</th><th>Escenario</th><th>Vista</th>
                        <th class="text-right">Capital</th><th class="text-right">GAD</th>
                        <th class="text-right">Acum. GAD</th><th class="text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div style="margin-top:12px;text-align:right;">
            <button type="button" class="btn btn-default btn-sm" id="btnCancelAjusteHistorial">Cerrar</button>
        </div>
    </div>
</div>

<div id="modalPartidaRubro" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box" style="width:480px;max-width:96%;">
        <span class="exa-pre-modal-close" id="btnClosePartidaRubroModal">&times;</span>
        <h3 class="exa-adq-section-title" style="margin-top:0;" id="modal_partida_rubro_titulo">Nueva partida</h3>
        <p class="text-muted" style="font-size:11px;margin:0 0 12px;" id="modal_partida_rubro_ayuda"></p>
        <input type="hidden" id="modal_partida_rubro_tipo" value="" />
        <input type="hidden" id="modal_partida_rubro_padre_id" value="" />
        <input type="hidden" id="modal_partida_rubro_clase" value="G" />
        <div class="form-group" style="margin-bottom:10px;">
            <label style="font-size:11px;font-weight:600;">Codigo</label>
            <input type="text" id="modal_partida_rubro_codigo" class="form-control input-sm" placeholder="Ej. 05.01.01" />
        </div>
        <div class="form-group" style="margin-bottom:10px;">
            <label style="font-size:11px;font-weight:600;">Descripcion</label>
            <input type="text" id="modal_partida_rubro_descripcion" class="form-control input-sm" placeholder="Nombre de la partida" />
        </div>
        <div class="exa-pre-form-actions" style="margin-top:16px;">
            <button type="button" class="btn btn-default btn-sm" id="btnCancelPartidaRubroModal">Cancelar</button>
            <button type="button" class="btn btn-success btn-sm" id="btnSavePartidaRubroModal"><i class="bi bi-plus-lg"></i> Crear partida</button>
        </div>
    </div>
</div>

<div id="modalEditRubro" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box" style="width:760px;max-width:96%;">
        <span class="exa-pre-modal-close" id="btnCloseEditRubroModal">&times;</span>
        <h3 class="exa-adq-section-title" style="margin-top:0;" id="modal_rubro_titulo">Rubro</h3>
        <p class="text-muted" style="font-size:11px;margin-bottom:10px;display:none;" id="modal_edit_rubro_resumen"></p>
        <input type="hidden" id="modal_edit_pdp_id" value="0" />
        <input type="hidden" id="modal_edit_ppa_id" value="0" />
        <input type="hidden" id="modal_edit_grupo_ppa_id" value="0" />
        <input type="hidden" id="modal_edit_meses_inicial" value="12" />
        <input type="hidden" id="pdp_rubro_nombre" value="" />
        <div id="modal_rubro_partida_block" style="margin-bottom:12px;">
            <p class="exa-ppto-rubro-form-help" style="font-size:11px;margin:0 0 8px;display:none;">Partida de detalle del cat&aacute;logo.</p>
            <div class="row exa-ppto-rubro-form-row">
                <div class="col-sm-4"><label style="font-size:11px;">Grupo principal</label><div class="exa-ppto-sel-with-btn"><select id="rub_grupo_cod" class="form-control input-sm"><option value="">-- Grupo --</option></select><button type="button" class="btn btn-default btn-xs btn-add-partida" data-partida-tipo="grupo" title="Nuevo grupo principal">+</button></div></div>
                <div class="col-sm-4"><label style="font-size:11px;">Subgrupo</label><div class="exa-ppto-sel-with-btn"><select id="rub_subgrupo_cod" class="form-control input-sm" disabled><option value="">-- Subgrupo --</option></select><button type="button" class="btn btn-default btn-xs btn-add-partida" data-partida-tipo="subgrupo" title="Nuevo subgrupo">+</button></div></div>
                <div class="col-sm-4"><label style="font-size:11px;">Partida detalle</label><div class="exa-ppto-sel-with-btn"><select id="rub_ppa_id" class="form-control input-sm" disabled><option value="">-- Detalle --</option></select><button type="button" class="btn btn-default btn-xs btn-add-partida" data-partida-tipo="detalle" title="Nueva partida detalle">+</button></div></div>
            </div>
            <div id="rubro_partida_resumen" class="exa-ppto-rubro-resumen" style="margin-top:6px;"></div>
        </div>
        <div class="row" style="margin-bottom:8px;">
            <div class="col-sm-4"><label class="control-label" style="font-size:11px;">Tn/d&iacute;a</label><input type="text" id="modal_edit_tn_dia" class="form-control input-sm exa-ppto-readonly" readonly /></div>
            <div class="col-sm-4"><label class="control-label" style="font-size:11px;">D&iacute;as</label><input type="text" id="modal_edit_dias" class="form-control input-sm exa-ppto-readonly" readonly /></div>
            <div class="col-sm-4"><label class="control-label" style="font-size:11px;" title="3.500 &times; 22 d">Ton/mes costo</label><input type="text" id="modal_edit_ton_mens" class="form-control input-sm exa-ppto-readonly" readonly /></div>
        </div>
        <div class="row" style="margin-bottom:8px;">
            <div class="col-sm-4"><label class="control-label" style="font-size:11px;">$/Ton anual</label><input type="number" step="0.0000001" min="0" id="modal_edit_factor_anual" class="form-control input-sm" /></div>
            <div class="col-sm-4"><label class="control-label" style="font-size:11px;">$/Ton mensual</label><input type="text" id="modal_edit_factor_mensual" class="form-control input-sm exa-ppto-readonly" readonly /></div>
            <div class="col-sm-4"><label class="control-label" style="font-size:11px;">Monto recalc.</label><input type="text" id="modal_edit_monto_recalc" class="form-control input-sm exa-ppto-readonly" readonly /></div>
        </div>
        <div class="row" style="margin-bottom:8px;">
            <div class="col-sm-4"><label class="control-label" style="font-size:11px;">Meses</label><input type="number" min="1" max="999" step="1" id="modal_edit_meses" class="form-control input-sm" title="Meses de vigencia del rubro (prorrateo)" /></div>
            <div class="col-sm-4"><label class="control-label" style="font-size:11px;">Presup. anual</label><input type="text" id="modal_edit_presup_anual" class="form-control input-sm exa-ppto-readonly" readonly /></div>
            <div class="col-sm-4"><label class="control-label" style="font-size:11px;">Presup. mensual</label><input type="text" id="modal_edit_presup_mensual" class="form-control input-sm exa-ppto-readonly" readonly /></div>
        </div>
        <div class="exa-pre-form-actions" style="margin-top:14px;">
            <button type="button" class="btn btn-default btn-sm" id="btnCancelEditRubroModal">Cancelar</button>
            <button type="button" class="btn btn-primary btn-sm" id="btnSaveEditRubroModal"><i class="bi bi-check-lg"></i> Guardar</button>
        </div>
    </div>
</div>

<div id="modalGrupoResumen" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box">
        <span class="exa-pre-modal-close" id="btnCloseGrupoResumenModal">&times;</span>
        <h3 class="exa-adq-section-title" style="margin-top:0;" id="grupo_resumen_titulo">Resumen del grupo</h3>
        <p class="text-muted" style="font-size:12px;margin-bottom:10px;" id="grupo_resumen_subtitulo"></p>
        <div class="grupo-resumen-kpi" id="grupo_resumen_kpi"></div>
        <div class="exa-adq-table-wrap">
            <table class="table table-bordered table-hover exa-adq-table modal-table">
                <thead><tr>
                    <th>C&oacute;digo</th>
                    <th>Descripci&oacute;n</th>
                    <th class="text-right" title="Driver egresos Excel">Ton/mes costo</th>
                    <th class="text-right">$/Ton anual</th>
                    <th class="text-right">$/Ton mensual</th>
                    <th class="text-right">Presup. anual</th>
                    <th class="text-right">Presup. mensual</th>
                </tr></thead>
                <tbody id="grupo_resumen_tbody"></tbody>
                <tfoot id="grupo_resumen_tfoot"></tfoot>
            </table>
        </div>
    </div>
</div>

<div id="modalPdfPreview" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box">
        <span class="exa-pre-modal-close" id="btnClosePdfModal">&times;</span>
        <h3 class="exa-adq-section-title" style="margin-top:0;">Vista previa importacion (PDF / Excel)</h3>
        <p class="text-muted" style="font-size:12px;" id="pdf_preview_resumen"></p>
        <div id="pdf_preview_catalogo_aviso" class="alert alert-warning" style="display:none;font-size:12px;margin:8px 0;padding:8px 12px;"></div>
        <div id="pdf_preview_decision" class="exa-pre-form-panel" style="display:none;margin:10px 0 12px;padding:12px 14px;background:#f7fafc;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;">
            <strong style="display:block;margin-bottom:8px;color:#2d3748;">Decision de importacion (catalogo compartido)</strong>
            <div class="row" style="margin:0 -6px 8px;">
                <div class="col-xs-4" style="padding:0 6px;"><div style="background:#fff;border:1px solid #cbd5e0;border-radius:4px;padding:8px;text-align:center;"><div class="text-muted" style="font-size:10px;">YA EXISTEN</div><div id="pdf_dec_existentes" style="font-size:18px;font-weight:700;color:#2b6cb0;">0</div></div></div>
                <div class="col-xs-4" style="padding:0 6px;"><div style="background:#fff;border:1px solid #cbd5e0;border-radius:4px;padding:8px;text-align:center;"><div class="text-muted" style="font-size:10px;">NUEVAS</div><div id="pdf_dec_nuevas" style="font-size:18px;font-weight:700;color:#276749;">0</div></div></div>
                <div class="col-xs-4" style="padding:0 6px;"><div style="background:#fff;border:1px solid #cbd5e0;border-radius:4px;padding:8px;text-align:center;"><div class="text-muted" style="font-size:10px;">NOMBRE DISTINTO</div><div id="pdf_dec_nombre" style="font-size:18px;font-weight:700;color:#c05621;">0</div></div></div>
            </div>
            <label style="display:flex;align-items:flex-start;gap:8px;font-weight:normal;margin:0 0 6px;cursor:pointer;">
                <input type="checkbox" id="pdf_opt_crear_nuevas" checked="checked" style="margin-top:2px;" />
                <span><strong>Crear partidas nuevas</strong> del archivo que no esten en el catalogo. Si lo desmarca, solo se importan montos de codigos ya existentes.</span>
            </label>
            <label style="display:flex;align-items:flex-start;gap:8px;font-weight:normal;margin:0;cursor:pointer;">
                <input type="checkbox" id="pdf_opt_actualizar_nombres" style="margin-top:2px;" />
                <span><strong>Actualizar nombres del catalogo</strong> cuando el archivo traiga otro texto (afecta a todos los proyectos). Por defecto se conserva el nombre del catalogo.</span>
            </label>
        </div>
        <div id="pdf_preview_warnings" class="alert alert-warning" style="display:none;font-size:12px;"></div>
        <div id="pdf_preview_conflictos" class="alert alert-danger" style="display:none;font-size:12px;"></div>
        <div class="exa-adq-table-wrap exa-ppto-pdf-preview">
            <table class="table table-bordered exa-adq-table modal-table">
                <thead><tr>
                    <th>Codigo</th>
                    <th>Descripcion</th>
                    <th>Clase</th>
                    <th>Estado</th>
                    <th class="text-right">ANUAL PDF</th>
                    <th class="text-right">Tn/d&iacute;a</th>
                    <th class="text-right">D&iacute;as</th>
                    <th class="text-right" title="Base driver de egresos (22 d&iacute;as laborables)">Ton/mens costo</th>
                    <th class="text-right">$/Ton anual</th>
                    <th class="text-right" title="Calculado: $/Ton anual / 12">$/Ton mensual</th>
                    <th class="text-right">Monto recalc.</th>
                    <th class="text-right" title="Meses de vigencia para todas las filas">
                        Meses
                        <input type="number" id="pdf_preview_meses_global" class="form-control input-sm pdf-preview-meses-global" min="1" max="999" step="1" value="12" title="Escriba aqui; se aplica a todas las filas" />
                    </th>
                    <th class="text-right" title="Presup. anual / 12">Presup. mensual</th>
                    <th class="text-right" title="Monto recalc. / (Meses / 12)">Presup. anual</th>
                </tr></thead>
                <tbody id="pdf_preview_tbody"></tbody>
                <tfoot id="pdf_preview_tfoot"></tfoot>
            </table>
        </div>
        <div class="exa-pre-form-actions" style="margin-top:12px;">
            <button type="button" class="btn btn-default btn-sm" id="btnCancelPdfModal">Cancelar</button>
            <button type="button" class="btn btn-success btn-sm" id="btnConfirmImportPdf"><i class="bi bi-check-lg"></i> Confirmar importacion</button>
        </div>
    </div>
</div>


<!-- JS partido: nucleo + rubros al inicio; import diferido (no bloquea primera pintura) -->
<script src="../VALIDACIONES/ppto_proyectos_core.js?v=20260808f"></script>
<script src="../VALIDACIONES/ppto_proyectos_rubros.js?v=20260808f"></script>
<script>
(function(){
  function loadImportJs() {
    if (window.__pptoImportJsLoaded || window.__pptoImportJsLoading) return;
    if (typeof window.ensurePptoImportJs === 'function') {
      window.ensurePptoImportJs();
      return;
    }
    window.__pptoImportJsLoading = true;
    var s = document.createElement('script');
    s.src = '../VALIDACIONES/ppto_proyectos_import.js?v=20260808b';
    s.onload = function() {
      window.__pptoImportJsLoaded = true;
      window.__pptoImportJsLoading = false;
    };
    s.onerror = function() { window.__pptoImportJsLoading = false; };
    document.body.appendChild(s);
  }
  if (window.addEventListener) {
    window.addEventListener('load', function(){ setTimeout(loadImportJs, 80); });
  } else {
    setTimeout(loadImportJs, 400);
  }
})();
</script>


</body>

</html>


