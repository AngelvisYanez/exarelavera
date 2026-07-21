<?php
/**
 * Assets compartidos Model3 para m�dulo Adquisiciones (pantalla completa, estilo ERP).
 */
require_once('../../mascaras/model1/estilos/jqgrid5.php');
$m3_ui_core_only = true;
require_once('../../mascaras/model3/estilos/estilos.php');
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
    /* Header con acciones */
    .exa-ui-panel > .panel-heading.exa-header.exa-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    .exa-ui-panel > .panel-heading.exa-header.exa-header-flex .panel-title {
        margin: 0;
        flex: 1 1 auto;
    }
    .exa-ui-panel .exa-header-actions {
        flex: 0 0 auto;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }
    .exa-ui-panel .exa-header-actions .btn {
        margin: 0;
    }

    /* Tabs estilo model3 (Bootstrap 3) */
    .exa-ui-panel .exa-ui-nav-tabs {
        border-bottom: 2px solid #e2e8f0;
        padding: 4px 8px 0;
        margin-bottom: 0;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 10px 10px 0 0;
    }
    .exa-ui-panel .exa-ui-nav-tabs > li > a {
        padding: 10px 18px;
        font-size: 13px;
        font-weight: bold;
        color: #1e3a5f !important;
        background: linear-gradient(180deg, #b5cce3 0%, #8ca9cf 48%, #7a9fc5 100%) !important;
        border: 1px solid #788999 !important;
        border-radius: 8px 8px 0 0;
        margin-right: 2px;
        margin-bottom: -2px;
    }
    .exa-ui-panel .exa-ui-nav-tabs > li > a:hover,
    .exa-ui-panel .exa-ui-nav-tabs > li > a:focus {
        background: linear-gradient(180deg, #c5d9ed 0%, #9eb9d4 48%, #8ca9cf 100%) !important;
        color: #1d5987 !important;
        border-color: #79b7e7 !important;
    }
    .exa-ui-panel .exa-ui-nav-tabs > li.active > a,
    .exa-ui-panel .exa-ui-nav-tabs > li.active > a:hover,
    .exa-ui-panel .exa-ui-nav-tabs > li.active > a:focus {
        color: #1e40af !important;
        background: var(--v2-bg-page) !important;
        border: 1px solid #e2e8f0 !important;
        border-bottom-color: var(--v2-bg-page) !important;
        font-weight: 600;
    }
    .exa-ui-panel .exa-ui-tab-content.panels-area {
        background: var(--v2-bg-page) !important;
        border-top: 1px solid #e2e8f0;
        padding: 14px 16px 20px;
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
    }
    .exa-ui-fill-page .exa-ui-page-view {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    /* Tablas de listado (no formulario) */
    .exa-ui-panel .exa-adq-table-wrap {
        border: var(--v2-elev-border);
        border-radius: var(--v2-radius);
        box-shadow: var(--v2-elev-shadow);
        background: var(--v2-bg-grid);
        overflow: auto;
    }
    .exa-ui-panel .exa-adq-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
        background: var(--v2-bg-grid);
        font-size: 12px;
    }
    .exa-ui-panel .exa-adq-table > thead > tr > th {
        background: var(--v2-grid-header-dark-gradient) !important;
        background-color: var(--v2-grid-header-dark-bg) !important;
        color: var(--v2-grid-header-dark-text) !important;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-color: var(--v2-grid-header-dark-border) !important;
        padding: 9px 10px !important;
        text-align: center;
        white-space: nowrap;
    }
    .exa-ui-panel .exa-adq-table > tbody > tr > td {
        padding: 8px 10px !important;
        border-color: var(--v2-border-light) !important;
        color: var(--v2-text);
        vertical-align: middle;
    }
    .exa-ui-panel .exa-adq-table > tbody > tr:hover > td {
        background-color: var(--v2-surface-hover) !important;
    }
    .exa-ui-panel .exa-adq-table > tbody > tr.exa-adq-empty > td {
        padding: 28px 16px !important;
        color: var(--v2-text-muted);
        font-style: italic;
    }

    /* KPI / m�tricas */
    .exa-adq-kpi-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 16px;
    }
    .exa-adq-kpi {
        flex: 1 1 180px;
        min-width: 160px;
        background: var(--v2-bg-panel);
        border: var(--v2-elev-border);
        border-radius: var(--v2-radius);
        box-shadow: var(--v2-elev-shadow);
        padding: 14px 16px;
        border-left: 4px solid var(--v2-brand);
    }
    .exa-adq-kpi.kpi-primary { border-left-color: #4a88b5; }
    .exa-adq-kpi.kpi-success { border-left-color: #198754; }
    .exa-adq-kpi.kpi-warning { border-left-color: #fd7e14; }
    .exa-adq-kpi.kpi-danger  { border-left-color: #dc3545; }
    .exa-adq-kpi.kpi-muted   { border-left-color: #6c757d; }
    .exa-adq-kpi .kpi-label {
        display: block;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--v2-text-muted);
        margin-bottom: 4px;
    }
    .exa-adq-kpi .kpi-value {
        font-size: 26px;
        font-weight: 700;
        color: var(--v2-text);
        line-height: 1.1;
    }
    .exa-adq-kpi .kpi-value small {
        font-size: 13px;
        font-weight: 600;
        color: var(--v2-text-muted);
    }

    /* Paneles de secci�n (dashboard) */
    .exa-adq-section {
        background: var(--v2-bg-panel);
        border: var(--v2-elev-border);
        border-radius: var(--v2-radius);
        box-shadow: var(--v2-elev-shadow);
        padding: 14px 16px;
        margin-bottom: 14px;
        height: 100%;
    }
    .exa-adq-section-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--v2-brand-dark);
        margin: 0 0 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--v2-border);
    }

    /* Barra de filtros compacta (no fieldset) */
    .exa-adq-filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
        padding: 10px 12px;
        margin-bottom: 14px;
        background: var(--v2-bg-panel);
        border: var(--v2-elev-border);
        border-radius: var(--v2-radius);
    }
    .exa-adq-filter-bar .filter-item {
        flex: 1 1 160px;
        min-width: 140px;
    }
    .exa-adq-filter-bar .filter-item label {
        display: block;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--v2-text-muted);
        margin-bottom: 3px;
    }
    .exa-adq-filter-bar .filter-actions {
        flex: 0 0 auto;
        display: flex;
        gap: 6px;
    }

    /* Badges prioridad */
    .badge-alta { background-color: #dc3545; }
    .badge-media { background-color: #fd7e14; }
    .badge-baja { background-color: #198754; }
    .badge-urgente { background-color: #6f42c1; }

    /* Tracker workflow */
    .tracker-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        overflow-x: auto;
        padding: 8px;
        background-color: var(--v2-bg-panel);
        border: var(--v2-elev-border);
        border-radius: var(--v2-radius);
    }
    .tracker-node {
        padding: 4px 8px;
        background-color: #ffffff;
        border: 2px solid #adb5bd;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        color: #495057;
        text-align: center;
        min-width: 110px;
        max-width: 150px;
        flex-shrink: 0;
    }
    .tracker-node.color-green { border-color: #198754; background-color: #e2f0d9; color: #198754; }
    .tracker-node.color-blue { border-color: #0d6efd; background-color: #cfe2ff; color: #0d6efd; box-shadow: 0 0 6px rgba(13,110,253,0.2); }
    .tracker-node.color-red { border-color: #dc3545; background-color: #f8d7da; color: #dc3545; }
    .tracker-node.color-grey { border-color: #6c757d; background-color: #f8f9fa; color: #6c757d; }
    .tracker-actor {
        font-size: 8px;
        font-weight: 600;
        display: block;
        margin-top: 3px;
        line-height: 1.25;
        opacity: 0.95;
        white-space: normal;
        word-break: break-word;
    }
    .tracker-pendiente span {
        display: block;
    }
    .tracker-arrow { font-size: 14px; color: #6c757d; flex-shrink: 0; }

    .semaforo-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 5px;
        vertical-align: middle;
    }
    .bg-semaforo-verde { background-color: #198754; box-shadow: 0 0 6px #198754; }
    .bg-semaforo-amarillo { background-color: #ffc107; box-shadow: 0 0 6px #ffc107; }
    .bg-semaforo-rojo { background-color: #dc3545; box-shadow: 0 0 6px #dc3545; }
    .bg-semaforo-gris { background-color: #6c757d; box-shadow: 0 0 6px #6c757d; }

    /* Compatibilidad utilidades BS5 en contenido PHP */
    .exa-ui-panel .badge.bg-primary, .exa-ui-panel .badge.bg-success,
    .exa-ui-panel .badge.bg-danger, .exa-ui-panel .badge.bg-warning,
    .exa-ui-panel .badge.bg-secondary { color: #fff; }
    .exa-ui-panel .badge.bg-primary { background-color: #337ab7; }
    .exa-ui-panel .badge.bg-success { background-color: #5cb85c; }
    .exa-ui-panel .badge.bg-danger { background-color: #d9534f; }
    .exa-ui-panel .badge.bg-warning { background-color: #f0ad4e; color: #333; }
    .exa-ui-panel .badge.bg-secondary { background-color: #777; }
    .exa-ui-panel .text-start { text-align: left; }
    .exa-ui-panel .text-end { text-align: right; }
    .exa-ui-panel .fw-bold { font-weight: 700; }
    .exa-ui-panel .btn-xs {
        padding: 1px 5px;
        font-size: 11px;
        border-radius: 3px;
    }

    /* Estilos Modernizados para Modales de Detalle (adq_bandeja.php) */
    #mdlResolution .modal-dialog.adq-resolution-dialog,
    #mdlResolution .modal-dialog {
        width: 94%;
        max-width: 1320px;
        margin: 24px auto;
    }
    #mdlResolution .modal-content.adq-resolution-content,
    #mdlResolution .modal-content {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.22);
    }
    #mdlResolution .modal-header.adq-resolution-header,
    #mdlResolution .modal-header, #mdlSeguimiento .modal-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #3b82f6 100%);
        color: #ffffff;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        padding: 16px 20px;
        min-height: 0;
        border-bottom: none;
    }
    #mdlResolution .adq-resolution-header-text {
        padding-right: 28px;
    }
    #mdlResolution .adq-modal-subtitle {
        margin: 4px 0 0;
        font-size: 13px;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.88);
        line-height: 1.4;
    }
    #mdlResolution .modal-header .close, #mdlSeguimiento .modal-header .close {
        color: #ffffff;
        opacity: 0.85;
        font-size: 26px;
        line-height: 1;
        margin-top: -2px;
        padding: 0;
        text-shadow: none;
    }
    #mdlResolution .modal-header .close:hover, #mdlSeguimiento .modal-header .close:hover {
        opacity: 1;
    }
    #mdlResolution .modal-title, #mdlSeguimiento .modal-title {
        font-weight: 700;
        font-size: 18px;
        line-height: 1.3;
        margin: 0;
        padding-right: 0;
        color: #ffffff;
    }
    #mdlResolution .modal-body.adq-resolution-body,
    #mdlResolution .modal-body, #mdlSeguimiento .modal-body {
        padding: 18px 22px;
        background-color: #f1f5f9;
    }
    #mdlResolution .modal-body {
        max-height: 82vh;
        overflow-y: auto;
    }
    #mdlResolution .modal-footer.adq-resolution-footer {
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        padding: 12px 20px;
        text-align: right;
    }
    #mdlResolution .adq-resolution-layout {
        margin-left: -8px;
        margin-right: -8px;
    }
    #mdlResolution .adq-resolution-layout > [class*="col-"] {
        padding-left: 10px;
        padding-right: 10px;
    }
    #mdlResolution .adq-detail-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 14px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    }
    #mdlResolution .adq-section-header {
        font-size: 13px;
        font-weight: 700;
        color: #1e3a8a;
        margin: 0 0 10px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e2e8f0;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    #mdlResolution .adq-section-header i {
        margin-right: 6px;
    }
    #mdlResolution .adq-detail-kv td {
        padding: 7px 10px !important;
        border: none;
        vertical-align: top;
        font-size: 13px !important;
    }
    #mdlResolution .adq-detail-kv .adq-detail-kv-label {
        width: 118px;
        font-weight: 600;
        color: #64748b;
        white-space: nowrap;
    }
    #mdlResolution .adq-scroll-items {
        max-height: 260px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
    }
    #mdlResolution #tblDetItems {
        font-size: 13px !important;
        margin-bottom: 0;
    }
    #mdlResolution #tblDetItems thead th {
        font-size: 12px !important;
        padding: 8px 10px !important;
        background-color: #f8fafc !important;
        color: #475569;
        font-weight: 700;
        border-bottom: 2px solid #cbd5e1 !important;
    }
    #mdlResolution #tblDetItems tbody td {
        padding: 8px 10px !important;
        vertical-align: middle;
    }
    #mdlResolution .adq-scroll-cotizaciones {
        max-height: 240px;
        overflow-y: auto;
    }
    #mdlResolution .adq-cot-sustento-table {
        font-size: 12px;
        margin-bottom: 0;
    }
    #mdlResolution .adq-cot-sustento-table thead th {
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        background: #f8fafc;
        border-bottom: 2px solid #cbd5e1;
        padding: 8px 10px;
        white-space: nowrap;
    }
    #mdlResolution .adq-cot-sustento-table tbody td {
        padding: 8px 10px;
        vertical-align: middle;
        border-color: #e2e8f0;
    }
    #mdlResolution .adq-cot-sustento-table tr.adq-cot-row-ganadora {
        background-color: #f0fdf4;
    }
    #mdlResolution .adq-cot-sustento-table tr.adq-cot-row-ganadora td {
        border-color: #bbf7d0;
    }
    #mdlResolution .adq-cot-jus-cell {
        color: #475569;
        line-height: 1.35;
        max-width: 280px;
        word-break: break-word;
    }
    #mdlResolution .adq-scroll-historial {
        max-height: 520px;
        overflow-y: auto;
        padding: 6px 8px 10px 4px;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 56px);
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }
    #mdlResolution .adq-hist-empty {
        padding: 28px 16px;
        text-align: center;
        color: #64748b;
        font-size: 12px;
    }
    #mdlResolution .adq-hist-empty i {
        display: block;
        font-size: 22px;
        color: #94a3b8;
        margin-bottom: 8px;
    }
    #mdlResolution .adq-cot-card {
        padding: 12px 14px;
        border-radius: 8px;
        margin-bottom: 12px;
    }
    #mdlResolution .adq-cot-card .fw-bold {
        font-size: 14px !important;
    }
    #mdlResolution #flowTracker.adq-flow-tracker-host {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        flex-wrap: nowrap;
        gap: 10px;
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 10px;
        overflow-x: auto;
        min-height: 80px;
        width: 100%;
    }
    #mdlResolution #flowTracker.adq-flow-tracker-host .tracker-node {
        font-size: 13px !important;
        min-width: 132px;
        max-width: 200px;
        padding: 8px 10px !important;
        flex-shrink: 0;
    }
    #mdlResolution #flowTracker.adq-flow-tracker-host .tracker-arrow {
        font-size: 20px;
        flex-shrink: 0;
    }
    #mdlResolution #flowTracker.adq-flow-tracker-host .tracker-actor {
        font-size: 10px;
    }
    #mdlSeguimiento .adq-seg-modal-dialog {
        width: 96%;
        max-width: 1180px;
        margin: 24px auto;
    }
    #mdlSeguimiento .adq-seg-modal-content {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.28);
    }
    #mdlSeguimiento .adq-seg-modal-header {
        background: linear-gradient(135deg, #1e3a5f 0%, #2f5278 55%, #3d6a94 100%);
        color: #ffffff;
        border-bottom: none;
        padding: 16px 20px;
        position: relative;
    }
    #mdlSeguimiento .adq-seg-modal-header::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 3px;
        background: linear-gradient(90deg, #38bdf8 0%, #2f6fed 50%, #1e3a5f 100%);
    }
    #mdlSeguimiento .adq-seg-modal-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-right: 28px;
    }
    #mdlSeguimiento .adq-seg-modal-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.22);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex: 0 0 auto;
    }
    #mdlSeguimiento .adq-seg-modal-header .modal-title {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: 0.01em;
        line-height: 1.25;
    }
    #mdlSeguimiento .adq-seg-modal-sub {
        margin: 3px 0 0;
        font-size: 12px;
        color: rgba(226, 232, 240, 0.92);
        font-weight: 400;
    }
    #mdlSeguimiento .adq-seg-modal-header .close {
        color: #ffffff;
        opacity: 0.85;
        text-shadow: none;
        margin-top: 2px;
        font-size: 26px;
    }
    #mdlSeguimiento .adq-seg-modal-header .close:hover {
        opacity: 1;
        color: #ffffff;
    }
    #mdlSeguimiento .adq-seg-modal-body {
        background: linear-gradient(180deg, #f1f5f9 0%, #f8fafc 28%, #ffffff 100%);
        padding: 16px 18px 12px;
        max-height: 72vh;
        overflow-y: auto;
    }
    #mdlSeguimiento .adq-seg-modal-footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 10px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }
    #mdlSeguimiento .adq-seg-modal-hint {
        font-size: 11.5px;
    }
    #mdlSeguimiento .adq-seg-modal-footer .btn {
        min-width: 88px;
    }
    #mdlSeguimiento .adq-seg-loading {
        text-align: center;
        padding: 48px 20px;
        color: #475569;
    }
    #mdlSeguimiento .adq-seg-loading-spinner {
        width: 36px;
        height: 36px;
        margin: 0 auto 14px;
        border: 3px solid #dbeafe;
        border-top-color: #1e3a8a;
        border-radius: 50%;
        animation: adqSegSpin 0.8s linear infinite;
    }
    @keyframes adqSegSpin {
        to { transform: rotate(360deg); }
    }
    #mdlSeguimiento .adq-seg-loading-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }
    #mdlSeguimiento .adq-seg-loading-text {
        font-size: 12px;
        color: #64748b;
    }
    #mdlSeguimiento .adq-seg-error {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin: 12px;
        padding: 14px 16px;
        border-radius: 10px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        font-size: 13px;
    }
    #mdlSeguimiento .adq-seg-error .bi {
        font-size: 20px;
        margin-top: 1px;
    }
    #mdlSeguimiento .adq-seg-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin: 4px 0 12px;
        padding: 10px 12px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #dbe3ef;
        border-radius: 10px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    #mdlSeguimiento .adq-seg-toolbar-copy {
        min-width: 0;
        flex: 1 1 220px;
    }
    #mdlSeguimiento .adq-seg-toolbar-copy strong {
        display: block;
        font-size: 12.5px;
        color: #1e293b;
        margin-bottom: 2px;
    }
    #mdlSeguimiento .adq-seg-toolbar-copy span {
        font-size: 11.5px;
        color: #64748b;
    }
    #mdlSeguimiento #btnDescargarDocsZip {
        background: linear-gradient(180deg, #059669 0%, #047857 100%);
        border: 1px solid #065f46;
        color: #ffffff;
        font-weight: 700;
        font-size: 12px;
        padding: 7px 14px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(4, 120, 87, 0.28);
        white-space: nowrap;
    }
    #mdlSeguimiento #btnDescargarDocsZip:hover,
    #mdlSeguimiento #btnDescargarDocsZip:focus {
        background: linear-gradient(180deg, #047857 0%, #065f46 100%);
        color: #ffffff;
    }
    #mdlSeguimiento #btnDescargarDocsZip:disabled {
        opacity: 0.75;
    }
    #mdlSeguimiento .adq-detail-card {
        border-radius: 10px;
        border-color: #dbe3ef;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        background: #ffffff;
    }
    #mdlSeguimiento .adq-section-header {
        font-size: 11px;
        letter-spacing: 0.05em;
    }
    #mdlSeguimiento .adq-seg-flow-tracker,
    #mdlSeguimiento .tracker-wrapper.adq-seg-flow-tracker {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        flex-wrap: nowrap;
        gap: 10px;
        overflow-x: auto;
        min-height: 88px;
        width: 100%;
        padding: 12px 10px;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }
    #mdlSeguimiento .adq-seg-flow-tracker .tracker-node {
        font-size: 13px !important;
        min-width: 132px;
        max-width: 210px;
        padding: 8px 10px !important;
        flex-shrink: 0;
        white-space: normal;
    }
    #mdlSeguimiento .adq-seg-flow-tracker .tracker-arrow {
        font-size: 20px;
        flex-shrink: 0;
    }
    #mdlSeguimiento .adq-seg-flow-tracker .tracker-actor {
        font-size: 10px;
    }
    #mdlSeguimiento .adq-seg-flow-tracker .tracker-node-clickable {
        cursor: pointer;
        transition: box-shadow 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
    }
    #mdlSeguimiento .adq-seg-flow-tracker .tracker-node-clickable:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.12);
    }
    #mdlSeguimiento .adq-seg-flow-tracker .tracker-node-selected {
        outline: 2px solid #1e3a8a;
        outline-offset: 2px;
        box-shadow: 0 0 0 4px rgba(30, 58, 138, 0.15);
    }
    #mdlSeguimiento .adq-seg-nodo-tareas-card {
        border-color: #bfdbfe;
        background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
        padding: 10px 12px;
    }
    #mdlSeguimiento .adq-seg-nodo-tareas-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 8px;
        padding-bottom: 6px;
        border-bottom: 1px solid #dbeafe;
    }
    #mdlSeguimiento #segNodoTareasBody {
        max-height: 280px;
        overflow-y: auto;
        padding-right: 4px;
    }
    #mdlResolution .adq-timeline-title {
        font-size: 13px;
    }
    #mdlResolution .adq-timeline-date {
        font-size: 11px;
    }
    #mdlResolution .adq-timeline-body {
        font-size: 12px;
    }
    #mdlResolution .adq-timeline-comment {
        font-size: 12px;
        padding: 6px 10px;
    }
    #mdlResolution .adq-timeline-content {
        padding: 10px 12px;
        border-radius: 8px;
    }
    #mdlResolution .adq-action-buttons .btn {
        font-size: 13px !important;
        padding: 9px 14px !important;
        border-radius: 8px;
    }
    #mdlResolution #actionComentario {
        font-size: 13px !important;
        padding: 10px 12px !important;
        min-height: 72px;
        border-radius: 8px;
    }
    #mdlResolution .adq-wf-progress-card {
        background-color: #f8fafc;
        border-color: #e2e8f0;
    }
    #mdlResolution .adq-wf-progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        flex-wrap: wrap;
        gap: 8px;
    }
    #mdlResolution .adq-wf-progress-header .form-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    #mdlResolution .adq-wf-progress-header .btn {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
        font-size: 11px;
        padding: 4px 10px;
    }
    #mdlResolution #panelDecision {
        padding: 14px 16px !important;
    }
    .adq-detail-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 14px;
        margin-bottom: 12px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .adq-section-header {
        font-size: 11.5px;
        font-weight: 700;
        color: #1e3a8a;
        margin: 0 0 8px;
        padding-bottom: 6px;
        border-bottom: 2px solid #e2e8f0;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .adq-detail-label {
        font-size: 9.5px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 2px;
        display: block;
    }
    .adq-detail-value {
        font-size: 12px;
        font-weight: 600;
        color: #1e293b;
        display: block;
    }
    .adq-cot-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        padding: 8px 12px;
        margin-bottom: 10px;
        transition: all 0.2s ease;
    }
    .adq-cot-card.ganadora {
        border-color: #10b981;
        background-color: #f0fdf4;
        box-shadow: 0 0 6px rgba(16,185,129,0.1);
    }
    .adq-timeline {
        position: relative;
        padding-left: 22px;
        margin-top: 6px;
    }
    .adq-timeline::before {
        content: '';
        position: absolute;
        top: 6px;
        bottom: 6px;
        left: 7px;
        width: 2px;
        background: linear-gradient(180deg, #94a3b8 0%, #e2e8f0 100%);
        border-radius: 2px;
    }
    .adq-timeline-item {
        position: relative;
        margin-bottom: 12px;
    }
    .adq-timeline-item:last-child {
        margin-bottom: 2px;
    }
    .adq-timeline-item::before {
        content: '';
        position: absolute;
        left: -19px;
        top: 14px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #94a3b8;
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 2px #cbd5e1;
        z-index: 2;
    }
    .adq-timeline-item.active::before {
        background-color: #2563eb;
        box-shadow: 0 0 0 3px #bfdbfe;
    }
    .adq-timeline-item.success::before {
        background-color: #059669;
        box-shadow: 0 0 0 3px #a7f3d0;
    }
    .adq-timeline-item.danger::before {
        background-color: #dc2626;
        box-shadow: 0 0 0 3px #fecaca;
    }
    .adq-timeline-item.warning::before {
        background-color: #d97706;
        box-shadow: 0 0 0 3px #fde68a;
    }
    .adq-timeline-content {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-left: 3px solid #cbd5e1;
        border-radius: 8px;
        padding: 10px 12px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .adq-timeline-item.active .adq-timeline-content {
        border-left-color: #2563eb;
        background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    }
    .adq-timeline-item.success .adq-timeline-content {
        border-left-color: #059669;
        background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
    }
    .adq-timeline-item.danger .adq-timeline-content {
        border-left-color: #dc2626;
        background: linear-gradient(180deg, #fef2f2 0%, #ffffff 100%);
    }
    .adq-timeline-item.warning .adq-timeline-content {
        border-left-color: #d97706;
        background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%);
    }
    .adq-timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8px;
        flex-wrap: wrap;
        gap: 6px 10px;
    }
    .adq-timeline-title {
        font-size: 12px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px 8px;
        line-height: 1.35;
        flex: 1 1 180px;
        min-width: 0;
    }
    .adq-timeline-step {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex: 0 1 auto;
    }
    .adq-timeline-stage {
        display: inline;
        font-size: 13px;
        font-weight: 700;
        color: #1e3a8a;
        letter-spacing: 0.01em;
    }
    .adq-timeline-step-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 6px;
        background-color: #1e3a8a;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        flex-shrink: 0;
    }
    #mdlResolution .adq-timeline-step-num {
        min-width: 24px;
        height: 24px;
        font-size: 12px;
    }
    .adq-timeline-date {
        font-size: 11px;
        font-family: Consolas, "Courier New", monospace;
        color: #64748b;
        white-space: nowrap;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 2px 8px;
    }
    .adq-timeline-body {
        font-size: 12px;
        color: #475569;
    }
    .adq-hist-actor {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 2px;
    }
    .adq-hist-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #1e3a8a;
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.02em;
        flex-shrink: 0;
    }
    .adq-timeline-item.success .adq-hist-avatar { background: #059669; }
    .adq-timeline-item.danger .adq-hist-avatar { background: #dc2626; }
    .adq-timeline-item.warning .adq-hist-avatar { background: #d97706; }
    .adq-timeline-item.active .adq-hist-avatar { background: #2563eb; }
    .adq-hist-actor-meta {
        min-width: 0;
        line-height: 1.25;
    }
    .adq-hist-actor-mode {
        display: block;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #94a3b8;
        font-weight: 600;
    }
    .adq-hist-actor-name {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #1e293b;
    }
    .adq-timeline-comment {
        font-size: 12px;
        font-style: normal;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left: 3px solid #94a3b8;
        padding: 8px 10px;
        margin-top: 8px;
        border-radius: 0 6px 6px 0;
        color: #475569;
        line-height: 1.4;
    }
    .adq-timeline-comment::before {
        content: 'Comentario';
        display: block;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
        margin-bottom: 3px;
    }
    .adq-hist-archivos,
    .adq-hist-facturas {
        margin-top: 8px;
    }
    .adq-hist-archivos .btn,
    .adq-hist-facturas .btn {
        border-radius: 6px;
    }
    .adq-hist-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 10px !important;
        font-weight: 700 !important;
        letter-spacing: 0.02em;
        padding: 3px 8px !important;
        border-radius: 999px !important;
        border: none !important;
        line-height: 1.2 !important;
    }
    .adq-action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .adq-action-buttons .btn {
        flex: 1 1 auto;
        font-weight: 600;
        font-size: 11px;
        padding: 6px 10px;
        border-radius: 5px;
        margin: 0;
    }
    #mdlResolution .adq-action-buttons .btn-adq-devolver {
        background-color: #4f46e5 !important;
        border-color: #4338ca !important;
        color: #ffffff !important;
    }
    #mdlResolution .adq-action-buttons .btn-adq-devolver:hover,
    #mdlResolution .adq-action-buttons .btn-adq-devolver:focus,
    #mdlResolution .adq-action-buttons .btn-adq-devolver:active {
        background-color: #4338ca !important;
        border-color: #3730a3 !important;
        color: #ffffff !important;
    }

    /* Modales Model3 - Departamentos / Configuracion */
    #mdlDepto .modal-dialog,
    #mdlDeptoUsuarios .modal-dialog,
    #mdlMensajeExa .modal-dialog {
        margin: 28px auto;
    }
    #mdlDepto .modal-content,
    #mdlDeptoUsuarios .modal-content,
    #mdlMensajeExa .modal-content {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.22);
    }
    #mdlDepto .modal-header,
    #mdlDeptoUsuarios .modal-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #3b82f6 100%);
        color: #ffffff;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        padding: 16px 20px;
        min-height: 0;
        border-bottom: none;
    }
    #mdlDepto .modal-header .close,
    #mdlDeptoUsuarios .modal-header .close {
        color: #ffffff;
        opacity: 0.85;
        font-size: 26px;
        line-height: 1;
        margin-top: -2px;
        padding: 0;
        text-shadow: none;
    }
    #mdlDepto .modal-header .close:hover,
    #mdlDeptoUsuarios .modal-header .close:hover {
        opacity: 1;
    }
    #mdlDepto .modal-title,
    #mdlDeptoUsuarios .modal-title {
        font-weight: 700;
        font-size: 18px;
        line-height: 1.3;
        margin: 0;
        padding-right: 28px;
        color: #ffffff;
    }
    #mdlDeptoUsuarios .adq-modal-subtitle {
        margin: 4px 0 0;
        font-size: 13px;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.88);
        line-height: 1.4;
    }
    #mdlDepto .modal-body,
    #mdlDeptoUsuarios .modal-body {
        padding: 18px 22px;
        background-color: #f1f5f9;
    }
    #mdlDepto .modal-footer,
    #mdlDeptoUsuarios .modal-footer {
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        padding: 12px 20px;
        text-align: right;
    }
    #mdlDepto .select2-container {
        width: 100% !important;
    }
    #mdlDepto .select2-container--default .select2-selection--single {
        height: 34px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    #mdlDepto .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 32px;
        padding-left: 10px;
    }
    #mdlDepto .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px;
    }
    #mdlDeptoUsuarios .adq-depto-users-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    }
    #mdlDeptoUsuarios .adq-depto-users-card .adq-section-header {
        margin-bottom: 12px;
    }
    #mdlDeptoUsuarios .adq-depto-users-search {
        margin-bottom: 12px;
    }
    #mdlDeptoUsuarios .adq-depto-users-scroll {
        max-height: 320px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
    }
    #mdlDeptoUsuarios .item-usuario-depto {
        cursor: pointer;
        padding: 9px 12px;
        margin: 0;
        border: none;
        border-bottom: 1px solid #e2e8f0;
        display: block;
        background: #fff;
        transition: background-color 0.15s ease;
    }
    #mdlDeptoUsuarios .item-usuario-depto:last-child {
        border-bottom: none;
    }
    #mdlDeptoUsuarios .item-usuario-depto:hover {
        background-color: #f8fafc;
    }
    #mdlDeptoUsuarios .item-usuario-depto .form-check {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    #mdlDeptoUsuarios .item-usuario-depto .lbl-usuario-nom {
        font-size: 13px;
        color: #1e293b;
        font-weight: 500;
    }
    #mdlDeptoUsuarios .adq-depto-users-empty {
        padding: 24px 16px;
        text-align: center;
        color: #64748b;
        font-size: 13px;
    }
    #mdlMensajeExa .modal-header {
        border-bottom: none;
        padding: 14px 16px;
    }
    #mdlMensajeExa .modal-body {
        padding: 20px 24px;
        font-size: 14px;
        background: #f8fafc;
    }
    #mdlMensajeExa .modal-footer {
        text-align: center;
        border-top: 1px solid #e5e7eb;
        padding: 12px 16px;
        background: #fff;
    }
</style>
