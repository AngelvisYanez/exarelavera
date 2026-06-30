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
    #mdlResolution .modal-dialog {
        width: 90%;
        max-width: 950px;
    }
    #mdlResolution .modal-header, #mdlSeguimiento .modal-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        color: #ffffff;
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
        padding: 8px 14px;
        min-height: 0;
    }
    #mdlResolution .modal-header .close, #mdlSeguimiento .modal-header .close {
        color: #ffffff;
        opacity: 0.8;
        font-size: 20px;
        line-height: 1;
        margin-top: 0;
        padding: 0;
    }
    #mdlResolution .modal-header .close:hover, #mdlSeguimiento .modal-header .close:hover {
        opacity: 1;
    }
    #mdlResolution .modal-title, #mdlSeguimiento .modal-title {
        font-weight: 700;
        font-size: 14px;
        line-height: 1.25;
        margin: 0;
        padding-right: 24px;
        color: #ffffff;
    }
    #mdlResolution .modal-body, #mdlSeguimiento .modal-body {
        padding: 12px 16px;
        background-color: #f8fafc;
    }
    #mdlResolution .modal-body {
        max-height: 85vh;
        overflow-y: auto;
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
        padding-left: 18px;
        margin-top: 10px;
    }
    .adq-timeline::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 4px;
        width: 2px;
        background-color: #e2e8f0;
    }
    .adq-timeline-item {
        position: relative;
        margin-bottom: 10px;
    }
    .adq-timeline-item::before {
        content: '';
        position: absolute;
        left: -18px;
        top: 10px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #cbd5e1;
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 2px #e2e8f0;
        z-index: 2;
    }
    .adq-timeline-item.active::before {
        background-color: #3b82f6;
        box-shadow: 0 0 0 2px #93c5fd;
    }
    .adq-timeline-item.success::before {
        background-color: #10b981;
        box-shadow: 0 0 0 2px #a7f3d0;
    }
    .adq-timeline-item.danger::before {
        background-color: #ef4444;
        box-shadow: 0 0 0 2px #fca5a5;
    }
    .adq-timeline-item.warning::before {
        background-color: #f59e0b;
        box-shadow: 0 0 0 2px #fde68a;
    }
    .adq-timeline-content {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        padding: 6px 10px;
    }
    .adq-timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2px;
        flex-wrap: wrap;
        gap: 4px;
    }
    .adq-timeline-title {
        font-size: 11px;
        font-weight: 700;
        color: #1e293b;
    }
    .adq-timeline-date {
        font-size: 10px;
        font-family: monospace;
        color: #64748b;
    }
    .adq-timeline-body {
        font-size: 11px;
        color: #475569;
    }
    .adq-timeline-comment {
        font-size: 10.5px;
        font-style: italic;
        background-color: #ffffff;
        border-left: 3px solid #cbd5e1;
        padding: 3px 6px;
        margin-top: 4px;
        border-radius: 0 3px 3px 0;
        color: #64748b;
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
</style>
