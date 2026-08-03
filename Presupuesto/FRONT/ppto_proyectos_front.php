<?php require_once('../../administrador/LOGICA/seguridad.php'); ?>

<!DOCTYPE html>

<html lang="es" class="exa-ui-fill-root">

<head>

    <meta charset="UTF-8">

    <title>Proyectos Presupuestarios - EXA</title>

    <?php require_once dirname(dirname(__DIR__)) . '/contabilidad/FRONT/con_model3_assets.php'; ?>

    <script src="../VALIDACIONES/ppto_format.js"></script>

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
            padding: 14px 16px;
            background: #fffaf0;
            border: 1px solid #f6e05e;
            border-radius: 8px;
        }
        .exa-ppto-ajuste-box h5 {
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: 700;
            color: #744210;
        }
        .exa-ppto-ajuste-box .ajuste-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 14px;
            margin-bottom: 10px;
        }
        .exa-ppto-ajuste-box .ajuste-field {
            min-width: 140px;
            flex: 1;
        }
        .exa-ppto-ajuste-box .ajuste-field label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            color: #744210;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .exa-ppto-ajuste-box .ajuste-kpis {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 8px 0 12px;
        }
        .exa-ppto-ajuste-box .ajuste-kpi {
            flex: 1;
            min-width: 120px;
            background: #fff;
            border: 1px solid #faf089;
            border-radius: 6px;
            padding: 8px 10px;
        }
        .exa-ppto-ajuste-box .ajuste-kpi .lbl { font-size: 9px; text-transform: uppercase; color: #975a16; font-weight: 600; }
        .exa-ppto-ajuste-box .ajuste-kpi .val { font-size: 14px; font-weight: 700; color: #744210; }
        .exa-ppto-ajuste-box .table { font-size: 11px; margin-bottom: 0; background: #fff; }
        .exa-ppto-ajuste-box .ajuste-actions { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .exa-ppto-ajuste-box .ajuste-hint { font-size: 11px; color: #975a16; margin: 0; }
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

        <h3 class="panel-title"><i class="bi bi-folder2-open"></i> Proyectos Presupuestarios</h3>

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

                <div class="row">

                    <div class="col-md-2"><label>Codigo</label><input id="proy_id" class="form-control input-sm" /></div>

                    <div class="col-md-4"><label>Nombre</label><input id="proy_nombre" class="form-control input-sm" /></div>

                    <div class="col-md-2"><label>Estado</label><select id="proy_estado" class="form-control input-sm"><option value="A">Activo</option><option value="I">Inactivo</option></select></div>

                    <div class="col-md-3"><label>Plantilla</label><select id="plt_id" class="form-control input-sm"></select></div>

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

                    <div class="col-md-2"><label>Version</label><select id="rub_ppe_id" class="form-control input-sm"></select></div>

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

            </div>

            </div><!-- tab rubros y toneladas -->

            <div role="tabpanel" class="tab-pane" id="tabCuadro">
                <div class="exa-ppto-cuadro-periodo" id="cuadroPeriodoBar">
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
                        <label for="cuadro_anio_precio" title="Anio de proyeccion: define el $/Ton con IVA usado en ingresos y costo de capital">A&ntilde;o proyecci&oacute;n</label>
                        <select id="cuadro_anio_precio" class="form-control input-sm" style="width:110px;"></select>
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
                            <span class="esc-btn-v" id="escTotEsperada">—</span>
                            <span class="esc-btn-s">Gastos anuales</span>
                        </button>
                        <button type="button" class="esc-btn" data-esc="proyectada">
                            <span class="esc-btn-t"><i class="bi bi-graph-up-arrow"></i> Proyectada</span>
                            <span class="esc-btn-v" id="escTotProyectada">—</span>
                            <span class="esc-btn-s">Gastos anuales</span>
                        </button>
                        <button type="button" class="esc-btn" data-esc="real">
                            <span class="esc-btn-t"><i class="bi bi-check2-square"></i> Real (+proyectado)</span>
                            <span class="esc-btn-v" id="escTotReal">—</span>
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
                                    <td class="esc-res-col" data-esc="esperada" id="escTonAn_esperada">—</td>
                                    <td class="esc-res-col" data-esc="proyectada" id="escTonAn_proyectada">—</td>
                                    <td class="esc-res-col" data-esc="real" id="escTonAn_real">—</td>
                                </tr>
                                <tr>
                                    <td>Ingresos</td>
                                    <td class="esc-res-col" data-esc="esperada" id="escIng_esperada">—</td>
                                    <td class="esc-res-col" data-esc="proyectada" id="escIng_proyectada">—</td>
                                    <td class="esc-res-col" data-esc="real" id="escIng_real">—</td>
                                </tr>
                                <tr>
                                    <td>Gastos presup.</td>
                                    <td class="esc-res-col" data-esc="esperada" id="escGas_esperada">—</td>
                                    <td class="esc-res-col" data-esc="proyectada" id="escGas_proyectada">—</td>
                                    <td class="esc-res-col" data-esc="real" id="escGas_real">—</td>
                                </tr>
                                <tr style="background:#f7fafc;">
                                    <td>Utilidad / P&eacute;rdida</td>
                                    <td class="esc-res-col" data-esc="esperada" id="escUtil_esperada">—</td>
                                    <td class="esc-res-col" data-esc="proyectada" id="escUtil_proyectada">—</td>
                                    <td class="esc-res-col" data-esc="real" id="escUtil_real">—</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="exa-ppto-ajuste-box" id="boxAjusteFinanciero">
                    <h5><i class="bi bi-bank"></i> Ajustes financieros (costo de capital + recuperaci&oacute;n GAD)</h5>
                    <p class="ajuste-hint">Se calculan sobre el escenario activo de arriba. La partida base no se sobrescribe: partida final = base &minus; capital &minus; GAD.</p>
                    <div class="ajuste-grid">
                        <div class="ajuste-field">
                            <label>Costo capital %</label>
                            <input type="number" step="0.0001" class="form-control input-sm" id="aj_capital_pct" value="11" />
                        </div>
                        <div class="ajuste-field">
                            <label>Factor GAD $/t</label>
                            <input type="number" step="0.000001" class="form-control input-sm" id="aj_gad_factor" value="0.1984" />
                        </div>
                        <div class="ajuste-field">
                            <label>Objetivo GAD</label>
                            <input type="number" step="0.01" class="form-control input-sm" id="aj_gad_objetivo" value="2000000" />
                        </div>
                        <div class="ajuste-field">
                            <label>GAD recuperado acum.</label>
                            <input type="number" step="0.01" class="form-control input-sm" id="aj_gad_acum" value="0" />
                        </div>
                        <div class="ajuste-field">
                            <label>A&ntilde;o precio (sincronizado)</label>
                            <input type="number" step="1" class="form-control input-sm" id="aj_anio_precio" value="" readonly title="Se controla con Ano proyeccion arriba" />
                        </div>
                        <div class="ajuste-field" style="min-width:180px;">
                            <label>Usar partida final en cuadro</label>
                            <label class="checkbox-inline" style="margin-top:4px;">
                                <input type="checkbox" id="aj_activo" /> Activar
                            </label>
                        </div>
                    </div>
                    <div class="ajuste-actions" style="margin-top:0;margin-bottom:8px;">
                        <button type="button" class="btn btn-default btn-sm" id="btnAjGuardarCfg"><i class="bi bi-save"></i> Guardar config</button>
                        <button type="button" class="btn btn-default btn-sm" id="btnAjPrecios"><i class="bi bi-calendar3"></i> Precios por a&ntilde;o</button>
                        <button type="button" class="btn btn-primary btn-sm" id="btnAjSimular"><i class="bi bi-calculator"></i> Simular</button>
                        <button type="button" class="btn btn-warning btn-sm" id="btnAjAplicar"><i class="bi bi-check2-circle"></i> Aplicar ajuste</button>
                        <button type="button" class="btn btn-link btn-sm" id="btnAjHistorial">Historial</button>
                    </div>
                    <div class="ajuste-kpis" id="ajKpis">
                        <div class="ajuste-kpi"><div class="lbl">Precio neto</div><div class="val" id="ajKpiNeto">—</div></div>
                        <div class="ajuste-kpi"><div class="lbl">Capital $/t</div><div class="val" id="ajKpiCapTon">—</div></div>
                        <div class="ajuste-kpi"><div class="lbl">Capital per&iacute;odo</div><div class="val" id="ajKpiCapTot">—</div></div>
                        <div class="ajuste-kpi"><div class="lbl">GAD per&iacute;odo</div><div class="val" id="ajKpiGad">—</div></div>
                        <div class="ajuste-kpi"><div class="lbl">Saldo GAD</div><div class="val" id="ajKpiSaldo">—</div></div>
                        <div class="ajuste-kpi"><div class="lbl">Gasto final</div><div class="val" id="ajKpiGastoFin">—</div></div>
                        <div class="ajuste-kpi"><div class="lbl">Utilidad base</div><div class="val" id="ajKpiUtil">—</div></div>
                    </div>
                    <div class="table-responsive" style="max-height:320px;overflow:auto;">
                        <table class="table table-bordered table-condensed" id="tblAjusteDist">
                            <thead>
                                <tr>
                                    <th>Grupo</th>
                                    <th class="text-right">Base</th>
                                    <th class="text-right">%</th>
                                    <th class="text-right">Base $/t</th>
                                    <th class="text-right">Capital $/t</th>
                                    <th class="text-right">GAD $/t</th>
                                    <th class="text-right">Final $/t</th>
                                    <th class="text-right">Partida final</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
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
                    Seleccione proyecto y version en la pestana Rubros y toneladas, o agregue rubros alli.
                </div>

            </div><!-- tab cuadro -->

            </div><!-- tab-content -->

        </div>

    </div>

</div>

<div id="modalPublicarPreview" class="exa-pre-modal-overlay" style="display:none;">
    <div class="exa-pre-modal-box" style="width:720px;max-width:96%;">
        <span class="exa-pre-modal-close" id="btnClosePublicarModal">&times;</span>
        <h3 class="exa-adq-section-title" style="margin-top:0;">Vista previa — publicar presupuesto</h3>
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
                        <th>ID</th><th>Fecha</th><th>Escenario</th><th>Vista</th>
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

<script>

var API = '../LOGICA/ppto_proyectos_logica.php';

var partidasRubro = [];
var toastHideTimer = null;
var partidasGrupo = [];

var tonBaseProy = 0;

var pdfImportPayload = null;
var pdfImportConflictos = [];
var pdfImportPreviewTon = 0;
var rubrosCache = [];
var gruposTopeCache = {};
var escenarioActivo = 'esperada';
var escenarioMesesReal = 0;
var ajusteSimCache = null;
var ajusteCfgCache = null;
var ajustePreciosCache = [];
var cuadroAnioPrecio = 0;
var escenariosTonMes = { esperada: 0, proyectada: 0, real: 0 };
var escenariosTonAnual = { esperada: 0, proyectada: 0, real: 0 };
var escenariosTonPeriodo = { esperada: 0, proyectada: 0, real: 0 };
var escenariosIngreso = { esperada: 0, proyectada: 0, real: 0 };
var ingresoCfg = { tarifa: 3, iva: 1.15, factor_precio_gasto: 1 };
var cuadroVista = 'anual';
var cuadroMes = (new Date()).getMonth() + 1;
var cuadroMesDefault = cuadroMes;
var cuadroPeriodoLabel = 'Anual completo';
var ESC_LABEL = { esperada: 'Base PDF (esperada)', proyectada: 'Proyectada', real: 'Real (+proyectado)' };
var MESES_NOM = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

function factorPrecioGastoActivo() {
  var f = parseFloat(ingresoCfg.factor_precio_gasto);
  return (f > 0) ? f : 1;
}

function rubroEscEsperadaActual(x) {
  var fac = parseFloat(x && x.pdp_factor_anual_tonelada) || 0;
  var ton = tonCostoActivo();
  if (fac > 0.0001 && ton > 0) {
    return ton * fac;
  }
  return parseFloat(x && x.pdp_presupuesto_anual) || 0;
}

function rubroMontoFijoPeriodo(anual) {
  anual = parseFloat(anual) || 0;
  if (cuadroVista === 'anual') return anual;
  if (cuadroVista === 'mes') return anual / 12;
  return anual * (cuadroMes / 12);
}

function rubroAnualPorEscenario(x, esc) {
  var fac = parseFloat(x && x.pdp_factor_anual_tonelada) || 0;
  var fPrecio = factorPrecioGastoActivo();
  if (fac > 0.0001) {
    var espAnual = rubroEscEsperadaActual(x);
    var tonEspAnual = parseFloat(escenariosTonAnual.esperada) || 0;
    var tonEsp = (cuadroVista === 'anual')
      ? tonEspAnual
      : (parseFloat(escenariosTonPeriodo.esperada) || 0);
    var tonEsc = (cuadroVista === 'anual')
      ? (parseFloat(escenariosTonAnual[esc]) || 0)
      : (parseFloat(escenariosTonPeriodo[esc]) || 0);
    var espBase = (cuadroVista === 'anual' || tonEspAnual <= 0.0001)
      ? espAnual
      : espAnual * (tonEsp / tonEspAnual);
    // Proyecta gastos con el mismo factor del PVP vs anio base (~12% margen)
    if (esc === 'esperada') {
      return espBase * fPrecio;
    }
    if (tonEsp > 0.0001) {
      return espBase * (tonEsc / tonEsp) * fPrecio;
    }
  }
  var key = 'esc_' + esc;
  if (x && x[key] !== undefined && x[key] !== null && x[key] !== '') {
    return parseFloat(x[key]) || 0;
  }
  return rubroMontoFijoPeriodo(x.pdp_presupuesto_anual) * fPrecio;
}

function rubroAnualEscenario(x) {
  return rubroAnualPorEscenario(x, escenarioActivo);
}

/** Presupuesto anual fijo del Excel/PDF (referencia historica). */
function rubroAnualBasePdf(x) {
  return parseFloat(x && x.pdp_presupuesto_anual) || 0;
}

function rubroFactorAnualEscenario(x) {
  if (escenarioActivo === 'esperada') {
    return parseFloat(x.pdp_factor_anual_tonelada) || 0;
  }
  var escAn = rubroAnualEscenario(x);
  var tonAn = (cuadroVista === 'anual')
    ? (parseFloat(escenariosTonAnual[escenarioActivo]) || 0)
    : (parseFloat(escenariosTonPeriodo[escenarioActivo]) || 0);
  if (escAn > 0 && tonAn > 0) {
    return escAn / tonAn;
  }
  return parseFloat(x.pdp_factor_anual_tonelada) || 0;
}

function rubroTonMesCosto(x) {
  if (escenarioActivo === 'esperada') {
    return tonCostoActivo();
  }
  var tonRubro = normalizarTonMesRubro(x && x.pdp_toneladas_base);
  if (tonRubro > 0) {
    return tonRubro;
  }
  return tonCostoActivo();
}

function tonMesActivo() {
  var t = parseFloat(escenariosTonMes[escenarioActivo]) || 0;
  if (t > 0) return t;
  return tonBaseProy > 0 ? tonBaseProy : 0;
}

function normalizarTonMesRubro(ton) {
  ton = parseFloat(ton) || 0;
  var oper = pdfPreviewTonMensCalc(RUBRO_EDIT_TN_DIA, PDF_PREVIEW_DIAS_FIJO);
  if (ton <= 0 || Math.abs(ton - 105000) < 0.01) {
    return oper;
  }
  return ton;
}

function tonBaseVersionMes(ton) {
  ton = parseFloat(ton) || 0;
  return ton > 0 ? ton : 0;
}

function rubroTonMesEscenario(x) {
  return rubroTonMesCosto(x);
}



function hideToast() {
  if (toastHideTimer) {
    clearTimeout(toastHideTimer);
    toastHideTimer = null;
  }
  $('#msg').stop(true, true).fadeOut(180);
}

function scheduleToastHide(ms) {
  if (toastHideTimer) clearTimeout(toastHideTimer);
  toastHideTimer = setTimeout(function() {
    toastHideTimer = null;
    $('#msg').fadeOut(180);
  }, ms || 4000);
}

function toast(msg, ok){

  var m = $('#msg');

  if (toastHideTimer) {
    clearTimeout(toastHideTimer);
    toastHideTimer = null;
  }
  m.stop(true, true).removeClass('alert-success alert-danger alert-warning')
    .addClass('exa-ppto-toast-float alert-' + (ok ? 'success' : 'danger'))
    .text(msg).fadeIn(120);
  scheduleToastHide(ok ? 2500 : 3500);

}

function toastWarn(msg){

  var m = $('#msg');

  if (toastHideTimer) {
    clearTimeout(toastHideTimer);
    toastHideTimer = null;
  }
  m.stop(true, true).removeClass('alert-success alert-danger')
    .addClass('exa-ppto-toast-float alert-warning')
    .text(msg).fadeIn(120);
  scheduleToastHide(4000);

}

function factorMensual(anual){

  var a = parseFloat(anual);

  if (isNaN(a)) return 0;

  return a / 12;

}

function presupuestoMensual(anual) {
  var a = parseFloat(anual) || 0;
  if (cuadroVista === 'mes') {
    return a;
  }
  if (cuadroVista === 'acumulado') {
    return a / Math.max(1, cuadroMes);
  }
  return factorMensual(a);
}

function sumRubrosPresupMensual(rows) {
  var sum = 0;
  $.each(rows || [], function(i, x) {
    sum += presupuestoMensual(rubroAnualEscenario(x));
  });
  return sum;
}

function grupoMesesProrrateo(grupoCod) {
  var info = grupoTopeInfo(grupoCod);
  var m = info && info.meses_prorrateo ? parseInt(info.meses_prorrateo, 10) : 12;
  return (m > 0) ? m : 12;
}

function presupuestoMensualGrupo(anual, grupoCod) {
  var meses = grupoMesesProrrateo(grupoCod);
  var a = parseFloat(anual) || 0;
  return meses > 0 ? (a / meses) : 0;
}

function factorMensualGrupo(facAnual, grupoCod) {
  var meses = grupoMesesProrrateo(grupoCod);
  var f = parseFloat(facAnual) || 0;
  return meses > 0 ? (f / meses) : 0;
}

function buscarPartidaGrupoPorCod(cod) {
  var found = null;
  $.each(partidasGrupo, function(i, p) {
    if (p.ppa_codigo_clasificacion === cod) found = p;
  });
  return found;
}

function reloadCatalogosPartidas(cb) {
  $.getJSON(API, {action:'catalogos'}, function(r) {
    partidasRubro = r.partidas || [];
    partidasGrupo = r.partidas_grupo || [];
    fillPartidasRubro();
    if (cb) cb();
  });
}

function abrirModalPartidaRubro(tipo) {
  var padreId = '';
  var clase = 'G';
  var titulo = 'Nuevo grupo principal';
  var ayuda = 'Capitulo de nivel 1 (ej. 05). Clase Grupo.';
  var grupoCod = $('#rub_grupo_cod').val();
  var subgrupoCod = $('#rub_subgrupo_cod').val();

  if (tipo === 'subgrupo') {
    if (!grupoCod) {
      toast('Seleccione primero un grupo principal.', false);
      return;
    }
    var g = buscarPartidaGrupoPorCod(grupoCod);
    if (!g || !g.ppa_id) {
      toast('No se encontro el grupo ' + grupoCod + ' en el catalogo.', false);
      return;
    }
    padreId = g.ppa_id;
    titulo = 'Nuevo subgrupo bajo ' + grupoCod;
    ayuda = 'Subgrupo intermedio (ej. 05.01). Clase Grupo.';
  } else if (tipo === 'detalle') {
    clase = 'D';
    var subs = listSubgrupos(grupoCod);
    var padre = null;
    if (subs.length > 0) {
      if (!subgrupoCod) {
        toast('Seleccione primero el subgrupo.', false);
        return;
      }
      padre = buscarPartidaGrupoPorCod(subgrupoCod);
    } else {
      if (!grupoCod) {
        toast('Seleccione primero el grupo principal.', false);
        return;
      }
      padre = buscarPartidaGrupoPorCod(grupoCod);
    }
    if (!padre || !padre.ppa_id) {
      toast('No se encontro la partida contenedora en el catalogo.', false);
      return;
    }
    padreId = padre.ppa_id;
    titulo = 'Nueva partida detalle';
    ayuda = 'Cuenta imputable donde registrara el rubro driver (ej. 05.01.01).';
  }

  $('#modal_partida_rubro_tipo').val(tipo);
  $('#modal_partida_rubro_padre_id').val(padreId);
  $('#modal_partida_rubro_clase').val(clase);
  $('#modal_partida_rubro_titulo').text(titulo);
  $('#modal_partida_rubro_ayuda').text(ayuda);
  $('#modal_partida_rubro_descripcion').val('');
  $('#modal_partida_rubro_codigo').val('');

  $.getJSON(API, {action:'sugerir_codigo_partida', padre_id: padreId}, function(r) {
    if (r.status === 'success' && r.codigo) {
      $('#modal_partida_rubro_codigo').val(r.codigo);
    }
    $('#modalPartidaRubro').show();
    $('#modal_partida_rubro_descripcion').focus();
  });
}

function cerrarModalPartidaRubro() {
  $('#modalPartidaRubro').hide();
}

function aplicarPartidaCreada(partida) {
  if (!partida) return;
  var cod = partida.ppa_codigo_clasificacion || '';
  var jer = parsePartidaJerarquia(cod);
  if (partida.ppa_clase === 'G') {
    if ((cod.split('.').length === 1)) {
      $('#rub_grupo_cod').val(cod);
      fillSubgruposRubro();
      fillDetallesRubro();
    } else {
      $('#rub_grupo_cod').val(jer.grupo);
      fillSubgruposRubro();
      $('#rub_subgrupo_cod').val(cod);
      fillDetallesRubro();
    }
  } else {
    setRubroPartidaSeleccion(cod, partida.ppa_id);
  }
}

function rubroNombreDesdePartida(ppaId) {
  var id = ppaId || $('#rub_ppa_id').val();
  if (!id) return '';
  var opt = $('#rub_ppa_id option:selected');
  if (String(opt.val()) === String(id)) {
    var d = opt.data('desc');
    if (d) return d;
  }
  var nombre = '';
  $.each(partidasRubro, function(i, p) {
    if (String(p.ppa_id) === String(id)) nombre = p.ppa_descripcion || '';
  });
  return nombre;
}

function updateRubroPartidaResumen() {
  var id = $('#rub_ppa_id').val();
  var $el = $('#rubro_partida_resumen').empty();
  if (!id) return;
  var opt = $('#rub_ppa_id option:selected');
  var nombre = rubroNombreDesdePartida(id);
  $('#pdp_rubro_nombre').val(nombre);
  $el.html('Rubro driver: <strong>' + (opt.text() || nombre) + '</strong>');
  actualizarModalRubroMesesDesdePartida();
  if ($('#modalEditRubro').is(':visible')) {
    calcModalEditRubroPreview();
  }
}

function parsePartidaJerarquia(cod) {
  var parts = (cod || '').split('.');
  return {
    grupo: parts[0] || '',
    subgrupo: parts.length >= 3 ? (parts[0] + '.' + parts[1]) : ''
  };
}

function partidaBajoPrefijo(cod, prefijo) {
  if (!prefijo || !cod) return false;
  return cod === prefijo || cod.indexOf(prefijo + '.') === 0;
}

function listGruposPrincipales() {
  var map = {};
  var orden = [];
  $.each(partidasGrupo, function(i, p) {
    var cod = p.ppa_codigo_clasificacion || '';
    var parts = cod.split('.');
    if (parts.length !== 1) return;
    if (!map[cod]) {
      map[cod] = p;
      orden.push(cod);
    }
  });
  if (!orden.length) {
    $.each(partidasRubro, function(i, p) {
      var gk = (p.ppa_codigo_clasificacion || '').split('.')[0];
      if (gk && !map[gk]) {
        map[gk] = { ppa_codigo_clasificacion: gk, ppa_descripcion: 'Grupo ' + gk };
        orden.push(gk);
      }
    });
  }
  orden.sort();
  var out = [];
  $.each(orden, function(i, cod) { out.push(map[cod]); });
  return out;
}

function listSubgrupos(grupoCod) {
  if (!grupoCod) return [];
  var out = [];
  $.each(partidasGrupo, function(i, p) {
    var cod = p.ppa_codigo_clasificacion || '';
    var parts = cod.split('.');
    if (parts.length === 2 && parts[0] === grupoCod) out.push(p);
  });
  out.sort(function(a, b) {
    return (a.ppa_codigo_clasificacion > b.ppa_codigo_clasificacion) ? 1 : -1;
  });
  return out;
}

function listDetallesRubro(grupoCod, subgrupoCod) {
  if (!grupoCod) return [];
  var subs = listSubgrupos(grupoCod);
  var prefijo = subgrupoCod || grupoCod;
  if (subs.length > 0 && !subgrupoCod) return [];
  var out = [];
  $.each(partidasRubro, function(i, p) {
    if (p.ppa_clase && p.ppa_clase !== 'D') return;
    var cod = p.ppa_codigo_clasificacion || '';
    if (!partidaBajoPrefijo(cod, prefijo)) return;
    if (subgrupoCod) {
      if (cod.split('.').length < 3) return;
    } else if (subs.length === 0) {
      if (cod.split('.').length < 2) return;
    }
    out.push(p);
  });
  out.sort(function(a, b) {
    return (a.ppa_codigo_clasificacion > b.ppa_codigo_clasificacion) ? 1 : -1;
  });
  return out;
}

function fillGruposRubro() {
  var sel = $('#rub_grupo_cod');
  var val = sel.val();
  sel.empty().append('<option value="">-- Grupo --</option>');
  $.each(listGruposPrincipales(), function(i, p) {
    sel.append('<option value="' + p.ppa_codigo_clasificacion + '">' + p.ppa_codigo_clasificacion + ' - ' + p.ppa_descripcion + '</option>');
  });
  if (val) sel.val(val);
}

function fillSubgruposRubro() {
  var grupoCod = $('#rub_grupo_cod').val();
  var sel = $('#rub_subgrupo_cod');
  var val = sel.val();
  sel.empty();
  var subs = listSubgrupos(grupoCod);
  if (!grupoCod) {
    sel.append('<option value="">-- Subgrupo --</option>').prop('disabled', true);
    return;
  }
  if (!subs.length) {
    sel.append('<option value="">(sin subgrupos)</option>').prop('disabled', true);
    return;
  }
  sel.append('<option value="">-- Subgrupo --</option>');
  $.each(subs, function(i, p) {
    sel.append('<option value="' + p.ppa_codigo_clasificacion + '">' + p.ppa_codigo_clasificacion + ' - ' + p.ppa_descripcion + '</option>');
  });
  sel.prop('disabled', false);
  if (val && subs.length) {
    var ok = false;
    $.each(subs, function(i, p) { if (p.ppa_codigo_clasificacion === val) ok = true; });
    if (ok) sel.val(val);
  }
}

function fillDetallesRubro() {
  var grupoCod = $('#rub_grupo_cod').val();
  var subgrupoCod = $('#rub_subgrupo_cod').val();
  var sel = $('#rub_ppa_id');
  var val = sel.val();
  sel.empty();
  if (!grupoCod) {
    sel.append('<option value="">-- Detalle --</option>').prop('disabled', true);
    return;
  }
  var subs = listSubgrupos(grupoCod);
  if (subs.length > 0 && !subgrupoCod) {
    sel.append('<option value="">Seleccione subgrupo</option>').prop('disabled', true);
    return;
  }
  var detalles = listDetallesRubro(grupoCod, subgrupoCod);
  if (!detalles.length) {
    sel.append('<option value="">Sin partidas detalle</option>').prop('disabled', true);
    return;
  }
  sel.append('<option value="">-- Detalle --</option>');
  $.each(detalles, function(i, p) {
    sel.append('<option value="' + p.ppa_id + '" data-desc="' + (p.ppa_descripcion || '').replace(/"/g, '&quot;') + '">' + p.ppa_codigo_clasificacion + ' - ' + p.ppa_descripcion + '</option>');
  });
  sel.prop('disabled', false);
  if (val) {
    var ok = false;
    $.each(detalles, function(i, p) { if (String(p.ppa_id) === String(val)) ok = true; });
    if (ok) sel.val(val);
  }
  updateRubroPartidaResumen();
}

function fillPartidasRubro() {
  fillGruposRubro();
  fillSubgruposRubro();
  fillDetallesRubro();
}

function setRubroPartidaSeleccion(codigo, ppaId) {
  var jer = parsePartidaJerarquia(codigo || '');
  $('#rub_grupo_cod').val(jer.grupo);
  fillSubgruposRubro();
  if (jer.subgrupo) $('#rub_subgrupo_cod').val(jer.subgrupo);
  fillDetallesRubro();
  if (ppaId) $('#rub_ppa_id').val(ppaId);
}

function loadCatalogos(cb){

  $.getJSON(API, {action:'catalogos'}, function(r){

    $('#plt_id').html('<option value="">-- Sin plantilla --</option>');

    $.each(r.plantillas||[], function(i,p){ $('#plt_id').append('<option value="'+p.plt_id+'">'+p.plt_nombre+'</option>'); });

    partidasRubro = r.partidas || [];
    partidasGrupo = r.partidas_grupo || [];

    fillPartidasRubro();

    $('#rub_ppe_id').html('');

    $.each(r.versiones||[], function(i,v){ $('#rub_ppe_id').append('<option value="'+v.ppe_id+'">'+v.ppe_anio+' V'+v.ppe_version+'</option>'); });

    if (cb) cb();

  });

}

function loadProyectos(cb){

  $.getJSON(API, {action:'list'}, function(r){

    var tb=$('#tblProy tbody').empty(), sel=$('#rub_proy_id').empty();

    $.each(r.rows||[], function(i,p){

      tb.append('<tr><td>'+p.proy_id+'</td><td>'+p.proy_nombre+'</td><td>'+p.proy_estado+'</td><td>'+(p.plt_nombre||'-')+'</td><td><button class="btn btn-xs btn-default btnEdit" data-json=\''+JSON.stringify(p)+'\'>Editar</button></td></tr>');

      sel.append('<option value="'+p.proy_id+'">'+p.proy_nombre+'</option>');

    });

    if (cb) cb();

  });

}

function versionTonPayload(aplicarRubros) {
  return {
    action: 'save_version_ton',
    proy_id: $('#rub_proy_id').val(),
    ppe_id: $('#rub_ppe_id').val(),
    pv_toneladas_base_mes: $('#pv_toneladas_base_mes').val(),
    pv_toneladas_costo_mes: $('#pv_toneladas_costo_mes').val(),
    pv_tarifa_ton_iva: $('#pv_tarifa_ton_iva').val() || 3,
    pv_iva_divisor: $('#pv_iva_divisor').val() || 1.15,
    aplicar_rubros: aplicarRubros ? 1 : 0
  };
}

function loadVersionConfig(cb){

  var proy = $('#rub_proy_id').val();

  var ppe = $('#rub_ppe_id').val() || '';

  if (!proy || !ppe) {

    tonBaseProy = 0;

    $('#pv_toneladas_base_mes').val('');
    $('#pv_toneladas_costo_mes').val('');

    if (cb) cb();

    return;

  }

  $.getJSON(API, {action:'get_version_config', proy_id:proy, ppe_id:ppe}, function(r){

    if (r.status === 'success') {

      tonBaseProy = tonBaseVersionMes(parseFloat(r.pv_toneladas_base_mes) || 0);

      $('#pv_toneladas_base_mes').val(tonBaseProy > 0 ? tonBaseProy : '');

      var tonCosto = parseFloat(r.pv_toneladas_costo_mes) || 0;
      $('#pv_toneladas_costo_mes').val(tonCosto > 0 ? tonCosto : '');

      if (r.pv_tarifa_ton_iva) $('#pv_tarifa_ton_iva').val(r.pv_tarifa_ton_iva);

      if (r.pv_iva_divisor) $('#pv_iva_divisor').val(r.pv_iva_divisor);

    } else {

      tonBaseProy = 0;

    }

    if (cb) cb();

  });

}

function rubroGrupoPrincipal(cod) {
  var c = (cod || '').split('.');
  return c[0] || '00';
}

function normGrupoCod(cod) {
  if (cod === undefined || cod === null) return '';
  return String(cod).trim();
}

function tonCostoActivo() {
  var t = parseFloat($('#pv_toneladas_costo_mes').val());
  if (!isNaN(t) && t > 0) return t;
  return pdfPreviewTonMensCalc(RUBRO_EDIT_TN_DIA, PDF_PREVIEW_DIAS_FIJO);
}

function grupoTonBaseMes(rows) {
  if (escenarioActivo === 'esperada') {
    return tonCostoActivo();
  }
  var tonAn = parseFloat(escenariosTonAnual[escenarioActivo]) || 0;
  if (tonAn > 0) {
    return tonAn / 12;
  }
  var sum = 0;
  var cnt = 0;
  $.each(rows || [], function(i, x) {
    var t = normalizarTonMesRubro(x.pdp_toneladas_base);
    if (t > 0) {
      sum += t;
      cnt++;
    }
  });
  if (cnt > 0) {
    return sum / cnt;
  }
  return tonCostoActivo();
}

function grupoFactorAnual(total, tonMes) {
  var t = parseFloat(tonMes) || 0;
  var tot = parseFloat(total) || 0;
  return t > 0 ? (tot / t) : 0;
}

function rubroSubgrupoCod(cod) {
  var p = (cod || '').split('.');
  if (p.length >= 3) return p[0] + '.' + p[1];
  return '';
}

function agruparPorSubgrupo(rows) {
  var subs = {};
  var orden = [];
  var sinSub = [];
  $.each(rows || [], function(i, x) {
    var sk = x.subgrupo_cod || rubroSubgrupoCod(x.ppa_codigo_clasificacion);
    if (!sk) {
      sinSub.push(x);
      return;
    }
    if (!subs[sk]) {
      subs[sk] = {
        cod: sk,
        nombre: x.subgrupo_descripcion || ('Subgrupo ' + sk),
        rows: [],
        total: 0
      };
      orden.push(sk);
    }
    var anual = rubroAnualEscenario(x);
    subs[sk].rows.push(x);
    subs[sk].total += anual;
  });
  return { subgrupos: subs, orden: orden, sinSub: sinSub };
}

function grupoTopeInfo(cod) {
  return gruposTopeCache[cod] || null;
}

function formatPctTopeInput(pct) {
  var n = parseFloat(pct);
  if (!n || isNaN(n) || n <= 0) return '';
  return String(parseFloat(n.toFixed(4)));
}

function grupoPctCuadroHtml(info) {
  if (!info || !info.ppa_id) return '';
  var val = formatPctTopeInput(parseFloat(info.tope_pct) || 0);
  return '<span class="grupo-pct-wrap">'
    + '<input type="number" class="form-control input-sm grupo-pct-edit" min="0" max="100" step="0.0001" '
    + 'data-ppa-id="' + info.ppa_id + '" data-grupo-cod="' + info.codigo + '" value="' + val + '" title="% tope del grupo" />'
    + '<button type="button" class="btn btn-default btn-xs btn-save-grupo-pct" data-ppa-id="' + info.ppa_id + '" title="Guardar %">OK</button>'
    + '</span>';
}

function grupoTopeCuadroHtml(info) {
  if (!info || !info.ppa_id) return '<span class="text-muted">—</span>';
  var topeAnual = parseFloat(info.tope_anual) || 0;
  var usado = parseFloat(info.usado_pct) || 0;
  if (topeAnual <= 0) return '<span class="text-muted">—</span>';
  var badgeCls = info.excedido ? 'label label-danger grupo-tope-val' : 'label label-default grupo-tope-val';
  var tip = info.formula ? info.formula : 'Tope anual del grupo';
  return '<span class="grupo-tope-wrap" title="' + tip + '">'
    + '<span class="' + badgeCls + '">' + formatCurrency(topeAnual) + '</span>'
    + (usado > 0 ? '<span class="grupo-tope-usado" style="color:' + (info.excedido ? '#c53030' : '#718096') + ';">' + formatNumber(usado, 1) + '% usado</span>' : '')
    + '</span>';
}

function grupoMesesControlHtml(info) {
  if (!info || !info.ppa_id) return '';
  var m = parseInt(info.meses_prorrateo, 10) || 12;
  return '<span class="grupo-meses-wrap" title="Meses de prorrateo del grupo (importación PDF/Excel). El presup. mensual del cuadro es anual ÷ 12 por rubro.">'
    + '<span class="grupo-meses-label">Meses</span>'
    + '<input type="number" class="form-control input-sm grupo-meses-edit" min="1" max="999" step="1" '
    + 'data-ppa-id="' + info.ppa_id + '" data-grupo-cod="' + info.codigo + '" value="' + m + '" />'
    + '<button type="button" class="btn btn-default btn-xs btn-save-grupo-meses" data-ppa-id="' + info.ppa_id + '" title="Guardar meses">OK</button>'
    + '</span>';
}

function grupoPctControlHtml(info) {
  if (!info || !info.ppa_id) return '';
  return grupoPctCuadroHtml(info);
}

function subgrupoTopeInfo(sg) {
  if (!sg || !sg.cod) return null;
  return grupoTopeInfo(sg.cod);
}

function cuadroUsaSubgrupos(rows) {
  if (!rows || !rows.length) return false;
  var agr = agruparPorSubgrupo(rows);
  if (agr.sinSub.length > 0) return false;
  if (!agr.orden.length) return false;
  return agr.orden.length > 1 || rubroSubgrupoCod(rows[0].ppa_codigo_clasificacion) !== '';
}

function rubroRowHtml(x, indent) {
  var f = parseFloat(cuadroFinalFactorCtx) || 1;
  var facAnualBase = rubroFactorAnualEscenario(x);
  var anualBase = rubroAnualEscenario(x);
  var mensualBase = presupuestoMensual(anualBase);
  var facAnual = facAnualBase * f;
  var facMes = factorMensual(facAnual);
  var anual = anualBase * f;
  var mensual = presupuestoMensual(anual);
  var json = JSON.stringify(x).replace(/'/g, '&#39;');
  var trCls = indent ? ' class="exa-ppto-rubro-indent"' : '';
  var celAnual = (Math.abs(f - 1) < 0.00001)
    ? '<strong>' + formatCurrency(anualBase) + '</strong>'
    : htmlCeldaPresupDual(anualBase, anual);
  var celMensual = (Math.abs(f - 1) < 0.00001)
    ? formatCurrency(mensualBase)
    : htmlCeldaPresupDual(mensualBase, mensual);
  return '<tr' + trCls + '>'
    + '<td><span class="text-muted">' + x.ppa_codigo_clasificacion + '</span></td>'
    + '<td>' + x.pdp_rubro + '</td>'
    + '<td class="text-right">' + formatNumber(rubroTonMesEscenario(x), 2) + '</td>'
    + '<td class="text-right">' + formatNumber(facAnual, 4) + '</td>'
    + '<td class="text-right">' + formatNumber(facMes, 6) + '</td>'
    + '<td class="text-right">' + celAnual + '</td>'
    + '<td class="text-right">' + celMensual + '</td>'
    + '<td class="text-center exa-ppto-rubro-actions-cell"><span class="exa-ppto-rubro-actions">'
    + '<button type="button" class="btn btn-xs btn-info btn-edit-rubro" title="Editar" data-json=\'' + json + '\'><i class="bi bi-pencil-square"></i></button>'
    + '<button type="button" class="btn btn-xs btn-danger btn-del-rubro" title="Eliminar" data-json=\'' + json + '\'><i class="bi bi-trash"></i></button>'
    + '</span></td>'
    + '</tr>';
}

function subgrupoHeadHtml(sg, tonMes, grupoCod) {
  var f = parseFloat(cuadroFinalFactorCtx) || 1;
  var totalBase = sg.total;
  var total = totalBase * f;
  var facAnual = grupoFactorAnual(total, tonMes);
  var facMes = factorMensual(facAnual);
  var totalMesBase = sumRubrosPresupMensual(sg.rows);
  var totalMes = totalMesBase * f;
  var topeInfo = subgrupoTopeInfo(sg);
  var topeHtml = topeInfo ? grupoTopeCuadroHtml(topeInfo) : '';
  var txtAnual = (Math.abs(f - 1) < 0.00001)
    ? formatCurrency(totalBase)
    : (formatCurrency(totalBase) + ' → <strong style="color:#276749;">' + formatCurrency(total) + '</strong>');
  var txtMes = (Math.abs(f - 1) < 0.00001)
    ? formatCurrency(totalMesBase)
    : (formatCurrency(totalMesBase) + ' → <strong style="color:#276749;">' + formatCurrency(totalMes) + '</strong>');
  return '<tr class="exa-ppto-subgrupo-head">'
    + '<td colspan="8">'
    + '<div class="exa-ppto-subgrupo-head-inner">'
    + '<span class="subgrupo-cod">' + sg.cod + '</span>'
    + '<span class="subgrupo-nom">' + sg.nombre + '</span>'
    + (topeHtml ? '<span class="subgrupo-tope-inline">' + topeHtml + '</span>' : '')
    + '<span class="subgrupo-metrics">'
    + '<span class="badge">' + sg.rows.length + ' rubro' + (sg.rows.length === 1 ? '' : 's') + '</span>'
    + '<span class="subgrupo-total">Anual: ' + txtAnual + '</span>'
    + '<span class="subgrupo-total-mes">Mens: ' + txtMes + '</span>'
    + '<span class="subgrupo-ton">$/Ton anual: ' + (facAnual > 0 ? formatNumber(facAnual, 4) : '-') + '</span>'
    + '<span class="subgrupo-ton">$/Ton mens: ' + (facMes > 0 ? formatNumber(facMes, 6) : '-') + '</span>'
    + '</span>'
    + '</div>'
    + '</td>'
    + '</tr>';
}

function subgrupoFootHtml(sg, tonMes, grupoCod) {
  var f = parseFloat(cuadroFinalFactorCtx) || 1;
  var totalBase = sg.total;
  var total = totalBase * f;
  var facAnual = grupoFactorAnual(total, tonMes);
  var facMes = factorMensual(facAnual);
  var totalMesBase = sumRubrosPresupMensual(sg.rows);
  var totalMes = totalMesBase * f;
  return '<tr class="exa-ppto-subgrupo-foot">'
    + '<td colspan="3" class="text-right">Subtotal ' + sg.cod + '</td>'
    + '<td class="text-right">' + (facAnual > 0 ? formatNumber(facAnual, 4) : '-') + '</td>'
    + '<td class="text-right">' + (facMes > 0 ? formatNumber(facMes, 6) : '-') + '</td>'
    + '<td class="text-right">' + htmlCeldaPresupDual(totalBase, total) + '</td>'
    + '<td class="text-right">' + htmlCeldaPresupDual(totalMesBase, totalMes) + '</td><td></td>'
    + '</tr>';
}

function filtrarRubrosGrupo(grupoCod) {
  var needle = normGrupoCod(grupoCod);
  var out = [];
  $.each(rubrosCache || [], function(i, x) {
    var gk = normGrupoCod(x.grupo_cod || rubroGrupoPrincipal(x.ppa_codigo_clasificacion));
    if (gk === needle) out.push(x);
  });
  return out;
}

function grupoResumenMeta(grupoCod, rows) {
  var nombre = '';
  if (rows.length && rows[0].grupo_descripcion) {
    nombre = rows[0].grupo_descripcion;
  }
  var tonMes = rows.length ? grupoTonBaseMes(rows) : tonCostoActivo();
  var totalAnual = 0;
  $.each(rows, function(i, x) { totalAnual += rubroAnualEscenario(x); });
  var totalMes = sumRubrosPresupMensual(rows);
  var facAnual = grupoFactorAnual(totalAnual, tonMes);
  var facMes = factorMensual(facAnual);
  return {
    cod: normGrupoCod(grupoCod),
    nombre: nombre || ('Grupo ' + normGrupoCod(grupoCod)),
    rows: rows,
    tonMes: tonMes,
    totalAnual: totalAnual,
    totalMes: totalMes,
    facAnual: facAnual,
    facMes: facMes
  };
}

function grupoResumenFilaRubro(x) {
  var facAnual = rubroFactorAnualEscenario(x);
  var facMes = factorMensual(facAnual);
  var anual = rubroAnualEscenario(x);
  var mensual = presupuestoMensual(anual);
  var desc = x.pdp_rubro || x.ppa_descripcion || '';
  return '<tr>'
    + '<td><strong>' + (x.ppa_codigo_clasificacion || '') + '</strong></td>'
    + '<td>' + desc + '</td>'
    + '<td class="text-right">' + formatNumber(rubroTonMesEscenario(x), 2) + '</td>'
    + '<td class="text-right">' + (facAnual > 0 ? formatNumber(facAnual, 4) : '-') + '</td>'
    + '<td class="text-right">' + (facMes > 0 ? formatNumber(facMes, 6) : '-') + '</td>'
    + '<td class="text-right"><strong>' + formatCurrency(anual) + '</strong></td>'
    + '<td class="text-right">' + formatCurrency(mensual) + '</td>'
    + '</tr>';
}

function renderGrupoResumenModal(grupoCod) {
  var rows = filtrarRubrosGrupo(grupoCod);
  var meta = grupoResumenMeta(grupoCod, rows);
  var topeInfo = grupoTopeInfo(grupoCod);
  $('#grupo_resumen_titulo').text('Resumen grupo ' + meta.cod + ' — ' + meta.nombre);
  $('#grupo_resumen_subtitulo').text(
    rows.length + ' rubro' + (rows.length === 1 ? '' : 's') + ' cargados'
    + (topeInfo && topeInfo.tope_anual > 0 ? ' · Tope anual: ' + formatCurrency(topeInfo.tope_anual) : '')
  );
  $('#grupo_resumen_kpi').html(
    '<div class="item"><span class="lbl">Presup. anual</span><span class="val">' + formatCurrency(meta.totalAnual) + '</span></div>'
    + '<div class="item"><span class="lbl">Presup. mensual</span><span class="val val-mes">' + formatCurrency(meta.totalMes) + '</span></div>'
    + '<div class="item"><span class="lbl">$/Ton anual</span><span class="val val-ton">' + (meta.facAnual > 0 ? formatNumber(meta.facAnual, 4) : '-') + '</span></div>'
    + '<div class="item"><span class="lbl">$/Ton mensual</span><span class="val val-ton">' + (meta.facMes > 0 ? formatNumber(meta.facMes, 6) : '-') + '</span></div>'
    + '<div class="item"><span class="lbl">Ton/mes costo</span><span class="val val-ton">' + (meta.tonMes > 0 ? formatNumber(meta.tonMes, 0) : '-') + '</span></div>'
    + '<div class="item"><span class="lbl">Rubros</span><span class="val">' + rows.length + '</span></div>'
  );
  var tb = $('#grupo_resumen_tbody').empty();
  if (!rows.length) {
    tb.append('<tr><td colspan="7" class="text-center text-muted">Sin rubros en este grupo.</td></tr>');
    $('#grupo_resumen_tfoot').empty();
    return;
  }
  if (cuadroUsaSubgrupos(rows)) {
    var agr = agruparPorSubgrupo(rows);
    $.each(agr.orden, function(i, sk) {
      var sg = agr.subgrupos[sk];
      var sgMes = sumRubrosPresupMensual(sg.rows);
      tb.append('<tr class="grupo-resumen-subhead"><td colspan="5"><strong>' + sg.cod + '</strong> — ' + sg.nombre + '</td>'
        + '<td class="text-right"><strong>' + formatCurrency(sg.total) + '</strong></td>'
        + '<td class="text-right"><strong>' + formatCurrency(sgMes) + '</strong></td></tr>');
      $.each(sg.rows, function(j, x) { tb.append(grupoResumenFilaRubro(x)); });
    });
    $.each(agr.sinSub, function(i, x) { tb.append(grupoResumenFilaRubro(x)); });
  } else {
    $.each(rows, function(i, x) { tb.append(grupoResumenFilaRubro(x)); });
  }
  $('#grupo_resumen_tfoot').html(
    '<tr style="background:#f7fafc;font-weight:600;">'
    + '<td colspan="5" class="text-right">Total grupo ' + meta.cod + '</td>'
    + '<td class="text-right">' + formatCurrency(meta.totalAnual) + '</td>'
    + '<td class="text-right">' + formatCurrency(meta.totalMes) + '</td>'
    + '</tr>'
  );
}

function abrirGrupoResumen(grupoCod) {
  if (!grupoCod) return;
  renderGrupoResumenModal(grupoCod);
  $('#modalGrupoResumen').show();
}

function buildGrupoTableRows(rows, tonMesGrupo, grupoCod) {
  // === CUADRO_PARTIDA_FINAL_UI (reversible) ===
  cuadroFinalFactorCtx = factorFinalParaGrupo(grupoCod);
  // === end ===
  var html = '';
  var usarSub = cuadroUsaSubgrupos(rows);
  if (!usarSub) {
    $.each(rows || [], function(i, x) { html += rubroRowHtml(x, false); });
    cuadroFinalFactorCtx = 1;
    return html;
  }
  var agr = agruparPorSubgrupo(rows);
  $.each(agr.orden, function(i, sk) {
    var sg = agr.subgrupos[sk];
    html += subgrupoHeadHtml(sg, tonMesGrupo, grupoCod);
    $.each(sg.rows, function(j, x) { html += rubroRowHtml(x, true); });
    html += subgrupoFootHtml(sg, tonMesGrupo, grupoCod);
  });
  $.each(agr.sinSub, function(i, x) { html += rubroRowHtml(x, false); });
  cuadroFinalFactorCtx = 1;
  return html;
}

function renderTablaRubros(rows) {
  var $tb = $('#tblRubros tbody').empty();
  if (!rows || !rows.length) {
    $tb.append('<tr><td colspan="8" class="text-center text-muted" style="padding:24px;">Sin rubros para este proyecto y version.</td></tr>');
    return;
  }
  $.each(rows, function(i, x) {
    $tb.append(rubroRowHtml(x, false));
  });
}

/* === CUADRO_PARTIDA_FINAL_UI (reversible) START === */
var cuadroFinalFactorCtx = 1;

function cuadroUsaPartidaFinal() {
  var checked = $('#aj_activo').is(':checked')
    || (ajusteCfgCache && parseInt(ajusteCfgCache.ajuste_activo, 10) === 1);
  return !!(checked && ajusteSimCache && ajusteSimCache.ok && ajusteSimCache.detalle);
}

function ajusteMapaFinalPorGrupo() {
  var map = {};
  if (!ajusteSimCache || !ajusteSimCache.detalle) return map;
  $.each(ajusteSimCache.detalle, function(i, d) {
    var cod = normGrupoCod(d.grupo_cod);
    map[cod] = {
      partida_base: parseFloat(d.partida_base) || 0,
      partida_final: parseFloat(d.partida_final) || 0,
      final_por_ton: parseFloat(d.final_por_ton) || 0,
      base_por_ton: parseFloat(d.base_por_ton) || 0
    };
  });
  return map;
}

function factorFinalParaGrupo(grupoCod) {
  if (!cuadroUsaPartidaFinal()) return 1;
  var m = ajusteMapaFinalPorGrupo()[normGrupoCod(grupoCod)];
  if (!m) return 1;
  var base = parseFloat(m.partida_base) || 0;
  if (base <= 0.0001) return 1;
  return (parseFloat(m.partida_final) || 0) / base;
}

function cuadroPresupMesDesdePeriodo(montoPeriodo) {
  montoPeriodo = parseFloat(montoPeriodo) || 0;
  if (cuadroVista === 'mes') return montoPeriodo;
  if (cuadroVista === 'acumulado') {
    var m = parseInt(cuadroMes, 10) || 1;
    return m > 0 ? (montoPeriodo / m) : montoPeriodo;
  }
  return montoPeriodo / 12;
}

function htmlGrupoPresupDual(baseVal, finalVal, isMes, targetSel) {
  return '<span class="grupo-metric col-num'
    + (isMes ? ' val-mes' : '')
    + ' grupo-presup-dual cuadro-grupo-toggle" data-target="' + targetSel + '" title="Base: '
    + formatCurrency(baseVal) + ' | Final: ' + formatCurrency(finalVal) + '">'
    + '<span class="presup-base">' + formatCurrency(baseVal) + '</span>'
    + '<span class="presup-final">' + formatCurrency(finalVal) + '</span>'
    + '</span>';
}

function htmlCeldaPresupDual(baseVal, finalVal) {
  baseVal = parseFloat(baseVal) || 0;
  finalVal = parseFloat(finalVal) || 0;
  if (Math.abs(baseVal - finalVal) < 0.005) {
    return '<strong>' + formatCurrency(baseVal) + '</strong>';
  }
  return '<span class="grupo-presup-dual" style="display:inline-flex;flex-direction:column;align-items:flex-end;line-height:1.15;">'
    + '<span class="presup-base">' + formatCurrency(baseVal) + '</span>'
    + '<span class="presup-final">' + formatCurrency(finalVal) + '</span>'
    + '</span>';
}
/* === CUADRO_PARTIDA_FINAL_UI (reversible) END === */

function renderCuadroRubros(rows, gruposTope) {
  rubrosCache = rows || [];
  gruposTopeCache = gruposTope || {};
  var $acc = $('#rubrosCuadroAccordion').empty();
  var $empty = $('#rubrosCuadroEmpty');
  // === CUADRO_PARTIDA_FINAL_UI (reversible) ===
  var usarFinal = cuadroUsaPartidaFinal();
  var mapaFinal = usarFinal ? ajusteMapaFinalPorGrupo() : {};
  var totalFinalSum = 0;
  var totalFinalMesSum = 0;
  // === end ===

  if (!rows || !rows.length) {
    $empty.show();
    $('#rubrosCuadroHead').hide();
    $('#cuadroKpiGrupos').text('0');
    $('#cuadroKpiRubros').text('0');
    $('#cuadroKpiTon').text(tonMesActivo() > 0 ? formatNumber(tonMesActivo(), 0) : '-');
    $('#cuadroKpiTotal').text(formatCurrency(0));
    $('#cuadroKpiTotalMes').text(formatCurrency(0));
    $('#cuadroKpiTonAnual').text('-');
    $('#cuadroKpiTonMes').text('-');
    return;
  }
  $empty.hide();
  $('#rubrosCuadroHead').show();

  var grupos = {};
  var totalAnual = 0;
  $.each(rows, function(i, x) {
    var gk = normGrupoCod(x.grupo_cod || rubroGrupoPrincipal(x.ppa_codigo_clasificacion));
    if (!grupos[gk]) {
      grupos[gk] = {
        cod: gk,
        nombre: x.grupo_descripcion || ('Grupo ' + gk),
        rows: [],
        total: 0
      };
    }
    var anual = rubroAnualEscenario(x);
    grupos[gk].rows.push(x);
    grupos[gk].total += anual;
    totalAnual += anual;
  });

  var keys = Object.keys(grupos).sort();
  var totalMesSum = 0;
  $.each(keys, function(idx, gk) {
    var g = grupos[gk];
    var collapseId = 'cuadroGrupo' + gk.replace(/\W/g, '');
    var open = '';
    var headingOpenCls = '';
    var tonMesGrupo = grupoTonBaseMes(g.rows);
    var facGrupoAnual = grupoFactorAnual(g.total, tonMesGrupo);
    var totalMesGrupo = sumRubrosPresupMensual(g.rows);
    var facGrupoMensual = factorMensual(facGrupoAnual);
    totalMesSum += totalMesGrupo;
    var tableRows = buildGrupoTableRows(g.rows, tonMesGrupo, gk);
    var topeInfo = grupoTopeInfo(g.cod);
    var panelCls = 'panel panel-default' + ((topeInfo && topeInfo.excedido) ? ' exa-ppto-grupo-excedido' : '');
    var pctHtml = topeInfo ? grupoPctCuadroHtml(topeInfo) : '';
    var topeHtml = topeInfo ? grupoTopeCuadroHtml(topeInfo) : '<span class="text-muted">&mdash;</span>';

    // === CUADRO_PARTIDA_FINAL_UI (reversible) ===
    var htmlPresupAnual = '<span class="grupo-metric col-num cuadro-grupo-toggle" data-target="#' + collapseId + '">' + formatCurrency(g.total) + '</span>';
    var htmlPresupMes = '<span class="grupo-metric col-num val-mes cuadro-grupo-toggle" data-target="#' + collapseId + '">' + formatCurrency(totalMesGrupo) + '</span>';
    var htmlTonAnual = '<span class="grupo-metric col-num grupo-ton-val cuadro-grupo-toggle" data-target="#' + collapseId + '">' + (facGrupoAnual > 0 ? formatNumber(facGrupoAnual, 4) : '-') + '</span>';
    var htmlTonMes = '<span class="grupo-metric col-num grupo-ton-val val-mes cuadro-grupo-toggle" data-target="#' + collapseId + '">' + (facGrupoMensual > 0 ? formatNumber(facGrupoMensual, 6) : '-') + '</span>';
    var footAnual = formatCurrency(g.total);
    var footMes = formatCurrency(totalMesGrupo);
    var footTonA = facGrupoAnual > 0 ? formatNumber(facGrupoAnual, 4) : '-';
    var footTonM = facGrupoMensual > 0 ? formatNumber(facGrupoMensual, 6) : '-';
    if (usarFinal && mapaFinal[gk]) {
      var fin = mapaFinal[gk].partida_final;
      var finMes = cuadroPresupMesDesdePeriodo(fin);
      var finTon = mapaFinal[gk].final_por_ton;
      var finTonMes = finTon > 0 ? (finTon / 12) : 0;
      totalFinalSum += fin;
      totalFinalMesSum += finMes;
      htmlPresupAnual = htmlGrupoPresupDual(g.total, fin, false, '#' + collapseId);
      htmlPresupMes = htmlGrupoPresupDual(totalMesGrupo, finMes, true, '#' + collapseId);
      htmlTonAnual = '<span class="grupo-metric col-num grupo-ton-val cuadro-grupo-toggle" data-target="#' + collapseId + '" title="$/Ton final">'
        + (finTon > 0 ? formatNumber(finTon, 4) : '-') + '</span>';
      htmlTonMes = '<span class="grupo-metric col-num grupo-ton-val val-mes cuadro-grupo-toggle" data-target="#' + collapseId + '" title="$/Ton mensual final">'
        + (finTonMes > 0 ? formatNumber(finTonMes, 6) : '-') + '</span>';
      footAnual = htmlCeldaPresupDual(g.total, fin);
      footMes = htmlCeldaPresupDual(totalMesGrupo, finMes);
      footTonA = finTon > 0 ? formatNumber(finTon, 4) : '-';
      footTonM = finTonMes > 0 ? formatNumber(finTonMes, 6) : '-';
    }
    // === end ===

    $acc.append(
      '<div class="' + panelCls + '">'
      + '<div class="panel-heading cuadro-grupo-heading' + headingOpenCls + '" role="tab" id="heading' + collapseId + '">'
      + '<div class="cuadro-grupo-head exa-ppto-cuadro-grid">'
      + '<span class="grupo-head-left cuadro-grupo-toggle" data-target="#' + collapseId + '">'
      + '<span class="grupo-cod">' + g.cod + '</span>'
      + '<span class="grupo-nom">' + g.nombre + '</span>'
      + '</span>'
      + htmlPresupAnual
      + htmlPresupMes
      + htmlTonAnual
      + htmlTonMes
      + '<span class="grupo-col-pct">' + pctHtml + '</span>'
      + '<span class="grupo-col-tope">' + topeHtml + '</span>'
      + '<span class="grupo-head-right grupo-meta">'
      + '<button type="button" class="btn btn-default btn-xs btn-grupo-resumen" data-grupo-cod="' + gk + '" title="Resumen del grupo (como Excel)"><i class="bi bi-table"></i></button>'
      + '<span class="cuadro-grupo-toggle" data-target="#' + collapseId + '">'
      + '<span class="badge">' + g.rows.length + ' rubro' + (g.rows.length === 1 ? '' : 's') + '</span>'
      + '<i class="bi bi-chevron-down"></i>'
      + '</span>'
      + '</span>'
      + '</div></div>'
      + '<div id="' + collapseId + '" class="panel-collapse collapse' + open + '" role="tabpanel" aria-labelledby="heading' + collapseId + '">'
      + '<div class="panel-body"><div class="table-responsive">'
      + '<table class="table table-hover exa-adq-table">'
      + '<thead><tr><th>Partida</th><th>Rubro</th><th title="Driver egresos Excel (77.000)">Ton/mes costo</th><th>$/Ton anual</th><th>$/Ton mensual</th><th>Presup. anual</th><th>Presup. mensual</th><th style="width:88px;"></th></tr></thead>'
      + '<tbody>' + tableRows + '</tbody>'
      + '<tfoot><tr style="background:#f7fafc;font-weight:600;">'
      + '<td colspan="3" class="text-right">Subtotal grupo ' + g.cod + '</td>'
      + '<td class="text-right">' + footTonA + '</td>'
      + '<td class="text-right">' + footTonM + '</td>'
      + '<td class="text-right">' + footAnual + '</td>'
      + '<td class="text-right">' + footMes + '</td><td></td>'
      + '</tr></tfoot>'
      + '</table></div></div></div></div>'
    );
  });

  var tonProy = tonMesActivo() > 0 ? tonMesActivo() : grupoTonBaseMes(rows);
  var kpiTotal = totalAnual;
  var totalMes = totalMesSum;
  // === CUADRO_PARTIDA_FINAL_UI (reversible) ===
  if (usarFinal && totalFinalSum > 0) {
    kpiTotal = totalFinalSum;
    totalMes = totalFinalMesSum;
    $('#cuadroColPresupLbl').addClass('col-final-on').html('Presup. <span style="text-transform:none;">base/final</span>');
    $('#cuadroColPresupMesLbl').addClass('col-final-on').html('Mes <span style="text-transform:none;">base/final</span>');
  } else {
    $('#cuadroColPresupLbl').removeClass('col-final-on');
    $('#cuadroColPresupMesLbl').removeClass('col-final-on');
    actualizarCuadroPeriodoUi();
  }
  // === end ===
  var facProyAnual = grupoFactorAnual(kpiTotal, tonProy);
  var facProyMes = tonProy > 0 ? (totalMes / tonProy) : 0;

  $('#cuadroKpiGrupos').text(keys.length);
  $('#cuadroKpiRubros').text(rows.length);
  $('#cuadroKpiTon').text(tonProy > 0 ? formatNumber(tonProy, 0) : '-');
  $('#cuadroKpiTotal').text(formatCurrency(kpiTotal));
  $('#cuadroKpiTotalMes').text(formatCurrency(totalMes));
  $('#cuadroKpiTonAnual').text(facProyAnual > 0 ? formatNumber(facProyAnual, 4) : '-');
  $('#cuadroKpiTonMes').text(facProyMes > 0 ? formatNumber(facProyMes, 6) : '-');
}

function actualizarCuadroPeriodoUi() {
  var esAnual = (cuadroVista === 'anual');
  $('.cuadro-vista-btn').removeClass('active');
  $('.cuadro-vista-btn[data-vista="' + cuadroVista + '"]').addClass('active');
  $('#cuadroMesWrap').toggle(!esAnual);
  $('#cuadroMesLbl').text(cuadroVista === 'mes' ? 'Mes' : 'Hasta mes');
  $('#cuadro_mes_sel').val(String(cuadroMes));
  $('#cuadroPeriodoLbl').html(cuadroPeriodoLabel ? '<strong>' + cuadroPeriodoLabel + '</strong>' : '');

  var titPeriodo = '(anual)';
  var tonLbl = 'Ton anual ingresos';
  var presupLbl = 'Presup. anual';
  var presupMesLbl = 'Presup. mensual';
  var kpiTotalLbl = 'Presupuesto anual total';
  var kpiMesLbl = 'Presupuesto mensual total';
  var kpiMesSub = 'Anual / 12';
  var escBtnSub = 'Gastos anuales';
  if (cuadroVista === 'acumulado') {
    titPeriodo = '(acumulado)';
    tonLbl = 'Ton ingresos acum.';
    presupLbl = 'Presup. acumulado';
    presupMesLbl = 'Prom. mensual';
    kpiTotalLbl = 'Presupuesto acumulado';
    kpiMesLbl = 'Promedio mensual';
    kpiMesSub = 'Acum. / meses';
    escBtnSub = 'Gastos acumulados';
  } else if (cuadroVista === 'mes') {
    titPeriodo = '(mes)';
    tonLbl = 'Ton ingresos mes';
    presupLbl = 'Presup. del mes';
    presupMesLbl = 'Presup. del mes';
    kpiTotalLbl = 'Presupuesto del mes';
    kpiMesLbl = 'Presupuesto del mes';
    kpiMesSub = '';
    escBtnSub = 'Gastos del mes';
  }
  $('#escResumenPeriodoTit').text(titPeriodo);
  $('#escTonRowLbl').text(tonLbl);
  $('#cuadroColPresupLbl').text(presupLbl);
  $('#cuadroColPresupMesLbl').text(presupMesLbl);
  $('#cuadroKpiTotalLbl').text(kpiTotalLbl);
  $('#cuadroKpiTotalMesLbl').text(kpiMesLbl);
  $('#cuadroKpiTotalMesSub').text(kpiMesSub).toggle(kpiMesSub !== '');
  $('.esc-btn-s').text(escBtnSub);
}

function recalcIngresoEsperadaCliente() {
  var tonMes = parseFloat($('#pv_toneladas_base_mes').val()) || tonBaseProy || 0;
  if (tonMes <= 0.0001) return;
  var meses = 12;
  if (cuadroVista === 'mes') {
    meses = 1;
  } else if (cuadroVista === 'acumulado') {
    meses = cuadroMes;
  }
  var tonPeriod = tonMes * meses;
  escenariosTonPeriodo.esperada = tonPeriod;
  if (cuadroVista === 'anual') {
    escenariosTonAnual.esperada = tonPeriod;
  }
  escenariosIngreso.esperada = tonPeriod * ingresoCfg.tarifa / ingresoCfg.iva;
}

function aplicarCuadroPeriodoResponse(r) {
  if (r.cuadro_periodo) {
    cuadroVista = r.cuadro_periodo.vista || cuadroVista;
    cuadroMes = parseInt(r.cuadro_periodo.mes, 10) || cuadroMes;
    cuadroMesDefault = parseInt(r.cuadro_periodo.mes_default, 10) || cuadroMesDefault;
    cuadroPeriodoLabel = r.cuadro_periodo.label || cuadroPeriodoLabel;
  }
  if (r.escenarios_ton_periodo) {
    escenariosTonPeriodo = {
      esperada: parseFloat(r.escenarios_ton_periodo.esperada) || 0,
      proyectada: parseFloat(r.escenarios_ton_periodo.proyectada) || 0,
      real: parseFloat(r.escenarios_ton_periodo.real) || 0
    };
  }
  actualizarCuadroPeriodoUi();
}

function refreshVistaPresupuesto() {
  recalcIngresoEsperadaCliente();
  if (!rubrosCache || !rubrosCache.length) return;
  renderTablaRubros(rubrosCache);
  renderCuadroRubros(rubrosCache, gruposTopeCache);
  actualizarBotonesEscenario(rubrosCache);
}

function loadRubrosParams() {
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val() || '';
  return {
    action: 'list_rubros',
    proy_id: proy,
    ppe_id: ppe,
    cuadro_vista: cuadroVista,
    cuadro_mes: cuadroMes,
    escenario: escenarioActivo || 'esperada',
    anio_precio: cuadroAnioPrecio || $('#cuadro_anio_precio').val() || ''
  };
}

function loadRubros(){

  var proy=$('#rub_proy_id').val(); if(!proy) return;

  $.getJSON(API, loadRubrosParams(), function(r){

    if (!r.rows || !r.rows.length) {
      rubrosCache = [];
      gruposTopeCache = {};
      escenariosIngreso = { esperada: 0, proyectada: 0, real: 0 };
      escenariosTonAnual = { esperada: 0, proyectada: 0, real: 0 };
      escenariosTonPeriodo = { esperada: 0, proyectada: 0, real: 0 };
      renderTablaRubros([]);
      renderCuadroRubros([], {});
      actualizarBotonesEscenario([]);
      aplicarCuadroPeriodoResponse(r);
      return;
    }

    rubrosCache = r.rows;
    escenarioMesesReal = parseInt(r.escenarios_meses_con_real, 10) || 0;
    if (r.escenarios_ton_mes) {
      escenariosTonMes = {
        esperada: parseFloat(r.escenarios_ton_mes.esperada) || 0,
        proyectada: parseFloat(r.escenarios_ton_mes.proyectada) || 0,
        real: parseFloat(r.escenarios_ton_mes.real) || 0
      };
    }
    if (r.escenarios_ton_anual) {
      escenariosTonAnual = {
        esperada: parseFloat(r.escenarios_ton_anual.esperada) || 0,
        proyectada: parseFloat(r.escenarios_ton_anual.proyectada) || 0,
        real: parseFloat(r.escenarios_ton_anual.real) || 0
      };
    }
    if (r.escenarios_ingreso) {
      escenariosIngreso = {
        esperada: parseFloat(r.escenarios_ingreso.esperada) || 0,
        proyectada: parseFloat(r.escenarios_ingreso.proyectada) || 0,
        real: parseFloat(r.escenarios_ingreso.real) || 0
      };
    }
    if (r.ingreso_cfg) {
      ingresoCfg.tarifa = parseFloat(r.ingreso_cfg.tarifa_ton_iva) || 3;
      ingresoCfg.iva = parseFloat(r.ingreso_cfg.iva_divisor) || 1.15;
      ingresoCfg.factor_precio_gasto = parseFloat(r.ingreso_cfg.factor_precio_gasto) || 1;
      ingresoCfg.anio_base = r.ingreso_cfg.anio_base || null;
      $('#escTarifaLbl').text(formatNumber(ingresoCfg.tarifa, 4));
      $('#escIvaLbl').text(formatNumber(ingresoCfg.iva, 2));
    }
    populateCuadroAnioSelect(
      r.precios_proyeccion || ajustePreciosCache || [],
      r.anio_proyeccion || (r.ingreso_cfg && r.ingreso_cfg.anio),
      r.escenarios_anio
    );
    updateCuadroPrecioLbl(r.ingreso_cfg, r.precio_anio);
    if (r.ajuste_cfg) {
      fillAjusteCfgForm(r.ajuste_cfg);
    }
    if (r.ajuste_financiero) {
      renderAjusteSim(r.ajuste_financiero);
    }
    aplicarCuadroPeriodoResponse(r);
    renderTablaRubros(rubrosCache);
    renderCuadroRubros(rubrosCache, r.grupos_tope || {});
    actualizarBotonesEscenario(rubrosCache);

  });

}

function sumaEscenario(rows, esc) {
  var t = 0;
  $.each(rows || [], function(i, x) {
    t += rubroAnualPorEscenario(x, esc);
  });
  return t;
}

function actualizarBotonesEscenario(rows) {
  $('#escTotEsperada').text(formatCurrency(sumaEscenario(rows, 'esperada')));
  $('#escTotProyectada').text(formatCurrency(sumaEscenario(rows, 'proyectada')));
  $('#escTotReal').text(formatCurrency(sumaEscenario(rows, 'real')));
  var info = escenarioMesesReal > 0
    ? (escenarioMesesReal + ' mes(es) con real; el resto usa proyectada')
    : 'Sin meses con real aún; "Real" usa proyectada';
  $('#escMesesRealInfo').text(info);
  $('.esc-btn').removeClass('active');
  $('.esc-btn[data-esc="' + escenarioActivo + '"]').addClass('active');
  actualizarResumenEconomico(rows);
}

function actualizarResumenEconomico(rows) {
  var escs = ['esperada', 'proyectada', 'real'];
  var usarAjuste = ajusteCfgCache && parseInt(ajusteCfgCache.ajuste_activo, 10) === 1 && ajusteSimCache && ajusteSimCache.ok;
  $.each(escs, function(i, esc) {
    var gastos = sumaEscenario(rows, esc);
    var ingresos = parseFloat(escenariosIngreso[esc]) || 0;
    var util = ingresos - gastos;
    var tonAn = (cuadroVista === 'anual')
      ? (parseFloat(escenariosTonAnual[esc]) || 0)
      : (parseFloat(escenariosTonPeriodo[esc]) || 0);
    if (usarAjuste && esc === (ajusteSimCache.meta && ajusteSimCache.meta.escenario) && ajusteSimCache.resumen) {
      gastos = parseFloat(ajusteSimCache.resumen.gasto_final) || gastos;
      // Utilidad coherente: ingresos - final - capital - gad ≈ utilidad base
      util = parseFloat(ajusteSimCache.resumen.utilidad_coherente);
      if (isNaN(util)) util = ingresos - gastos;
    }
    $('#escTonAn_' + esc).text(tonAn > 0 ? formatNumber(tonAn, 0) : '—');
    $('#escIng_' + esc).text(formatCurrency(ingresos));
    $('#escGas_' + esc).text(formatCurrency(gastos));
    var $u = $('#escUtil_' + esc);
    $u.text(formatCurrency(util)).removeClass('eco-pos eco-neg');
    if (ingresos > 0 || gastos > 0) {
      $u.addClass(util >= 0 ? 'eco-pos' : 'eco-neg');
    }
  });
  $('.esc-res-col').removeClass('active');
  $('.esc-res-col[data-esc="' + escenarioActivo + '"]').addClass('active');
}

function fillAjusteCfgForm(cfg) {
  if (!cfg) return;
  ajusteCfgCache = cfg;
  $('#aj_capital_pct').val(cfg.costo_capital_pct);
  $('#aj_gad_factor').val(cfg.gad_factor_ton);
  $('#aj_gad_objetivo').val(cfg.gad_monto_objetivo);
  $('#aj_gad_acum').val(cfg.gad_recuperado_acum);
  $('#aj_activo').prop('checked', parseInt(cfg.ajuste_activo, 10) === 1);
  if (cuadroAnioPrecio > 0) {
    $('#aj_anio_precio').val(cuadroAnioPrecio);
  } else if (!$('#aj_anio_precio').val()) {
    var anio = (ajusteSimCache && ajusteSimCache.meta && ajusteSimCache.meta.anio)
      ? ajusteSimCache.meta.anio
      : (new Date()).getFullYear();
    $('#aj_anio_precio').val(anio);
  }
}

function populateCuadroAnioSelect(precios, anioActivo, anioVersion) {
  var $sel = $('#cuadro_anio_precio');
  var prev = parseInt($sel.val(), 10) || cuadroAnioPrecio || 0;
  var map = {};
  var years = [];
  $.each(precios || [], function(i, p) {
    var a = parseInt(p.anio, 10);
    if (a > 0 && !map[a]) {
      map[a] = parseFloat(p.tarifa_ton_iva) || 0;
      years.push(a);
    }
  });
  var base = parseInt(anioVersion, 10) || (new Date()).getFullYear();
  if (years.indexOf(base) < 0) {
    years.push(base);
    if (!map[base]) map[base] = parseFloat(ingresoCfg.tarifa) || 3;
  }
  // Rango de apoyo: base .. base+7 si no hay precios
  if (years.length <= 1) {
    for (var y = base; y <= base + 7; y++) {
      if (years.indexOf(y) < 0) years.push(y);
    }
  }
  years.sort(function(a, b) { return a - b; });
  var want = parseInt(anioActivo, 10) || prev || base;
  $sel.empty();
  $.each(years, function(i, a) {
    var t = map[a] ? (' — $' + formatNumber(map[a], 2)) : '';
    $sel.append('<option value="' + a + '">' + a + t + '</option>');
  });
  if ($sel.find('option[value="' + want + '"]').length) {
    $sel.val(String(want));
  } else {
    $sel.val(String(years[0]));
  }
  cuadroAnioPrecio = parseInt($sel.val(), 10) || base;
  $('#aj_anio_precio').val(cuadroAnioPrecio);
}

function updateCuadroPrecioLbl(ingCfg, precioAnio) {
  if (!ingCfg) {
    $('#cuadroPrecioAnioLbl').text('');
    return;
  }
  var anio = ingCfg.anio || cuadroAnioPrecio;
  var tarifa = parseFloat(ingCfg.tarifa_ton_iva) || 0;
  var neto = parseFloat(ingCfg.tarifa_ton_neta) || (tarifa / (parseFloat(ingCfg.iva_divisor) || 1.15));
  var fuente = (ingCfg.fuente_precio === 'proyeccion_anio') ? 'proyeccion' : 'base version';
  var fac = parseFloat(ingCfg.factor_precio_gasto) || 1;
  var facTxt = (Math.abs(fac - 1) > 0.0001)
    ? ' | gastos x' + formatNumber(fac, 4) + ' vs ' + (ingCfg.anio_base || '')
    : '';
  $('#cuadroPrecioAnioLbl').html(
    'Precio <strong>' + anio + '</strong>: <strong>$' + formatNumber(tarifa, 2) + '</strong> c/IVA'
    + ' (neto $' + formatNumber(neto, 4) + ', ' + fuente + ')' + facTxt
  );
  if ($('#escTarifaLbl').length) {
    $('#escTarifaLbl').text(formatNumber(tarifa, 4));
  }
}

function renderAjusteSim(sim) {
  ajusteSimCache = sim || null;
  var $tb = $('#tblAjusteDist tbody').empty();
  if (!sim || !sim.ok) {
    $('#ajKpiNeto,#ajKpiCapTon,#ajKpiCapTot,#ajKpiGad,#ajKpiSaldo,#ajKpiGastoFin,#ajKpiUtil').text('—');
    return;
  }
  $('#ajKpiNeto').text(formatNumber(sim.precio.precio_neto, 4));
  $('#ajKpiCapTon').text(formatNumber(sim.capital.por_ton, 4));
  $('#ajKpiCapTot').text(formatCurrency(sim.capital.total));
  $('#ajKpiGad').text(formatCurrency(sim.gad.aplicado) + (sim.gad.agotado ? ' (agotado)' : ''));
  $('#ajKpiSaldo').text(formatCurrency(sim.gad.saldo_despues));
  $('#ajKpiGastoFin').text(formatCurrency(sim.resumen.gasto_final));
  $('#ajKpiUtil').text(formatCurrency(sim.resumen.utilidad_base));
  $.each(sim.detalle || [], function(i, d) {
    $tb.append(
      '<tr>'
      + '<td><strong>' + d.grupo_cod + '</strong> ' + (d.grupo_nombre || '') + '</td>'
      + '<td class="text-right">' + formatCurrency(d.partida_base) + '</td>'
      + '<td class="text-right">' + formatNumber(d.participacion_pct, 2) + '%</td>'
      + '<td class="text-right">' + formatNumber(d.base_por_ton, 4) + '</td>'
      + '<td class="text-right">' + formatNumber(d.capital_por_ton, 4) + '</td>'
      + '<td class="text-right">' + formatNumber(d.gad_por_ton, 4) + '</td>'
      + '<td class="text-right"><strong>' + formatNumber(d.final_por_ton, 4) + '</strong></td>'
      + '<td class="text-right"><strong>' + formatCurrency(d.partida_final) + '</strong></td>'
      + '</tr>'
    );
  });
  // === CUADRO_PARTIDA_FINAL_UI (reversible) ===
  if (cuadroUsaPartidaFinal()) {
    renderCuadroRubros(rubrosCache, gruposTopeCache);
  }
  // === end ===
}

function ajusteOptsFromForm() {
  return {
    proy_id: $('#rub_proy_id').val(),
    ppe_id: $('#rub_ppe_id').val(),
    cuadro_vista: cuadroVista || 'anual',
    cuadro_mes: cuadroMes || '',
    escenario: escenarioActivo || 'esperada',
    costo_capital_pct: $('#aj_capital_pct').val(),
    gad_factor_ton: $('#aj_gad_factor').val(),
    gad_monto_objetivo: $('#aj_gad_objetivo').val(),
    gad_recuperado_acum: $('#aj_gad_acum').val(),
    anio: $('#aj_anio_precio').val()
  };
}

function loadAjusteCfg(cb) {
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  if (!proy || !ppe) {
    if (cb) cb();
    return;
  }
  $.getJSON(API, { action: 'ajuste_cfg_get', proy_id: proy, ppe_id: ppe }, function(r) {
    if (r.status === 'success') {
      fillAjusteCfgForm(r.cfg);
      ajustePreciosCache = r.precios || [];
    }
    if (cb) cb();
  });
}

function simularAjuste(andApply) {
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  if (!proy || !ppe) {
    toast('Seleccione proyecto y version.', false);
    return;
  }
  var data = $.extend({ action: andApply ? 'ajuste_aplicar' : 'ajuste_simular' }, ajusteOptsFromForm());
  if (andApply) {
    if (!confirm('Aplicar ajuste financiero?\n\n- No modifica partidas base\n- Actualiza GAD acumulado\n- Guarda historial auditable\n- Activa partida final en el cuadro')) {
      return;
    }
    data.observacion = 'Aplicacion desde cuadro presupuestario';
  }
  $.post(API, data, function(r) {
    if (r.status !== 'success') {
      toast(r.message || 'Error en ajuste.', false);
      return;
    }
    renderAjusteSim(r.sim);
    if (r.cfg) fillAjusteCfgForm(r.cfg);
    if (andApply) {
      toast(r.message || 'Ajuste aplicado.', true);
      loadRubros();
    } else {
      toast('Simulacion lista (sin guardar).', true);
      actualizarResumenEconomico(rubrosCache);
    }
  }, 'json').fail(function() {
    toast('Error de red al simular ajuste.', false);
  });
}

function renderPreciosRows(precios) {
  var $tb = $('#tblAjustePrecios tbody').empty();
  if (!precios || !precios.length) {
    $tb.append('<tr><td colspan="3" class="text-muted">Sin precios. Use "Cargar ejemplo" o agregue anios.</td></tr>');
    return;
  }
  $.each(precios, function(i, p) {
    $tb.append(
      '<tr>'
      + '<td><input type="number" class="form-control input-sm aj-precio-anio" value="' + p.anio + '" /></td>'
      + '<td><input type="number" step="0.0001" class="form-control input-sm aj-precio-tarifa" value="' + p.tarifa_ton_iva + '" /></td>'
      + '<td><button type="button" class="btn btn-default btn-xs btn-aj-del-precio">&times;</button></td>'
      + '</tr>'
    );
  });
}

function collectPreciosFromModal() {
  var out = [];
  $('#tblAjustePrecios tbody tr').each(function() {
    var anio = parseInt($(this).find('.aj-precio-anio').val(), 10);
    var tarifa = parseFloat($(this).find('.aj-precio-tarifa').val());
    if (anio > 0 && tarifa > 0) {
      out.push({ anio: anio, tarifa_ton_iva: tarifa });
    }
  });
  return out;
}

function setEscenario(esc) {
  if (!ESC_LABEL[esc]) { return; }
  escenarioActivo = esc;
  renderCuadroRubros(rubrosCache, gruposTopeCache);
  renderTablaRubros(rubrosCache);
  actualizarBotonesEscenario(rubrosCache);
  loadRubros();
}

function saveGrupoMeses(ppaId, $input) {
  var meses = $.trim($input.val());
  $.post(API, {
    action: 'save_grupo_meses',
    ppa_id: ppaId,
    ppa_meses_prorrateo: meses
  }, function(r) {
    toast(r.message, r.status === 'success');
    if (r.status === 'success') {
      loadRubros();
    }
  }, 'json').fail(function() {
    toast('Error al guardar los meses.', false);
  });
}

function saveGrupoPct(ppaId, $input) {
  var pct = $.trim($input.val());
  $.post(API, {
    action: 'save_grupo_pct',
    ppa_id: ppaId,
    ppa_porcentaje_tope: pct
  }, function(r) {
    toast(r.message, r.status === 'success');
    if (r.status === 'success') {
      loadRubros();
    }
  }, 'json').fail(function() {
    toast('Error al guardar el porcentaje.', false);
  });
}

function reloadRubrosSection(){

  loadVersionConfig(function(){ loadRubros(); });

}

var publicarPreviewCache = null;

function publicarParams() {
  return {
    proy_id: $('#rub_proy_id').val(),
    ppe_id: $('#rub_ppe_id').val() || ''
  };
}

function loadUltimaPublicacion() {
  if (!$('#pubUltimaMeta').length) return;
  var p = publicarParams();
  if (!p.proy_id || !p.ppe_id) {
    $('#pubUltimaMeta').text('Seleccione proyecto y version para publicar.');
    return;
  }
  $.getJSON(API, $.extend({ action: 'ultima_publicacion' }, p), function(r) {
    if (r.status !== 'success' || !r.ultima) {
      $('#pubUltimaMeta').text('Sin publicaciones registradas para esta version.');
      return;
    }
    var u = r.ultima;
    var f = (u.pub_fecha_registro || '').replace(' ', ' — ');
    $('#pubUltimaMeta').html(
      'Ultima publicacion: <strong>' + formatCurrency(u.pub_total_nuevo) + '</strong> el ' + f + ' (anio ' + u.pub_anio + ').'
    );
  });
}

function renderPublicarPreview(prev) {
  publicarPreviewCache = prev;
  $('#pubPrevVigente').text(formatCurrency(prev.total_vigente));
  $('#pubPrevNuevo').text(formatCurrency(prev.total_publicar));
  var d = parseFloat(prev.delta) || 0;
  $('#pubPrevDelta').text((d >= 0 ? '+' : '') + formatCurrency(d)).css('color', d >= 0 ? '#276749' : '#c53030');
  $('#pubPrevTon').text(formatNumber(prev.ton_proyectada_anual || 0, 2));
  var $w = $('#pubPrevWarnings').empty().hide();
  if (prev.warnings && prev.warnings.length) {
    $w.html(prev.warnings.join('<br>')).show();
  }
  var $tb = $('#pubPrevTbody').empty();
  $.each(prev.detalle || [], function(i, row) {
    var dd = parseFloat(row.delta) || 0;
    $tb.append(
      '<tr>'
      + '<td>' + htmlspecialchars(row.codigo) + '</td>'
      + '<td>' + htmlspecialchars(row.rubro) + '</td>'
      + '<td class="text-right">' + formatCurrency(row.vigente) + '</td>'
      + '<td class="text-right">' + formatCurrency(row.publicar) + '</td>'
      + '<td class="text-right" style="color:' + (dd >= 0 ? '#276749' : '#c53030') + ';">'
      + (dd >= 0 ? '+' : '') + formatCurrency(dd) + '</td>'
      + '</tr>'
    );
  });
  $('#modalPublicarPreview').show();
}

function previewPublicar() {
  var p = publicarParams();
  if (!p.proy_id || !p.ppe_id) {
    toast('Seleccione proyecto y version.', false);
    return;
  }
  $.getJSON(API, $.extend({ action: 'preview_publicar' }, p), function(r) {
    if (r.status !== 'success') {
      toast(r.message || 'No se pudo generar la vista previa.', false);
      return;
    }
    renderPublicarPreview(r.preview);
  }).fail(function() { toast('Error de red al consultar vista previa.', false); });
}

function ejecutarPublicar(confirmarRepublicacion) {
  var p = publicarParams();
  if (!p.proy_id || !p.ppe_id) {
    toast('Seleccione proyecto y version.', false);
    return;
  }
  var postData = $.extend({ action: 'publish_aprobado' }, p);
  if (confirmarRepublicacion) {
    postData.confirmar_republicacion = '1';
  }
  $.post(API, postData, function(r) {
    if (r.status === 'confirm') {
      if (!confirm('Ya existe una publicacion previa. ¿Desea republicar y sobrescribir el presupuesto aprobado?')) {
        return;
      }
      ejecutarPublicar(true);
      return;
    }
    if (r.status !== 'success') {
      var msg = r.message || 'No se pudo publicar.';
      if (r.bloqueos && r.bloqueos.length) {
        msg += ' Revise rubros con comprometido/ejecutado superior al nuevo monto.';
      }
      toast(msg, false);
      return;
    }
    $('#modalPublicarPreview').hide();
    toast(r.message, true);
    loadUltimaPublicacion();
    reloadRubrosSection();
  }, 'json').fail(function() { toast('Error de red al publicar.', false); });
}

function pptoClaseEtiqueta(clase) {
  return (clase === 'G') ? 'Grupo' : 'Detalle';
}

function pptoEstadoImportLabel(estado, cat) {
  if (estado === 'conflicto') return '<span class="label label-danger">Conflicto</span>';
  if (estado === 'rubro_existente') {
    var tip = (cat && cat.rubro_nombre_actual) ? (' title="Nombre actual: ' + pdfPreviewEscHtml(cat.rubro_nombre_actual) + '"') : '';
    return '<span class="label label-info"' + tip + '>Se actualiza</span>';
  }
  if (estado === 'existente') return '<span class="label label-default">Partida catalogo</span>';
  return '<span class="label label-success">Nuevo</span>';
}

function pptoEstadoImportRow(codigo, catalogo) {
  var cat = catalogo[codigo] || {};
  var estado = cat.estado || 'nuevo';
  if (cat.rubro_proyecto && estado !== 'conflicto') {
    estado = 'rubro_existente';
  }
  return { estado: estado, cat: cat };
}

function pdfPreviewTonBase() {
  var ton = parseFloat($('#pv_toneladas_base_mes').val());
  if (!isNaN(ton) && ton > 0) return tonBaseVersionMes(ton);
  if (pdfImportPreviewTon > 0) return tonBaseVersionMes(pdfImportPreviewTon);
  return tonBaseProy > 0 ? tonBaseProy : pdfPreviewTonMensCalc(RUBRO_EDIT_TN_DIA, PDF_PREVIEW_DIAS_FIJO);
}

function pdfPreviewEscHtml(s) {
  return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function pdfPreviewNumCell(v, dec) {
  var n = parseFloat(v) || 0;
  return n > 0 ? formatNumber(n, dec) : '<span class="text-muted">-</span>';
}

var PDF_PREVIEW_DIAS_FIJO = 22;
var RUBRO_EDIT_TN_DIA = 3500;
var modalEditRubroCache = null;
var modalRubroModo = '';

function rubroGrupoCodDesdeSeleccion() {
  var sub = $('#rub_subgrupo_cod').val();
  if (sub) return sub;
  return $('#rub_grupo_cod').val() || '';
}

function rubroGrupoMesesDesdeSeleccion() {
  var cod = rubroGrupoCodDesdeSeleccion();
  if (!cod) return 12;
  var info = grupoTopeInfo(cod);
  var m = info && info.meses_prorrateo ? parseInt(info.meses_prorrateo, 10) : 12;
  return (m > 0) ? m : 12;
}

function rubroGrupoPpaIdDesdeSeleccion() {
  var cod = rubroGrupoCodDesdeSeleccion();
  if (!cod) return 0;
  var p = buscarPartidaGrupoPorCod(cod);
  return (p && p.ppa_id) ? parseInt(p.ppa_id, 10) : 0;
}

function resetRubroModalPartidas() {
  $('#rub_grupo_cod').prop('disabled', false).val('');
  $('#rub_subgrupo_cod').prop('disabled', true).html('<option value="">-- Subgrupo --</option>');
  $('#rub_ppa_id').prop('disabled', true).html('<option value="">-- Detalle --</option>');
  $('#pdp_rubro_nombre').val('');
  $('#rubro_partida_resumen').empty();
  if (partidasGrupo.length || partidasRubro.length) {
    fillPartidasRubro();
  }
}

function actualizarModalRubroMesesDesdePartida() {
  if (modalRubroModo !== 'add') return;
  var meses = rubroGrupoMesesDesdeSeleccion();
  var grupoPpaId = rubroGrupoPpaIdDesdeSeleccion();
  $('#modal_edit_meses').val(meses);
  $('#modal_edit_meses_inicial').val(meses);
  $('#modal_edit_grupo_ppa_id').val(grupoPpaId);
}

function rubroGrupoMesesInfo(x) {
  if (!x) return null;
  var cod = x.subgrupo_cod || x.grupo_cod || '';
  if (!cod) return null;
  return grupoTopeInfo(cod);
}

function rubroGrupoMesesPpaId(x) {
  if (!x) return 0;
  if (x.subgrupo_ppa_id && parseInt(x.subgrupo_ppa_id, 10) > 0) {
    return parseInt(x.subgrupo_ppa_id, 10);
  }
  if (x.grupo_ppa_id && parseInt(x.grupo_ppa_id, 10) > 0) {
    return parseInt(x.grupo_ppa_id, 10);
  }
  var info = rubroGrupoMesesInfo(x);
  return info && info.ppa_id ? parseInt(info.ppa_id, 10) : 0;
}

function rubroEditTnDia(x) {
  var tn = parseFloat(x && x.pdp_toneladas_base) || 0;
  if (tn >= 50000) {
    var por30 = tn / 30;
    if (por30 >= 3000 && por30 <= 9999) return por30;
  }
  if (tn > 0) {
    var porDias = tn / PDF_PREVIEW_DIAS_FIJO;
    if (porDias >= 3000 && porDias <= 9999) return porDias;
  }
  return RUBRO_EDIT_TN_DIA;
}

function calcModalEditRubroPreview() {
  var factor = parseFloat($('#modal_edit_factor_anual').val()) || 0;
  var meses = parseInt($('#modal_edit_meses').val(), 10) || 12;
  if (meses < 1) meses = 12;
  var tnDia = pptoParseNumber($('#modal_edit_tn_dia').val()) || RUBRO_EDIT_TN_DIA;
  var tonMens = pdfPreviewTonMensCalc(tnDia, PDF_PREVIEW_DIAS_FIJO);
  var usdTonMes = pdfPreviewUsdTonMensualCalc(factor);
  var montoRecalc = pdfPreviewMontoRecalcCalc(tnDia, PDF_PREVIEW_DIAS_FIJO, factor, 0);
  var presupAnual = pdfPreviewPresupAnualCalc(montoRecalc, meses);
  var presupMensual = pdfPreviewPresupMensualCalc(presupAnual);
  $('#modal_edit_ton_mens').val(tonMens > 0 ? formatNumber(tonMens, 0) : '');
  $('#modal_edit_factor_mensual').val(usdTonMes > 0 ? formatNumber(usdTonMes, 6) : '');
  $('#modal_edit_monto_recalc').val(montoRecalc > 0 ? formatCurrency(montoRecalc) : '');
  $('#modal_edit_presup_anual').val(presupAnual > 0 ? formatCurrency(presupAnual) : '');
  $('#modal_edit_presup_mensual').val(presupMensual > 0 ? formatCurrency(presupMensual) : '');
}

function cerrarModalRubro() {
  $('#modalEditRubro').hide();
  modalEditRubroCache = null;
  modalRubroModo = '';
  resetRubroModalPartidas();
}

function abrirModalAgregarRubro() {
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  if (!proy || !ppe) {
    toast('Seleccione proyecto y version antes de agregar rubros.', false);
    return;
  }
  modalRubroModo = 'add';
  modalEditRubroCache = null;
  resetRubroModalPartidas();
  $('#modal_rubro_titulo').text('Agregar rubro');
  $('#btnSaveEditRubroModal').html('<i class="bi bi-plus-lg"></i> Agregar rubro');
  $('#modal_edit_rubro_resumen').hide();
  $('#modal_rubro_partida_block').show();
  $('#modal_edit_pdp_id').val(0);
  $('#modal_edit_ppa_id').val(0);
  $('#modal_edit_grupo_ppa_id').val(0);
  $('#modal_edit_meses_inicial').val(12);
  $('#modal_edit_tn_dia').val(formatNumber(RUBRO_EDIT_TN_DIA, 0));
  $('#modal_edit_dias').val(String(PDF_PREVIEW_DIAS_FIJO));
  $('#modal_edit_factor_anual').val('');
  $('#modal_edit_meses').val(12);
  calcModalEditRubroPreview();
  $('#modalEditRubro').show();
}

function abrirModalEditRubro(x) {
  if (!x || !x.pdp_id) return;
  modalRubroModo = 'edit';
  modalEditRubroCache = x;
  var mesesInfo = rubroGrupoMesesInfo(x);
  var meses = mesesInfo && mesesInfo.meses_prorrateo ? parseInt(mesesInfo.meses_prorrateo, 10) : 12;
  if (meses < 1) meses = 12;
  var tnDia = rubroEditTnDia(x);
  var etiqueta = (x.ppa_codigo_clasificacion || '') + ' — ' + (x.pdp_rubro || x.ppa_descripcion || 'Rubro');
  $('#modal_rubro_titulo').text('Editar rubro');
  $('#btnSaveEditRubroModal').html('<i class="bi bi-check-lg"></i> Guardar cambios');
  $('#modal_edit_rubro_resumen').text(etiqueta).show();
  $('#modal_rubro_partida_block').hide();
  $('#modal_edit_pdp_id').val(x.pdp_id);
  $('#modal_edit_ppa_id').val(x.ppa_id || 0);
  $('#modal_edit_grupo_ppa_id').val(rubroGrupoMesesPpaId(x));
  $('#modal_edit_meses_inicial').val(meses);
  $('#modal_edit_tn_dia').val(formatNumber(tnDia, 0));
  $('#modal_edit_dias').val(String(PDF_PREVIEW_DIAS_FIJO));
  $('#modal_edit_factor_anual').val(x.pdp_factor_anual_tonelada || '');
  $('#modal_edit_meses').val(meses);
  calcModalEditRubroPreview();
  $('#modalEditRubro').show();
}

function guardarModalEditRubro() {
  var isAdd = modalRubroModo === 'add';
  var x = modalEditRubroCache;
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  var pdpId = isAdd ? 0 : (parseInt($('#modal_edit_pdp_id').val(), 10) || 0);
  var ppaId = isAdd ? (parseInt($('#rub_ppa_id').val(), 10) || 0) : (parseInt($('#modal_edit_ppa_id').val(), 10) || 0);
  var factor = parseFloat($('#modal_edit_factor_anual').val()) || 0;
  var meses = parseInt($('#modal_edit_meses').val(), 10) || 12;
  var mesesInicial = parseInt($('#modal_edit_meses_inicial').val(), 10) || 12;
  var grupoPpaId = parseInt($('#modal_edit_grupo_ppa_id').val(), 10) || 0;
  if (!proy || !ppe) {
    toast('Seleccione proyecto y version.', false);
    return;
  }
  if (!isAdd && (!pdpId || !ppaId)) {
    toast('Rubro invalido.', false);
    return;
  }
  if (isAdd && !ppaId) {
    toast('Seleccione grupo, subgrupo (si aplica) y partida detalle.', false);
    return;
  }
  if (factor <= 0) {
    toast('Ingrese $/Ton anual mayor a cero.', false);
    return;
  }
  if (meses < 1 || meses > 999) {
    toast('Los meses deben estar entre 1 y 999.', false);
    return;
  }
  if (isAdd) {
    grupoPpaId = rubroGrupoPpaIdDesdeSeleccion();
    if (!grupoPpaId) {
      toast('No se encontro el grupo/subgrupo para los meses de prorrateo.', false);
      return;
    }
  }
  var tnDia = pptoParseNumber($('#modal_edit_tn_dia').val()) || RUBRO_EDIT_TN_DIA;
  var tonMens = pdfPreviewTonMensCalc(tnDia, PDF_PREVIEW_DIAS_FIJO);
  var montoRecalc = pdfPreviewMontoRecalcCalc(tnDia, PDF_PREVIEW_DIAS_FIJO, factor, 0);
  var presupAnual = pdfPreviewPresupAnualCalc(montoRecalc, meses);
  if (presupAnual <= 0) {
    toast('No se pudo calcular el presupuesto anual.', false);
    return;
  }
  var rubroNombre = isAdd ? rubroNombreDesdePartida(ppaId) : (x.pdp_rubro || x.ppa_descripcion || '');

  function postSaveRubro() {
    $.post(API, {
      action: 'save_rubro',
      pdp_id: pdpId,
      proy_id: proy,
      ppe_id: ppe,
      ppa_id: ppaId,
      pdp_rubro: rubroNombre,
      pdp_factor_anual_tonelada: factor,
      pdp_presupuesto_anual: presupAnual,
      pdp_toneladas_base: tonMens,
      pdp_tn_dia: tnDia
    }, function(r) {
      if (r.status !== 'success') {
        toast(r.message || 'No se pudo guardar el rubro.', false);
        return;
      }
      toast(r.message, true);
      if (r.warning) toastWarn(r.warning);
      cerrarModalRubro();
      loadRubros();
    }, 'json').fail(function() {
      toast('Error de red al guardar el rubro.', false);
    });
  }

  if (meses !== mesesInicial && grupoPpaId > 0) {
    $.post(API, {
      action: 'save_grupo_meses',
      ppa_id: grupoPpaId,
      ppa_meses_prorrateo: meses
    }, function(r) {
      if (r.status !== 'success') {
        toast(r.message || 'No se pudieron guardar los meses.', false);
        return;
      }
      postSaveRubro();
    }, 'json').fail(function() {
      toast('Error de red al guardar los meses.', false);
    });
    return;
  }
  postSaveRubro();
}

function pdfPreviewDiasFijos() {
  return PDF_PREVIEW_DIAS_FIJO;
}

function pdfPreviewDescCell(codigo, desc) {
  return '<input type="text" class="form-control input-sm pdf-preview-desc" data-codigo="' + pdfPreviewEscHtml(codigo) + '" value="' + pdfPreviewEscHtml(desc) + '" />';
}

function pdfPreviewDiasCellHtml(esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  return '<span class="pdf-preview-dias-fijo">' + PDF_PREVIEW_DIAS_FIJO + '</span>';
}

function pdfPreviewFactorCellHtml(codigo, factor, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var fac = parseFloat(factor);
  var val = (!isNaN(fac) && fac > 0) ? fac : '';
  return '<input type="number" step="0.0000001" min="0" class="form-control input-sm pdf-preview-factor" data-codigo="' + pdfPreviewEscHtml(codigo) + '" value="' + val + '" />';
}

function pdfPreviewMesesGlobalValue() {
  var m = parseInt($('#pdf_preview_meses_global').val(), 10);
  return (m > 0) ? m : 12;
}

function pdfPreviewMesesValue(codigo) {
  if (codigo) {
    var $tr = $('#pdf_preview_tbody tr[data-es-driver="1"]').filter(function() {
      return $(this).find('.pdf-preview-factor').data('codigo') === codigo;
    }).first();
    if ($tr.length) {
      var m = parseInt($tr.attr('data-meses'), 10);
      if (m > 0) return m;
    }
  }
  return pdfPreviewMesesGlobalValue();
}

function pdfPreviewMesesCellHtml(codigo, meses, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var m = parseInt(meses, 10);
  if (!m || m < 1) m = pdfPreviewMesesGlobalValue();
  return '<span class="pdf-preview-meses-val" data-codigo="' + pdfPreviewEscHtml(codigo) + '">' + m + '</span>';
}

function aplicarPdfPreviewMesesGlobal(val) {
  var m = parseInt(val, 10);
  if (!m || m < 1) return;
  $('#pdf_preview_meses_global').val(m);
  $('.pdf-preview-meses-val').text(m);
  $('#pdf_preview_tbody tr[data-es-driver="1"]').attr('data-meses', m);
  recalcPdfPreviewFilas();
}

function pdfPreviewTonMensCalc(tnDia, dias) {
  var tn = parseFloat(tnDia) || 0;
  var d = parseInt(dias, 10) || 0;
  if (tn <= 0 || d <= 0) return 0;
  return tn * d;
}

function pdfPreviewUsdTonMensualCalc(factor) {
  var fac = parseFloat(factor) || 0;
  if (fac <= 0) return 0;
  return fac / 12;
}

function pdfPreviewMontoRecalcCalc(tnDia, dias, factor, pdfMonto) {
  var tonMens = pdfPreviewTonMensCalc(tnDia, dias);
  var fac = parseFloat(factor) || 0;
  if (tonMens > 0 && fac > 0) return tonMens * fac;
  var monto = parseFloat(pdfMonto) || 0;
  if (monto > 0) return monto;
  var ton = pdfPreviewTonBase();
  if (ton > 0 && fac > 0) return ton * fac;
  return 0;
}

function pdfPreviewPresupAnualCalc(montoRecalc, meses) {
  var monto = parseFloat(montoRecalc) || 0;
  if (monto <= 0) return 0;
  var m = parseInt(meses, 10) || 12;
  if (m < 1) m = 12;
  return monto / (m / 12);
}

function pdfPreviewPresupMensualCalc(presupAnual) {
  var anual = parseFloat(presupAnual) || 0;
  if (anual <= 0) return 0;
  return anual / 12;
}

function pdfPreviewRowCalcFromTr($tr) {
  var cod = $tr.find('.pdf-preview-factor').data('codigo');
  var tnDia = parseFloat($tr.attr('data-tn-dia')) || 0;
  var dias = pdfPreviewDiasFijos();
  var factor = parseFloat($tr.find('.pdf-preview-factor').val()) || 0;
  var pdfMonto = parseFloat($tr.attr('data-monto-recalc-pdf')) || 0;
  var meses = pdfPreviewMesesValue(cod);
  var tonMens = pdfPreviewTonMensCalc(tnDia, dias);
  var usdTonMes = pdfPreviewUsdTonMensualCalc(factor);
  var montoRecalc = pdfPreviewMontoRecalcCalc(tnDia, dias, factor, pdfMonto);
  var presupAnual = pdfPreviewPresupAnualCalc(montoRecalc, meses);
  var presupMensual = pdfPreviewPresupMensualCalc(presupAnual);
  return {
    cod: cod,
    tonMens: tonMens,
    usdTonMes: usdTonMes,
    montoRecalc: montoRecalc,
    presupAnual: presupAnual,
    presupMensual: presupMensual,
    factor: factor
  };
}

function pdfPreviewTonMensCellHtml(codigo, tnDia, dias, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var tonMens = pdfPreviewTonMensCalc(tnDia, dias);
  return '<span class="pdf-preview-ton-mens" data-codigo="' + pdfPreviewEscHtml(codigo) + '">' + (tonMens > 0 ? formatNumber(tonMens, 0) : '-') + '</span>';
}

function pdfPreviewUsdTonMensualCellHtml(codigo, factor, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var mes = pdfPreviewUsdTonMensualCalc(factor);
  return '<span class="pdf-preview-usd-ton-mes" data-codigo="' + pdfPreviewEscHtml(codigo) + '">' + (mes > 0 ? formatNumber(mes, 4) : '-') + '</span>';
}

function pdfPreviewMontoRecalcCellHtml(codigo, tnDia, dias, factor, pdfMonto, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var monto = pdfPreviewMontoRecalcCalc(tnDia, dias, factor, pdfMonto);
  return '<span class="pdf-preview-monto-recalc" data-codigo="' + pdfPreviewEscHtml(codigo) + '">' + (monto > 0 ? formatCurrency(monto) : '-') + '</span>';
}

function pdfPreviewPresupMensualCellHtml(codigo, tnDia, dias, factor, pdfMonto, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var meses = pdfPreviewMesesValue(codigo);
  var monto = pdfPreviewMontoRecalcCalc(tnDia, dias, factor, pdfMonto);
  var mensual = pdfPreviewPresupMensualCalc(pdfPreviewPresupAnualCalc(monto, meses));
  return '<span class="pdf-preview-presup-mes" data-codigo="' + pdfPreviewEscHtml(codigo) + '">' + (mensual > 0 ? formatCurrency(mensual) : '-') + '</span>';
}

function pdfPreviewPresupAnualCellHtml(codigo, tnDia, dias, factor, pdfMonto, esDriver) {
  if (!esDriver) return '<span class="text-muted">-</span>';
  var meses = pdfPreviewMesesValue(codigo);
  var monto = pdfPreviewMontoRecalcCalc(tnDia, dias, factor, pdfMonto);
  var anual = pdfPreviewPresupAnualCalc(monto, meses);
  return '<span class="pdf-preview-presup-anual" data-codigo="' + pdfPreviewEscHtml(codigo) + '">' + (anual > 0 ? formatCurrency(anual) : '-') + '</span>';
}

function recalcPdfPreviewFila($tr) {
  if (!$tr.length || $tr.attr('data-es-driver') !== '1') return;
  var row = pdfPreviewRowCalcFromTr($tr);
  $('.pdf-preview-ton-mens[data-codigo="' + row.cod + '"]').text(row.tonMens > 0 ? formatNumber(row.tonMens, 0) : '-');
  $('.pdf-preview-usd-ton-mes[data-codigo="' + row.cod + '"]').text(row.usdTonMes > 0 ? formatNumber(row.usdTonMes, 4) : '-');
  $('.pdf-preview-monto-recalc[data-codigo="' + row.cod + '"]').text(row.montoRecalc > 0 ? formatCurrency(row.montoRecalc) : '-');
  $('.pdf-preview-presup-anual[data-codigo="' + row.cod + '"]').text(row.presupAnual > 0 ? formatCurrency(row.presupAnual) : '-');
  $('.pdf-preview-presup-mes[data-codigo="' + row.cod + '"]').text(row.presupMensual > 0 ? formatCurrency(row.presupMensual) : '-');
}

function recalcPdfPreviewFilas() {
  $('#pdf_preview_tbody tr[data-es-driver="1"]').each(function() {
    recalcPdfPreviewFila($(this));
  });
  recalcPdfPreviewTotales();
}

function pdfPreviewMergeRubroMeta(row, rubroDetailMap) {
  var d = rubroDetailMap[row.codigo] || {};
  row.presup_anual_pdf = parseFloat(d.presup_anual_pdf) || 0;
  row.tn_dia = parseFloat(d.tn_dia) || 0;
  row.dias_laborables = pdfPreviewDiasFijos();
  row.meses = parseFloat(d.meses) || 12;
  if (!row.meses || row.meses <= 0) row.meses = 12;
  row.usd_ton = parseFloat(d.usd_ton) || 0;
  row.monto_recalc = parseFloat(d.monto_recalc) || 0;
  if (row.esDriver && (!row.factor || parseFloat(row.factor) <= 0) && parseFloat(d.factor_anual) > 0) {
    row.factor = d.factor_anual;
  }
  return row;
}

function syncPdfPreviewToPayload() {
  if (!pdfImportPayload) return;
  $('.pdf-preview-desc').each(function() {
    var cod = $(this).data('codigo');
    var val = $.trim($(this).val());
    if (pdfImportPayload.partidas) {
      $.each(pdfImportPayload.partidas, function(i, p) {
        if (p.codigo === cod) p.descripcion = val;
      });
    }
    if (pdfImportPayload.rubros) {
      $.each(pdfImportPayload.rubros, function(i, r) {
        if (r.codigo === cod) r.descripcion = val;
      });
    }
  });
  $('.pdf-preview-factor').each(function() {
    var cod = $(this).data('codigo');
    var val = parseFloat($(this).val()) || 0;
    if (pdfImportPayload.rubros) {
      $.each(pdfImportPayload.rubros, function(i, r) {
        if (r.codigo === cod) r.factor_anual = val;
      });
    }
  });
  var mesesGlobal = pdfPreviewMesesGlobalValue();
  if (pdfImportPayload.partidas) {
    $.each(pdfImportPayload.partidas, function(i, p) {
      if (p.clase === 'G') {
        p.meses_prorrateo = mesesGlobal;
      }
    });
  }
  if (pdfImportPayload.rubros) {
    $.each(pdfImportPayload.rubros, function(i, r) {
      r.dias_laborables = pdfPreviewDiasFijos();
      var $tr = $('#pdf_preview_tbody tr[data-es-driver="1"]').filter(function() {
        return $(this).find('.pdf-preview-factor').data('codigo') === r.codigo;
      }).first();
      if ($tr.length) {
        var mesesRow = parseInt($tr.attr('data-meses'), 10);
        if (mesesRow > 0) r.meses = mesesRow;
        var row = pdfPreviewRowCalcFromTr($tr);
        r.monto_recalc = row.montoRecalc;
        r.presupuesto_anual = row.presupAnual;
        r.presupuesto_mensual = row.presupMensual;
      } else if (!r.meses || r.meses <= 0) {
        r.meses = mesesGlobal;
      }
    });
  }
}

function recalcPdfPreviewAnuales() {
  recalcPdfPreviewFilas();
}

function recalcPdfPreviewTotales() {
  var totalFactor = 0;
  var totalTonMens = 0;
  var totalUsdTonMes = 0;
  var totalPdfAnual = 0;
  var totalMontoRecalc = 0;
  var totalPresupAnual = 0;
  var totalPresupMensual = 0;
  var nDrivers = 0;
  $('#pdf_preview_tbody tr[data-es-driver="1"]').each(function() {
    nDrivers++;
    var $tr = $(this);
    var pdfAnual = parseFloat($tr.attr('data-pdf-anual')) || 0;
    var row = pdfPreviewRowCalcFromTr($tr);
    totalFactor += row.factor;
    totalTonMens += row.tonMens;
    totalUsdTonMes += row.usdTonMes;
    totalPdfAnual += pdfAnual;
    totalMontoRecalc += row.montoRecalc;
    totalPresupAnual += row.presupAnual;
    totalPresupMensual += row.presupMensual;
  });
  var $foot = $('#pdf_preview_tfoot');
  if (!$foot.length || nDrivers === 0) {
    $foot.empty().hide();
    return;
  }
  $foot.show().html(
    '<tr class="pdf-preview-total-row">'
    + '<td colspan="4" class="text-right"><strong>Total importacion</strong> <span class="text-muted">(' + nDrivers + ' rubro' + (nDrivers === 1 ? '' : 's') + ' driver)</span></td>'
    + '<td class="text-right">' + (totalPdfAnual > 0 ? formatCurrency(totalPdfAnual) : '-') + '</td>'
    + '<td colspan="2"></td>'
    + '<td class="text-right"><strong>' + formatNumber(totalTonMens, 0) + '</strong></td>'
    + '<td class="text-right"><strong id="pdf_preview_total_factor">' + formatNumber(totalFactor, 4) + '</strong></td>'
    + '<td class="text-right"><strong>' + formatNumber(totalUsdTonMes, 4) + '</strong></td>'
    + '<td class="text-right">' + (totalMontoRecalc > 0 ? formatCurrency(totalMontoRecalc) : '-') + '</td>'
    + '<td></td>'
    + '<td class="text-right"><strong id="pdf_preview_total_presup_mes">' + formatCurrency(totalPresupMensual) + '</strong></td>'
    + '<td class="text-right"><strong id="pdf_preview_total_presup_anual">' + formatCurrency(totalPresupAnual) + '</strong></td>'
    + '</tr>'
  );
}

function renderPdfPreview(data) {
  var partidas = data.partidas || [];
  var rubros = data.rubros || [];
  var mesesGlobalImport = 0;
  if (data.payload && data.payload.meses_prorrateo_global) {
    mesesGlobalImport = parseInt(data.payload.meses_prorrateo_global, 10) || 0;
  } else if (data.meses_prorrateo_global) {
    mesesGlobalImport = parseInt(data.meses_prorrateo_global, 10) || 0;
  }
  if (mesesGlobalImport > 0) {
    $('#pdf_preview_meses_global').val(mesesGlobalImport);
  }
  var catalogo = data.catalogo || {};
  var conflictos = data.conflictos || [];
  var conflictMap = {};
  $.each(conflictos, function(i, c) {
    if (c.codigo) conflictMap[c.codigo] = c;
  });

  var factorMap = {};
  var driverMap = {};
  var rubroDetailMap = {};
  $.each(rubros, function(i, r) {
    factorMap[r.codigo] = r.factor_anual;
    driverMap[r.codigo] = true;
    rubroDetailMap[r.codigo] = r;
  });

  var rows = [];
  $.each(partidas, function(i, p) {
    var cat = catalogo[p.codigo] || {};
    var estRow = pptoEstadoImportRow(p.codigo, catalogo);
    rows.push({
      codigo: p.codigo,
      descripcion: p.descripcion,
      clase: p.clase || 'G',
      factor: factorMap[p.codigo] || '',
      esDriver: !!driverMap[p.codigo],
      estado: estRow.estado,
      catInfo: estRow.cat,
      conflicto: conflictMap[p.codigo] || null
    });
  });
  $.each(rubros, function(i, r) {
    var found = false;
    $.each(rows, function(j, row) { if (row.codigo === r.codigo) found = true; });
    if (!found) {
      var estRow = pptoEstadoImportRow(r.codigo, catalogo);
      rows.push({
        codigo: r.codigo,
        descripcion: r.descripcion,
        clase: 'D',
        factor: r.factor_anual,
        esDriver: true,
        estado: estRow.estado,
        catInfo: estRow.cat,
        conflicto: conflictMap[r.codigo] || null
      });
    }
  });

  var tb = $('#pdf_preview_tbody').empty();
  if (!rows.length) {
    tb.append('<tr><td colspan="14" class="text-center text-muted">Sin datos detectados.</td></tr>');
    return;
  }
  rows.sort(function(a, b) { return a.codigo > b.codigo ? 1 : (a.codigo < b.codigo ? -1 : 0); });
  $.each(rows, function(i, row) {
    row = pdfPreviewMergeRubroMeta(row, rubroDetailMap);
    if (mesesGlobalImport > 0 && row.esDriver) {
      row.meses = mesesGlobalImport;
    }
    var trClass = row.estado === 'conflicto' ? ' class="danger"' : '';
    var title = row.conflicto && row.conflicto.mensaje ? ' title="' + row.conflicto.mensaje.replace(/"/g, '&quot;') + '"' : '';
    var esDriver = !!row.esDriver;
    var diasVal = pdfPreviewDiasFijos();
    var dataAttrs = ' data-es-driver="' + (esDriver ? '1' : '0') + '"'
      + ' data-pdf-anual="' + (parseFloat(row.presup_anual_pdf) || 0) + '"'
      + ' data-monto-recalc-pdf="' + (parseFloat(row.monto_recalc) || 0) + '"'
      + ' data-tn-dia="' + (parseFloat(row.tn_dia) || 0) + '"'
      + ' data-meses="' + (parseInt(row.meses, 10) || 12) + '"';
    tb.append('<tr' + trClass + dataAttrs + '>'
      + '<td><strong>' + pdfPreviewEscHtml(row.codigo) + '</strong></td>'
      + '<td' + title + '>' + pdfPreviewDescCell(row.codigo, row.descripcion)
      + (row.conflicto && row.conflicto.mensaje ? ' <small class="text-danger">(' + row.conflicto.mensaje + ')</small>' : '') + '</td>'
      + '<td>' + pptoClaseEtiqueta(row.clase) + '</td>'
      + '<td>' + pptoEstadoImportLabel(row.estado, row.catInfo) + '</td>'
      + '<td class="text-right">' + pdfPreviewNumCell(row.presup_anual_pdf, 2) + '</td>'
      + '<td class="text-right">' + pdfPreviewNumCell(row.tn_dia, 0) + '</td>'
      + '<td class="text-right">' + pdfPreviewDiasCellHtml(esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewTonMensCellHtml(row.codigo, row.tn_dia, diasVal, esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewFactorCellHtml(row.codigo, row.factor, esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewUsdTonMensualCellHtml(row.codigo, row.factor, esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewMontoRecalcCellHtml(row.codigo, row.tn_dia, diasVal, row.factor, row.monto_recalc, esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewMesesCellHtml(row.codigo, row.meses, esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewPresupMensualCellHtml(row.codigo, row.tn_dia, diasVal, row.factor, row.monto_recalc, esDriver) + '</td>'
      + '<td class="text-right">' + pdfPreviewPresupAnualCellHtml(row.codigo, row.tn_dia, diasVal, row.factor, row.monto_recalc, esDriver) + '</td>'
      + '</tr>');
  });
  if (mesesGlobalImport > 0) {
    $('#pdf_preview_meses_global').val(mesesGlobalImport);
    $('.pdf-preview-meses-val').text(mesesGlobalImport);
    $('#pdf_preview_tbody tr[data-es-driver="1"]').attr('data-meses', mesesGlobalImport);
  } else if (rubros.length) {
    var m0 = parseInt(rubros[0].meses, 10);
    if (m0 > 0) $('#pdf_preview_meses_global').val(m0);
  }
  recalcPdfPreviewFilas();
  recalcPdfPreviewTotales();
}

function actualizarPdfImportUi(conflictos, bloqueado) {
  pdfImportConflictos = conflictos || [];
  var hayConflictos = bloqueado || (pdfImportConflictos.length > 0);
  if (hayConflictos) {
    var msgs = [];
    $.each(pdfImportConflictos, function(i, c) {
      if (c.mensaje) msgs.push(c.mensaje);
    });
    $('#pdf_preview_conflictos').html('<strong>No se puede importar hasta corregir en Admin:</strong><ul style="margin:6px 0 0 16px;padding:0;">'
      + $.map(msgs, function(m) { return '<li>' + m + '</li>'; }).join('') + '</ul>').show();
    $('#btnConfirmImportPdf').prop('disabled', true).addClass('disabled');
    $('#pdf_import_status').text('Importacion bloqueada por conflictos de catalogo.');
  } else {
    $('#pdf_preview_conflictos').hide();
    $('#btnConfirmImportPdf').prop('disabled', false).removeClass('disabled');
  }
}

function parsePdfFile() {
  var fileInput = document.getElementById('pdf_import_file');
  if (!fileInput || !fileInput.files || !fileInput.files.length) {
    toast('Seleccione un archivo PDF, Excel o CSV.', false);
    return;
  }
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  if (!proy || !ppe) {
    toast('Seleccione proyecto y version antes de analizar el archivo.', false);
    return;
  }

  var fd = new FormData();
  fd.append('action', 'parse_pdf');
  fd.append('pdf_file', fileInput.files[0]);
  fd.append('proy_id', proy);
  fd.append('ppe_id', ppe);

  $('#pdf_import_status').text('Analizando archivo...');
  $.ajax({
    url: API,
    type: 'POST',
    data: fd,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(r) {
      if (r.status !== 'success') {
        $('#pdf_import_status').text('');
        toast(r.message || 'No se pudo analizar el archivo.', false);
        return;
      }
      pdfImportPayload = r.payload || null;
      var tonCosto = parseFloat(r.ton_costo_mes) || 0;
      if (tonCosto <= 0 && parseFloat(r.ton_detectada) > 0) {
        var det = parseFloat(r.ton_detectada) || 0;
        tonCosto = (det >= 70000 && det < 95000) ? det : pdfPreviewTonMensCalc(RUBRO_EDIT_TN_DIA, PDF_PREVIEW_DIAS_FIJO);
      }
      if (tonCosto <= 0) {
        tonCosto = pdfPreviewTonMensCalc(RUBRO_EDIT_TN_DIA, PDF_PREVIEW_DIAS_FIJO);
      }
      pdfImportPreviewTon = tonCosto;
      $('#pv_toneladas_costo_mes').val(tonCosto);
      var tonIngresoSrv = tonBaseVersionMes(parseFloat(r.ton_ingreso_mes) || parseFloat(r.ton_base) || 0);
      var tonIngresoActual = parseFloat($('#pv_toneladas_base_mes').val()) || tonBaseProy || 0;
      if (tonIngresoActual <= 0 && tonIngresoSrv > 0) {
        $('#pv_toneladas_base_mes').val(tonIngresoSrv);
        tonBaseProy = tonIngresoSrv;
        tonIngresoActual = tonIngresoSrv;
      } else if (tonIngresoActual > 0) {
        tonBaseProy = tonIngresoActual;
      }
      recalcPdfPreviewAnuales();
      var resumen = 'Archivo: ' + (r.archivo || '')
        + ' | Partidas: ' + (r.partidas ? r.partidas.length : 0)
        + ' | Rubros driver: ' + (r.rubros ? r.rubros.length : 0)
        + ' | Ton/mes costo (egreso): ' + formatNumber(tonCosto, 0)
        + ' | Ton ingresos (mes): ' + formatNumber(tonIngresoActual > 0 ? tonIngresoActual : tonIngresoSrv, 0);
      $('#pdf_preview_resumen').text(resumen);
      if (r.warnings && r.warnings.length) {
        $('#pdf_preview_warnings').text(r.warnings.join(' ')).show();
      } else {
        $('#pdf_preview_warnings').hide();
      }
      renderPdfPreview(r);
      actualizarPdfImportUi(r.conflictos, r.import_bloqueado);
      $('#btnImportPdf').show();
      if (!r.import_bloqueado) {
        $('#pdf_import_status').text('Listo para importar.');
      }
      $('#modalPdfPreview').show();
    },
    error: function(xhr) {
      $('#pdf_import_status').text('');
      var msg = 'Error al analizar el PDF.';
      if (xhr && xhr.responseText) {
        try {
          var j = JSON.parse(xhr.responseText);
          if (j && j.message) {
            msg = j.message;
          }
        } catch (e) {
          if (xhr.status === 403) {
            msg = 'Sin permiso. Recargue la sesion e intente de nuevo.';
          } else if (xhr.status === 500) {
            msg = 'Error interno al leer el PDF (archivo muy grande o formato no soportado).';
          }
        }
      }
      toast(msg, false);
    }
  });
}

function importPdfPayload() {
  if (!pdfImportPayload) {
    toast('Primero analice un PDF.', false);
    return;
  }
  if (pdfImportConflictos && pdfImportConflictos.length) {
    toast('Corrija los conflictos de Grupo/Detalle en Admin antes de importar.', false);
    return;
  }
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  var ton = $('#pv_toneladas_base_mes').val();
  if (!proy || !ppe) {
    toast('Seleccione proyecto y version.', false);
    return;
  }
  if (!confirm('Se crearan/actualizaran partidas y rubros del PDF. Los rubros con el mismo codigo se actualizan (incluye nombre y montos). Continuar?')) {
    return;
  }

  syncPdfPreviewToPayload();

  $.ajax({
    url: API,
    type: 'POST',
    data: {
      action: 'import_pdf',
      proy_id: proy,
      ppe_id: ppe,
      pv_toneladas_base_mes: ton,
      payload_json: JSON.stringify(pdfImportPayload)
    },
    dataType: 'json',
    success: function(r) {
      if (r.status === 'success') {
        toast(r.message, true);
        $('#modalPdfPreview').hide();
        pdfImportPayload = null;
        $('#btnImportPdf').hide();
        $('#pdf_import_status').text('');
        if (r.pv_toneladas_base_mes) {
          tonBaseProy = tonBaseVersionMes(parseFloat(r.pv_toneladas_base_mes) || tonBaseProy);
          $('#pv_toneladas_base_mes').val(tonBaseProy);
        }
        if (r.stats && r.stats.ton_costo_mes) {
          $('#pv_toneladas_costo_mes').val(parseFloat(r.stats.ton_costo_mes));
        }
        loadCatalogos(function() {
          reloadRubrosSection();
        });
      } else {
        toast(r.message || 'Error al importar.', false);
        if (r.conflictos && r.conflictos.length) {
          actualizarPdfImportUi(r.conflictos, true);
        }
      }
    },
    error: function() {
      toast('Error de red al importar el PDF.', false);
    }
  });
}



$('#btnSaveProy').click(function(){

  $.post(API, {action:'save', is_edit:$('#is_edit').val(), proy_id:$('#proy_id').val(), proy_nombre:$('#proy_nombre').val(), proy_estado:$('#proy_estado').val(), plt_id:$('#plt_id').val()}, function(r){

    toast(r.message, r.status==='success'); if(r.status==='success'){ loadProyectos(); $('#is_edit').val(0); }

  }, 'json');

});

$('#btnSaveTonBase').click(function(){

  var proy = $('#rub_proy_id').val();

  var ppe = $('#rub_ppe_id').val();

  var ton = $('#pv_toneladas_base_mes').val();

  if (!proy || !ppe) { toast('Seleccione proyecto y version.', false); return; }

  $.post(API, versionTonPayload(0), function(r){

    toast(r.message, r.status==='success');

    if (r.status==='success') {
      if (r.pv_toneladas_base_mes) {
        tonBaseProy = tonBaseVersionMes(parseFloat(r.pv_toneladas_base_mes) || tonBaseProy);
        $('#pv_toneladas_base_mes').val(tonBaseProy);
      }
      if (r.pv_toneladas_costo_mes) {
        $('#pv_toneladas_costo_mes').val(parseFloat(r.pv_toneladas_costo_mes));
      }
      reloadRubrosSection();
    }

  }, 'json');

});

$('#btnAplicarTonRubros').click(function(){

  var proy = $('#rub_proy_id').val();

  var ppe = $('#rub_ppe_id').val();

  var tonCosto = $.trim($('#pv_toneladas_costo_mes').val());

  if (!proy || !ppe) { toast('Seleccione proyecto y version.', false); return; }

  if (!tonCosto || parseFloat(tonCosto) <= 0) {
    toast('Ingrese ton costo egreso (mes), ej. 77000.', false);
    return;
  }

  if (!confirm('Aplicar ' + formatNumber(parseFloat(tonCosto), 0) + ' ton/mes (costo egreso) a todos los rubros?\n\n'
    + '$/Ton anual y mensual quedan fijos (como a 77.000).\n'
    + 'Se recalcula el presupuesto Base PDF (anual = ton x $/Ton).\n'
    + 'Proyectada y Real siguen con el presupuesto anual original del Excel.')) return;

  $.post(API, versionTonPayload(1), function(r){

    toast(r.message, r.status==='success');

    if (r.status==='success') {
      if (r.pv_toneladas_base_mes) {
        tonBaseProy = tonBaseVersionMes(parseFloat(r.pv_toneladas_base_mes) || tonBaseProy);
        $('#pv_toneladas_base_mes').val(tonBaseProy);
      }
      if (r.pv_toneladas_costo_mes) {
        $('#pv_toneladas_costo_mes').val(parseFloat(r.pv_toneladas_costo_mes) || tonCosto);
      }
      reloadRubrosSection();
    }

  }, 'json');

});

$('#btnAbrirAgregarRubro').click(abrirModalAgregarRubro);

$(document).on('click','.btnEdit', function(){

  var p=JSON.parse($(this).attr('data-json'));

  $('#is_edit').val(1); $('#proy_id').val(p.proy_id).prop('readonly',true);

  $('#proy_nombre').val(p.proy_nombre); $('#proy_estado').val(p.proy_estado); $('#plt_id').val(p.plt_id||'');

});

$(document).on('click','.btn-edit-rubro', function(){
  var x = JSON.parse($(this).attr('data-json'));
  abrirModalEditRubro(x);
});

$(document).on('click', '.btn-del-rubro', function() {
  var x = JSON.parse($(this).attr('data-json'));
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  if (!proy || !ppe) {
    toast('Seleccione proyecto y version.', false);
    return;
  }
  var etiqueta = (x.ppa_codigo_clasificacion || '') + ' - ' + (x.pdp_rubro || 'rubro');
  if (!confirm('Eliminar el rubro "' + etiqueta + '" de este proyecto?\n\nEsta accion no se puede deshacer.')) {
    return;
  }
  $.post(API, {
    action: 'delete_rubro',
    pdp_id: x.pdp_id,
    proy_id: proy,
    ppe_id: ppe
  }, function(r) {
    if (r.status !== 'success') {
      toast(r.message || 'No se pudo eliminar el rubro.', false);
      return;
    }
    if (modalEditRubroCache && modalEditRubroCache.pdp_id == x.pdp_id) {
      cerrarModalRubro();
    }
    toast(r.message || 'Rubro eliminado.', true);
    loadRubros();
  }, 'json').fail(function() {
    toast('Error de red al eliminar el rubro.', false);
  });
});

$('#rub_proy_id, #rub_ppe_id').change(function(){

  publicarPreviewCache = null;
  cerrarModalRubro();

  reloadRubrosSection();

});

$('#pv_toneladas_base_mes').on('input', function(){
  tonBaseProy = tonBaseVersionMes($(this).val());
  recalcPdfPreviewAnuales();
  refreshVistaPresupuesto();
});

$('#pv_toneladas_costo_mes').on('input change', function() {
  refreshVistaPresupuesto();
});

$(document).on('click', '.cuadro-vista-btn', function() {
  var v = $(this).data('vista');
  if (!v || v === cuadroVista) return;
  cuadroVista = v;
  if (v !== 'anual') {
    cuadroMes = cuadroMesDefault || cuadroMes;
  }
  actualizarCuadroPeriodoUi();
  loadRubros();
});

$('#cuadro_mes_sel').change(function() {
  cuadroMes = parseInt($(this).val(), 10) || cuadroMesDefault;
  loadRubros();
});

$('#cuadro_anio_precio').change(function() {
  cuadroAnioPrecio = parseInt($(this).val(), 10) || 0;
  $('#aj_anio_precio').val(cuadroAnioPrecio || '');
  loadRubros();
});

$('#btnExportCuadroExcel').click(function() {
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  if (!proy || !ppe) {
    toast('Seleccione proyecto y version antes de exportar.', false);
    return;
  }
  var qs = $.param({
    proy_id: proy,
    ppe_id: ppe,
    cuadro_vista: cuadroVista || 'anual',
    cuadro_mes: cuadroMes || '',
    escenario: escenarioActivo || 'esperada',
    anio_precio: cuadroAnioPrecio || $('#cuadro_anio_precio').val() || ''
  });
  window.open('ppto_proyectos_cuadro_export.php?' + qs, '_blank');
});

$('#btnAjGuardarCfg').click(function() {
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  if (!proy || !ppe) { toast('Seleccione proyecto y version.', false); return; }
  $.post(API, {
    action: 'ajuste_cfg_save',
    proy_id: proy,
    ppe_id: ppe,
    costo_capital_pct: $('#aj_capital_pct').val(),
    gad_factor_ton: $('#aj_gad_factor').val(),
    gad_monto_objetivo: $('#aj_gad_objetivo').val(),
    gad_recuperado_acum: $('#aj_gad_acum').val(),
    ajuste_activo: $('#aj_activo').is(':checked') ? 1 : 0
  }, function(r) {
    toast(r.message || '', r.status === 'success');
    if (r.status === 'success' && r.cfg) {
      fillAjusteCfgForm(r.cfg);
      loadRubros();
    }
  }, 'json');
});

$('#btnAjSimular').click(function() { simularAjuste(false); });
$('#btnAjAplicar').click(function() { simularAjuste(true); });

/* === CUADRO_PARTIDA_FINAL_UI (reversible) START === */
$('#aj_activo').on('change', function() {
  if (ajusteCfgCache) {
    ajusteCfgCache.ajuste_activo = $(this).is(':checked') ? 1 : 0;
  }
  renderCuadroRubros(rubrosCache, gruposTopeCache);
  actualizarResumenEconomico(rubrosCache);
});
/* === CUADRO_PARTIDA_FINAL_UI (reversible) END === */

$('#btnAjPrecios').click(function() {
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  if (!proy || !ppe) { toast('Seleccione proyecto y version.', false); return; }
  $.getJSON(API, { action: 'ajuste_precios_list', proy_id: proy, ppe_id: ppe }, function(r) {
    ajustePreciosCache = (r.status === 'success') ? (r.precios || []) : [];
    renderPreciosRows(ajustePreciosCache);
    $('#modalAjustePrecios').show();
  });
});

$('#btnAjAddPrecio').click(function() {
  var anio = (new Date()).getFullYear();
  var last = $('#tblAjustePrecios .aj-precio-anio').last().val();
  if (last) anio = parseInt(last, 10) + 1;
  if ($('#tblAjustePrecios tbody tr td.text-muted').length) {
    $('#tblAjustePrecios tbody').empty();
  }
  $('#tblAjustePrecios tbody').append(
    '<tr><td><input type="number" class="form-control input-sm aj-precio-anio" value="' + anio + '" /></td>'
    + '<td><input type="number" step="0.0001" class="form-control input-sm aj-precio-tarifa" value="3" /></td>'
    + '<td><button type="button" class="btn btn-default btn-xs btn-aj-del-precio">&times;</button></td></tr>'
  );
});

$('#btnAjSeedPrecios').click(function() {
  var base = parseInt($('#aj_anio_precio').val(), 10) || (new Date()).getFullYear();
  var seed = [
    { anio: base, tarifa_ton_iva: 3 },
    { anio: base + 1, tarifa_ton_iva: 3 },
    { anio: base + 2, tarifa_ton_iva: 3.25 },
    { anio: base + 3, tarifa_ton_iva: 3.25 },
    { anio: base + 4, tarifa_ton_iva: 3.25 },
    { anio: base + 5, tarifa_ton_iva: 3.5 },
    { anio: base + 6, tarifa_ton_iva: 3.5 },
    { anio: base + 7, tarifa_ton_iva: 3.75 }
  ];
  renderPreciosRows(seed);
});

$(document).on('click', '.btn-aj-del-precio', function() {
  $(this).closest('tr').remove();
});

$('#btnSaveAjustePrecios').click(function() {
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  $.post(API, {
    action: 'ajuste_precios_save',
    proy_id: proy,
    ppe_id: ppe,
    precios_json: JSON.stringify(collectPreciosFromModal())
  }, function(r) {
    toast(r.message || '', r.status === 'success');
    if (r.status === 'success') {
      ajustePreciosCache = r.precios || [];
      $('#modalAjustePrecios').hide();
      loadRubros();
    }
  }, 'json');
});

$('#btnCloseAjustePrecios, #btnCancelAjustePrecios').click(function() {
  $('#modalAjustePrecios').hide();
});

$('#btnAjHistorial').click(function() {
  var proy = $('#rub_proy_id').val();
  var ppe = $('#rub_ppe_id').val();
  if (!proy || !ppe) { toast('Seleccione proyecto y version.', false); return; }
  $.getJSON(API, { action: 'ajuste_historial', proy_id: proy, ppe_id: ppe }, function(r) {
    var $tb = $('#tblAjusteHistorial tbody').empty();
    if (r.status !== 'success' || !(r.rows || []).length) {
      $tb.append('<tr><td colspan="8" class="text-muted">Sin aplicaciones registradas.</td></tr>');
    } else {
      $.each(r.rows, function(i, x) {
        $tb.append(
          '<tr>'
          + '<td>' + x.ajc_id + '</td>'
          + '<td>' + (x.ajc_fecha_registro || '') + '</td>'
          + '<td>' + (x.ajc_escenario || '') + '</td>'
          + '<td>' + (x.ajc_vista || '') + '</td>'
          + '<td class="text-right">' + formatCurrency(x.ajc_capital_total) + '</td>'
          + '<td class="text-right">' + formatCurrency(x.ajc_gad_aplicado) + '</td>'
          + '<td class="text-right">' + formatCurrency(x.ajc_gad_acum_despues) + '</td>'
          + '<td class="text-right">' + formatCurrency(x.ajc_gad_saldo_despues) + '</td>'
          + '</tr>'
        );
      });
    }
    $('#modalAjusteHistorial').show();
  });
});

$('#btnCloseAjusteHistorial, #btnCancelAjusteHistorial').click(function() {
  $('#modalAjusteHistorial').hide();
});

$('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
  var target = $(e.target).attr('href') || '';
  if (target === '#tabCuadro' || target === '#tabRubros') {
    refreshVistaPresupuesto();
  }
});

$(document).on('input', '.pdf-preview-factor', function() {
  recalcPdfPreviewFila($(this).closest('tr'));
  recalcPdfPreviewTotales();
});

$(document).on('input change', '#pdf_preview_meses_global', function() {
  aplicarPdfPreviewMesesGlobal($(this).val());
});

$('#rub_grupo_cod').change(function() {
  fillSubgruposRubro();
  fillDetallesRubro();
  actualizarModalRubroMesesDesdePartida();
  if ($('#modalEditRubro').is(':visible')) calcModalEditRubroPreview();
});

$('#rub_subgrupo_cod').change(function() {
  fillDetallesRubro();
  actualizarModalRubroMesesDesdePartida();
  if ($('#modalEditRubro').is(':visible')) calcModalEditRubroPreview();
});

$('#rub_ppa_id').change(function(){
  updateRubroPartidaResumen();
});

$('#btnCloseEditRubroModal, #btnCancelEditRubroModal').click(cerrarModalRubro);
$('#btnSaveEditRubroModal').click(guardarModalEditRubro);
$('#modal_edit_factor_anual, #modal_edit_meses').on('input change', calcModalEditRubroPreview);

$(document).on('mousedown click focusin', '.grupo-col-pct, .grupo-col-tope, .btn-grupo-resumen', function(e) {
  e.stopPropagation();
});

$(document).on('click', '.btn-grupo-resumen', function(e) {
  e.preventDefault();
  e.stopPropagation();
  abrirGrupoResumen($(this).attr('data-grupo-cod'));
});

$(document).on('click', '.cuadro-grupo-toggle', function(e) {
  e.preventDefault();
  e.stopPropagation();
  var target = $(this).data('target');
  if (!target) return;
  $(target).collapse('toggle');
});

$('#rubrosCuadroAccordion').on('shown.bs.collapse hidden.bs.collapse', function(e) {
  var $heading = $(e.target).prev('.cuadro-grupo-heading');
  $heading.toggleClass('is-open', e.type === 'shown');
});

$(document).on('click', '.btn-save-grupo-meses', function() {
  var ppaId = parseInt($(this).data('ppa-id'), 10);
  var $input = $(this).siblings('.grupo-meses-edit');
  if (!ppaId || !$input.length) return;
  saveGrupoMeses(ppaId, $input);
});

$(document).on('click', '.btn-save-grupo-pct', function() {
  var ppaId = parseInt($(this).data('ppa-id'), 10);
  var $input = $(this).siblings('.grupo-pct-edit');
  if (!ppaId || !$input.length) return;
  saveGrupoPct(ppaId, $input);
});

$('#btnParsePdf').click(parsePdfFile);

$(document).on('click', '.btn-add-partida', function() {
  abrirModalPartidaRubro($(this).data('partida-tipo'));
});

$('#btnSavePartidaRubroModal').click(function() {
  var codigo = $.trim($('#modal_partida_rubro_codigo').val());
  var descripcion = $.trim($('#modal_partida_rubro_descripcion').val());
  if (!codigo || !descripcion) {
    toast('Ingrese codigo y descripcion de la partida.', false);
    return;
  }
  $.post(API, {
    action: 'save_partida_catalogo',
    ppa_codigo_clasificacion: codigo,
    ppa_descripcion: descripcion,
    ppa_clase: $('#modal_partida_rubro_clase').val(),
    ppa_padre_id: $('#modal_partida_rubro_padre_id').val(),
    ppa_tipo: 'G',
    ppa_naturaleza: 'OPE'
  }, function(r) {
    if (r.status !== 'success') {
      toast(r.message || 'No se pudo crear la partida.', false);
      return;
    }
    toast(r.message || 'Partida creada.', true);
    cerrarModalPartidaRubro();
    reloadCatalogosPartidas(function() {
      aplicarPartidaCreada(r.partida);
    });
  }, 'json').fail(function() {
    toast('Error de red al crear la partida.', false);
  });
});

$('#btnClosePartidaRubroModal, #btnCancelPartidaRubroModal').click(cerrarModalPartidaRubro);

$('#btnImportPdf, #btnConfirmImportPdf').click(importPdfPayload);

$(document).on('click', '.esc-btn', function() { setEscenario($(this).data('esc')); });

$('#btnPreviewPublicar').click(previewPublicar);
$('#btnPublicarAprobado').click(function() {
  if (publicarPreviewCache) {
    $('#modalPublicarPreview').show();
    return;
  }
  previewPublicar();
});
$('#btnConfirmPublicar').click(function() { ejecutarPublicar(false); });
$('#btnClosePublicarModal, #btnCancelPublicarModal').click(function() {
  $('#modalPublicarPreview').hide();
});


$('#btnClosePdfModal, #btnCancelPdfModal').click(function() {

  $('#modalPdfPreview').hide();

});

$('#btnCloseGrupoResumenModal').click(function() {

  $('#modalGrupoResumen').hide();

});

$(function(){

  actualizarCuadroPeriodoUi();

  loadCatalogos(function(){

    loadProyectos(function(){ reloadRubrosSection(); });

  });

});

</script>

</body>

</html>


