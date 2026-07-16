<?php
session_start();

// Detectar si viene desde EXA Contable
$from_exa = isset($_GET['from_exa']) || (isset($_SESSION['Ses_Usu_Cod']) && !empty($_SESSION['Ses_Usu_Cod']));

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$from_exa) {
    // El usuario quiere que los PDFs se borren al recargar la página (F5) o al pulsar "Recargar otro"
    unset($_SESSION['ct_xml_data']);
    unset($_SESSION['ct_resumen']);
    unset($_SESSION['ct_data']);
    // Limpiar estimaciones previas en la sesion
    foreach($_SESSION as $k => $v) {
        if(strpos($k, 'ct_') === 0 && $k !== 'ct_anio' && $k !== 'ct_regimen') {
            unset($_SESSION[$k]);
        }
    }

    if (isset($_GET['admin'])) {
        $_SESSION['ct_ruc'] = '0703703413001';
        $_SESSION['ct_is_admin'] = true;
    }
}

if (!isset($_SESSION['ct_parametros'])) {
    $_SESSION['ct_parametros'] = include 'config/parametros.php';
}
if (!isset($_SESSION['ct_anio'])) $_SESSION['ct_anio'] = '2026';
if (!isset($_SESSION['ct_regimen'])) $_SESSION['ct_regimen'] = 'pn';
if (!isset($_SESSION['ct_ruc'])) $_SESSION['ct_ruc'] = '0000000000001';
if (!isset($_SESSION['ct_nombre'])) $_SESSION['ct_nombre'] = 'Contribuyente Ejemplo';

$anio = $_SESSION['ct_anio'];
$regimen = $_SESSION['ct_regimen'];
$ruc = $_SESSION['ct_ruc'];
$nombre = $_SESSION['ct_nombre'];
$is_admin = isset($_SESSION['ct_is_admin']) ? $_SESSION['ct_is_admin'] : false;

// Function to generate the HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EXA Control Tributario</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Export libraries: ExcelJS + jsPDF -->
    <script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <?php 
        $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $base_url = $is_ajax ? "../../control-tributario-ec/" : ""; 
    ?>
    <link rel="stylesheet" href="<?=$base_url?>assets/css/style.css?v=28">
    <!-- Model 3 UI -->
    <link href="../mascaras/model3/estilos/exa-ui.css" rel="stylesheet" type="text/css">
    <!-- Premium Minimalist Design for Documentos Tab -->
    <style>
        /* ── Documentos Tab ── */
        #tab-documentos {
            border-radius: 0 0 12px 12px;
            padding: 1rem;
        }

        #tab-documentos .glass-card, #tab-documentos .card {
            border-radius: 12px;
            transition: all 0.25s ease;
        }

        #tab-documentos .card-title {
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: -0.01em;
        }

        #tab-documentos .card-title i {
            margin-right: 8px;
            background: #f1f5f9;
            color: #0f172a !important;
            padding: 7px;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        #tab-documentos .drop-zone {
            padding: 18px 12px;
        }

        #tab-documentos .text-primary  { color: #0f172a !important; }
        #tab-documentos .text-info     { color: #0f172a !important; }
        #tab-documentos .text-danger   { color: #0f172a !important; }
        #tab-documentos .text-warning  { color: #0f172a !important; }
        #tab-documentos .text-success  { color: #0f172a !important; }

        #tab-documentos .btn-outline-success {
            color: #059669;
            border-color: #10b981;
        }
        #tab-documentos .btn-outline-success:hover {
            background-color: #10b981;
            color: #fff;
        }

        /* Renta data panel */
        #renta-data {
            background: #eff6ff !important;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
        }

        /* ── Tab Special fix for JS-injected text colors ── */
        #tab-tabla .text-info, #tab-tabla .text-success,
        #tab-tabla .text-danger, #tab-tabla .text-warning,
        #tab-tabla .text-primary {
            color: inherit !important;
        }
    </style>
</head>
<body>

    <!-- TOPBAR -->
    <nav class="navbar navbar-expand-lg fixed-top exa-navbar glass-effect">
        <div class="container-fluid">
            <!-- Logo -->
            <a class="navbar-brand d-flex align-items-center" href="#">
                <span class="exa-logo">EXA</span>
                <span class="ms-2 app-title">Control Tributario</span>
            </a>

            <!-- Pill Info -->
            <div class="contribuyente-pill d-none d-md-flex align-items-center mx-3" style="cursor: pointer;" onclick="editarContribuyente()" title="Haz clic para editar manualmente">
                <i class="bi bi-person-badge text-dark me-2"></i>
                <span class="ruc fw-bold text-dark"><?= htmlspecialchars($ruc) ?></span>
                <span class="separator mx-2 text-dark">|</span>
                <span class="nombre text-muted me-3"><?= htmlspecialchars($nombre) ?></span>
                <button class="btn btn-sm btn-outline-dark" onclick="event.stopPropagation(); window.location.href='index.php'" title="Recargar otro contribuyente">
                    <i class="bi bi-arrow-clockwise"></i> Recargar otro
                </button>
            </div>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#topbarNav">
                <i class="bi bi-list text-dark fs-2"></i>
            </button>

            <div class="collapse navbar-collapse" id="topbarNav">
                <!-- Removed tabs from here to move them to the body -->

                <!-- Selects & Action -->
                <div class="d-flex align-items-center gap-2 right-controls">
                    <select class="form-select form-select-sm glass-select" id="select-anio">
                        <option value="2023" <?= $anio=='2023'?'selected':'' ?>>2023</option>
                        <option value="2024" <?= $anio=='2024'?'selected':'' ?>>2024</option>
                        <option value="2025" <?= $anio=='2025'?'selected':'' ?>>2025</option>
                        <option value="2026" <?= $anio=='2026'?'selected':'' ?>>2026</option>
                    </select>
                    
                    <select class="form-select form-select-sm glass-select" id="select-regimen">
                        <option value="pn" <?= $regimen=='pn'?'selected':'' ?>>P. Natural - General</option>
                        <option value="soc" <?= $regimen=='soc'?'selected':'' ?>>Sociedad - General</option>
                    </select>

                    <button class="btn btn-warning btn-sm btn-glow text-dark text-nowrap fw-bold" onclick="iniciarAuditoria()" id="btn-conciliar-global">
                        <i class="bi bi-shield-check"></i> Auditar vs EXA ERP
                    </button>
                    <button class="btn btn-primary btn-sm btn-glow text-nowrap" id="btn-generar">
                        <i class="bi bi-cloud-download"></i> Generar informe
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="panel panel-main exa-ui-panel" style="margin-top: 80px; border: none; background: transparent; max-width: 98%; margin-left: auto; margin-right: auto;">
        
        <!-- PESTAÑAS TIPO MODELO 3 -->
        <div id="tabsLabores" class="ui-tab-fix">
            <ul class="nav nav-tabs border-0" id="mainTab" role="tablist" style="border-bottom: 2px solid #e2e8f0 !important; padding: 6px 8px 0; background: #ffffff; border-radius: 10px 10px 0 0;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="doc-tab" data-bs-toggle="tab" data-bs-target="#tab-documentos" type="button" role="tab">
                        <i class="bi bi-file-earmark-pdf"></i> Documentos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tabla-tab" data-bs-toggle="tab" data-bs-target="#tab-tabla" type="button" role="tab">
                        <i class="bi bi-table"></i> Tabla maestra
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ir-tab" data-bs-toggle="tab" data-bs-target="#tab-ir" type="button" role="tab">
                        <i class="bi bi-calculator"></i> Resumen IR
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="dash-tab" data-bs-toggle="tab" data-bs-target="#tab-dashboard" type="button" role="tab">
                        <i class="bi bi-grid-1x2"></i> Dashboard
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="iess-tab" data-bs-toggle="tab" data-bs-target="#tab-iess" type="button" role="tab">
                        <i class="bi bi-person-lines-fill"></i> Detalle IESS
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="xml-tab" data-bs-toggle="tab" data-bs-target="#tab-xml" type="button" role="tab">
                        <i class="bi bi-file-zip"></i> Detalle ATS
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="exa-detalle-tab" data-bs-toggle="tab" data-bs-target="#tab-exa-detalle" type="button" role="tab">
                        <i class="bi bi-robot"></i> Detalle EXA
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="f101-tab" data-bs-toggle="tab" data-bs-target="#tab-f101" type="button" role="tab">
                        <i class="bi bi-file-earmark-bar-graph"></i> Análisis F101
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ret-tab" data-bs-toggle="tab" data-bs-target="#tab-ret" type="button" role="tab">
                        <i class="bi bi-file-earmark-zip"></i> Análisis Retenciones
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="auditoria-tab" data-bs-toggle="tab" data-bs-target="#tab-auditoria" type="button" role="tab">
                        <i class="bi bi-shield-check"></i> Auditoría EXA
                    </button>
                </li>
                <li class="nav-item" role="presentation" id="nav-config" style="display: none;">
                    <button class="nav-link text-warning" id="config-tab" data-bs-toggle="tab" data-bs-target="#tab-config" type="button" role="tab">
                        <i class="bi bi-gear-fill"></i> Configuración
                    </button>
                </li>
            </ul>
        </div>

        <div class="panel-body exa-body main-content container-fluid" style="background: #ffffff; border-top: 1px solid #e2e8f0; padding: 16px 18px 24px;">
        <div class="tab-content" id="mainTabContent">
            
            <!-- PESTAÑA 1: DOCUMENTOS -->
            <div class="tab-pane fade show active" id="tab-documentos" role="tabpanel" tabindex="0">
                <div class="row g-4 mt-2">
                    <!-- Fila Superior -->
                    <div class="col-md-4">
                        <div class="card glass-card h-100 drop-zone-card">
                            <div class="card-header border-0 bg-transparent">
                                <h5 class="card-title text-primary"><i class="bi bi-file-earmark-text"></i> Form. 104 IVA</h5>
                                <p class="text-muted small mb-0">Mensual / Semestral</p>
                            </div>
                            <div class="card-body">
                                <div class="drop-zone" id="drop-104">
                                    <div class="drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                    <span>Arrastra o haz clic para subir PDFs</span>
                                    <input type="file" multiple accept=".pdf" class="d-none">
                                </div>
                                <div class="mt-3 chips-container" id="chips-104">
                                    <!-- Chips here -->
                                </div>
                                <div class="alert alert-warning mt-3 py-2 px-3 small d-none" id="alert-104">
                                    <i class="bi bi-exclamation-triangle"></i> <span class="msg"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card glass-card h-100 drop-zone-card" id="card-103">
                            <div class="card-header border-0 bg-transparent">
                                <h5 class="card-title"><i class="bi bi-receipt"></i> Form. 103 Retenciones IR</h5>
                                <p class="card-subtitle-hint">&nbsp;</p>
                            </div>
                            <div class="card-body">
                                <div class="drop-zone" id="drop-103">
                                    <div class="drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                    <span>Arrastra o haz clic para subir PDFs</span>
                                    <input type="file" multiple accept=".pdf" class="d-none">
                                </div>
                                <div class="mt-3 chips-container" id="chips-103">
                                    <!-- Chips here -->
                                </div>
                                <div class="alert-rimpe d-none mt-3 text-center text-muted small p-2 glass-panel">
                                    <i class="bi bi-info-circle"></i> No aplica - RIMPE no es agente de retención
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card glass-card h-100 drop-zone-card">
                            <div class="card-header border-0 bg-transparent">
                                <h5 class="card-title text-info"><i class="bi bi-briefcase"></i> Form. 102 / 101 Renta</h5>
                                <p class="card-subtitle-hint"><i class="bi bi-calendar-event"></i> Se declara en marzo del año siguiente</p>
                            </div>
                            <div class="card-body">
                                <div class="drop-zone" id="drop-renta">
                                    <div class="drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                    <span>Arrastra un PDF por año</span>
                                    <input type="file" accept=".pdf" class="d-none">
                                </div>
                                <div id="renta-data" class="mt-2 text-muted small d-none p-2 rounded" style="background:#e3f2fd;">
                                    <!-- Extracted data -->
                                </div>
                                <div class="mt-3 chips-container" id="chips-renta">
                                    <!-- Chips here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fila Inferior -->
                    <div class="col-md-4">
                        <div class="card glass-card h-100 drop-zone-card">
                            <div class="card-header border-0 bg-transparent">
                                <h5 class="card-title text-danger"><i class="bi bi-h-square"></i> Planilla IESS consolidada</h5>
                                <p class="card-subtitle-hint">&nbsp;</p>
                            </div>
                            <div class="card-body">
                                <div class="drop-zone" id="drop-iess">
                                    <div class="drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                    <span>Arrastra planillas consolidadas (PDF o Excel)</span>
                                    <input type="file" multiple accept=".pdf,.xlsx,.xls" class="d-none">
                                </div>
                                <div id="iess-summary" class="mt-3 d-none">
                                    <h6 class="text-muted border-bottom pb-2 border-secondary"><span id="iess-emp-count">0</span> empleados · <span id="iess-reg-count">0</span> registros cargados</h6>
                                    <div class="empleados-list small" style="max-height: 120px; overflow-y: auto;">
                                        <!-- List here -->
                                    </div>
                                </div>
                                <div class="mt-3 chips-container" id="chips-iess">
                                    <!-- Chips here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nueva tarjeta ATS XML -->
                    <div class="col-md-4">
                        <div class="card glass-card h-100 drop-zone-card">
                            <div class="card-header border-0 bg-transparent">
                                <h5 class="card-title text-warning"><i class="bi bi-filetype-xml"></i> Anexo ATS (XML)</h5>
                                <p class="card-subtitle-hint"><i class="bi bi-info-circle"></i> Sube los 12 XML del año juntos o un ZIP.</p>
                            </div>
                            <div class="card-body">
                                <div class="drop-zone" id="drop-ats-docs">
                                    <div class="drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                    <span>Arrastra el ZIP o los XML del ATS o haz clic</span>
                                    <input type="file" id="file-xml" class="d-none" accept=".xml,.zip" multiple>
                                </div>
                                
                                <div class="progress mt-3 d-none" id="xml-progress-container" style="height: 15px; border-radius: 10px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning text-dark fw-bold" id="xml-progress" role="progressbar" style="width: 0%;"></div>
                                </div>
                                <div class="text-center mt-1 d-none text-muted small fw-bold" id="xml-status"></div>
                                <div id="ats-loaded-months" class="mt-3 text-center text-success fw-bold d-none small"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Nueva tarjeta Retenciones Recibidas (ZIP/XML) -->
                    <div class="col-md-4">
                        <div class="card glass-card h-100 drop-zone-card">
                            <div class="card-header border-0 bg-transparent">
                                <h5 class="card-title text-success"><i class="bi bi-file-earmark-zip"></i> Retenciones Recibidas</h5>
                                <p class="card-subtitle-hint">&nbsp;</p>
                            </div>
                            <div class="card-body">
                                <div class="drop-zone" id="drop-retenciones">
                                    <div class="drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                    <span>Arrastra el ZIP con XMLs o haz clic</span>
                                    <input type="file" id="file-retenciones" class="d-none" accept=".zip,.xml" multiple>
                                </div>
                                <div class="mt-3 chips-container" id="chips-retenciones"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Nueva tarjeta EXA ERP -->
                    <div class="col-md-4">
                        <div class="card glass-card h-100 drop-zone-card" id="card-exa" onclick="abrirModalCargaSRI('exa')" style="cursor: pointer; border-color: #6366f1;">
                            <div class="card-header border-0 bg-transparent">
                                <h5 class="card-title" style="color: #4f46e5 !important;"><i class="bi bi-shield-check"></i> Conexión EXA ERP</h5>
                                <p class="card-subtitle-hint"><i class="bi bi-link-45deg"></i> Sincronizar o subir Excel de EXA</p>
                            </div>
                            <div class="card-body">
                                <div class="drop-zone" id="drop-exa">
                                    <div class="drop-icon" style="color: #4f46e5;"><i class="bi bi-robot"></i></div>
                                    <span>Haz clic para conectar con EXA ERP o subir Excel</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 d-none">
                        <div class="card glass-card h-100 param-card">
                            <div class="card-header border-0 bg-transparent d-flex justify-content-between align-items-center">
                                <h5 class="card-title text-success"><i class="bi bi-sliders"></i> Parámetros tributarios</h5>
                                <button class="btn btn-sm btn-outline-success border-0" id="btn-save-params" title="Guardar"><i class="bi bi-check2-circle"></i></button>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-2 col-6">
                                        <label class="form-label small text-muted">Tarifa IVA %</label>
                                        <input type="number" class="form-control form-control-sm glass-input" id="param-iva" value="15">
                                    </div>
                                    <div class="col-md-2 col-6">
                                        <label class="form-label small text-muted">SBU vigente</label>
                                        <input type="number" class="form-control form-control-sm glass-input" id="param-sbu" value="460">
                                    </div>
                                    <div class="col-md-2 col-6">
                                        <label class="form-label small text-muted">9no dígito RUC</label>
                                        <input type="text" class="form-control form-control-sm glass-input" id="param-9digito" value="1" maxlength="1">
                                    </div>
                                    <div class="col-md-2 col-6">
                                        <label class="form-label small text-muted">Ap. Patronal %</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm glass-input" id="param-patronal" value="11.15">
                                    </div>
                                    <div class="col-md-2 col-6">
                                        <label class="form-label small text-muted">Ap. Individual %</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm glass-input" id="param-individual" value="9.45">
                                    </div>
                                    <div class="col-md-2 col-6">
                                        <label class="form-label small text-muted">CCC %</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm glass-input" id="param-ccc" value="1.00">
                                    </div>
                                </div>
                                <hr class="border-secondary my-3">
                                <div class="d-flex justify-content-between">
                                    <p class="small text-muted mb-1"><i class="bi bi-info-circle"></i> Régimen: <span id="param-regimen-text" class="fw-bold" style="color: var(--doc-accent);">Persona Natural - General</span></p>
                                    <p class="small text-muted mb-0"><i class="bi bi-table"></i> Tabla progresiva IR y multas se ajustan al año <span class="fw-bold" id="param-anio-text" style="color: var(--doc-accent);">2026</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- PESTAÑA 2: TABLA MAESTRA -->
            <div class="tab-pane fade" id="tab-tabla" role="tabpanel" tabindex="0">
                <!-- Barra de descarga -->
                <div class="d-flex justify-content-end gap-2 mb-3 mt-1">
                    <button class="btn btn-sm btn-outline-dark fw-semibold" onclick="exportarExcelTabla()">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </button>
                    <button class="btn btn-sm btn-dark fw-semibold" onclick="exportarTodoExcel()">
                        <i class="bi bi-download me-1"></i>Descargar Todo
                    </button>
                </div>
                <!-- KPIs -->
                <div class="row g-3 mt-1 mb-4" id="kpi-maestra">
                    <div class="col"><div class="kpi-card p-3 glass-card text-center"><h6 class="text-muted small">Ventas netas acum.</h6><h4 class="fw-bold kpi-val" style="color: var(--doc-accent);" id="kpi-ventas">$0.00</h4></div></div>
                    <div class="col"><div class="kpi-card p-3 glass-card text-center"><h6 class="text-muted small">Total pagado SRI</h6><h4 class="fw-bold kpi-val" style="color: var(--doc-accent);" id="kpi-pagado">$0.00</h4></div></div>
                    <div class="col"><div class="kpi-card p-3 glass-card text-center"><h6 class="text-muted small">IVA causado acum.</h6><h4 class="fw-bold kpi-val" style="color: var(--doc-accent);" id="kpi-iva-causado">$0.00</h4></div></div>
                    <div class="col"><div class="kpi-card p-3 glass-card text-center"><h6 class="text-muted small">Nómina bruta IESS</h6><h4 class="fw-bold kpi-val" style="color: var(--doc-accent);" id="kpi-nomina">$0.00</h4></div></div>
                    <div class="col"><div class="kpi-card p-3 glass-card text-center"><h6 class="text-muted small">IVA pend. próx. mes</h6><h4 class="fw-bold kpi-val" style="color: var(--doc-accent);" id="kpi-iva-pend">$0.00</h4></div></div>
                </div>

                <!-- Tabla -->
                <div class="table-responsive glass-panel p-2 rounded table-maestra-container">
                    <table class="table table-hover table-bordered mb-0 maestra-table" id="tabla-maestra">
                        <thead>
                            <tr>
                                <th rowspan="2" class="align-middle text-center sticky-header-mes" style="min-width: 100px; background-color: #f1f5f9; color: #1e293b;">Mes</th>
                                <th colspan="6" class="text-center th-azul">Form. 104 Ventas</th>
                                <th colspan="6" class="text-center text-white th-verde">FORM. 104 COMPRAS</th>
                                <th colspan="1" class="text-center text-white th-dark" style="background-color: #343a40;">RESULTADO</th>
                                <!-- Espacio para Retenciones Recibidas (se insertan columnas dinámicas) -->
                                <th colspan="13" class="text-center th-naranja" id="th-liq-iva">Liquidación IVA</th>
                                <th colspan="1" class="text-center th-morado th-f103" id="th-f103-group">Form. 103 Retenciones IR</th>
                                <th colspan="6" class="text-center th-rojo">IESS / Nómina</th>
                                <th colspan="2" class="text-center th-gris">Resumen</th>
                            </tr>
                            <tr id="tr-subheaders">
                                <!-- Ventas -->
                                <th class="th-azul th-sub">401 V. Bruto 15%</th>
                                <th class="th-azul th-sub">403 V. Bruto 0%</th>
                                <th class="th-azul th-sub">N/C 15%</th>
                                <th class="th-azul th-sub">N/C 0%</th>
                                <th class="th-azul th-sub">429 IVA Gen.</th>
                                <th class="th-azul th-sub fw-bold">TOTAL V. NETAS</th>
                                <!-- Compras -->
                                <th class="th-verde th-sub">TARIFA 15%</th>
                                <th class="th-verde th-sub">TARIFA 0%</th>
                                <th class="th-verde th-sub">N/C 15%</th>
                                <th class="th-verde th-sub">N/C 0%</th>
                                <th class="th-verde th-sub">I.V.A.</th>
                                <th class="th-verde th-sub fw-bold">TOTAL NETO</th>
                                <!-- Resultado -->
                                <th class="th-sub text-white fw-bold" style="background-color: #495057;">V - C</th>
                                <!-- Espacio Retenciones -->
                                <th class="d-none th-dinamico-retenciones"></th>
                                <!-- Liquidación -->
                                <th class="th-naranja th-sub col-601">601 IVA Caus.</th>
                                <th class="th-naranja th-sub col-606">606 Créd.Ant.</th>
                                <th class="th-naranja th-sub col-617">617 Créd.Sig.</th>
                                <th class="th-naranja th-sub col-485">485 IVA Pagar</th>
                                <th class="th-naranja th-sub col-902 d-none">902 Imp. Pagar</th>
                                <th class="th-naranja th-sub col-903 d-none">903 Mora</th>
                                <th class="th-naranja th-sub col-904 d-none">904 Multa</th>
                                <th class="th-naranja th-sub fw-bold col-999">999 Pagado</th>
                                <th class="th-naranja th-sub col-721">721 Ret.10%</th>
                                <th class="th-naranja th-sub col-723">723 Ret.20%</th>
                                <th class="th-naranja th-sub col-725">725 Ret.30%</th>
                                <th class="th-naranja th-sub col-727">727 Ret.50%</th>
                                <th class="th-naranja th-sub col-729">729 Ret.70%</th>
                                <th class="th-naranja th-sub col-731">731 Ret.100%</th>
                                <th class="th-naranja th-sub col-799">799 Tot.Ret.</th>
                                <th class="th-naranja th-sub fw-bold col-801">801 Pago</th>
                                <!-- F103 (Dinámico) -->
                                <th class="th-morado th-sub th-f103" id="th-f103-total">Total F103</th>
                                <!-- IESS -->
                                <th class="th-rojo th-sub">Nómina Bruta</th>
                                <th class="th-rojo th-sub">Pat. 11.15%</th>
                                <th class="th-rojo th-sub">Ind. 9.45%</th>
                                <th class="th-rojo th-sub">CCC 1%</th>
                                <th class="th-rojo th-sub">Prov. 13/14</th>
                                <th class="th-rojo th-sub">Prov. Vac</th>
                                <!-- Resumen -->
                                <th class="th-gris th-sub fw-bold">Total Pagado</th>
                                <th class="th-gris th-sub text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-maestra">
                            <!-- JS fills this -->
                        </tbody>
                        <tfoot id="tfoot-maestra">
                            <!-- JS fills this with totals -->
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- PESTAÑA 3: RESUMEN IR -->
            <div class="tab-pane fade" id="tab-ir" role="tabpanel" tabindex="0">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="legend-pills">
                        <span class="badge rounded-pill bg-primary bg-opacity-25 text-primary border border-primary"><i class="bi bi-box-arrow-in-down"></i> Jalado Form.104</span>
                        <span class="badge rounded-pill" style="background-color: rgba(168,85,247,0.2); color: #c084fc; border: 1px solid #c084fc;"><i class="bi bi-box-arrow-in-down"></i> Jalado IESS</span>
                        <span class="badge rounded-pill bg-warning bg-opacity-25 text-warning border border-warning"><i class="bi bi-pencil-square"></i> Ingresar manual</span>
                        <span class="badge rounded-pill bg-success bg-opacity-25 text-success border border-success"><i class="bi bi-calculator"></i> Calculado</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div id="ir-alert-partial" class="alert alert-warning py-1 px-3 mb-0 small border border-warning d-none">
                            <i class="bi bi-hourglass-split"></i> Datos parciales: <span id="ir-meses-count">0</span>/12 meses · IR aumentará
                        </div>
                        <button class="btn btn-sm btn-outline-dark fw-semibold" onclick="exportarExcelIR()">
                            <i class="bi bi-file-earmark-excel me-1"></i>Excel
                        </button>
                        <button class="btn btn-sm btn-dark fw-semibold" onclick="exportarPdfIR()">
                            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                        </button>
                    </div>
                </div>

                <!-- KPIs -->
                <div class="row g-3 mb-4">
                    <div class="col-3"><div class="kpi-card p-3 glass-card"><h6 class="text-muted small">Ventas / Ingresos</h6><h4 class="text-primary kpi-val" id="ir-kpi-ingresos">$0.00</h4></div></div>
                    <div class="col-3"><div class="kpi-card p-3 glass-card"><h6 class="text-muted small">Base Imponible</h6><h4 class="text-warning kpi-val" id="ir-kpi-base">$0.00</h4></div></div>
                    <div class="col-3"><div class="kpi-card p-3 glass-card border-danger"><h6 class="text-muted small text-danger">IR Estimado Causado</h6><h4 class="text-danger fw-bold kpi-val" id="ir-kpi-causado">$0.00</h4></div></div>
                    <div class="col-3"><div class="kpi-card p-3 card"><h6 class="text-muted small">Nómina IESS</h6><h4 class="text-secondary kpi-val" id="ir-kpi-nomina">$0.00</h4></div></div>
                </div>

                <div class="row g-4">
                    <!-- Columna Izquierda: Inputs -->
                    <div class="col-lg-6" id="ir-inputs-col">
                        <!-- Filled by JS depending on regimen -->
                    </div>

                    <!-- Columna Derecha: Conciliación -->
                    <div class="col-lg-6">
                        <div class="card glass-card p-4 h-100" id="ir-conciliacion-col">
                             <!-- Filled by JS depending on regimen -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA 4: DASHBOARD -->
            <div class="tab-pane fade" id="tab-dashboard" role="tabpanel" tabindex="0">
                <!-- Barra descarga Dashboard -->
                <div class="d-flex justify-content-end mb-3 mt-1">
                    <button class="btn btn-sm btn-dark fw-semibold" onclick="exportarPdfDashboard()">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
                    </button>
                </div>
                <!-- KPIs Dashboard -->
                <div class="row g-3 mb-4 text-center">
                    <div class="col"><div class="glass-card p-3 rounded"><h2 class="text-success mb-0" id="dash-cumplidas">0</h2><span class="small text-muted">Decl. Cumplidas</span></div></div>
                    <div class="col"><div class="glass-card p-3 rounded"><h2 class="text-warning mb-0" id="dash-faltantes">12</h2><span class="small text-muted">PDFs Faltantes</span></div></div>
                    <div class="col"><div class="glass-card p-3 rounded"><h2 class="text-primary mb-0" id="dash-f103">0</h2><span class="small text-muted">F103 Pendientes</span></div></div>
                    <div class="col"><div class="glass-card p-3 rounded"><h2 class="text-danger mb-0" id="dash-iess">0%</h2><span class="small text-muted">IESS al día</span></div></div>
                    <div class="col"><div class="glass-card p-3 rounded"><h2 class="text-info mb-0" id="dash-iva">$0.00</h2><span class="small text-muted">IVA próx. mes</span></div></div>
                </div>

                <!-- Semáforo Grid -->
                <div class="row g-4">
                    <!-- F104 -->
                    <div class="col-md-3">
                        <div class="card glass-card h-100">
                            <div class="card-header border-bottom border-secondary bg-transparent"><h6 class="mb-0 text-primary"><i class="bi bi-file-earmark-text"></i> Form. 104 IVA</h6></div>
                            <div class="card-body p-0" id="dash-list-104"></div>
                        </div>
                    </div>
                    <!-- F103 -->
                    <div class="col-md-3">
                        <div class="card glass-card h-100" id="dash-card-103">
                            <div class="card-header border-bottom border-secondary bg-transparent"><h6 class="mb-0" style="color: #a855f7;"><i class="bi bi-receipt"></i> Form. 103 Retenciones</h6></div>
                            <div class="card-body p-0" id="dash-list-103"></div>
                        </div>
                    </div>
                    <!-- IESS -->
                    <div class="col-md-3">
                        <div class="card glass-card h-100">
                            <div class="card-header border-bottom border-secondary bg-transparent"><h6 class="mb-0 text-danger"><i class="bi bi-h-square"></i> IESS Nómina</h6></div>
                            <div class="card-body p-0" id="dash-list-iess"></div>
                        </div>
                    </div>
                    <!-- Encadenamiento IVA -->
                    <div class="col-md-3">
                        <div class="card glass-card h-100">
                            <div class="card-header border-bottom border-secondary bg-transparent"><h6 class="mb-0 text-warning"><i class="bi bi-link"></i> Encadenamiento IVA</h6></div>
                            <div class="card-body p-3" id="dash-encadenamiento">
                                <!-- Cajas -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA 5: DETALLE IESS -->
            <div class="tab-pane fade" id="tab-iess" role="tabpanel" tabindex="0">
                <!-- Barra de descarga -->
                <div class="d-flex justify-content-end gap-2 mb-3 mt-1">
                    <button class="btn btn-sm btn-outline-dark fw-semibold" onclick="exportarExcelIESS()">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </button>
                    <button class="btn btn-sm btn-dark fw-semibold" onclick="exportarPdfIESS()">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </button>
                </div>
                <div class="card glass-card h-100 mt-2">
                    <div class="card-header border-bottom border-secondary bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title text-danger mb-0"><i class="bi bi-h-square"></i> Detalle Anual de Roles IESS</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive glass-panel p-2 rounded">
                            <table class="table table-hover table-bordered mb-0 maestra-table" id="tabla-iess">
                                <thead>
                                    <tr>
                                        <th class="align-middle text-center" style="min-width: 100px;">Mes</th>
                                        <th class="text-end th-rojo">Empleados</th>
                                        <th class="text-end th-rojo">Nómina Bruta</th>
                                        <th class="text-end th-rojo">Ap. Patronal (11.15%)</th>
                                        <th class="text-end th-rojo">Ap. Individual (9.45%)</th>
                                        <th class="text-end th-rojo">Secap/IECE (1%)</th>
                                        <th class="text-end th-rojo">Prov. 13/14</th>
                                        <th class="text-end th-rojo">Prov. Vacaciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-iess">
                                    <!-- JS fills this -->
                                </tbody>
                                <tfoot id="tfoot-iess">
                                    <!-- JS fills this -->
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- PESTAÑA DETALLE ATS -->
            <div class="tab-pane fade" id="tab-xml" role="tabpanel" tabindex="0">
                <!-- Barra de descarga -->
                <div class="d-flex justify-content-end gap-2 mb-3 mt-1">
                    <button class="btn btn-sm btn-outline-dark fw-semibold" onclick="exportarExcelATS()">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </button>
                    <button class="btn btn-sm btn-dark fw-semibold" onclick="exportarPdfATS()">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </button>
                </div>
                <div class="card glass-card h-100 mt-2">
                    <div class="card-header border-bottom border-secondary bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title text-primary mb-0"><i class="bi bi-file-zip"></i> Detalle ATS</h5>
                        <div class="d-flex align-items-center gap-2">
                            <span id="chip-ats-estado" class="badge bg-warning text-dark me-1"><i class="bi bi-exclamation-triangle"></i> ATS: No cargado</span>
                            <span id="chip-104-estado" class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> F104: No cargado</span>
                        </div>
                    </div>
                    <div class="card-body">


                        <!-- Tabs ATS Resumen y Anulados -->
                        <ul class="nav nav-tabs mb-4" id="atsSubTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold" id="ats-resumen-tab" data-bs-toggle="tab" data-bs-target="#ats-resumen" type="button" role="tab"><i class="bi bi-table"></i> Resumen Consolidado</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold" id="ats-anulados-tab" data-bs-toggle="tab" data-bs-target="#ats-anulados" type="button" role="tab"><i class="bi bi-x-circle"></i> Comprobantes Anulados</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content">
                            <!-- Resumen Consolidado -->
                            <div class="tab-pane fade show active" id="ats-resumen" role="tabpanel" tabindex="0">
                                <div class="table-responsive glass-panel p-2 rounded table-maestra-container">
                                    <table class="table table-hover table-bordered mb-0 maestra-table" id="tabla-ats-resumen">
                                        <thead>
                                            <tr id="tr-headers-ats">
                                                <th rowspan="2" class="align-middle text-center sticky-header-mes" style="min-width: 100px; background-color: #1a2233; color: white;">MES</th>
                                                <th colspan="6" class="text-center th-azul" data-static="true">VENTAS ATS</th>
                                                <th colspan="6" class="text-center text-white th-verde" data-static="true">COMPRAS ATS</th>
                                                <th colspan="2" class="text-center th-morado" data-static="true" style="color: #ffffff !important;">ME RETUVIERON (VENTAS)</th>
                                                <th colspan="1" class="text-center" data-static="true" style="background-color: #d97706; color: #ffffff !important;">RET. IVA (COMPRAS)</th>
                                                <th colspan="1" class="text-center th-dark" data-static="true" style="background-color: #343a40; color: #ffffff !important;">RESULTADO</th>
                                            </tr>
                                            <tr id="tr-subheaders-ats">
                                                <!-- Ventas -->
                                                <th class="th-azul th-sub">401 V. BRUTO 15%</th>
                                                <th class="th-azul th-sub">403 V. BRUTO 0%</th>
                                                <th class="th-azul th-sub">N/C 15%</th>
                                                <th class="th-azul th-sub">N/C 0%</th>
                                                <th class="th-azul th-sub">429 IVA GEN.</th>
                                                <th class="th-azul th-sub fw-bold">TOTAL V. NETAS</th>
                                                <!-- Compras -->
                                                <th class="th-verde th-sub">TARIFA 15%</th>
                                                <th class="th-verde th-sub">TARIFA 0%</th>
                                                <th class="th-verde th-sub">N/C 15%</th>
                                                <th class="th-verde th-sub">N/C 0%</th>
                                                <th class="th-verde th-sub">I.V.A.</th>
                                                <th class="th-verde th-sub fw-bold">TOTAL NETO</th>
                                                <!-- Retenciones Ventas -->
                                                <th class="th-morado th-sub" style="color: #ffffff !important;">Ret. IVA</th>
                                                <th class="th-morado th-sub" style="color: #ffffff !important;">Ret. Renta</th>
                                                <!-- Retenciones Compras -->
                                                <th class="th-sub" style="background-color: #b45309; color: #ffffff !important;">Total Ret. IVA</th>
                                                <!-- Resultado -->
                                                <th class="th-sub fw-bold" style="background-color: #495057; color: #ffffff !important;">V - C</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody-ats-resumen">
                                            <tr><td colspan="16" class="text-center text-muted py-4"><i class="bi bi-info-circle"></i> Sube el ATS XML en Documentos para ver el resumen consolidado</td></tr>
                                        </tbody>
                                        <tfoot id="tfoot-ats-resumen">
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Comprobantes Anulados -->
                            <div class="tab-pane fade" id="ats-anulados" role="tabpanel" tabindex="0">
                                <div class="table-responsive glass-panel p-2 rounded">
                                    <table class="table table-hover table-bordered mb-0 maestra-table" id="tabla-ats-anulados">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="background-color: #1a2233; color: white;">MES</th>
                                                <th class="text-center">Tipo Comprobante</th>
                                                <th class="text-center">Establecimiento</th>
                                                <th class="text-center">Punto Emisión</th>
                                                <th class="text-center">Secuencial Inicio</th>
                                                <th class="text-center">Secuencial Fin</th>
                                                <th class="text-center">Autorización</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody-ats-anulados">
                                            <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-info-circle"></i> Sube el ATS XML en Documentos para ver comprobantes anulados</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA: DETALLE EXA -->
            <div class="tab-pane fade" id="tab-exa-detalle" role="tabpanel" tabindex="0">
                <div class="d-flex justify-content-end gap-2 mb-3 mt-1">
                    <button class="btn btn-sm btn-outline-dark fw-semibold" onclick="exportarExcelEXADetalle()">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </button>
                    <button class="btn btn-sm btn-dark fw-semibold" onclick="exportarPdfEXADetalle()">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </button>
                </div>
                <div class="card glass-card h-100 mt-2">
                    <div class="card-header border-bottom border-secondary bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title" style="color: #4f46e5 !important;"><i class="bi bi-robot"></i> Detalle EXA ERP</h5>
                        <div class="d-flex align-items-center gap-2">
                            <span id="chip-exa-estado" class="badge bg-warning text-dark me-1"><i class="bi bi-exclamation-triangle"></i> Datos EXA: No cargado</span>
                        </div>
                    </div>
                    <style>
                        /* Sticky first column for MES */
                        #tabla-exa-detalle thead th.sticky-col {
                            position: -webkit-sticky;
                            position: sticky;
                            left: 0;
                            background-color: #4f46e5 !important;
                            color: white !important;
                            z-index: 10;
                            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
                        }
                        #tabla-exa-detalle tbody td.sticky-col {
                            position: -webkit-sticky;
                            position: sticky;
                            left: 0;
                            background-color: #f8fafc !important;
                            color: #000000 !important;
                            font-weight: bold !important;
                            z-index: 5;
                            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
                        }
                    </style>
                    <div class="card-body">
                        <div class="table-responsive glass-panel p-2 rounded">
                            <table class="table table-hover table-bordered mb-0 maestra-table" id="tabla-exa-detalle" style="min-width: 1800px;">
                                <thead>
                                    <!-- NIVEL 1: FORMULARIO 104 y FORMULARIO 103 -->
                                    <tr>
                                        <th rowspan="3" class="text-center align-middle sticky-col" style="background-color: #4f46e5; color: white;">MES</th>
                                        <th colspan="29" class="text-center bg-primary text-white py-2">FORMULARIO 104</th>
                                        <th colspan="2" class="text-center bg-secondary text-white py-2" id="header-f103-title">FORMULARIO 103</th>
                                    </tr>
                                    <!-- NIVEL 2: VENTAS, COMPRAS, V - C, RETENCIONES IVA -->
                                    <tr>
                                        <th colspan="11" class="text-center th-azul py-2">VENTAS</th>
                                        <th colspan="10" class="text-center th-verde py-2">COMPRAS</th>
                                        <th rowspan="2" class="text-center align-middle th-azul py-2">V - C</th>
                                        <th colspan="7" class="text-center th-naranja py-2">RETENCIONES IVA</th>
                                        <th colspan="2" class="text-center th-gris py-2" id="header-f103-subtitle">FORMULARIO 103</th>
                                    </tr>
                                    <!-- NIVEL 3: DETALLE -->
                                    <tr id="header-row-details">
                                        <!-- VENTAS (11 cols) -->
                                        <th class="text-center small th-azul">BI 15%</th>
                                        <th class="text-center small th-azul">BI 12%</th>
                                        <th class="text-center small th-azul">BI 8%</th>
                                        <th class="text-center small th-azul">BI 5%</th>
                                        <th class="text-center small th-azul">BI 0%</th>
                                        <th class="text-center small th-azul">N/D</th>
                                        <th class="text-center small th-azul">N/C</th>
                                        <th class="text-center small th-azul">I.V.A.</th>
                                        <th class="text-center small th-azul">I. RENTA</th>
                                        <th class="text-center small th-azul">RET. IVA</th>
                                        <th class="text-center small th-azul fw-bold">TOTAL</th>
                                        <!-- COMPRAS (10 cols) -->
                                        <th class="text-center small th-verde">BI 15%</th>
                                        <th class="text-center small th-verde">BI 12%</th>
                                        <th class="text-center small th-verde">BI 8%</th>
                                        <th class="text-center small th-verde">BI 5%</th>
                                        <th class="text-center small th-verde">BI 0%</th>
                                        <th class="text-center small th-verde">N/D</th>
                                        <th class="text-center small th-verde">N/C</th>
                                        <th class="text-center small th-verde">IMPOR.</th>
                                        <th class="text-center small th-verde">I.V.A.</th>
                                        <th class="text-center small th-verde fw-bold">TOTAL</th>
                                        <!-- RETENCIONES IVA (7 cols) -->
                                        <th class="text-center small th-naranja">10%</th>
                                        <th class="text-center small th-naranja">20%</th>
                                        <th class="text-center small th-naranja">30%</th>
                                        <th class="text-center small th-naranja">50%</th>
                                        <th class="text-center small th-naranja">70%</th>
                                        <th class="text-center small th-naranja">100%</th>
                                        <th class="text-center small th-naranja fw-bold">TOTAL</th>
                                        <!-- FORMULARIO 103 (2 cols) -->
                                        <th class="text-center small th-gris" id="header-f103-cas">332</th>
                                        <th class="text-center small th-gris fw-bold">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-exa-detalle">
                                    <tr><td colspan="32" class="text-center text-muted py-4"><i class="bi bi-info-circle"></i> Sincroniza o sube el Excel de EXA para ver el detalle</td></tr>
                                </tbody>
                                <tfoot id="tfoot-exa-detalle">
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA 6: CONFIGURACION -->
            <div class="tab-pane fade" id="tab-config" role="tabpanel" tabindex="0">
                <div class="card glass-card h-100 mt-2">
                    <div class="card-body p-0">
                        <iframe src="admin.php?embed=1" style="width: 100%; height: 80vh; border: none; border-radius: 10px;"></iframe>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA 7: AUDITORIA -->
            <div class="tab-pane fade" id="tab-auditoria" role="tabpanel" tabindex="0">
                <div class="card glass-card h-100 mt-2">
                    <div class="card-header border-bottom border-secondary bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="card-title text-primary mb-0"><i class="bi bi-shield-check"></i> Auditoría EXA ERP</h5>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm btn-primary text-nowrap" id="btn-conciliar" onclick="iniciarAuditoria()"><i class="bi bi-arrow-repeat"></i> Auditar vs EXA ERP</button>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        <div id="auditoria-estado-inicial" class="text-center py-5">
                            <i class="bi bi-shield-check display-1 text-secondary opacity-50 mb-3"></i>
                            <h4 class="text-muted">Presiona Auditar vs EXA ERP para iniciar la verificación</h4>
                        </div>
                        
                        <div id="auditoria-resultados" class="d-none">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="glass-card p-2 rounded mb-2">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-muted" style="font-size: 0.85rem;"><i class="bi bi-file-earmark-text"></i> Form. 104 (SRI)</span>
                                            <span id="badge-aud-104" class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> No cargado</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="glass-card p-2 rounded mb-2">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-muted" style="font-size: 0.85rem;"><i class="bi bi-filetype-xml"></i> ATS XML</span>
                                            <span id="badge-aud-ats" class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> No cargado</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="glass-card p-2 rounded mb-2">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-muted" style="font-size: 0.85rem;"><i class="bi bi-robot"></i> EXA ERP</span>
                                            <span id="badge-aud-exa" class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> No cargado</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="auditoria-banner" class="alert alert-warning text-center py-2 fw-bold shadow-sm">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> Ejecutando...
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary"><i class="bi bi-graph-up"></i> Ventas</h6>
                                    <div class="table-responsive glass-panel p-2 rounded mb-4">
                                        <table class="table table-hover table-bordered mb-0 maestra-table" id="tabla-auditoria-ventas" style="font-size: 0.85rem;">
                                            <thead>
                                                <tr>
                                                    <th class="text-center th-azul">MES</th>
                                                    <th class="text-center th-gris">VENTA MAESTRA</th>
                                                    <th class="text-center th-gris">VENTA EXA</th>
                                                    <th class="text-center th-gris">VENTA ATS</th>
                                                    <th class="text-center th-naranja">DESCUADRE observacion</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody-auditoria-ventas">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6 class="text-success"><i class="bi bi-cart"></i> Compras</h6>
                                    <div class="table-responsive glass-panel p-2 rounded mb-4">
                                        <table class="table table-hover table-bordered mb-0 maestra-table" id="tabla-auditoria-compras" style="font-size: 0.85rem;">
                                            <thead>
                                                <tr>
                                                    <th class="text-center th-verde">MES</th>
                                                    <th class="text-center th-gris">COMPRA MAESTRA</th>
                                                    <th class="text-center th-gris">COMPRA EXA</th>
                                                    <th class="text-center th-gris">COMPRA ATS</th>
                                                    <th class="text-center th-naranja">DESCUADRE observacion</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody-auditoria-compras">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-muted small mt-2">
                                * <strong>Fuentes de datos:</strong> EXA ERP (tesoreria/tes_alt_con_trib_1.0.php). 
                                <span id="nota-fuente-ats">ATS no cargado.</span>
                                <span id="nota-fuente-decl">Declaración 104 no cargada.</span>
                                <br>
                                * <strong>Diferencia:</strong> Calculada como (Declaración SRI) - (EXA ERP). Si no hay declaración, se calcula (ATS XML) - (EXA ERP).
                            </div>

                        </div>


                        </div>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA ANÁLISIS F101 -->
            <div class="tab-pane fade" id="tab-f101" role="tabpanel" tabindex="0">
                <!-- Barra descarga F101 -->
                <div class="d-flex justify-content-end gap-2 mb-2 mt-1">
                    <button class="btn btn-sm btn-outline-dark fw-semibold" onclick="exportarExcelF101()">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </button>
                    <button class="btn btn-sm btn-dark fw-semibold" onclick="exportarPdfF101()">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </button>
                </div>
                <div id="f101-no-data" class="card glass-card mt-2">
                    <div class="card-body text-center py-2 d-flex flex-column align-items-center justify-content-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-bar-graph" style="font-size: 1.5rem; color: #64748b;"></i>
                            <h5 class="text-muted mb-0">No se ha cargado el Formulario 101 / 102</h5>
                        </div>
                        <p class="text-muted small mt-1 mb-0">Sube el PDF del Impuesto a la Renta del <strong>año anterior</strong> en la pestaña <em>Documentos</em> para ver el análisis completo.</p>
                    </div>
                </div>

                <div id="f101-analisis" class="d-none">
                    <!-- Header con datos de identificación -->
                    <div class="card glass-card mt-2 mb-3">
                        <div class="card-header border-bottom border-secondary bg-transparent">
                            <h5 class="card-title text-primary mb-0"><i class="bi bi-building"></i> Datos de Identificación — <span id="f101-tipo-form">F101</span></h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Razón Social</small>
                                    <strong id="f101-razon-social">—</strong>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted d-block">RUC</small>
                                    <strong id="f101-ruc">—</strong>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted d-block">Período Fiscal</small>
                                    <strong id="f101-anio">—</strong>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted d-block">Tipo Declaración</small>
                                    <span id="f101-tipo-decl" class="badge bg-info">—</span>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted d-block">N° Serie</small>
                                    <span id="f101-serie" class="small">—</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPIs principales -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-2">
                            <div class="kpi-card p-3 glass-card text-center">
                                <p class="small text-muted mb-1">Total Ingresos</p>
                                <p class="kpi-value text-primary" id="f101-kpi-ingresos">$0.00</p>
                                <small class="text-muted">Cas. 6999</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="kpi-card p-3 glass-card text-center">
                                <p class="small text-muted mb-1">Total Costos/Gastos</p>
                                <p class="kpi-value text-danger" id="f101-kpi-gastos">$0.00</p>
                                <small class="text-muted">Cas. 7999</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="kpi-card p-3 glass-card text-center">
                                <p class="small text-muted mb-1">Utilidad / Pérdida</p>
                                <p class="kpi-value" id="f101-kpi-utilidad">$0.00</p>
                                <small class="text-muted">Cas. 801/802</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="kpi-card p-3 glass-card text-center">
                                <p class="small text-muted mb-1">IR Causado</p>
                                <p class="kpi-value text-warning" id="f101-kpi-ir">$0.00</p>
                                <small class="text-muted">Cas. 850</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="kpi-card p-3 glass-card text-center">
                                <p class="small text-muted mb-1">Total Pagado</p>
                                <p class="kpi-value text-success" id="f101-kpi-pagado">$0.00</p>
                                <small class="text-muted">Cas. 999</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="kpi-card p-3 glass-card text-center">
                                <p class="small text-muted mb-1">Total Activos</p>
                                <p class="kpi-value text-info" id="f101-kpi-activos">$0.00</p>
                                <small class="text-muted">Cas. 499</small>
                            </div>
                        </div>
                    </div>

                    <!-- Sub-pestañas internas -->
                    <ul class="nav nav-pills mb-3" id="f101SubTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#f101-balance">Balance General</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#f101-resultados">Estado de Resultados</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#f101-conciliacion">Conciliación Tributaria</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#f101-verificaciones">Verificaciones Cruzadas</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#f101-indicadores">Indicadores Financieros</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#f101-alertas">Alertas y Riesgos</button></li>
                    </ul>

                    <div class="tab-content">
                        <!-- SUB-TAB: BALANCE GENERAL -->
                        <div class="tab-pane fade show active" id="f101-balance">
                            <div class="row g-3">
                                <!-- Activos -->
                                <div class="col-md-6">
                                    <div class="card glass-card h-100">
                                        <div class="card-header bg-transparent border-bottom border-secondary">
                                            <h6 class="mb-0 text-primary"><i class="bi bi-box-arrow-in-right"></i> ACTIVOS</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm table-hover mb-0" style="font-size:0.85rem">
                                                <thead><tr class="th-azul"><th>Casillero</th><th>Concepto</th><th class="text-end">Valor</th></tr></thead>
                                                <tbody id="f101-activos-tbody"></tbody>
                                                <tfoot>
                                                    <tr class="table-primary fw-bold"><td>499</td><td>TOTAL ACTIVOS</td><td class="text-end" id="f101-total-activos">0.00</td></tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <!-- Pasivos y Patrimonio -->
                                <div class="col-md-6">
                                    <div class="card glass-card mb-3">
                                        <div class="card-header bg-transparent border-bottom border-secondary">
                                            <h6 class="mb-0 text-danger"><i class="bi bi-box-arrow-in-left"></i> PASIVOS</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm table-hover mb-0" style="font-size:0.85rem">
                                                <thead><tr class="th-verde"><th>Casillero</th><th>Concepto</th><th class="text-end">Valor</th></tr></thead>
                                                <tbody id="f101-pasivos-tbody"></tbody>
                                                <tfoot>
                                                    <tr class="table-success fw-bold"><td>599</td><td>TOTAL PASIVOS</td><td class="text-end" id="f101-total-pasivos">0.00</td></tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="card glass-card">
                                        <div class="card-header bg-transparent border-bottom border-secondary">
                                            <h6 class="mb-0" style="color:#a855f7"><i class="bi bi-pie-chart"></i> PATRIMONIO</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm table-hover mb-0" style="font-size:0.85rem">
                                                <thead><tr style="background:#7c3aed;color:#fff"><th>Casillero</th><th>Concepto</th><th class="text-end">Valor</th></tr></thead>
                                                <tbody id="f101-patrimonio-tbody"></tbody>
                                                <tfoot>
                                                    <tr class="fw-bold" style="background:#ede9fe"><td>698</td><td>TOTAL PATRIMONIO</td><td class="text-end" id="f101-total-patrimonio">0.00</td></tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                    <!-- Ecuación contable -->
                                    <div class="alert mt-3 p-3" id="f101-ecuacion-alert">
                                        <strong><i class="bi bi-check-circle"></i> Ecuación Contable:</strong>
                                        <span id="f101-ecuacion-texto">Activo = Pasivo + Patrimonio</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- NUEVO BLOQUE: ANÁLISIS DETALLADO DEL BALANCE -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card glass-card">
                                        <div class="card-header bg-transparent border-bottom border-secondary">
                                            <h6 class="mb-0 text-info"><i class="bi bi-robot"></i> Análisis Detallado del Estado de Situación Financiera</h6>
                                        </div>
                                        <div class="card-body">
                                            <div id="f101-analisis-bloques">
                                                <!-- Se llenará con JS -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SUB-TAB: ESTADO DE RESULTADOS -->
                        <div class="tab-pane fade" id="f101-resultados">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card glass-card h-100">
                                        <div class="card-header bg-transparent border-bottom border-secondary">
                                            <h6 class="mb-0 text-success"><i class="bi bi-graph-up-arrow"></i> INGRESOS</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm table-hover mb-0" style="font-size:0.85rem">
                                                <thead><tr class="th-azul"><th>Casillero</th><th>Concepto</th><th class="text-end">Valor</th></tr></thead>
                                                <tbody id="f101-ingresos-tbody"></tbody>
                                                <tfoot>
                                                    <tr class="table-primary fw-bold"><td>6999</td><td>TOTAL INGRESOS</td><td class="text-end" id="f101-total-ingresos">0.00</td></tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card glass-card h-100">
                                        <div class="card-header bg-transparent border-bottom border-secondary">
                                            <h6 class="mb-0 text-danger"><i class="bi bi-graph-down-arrow"></i> COSTOS Y GASTOS</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-sm table-hover mb-0" style="font-size:0.85rem">
                                                <thead><tr class="th-verde"><th>Casillero</th><th>Concepto</th><th class="text-end">Valor</th></tr></thead>
                                                <tbody id="f101-gastos-tbody"></tbody>
                                                <tfoot>
                                                    <tr class="table-success fw-bold"><td>7999</td><td>TOTAL COSTOS Y GASTOS</td><td class="text-end" id="f101-total-gastos">0.00</td></tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SUB-TAB: CONCILIACIÓN TRIBUTARIA -->
                        <div class="tab-pane fade" id="f101-conciliacion">
                            <div class="card glass-card">
                                <div class="card-header bg-transparent border-bottom border-secondary">
                                    <h6 class="mb-0 text-warning"><i class="bi bi-calculator"></i> Conciliación Tributaria y Liquidación del Impuesto</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-hover mb-0" style="font-size:0.85rem">
                                        <thead><tr class="th-naranja"><th style="width:80px">Casillero</th><th>Concepto</th><th class="text-end" style="width:140px">Valor</th><th style="width:200px">Observación</th></tr></thead>
                                        <tbody id="f101-conciliacion-tbody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- SUB-TAB: VERIFICACIONES CRUZADAS -->
                        <div class="tab-pane fade" id="f101-verificaciones">
                            <div class="card glass-card">
                                <div class="card-header bg-transparent border-bottom border-secondary">
                                    <h6 class="mb-0 text-info"><i class="bi bi-check2-all"></i> Verificaciones Cruzadas Obligatorias</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-hover mb-0" style="font-size:0.85rem">
                                        <thead><tr style="background:#0ea5e9;color:#fff"><th style="width:40px"></th><th>Verificación</th><th class="text-end">Valor A</th><th class="text-end">Valor B</th><th class="text-end">Diferencia</th><th>Estado</th></tr></thead>
                                        <tbody id="f101-verif-tbody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- SUB-TAB: INDICADORES FINANCIEROS -->
                        <div class="tab-pane fade" id="f101-indicadores">
                            <div class="card glass-card">
                                <div class="card-header bg-transparent border-bottom border-secondary">
                                    <h6 class="mb-0 text-primary"><i class="bi bi-speedometer2"></i> Indicadores Financiero-Tributarios</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-hover mb-0" style="font-size:0.85rem">
                                        <thead><tr class="th-azul"><th>Indicador</th><th>Fórmula</th><th class="text-end">Valor</th><th>Comentario</th></tr></thead>
                                        <tbody id="f101-indicadores-tbody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- SUB-TAB: ALERTAS Y RIESGOS -->
                        <div class="tab-pane fade" id="f101-alertas">
                            <div class="card glass-card">
                                <div class="card-header bg-transparent border-bottom border-secondary">
                                    <h6 class="mb-0 text-danger"><i class="bi bi-exclamation-triangle"></i> Alertas y Riesgos Tributarios</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-hover mb-0" style="font-size:0.85rem">
                                        <thead><tr style="background:#dc2626;color:#fff"><th style="width:100px">Riesgo</th><th>Descripción</th><th>Casillero</th><th>Valor</th></tr></thead>
                                        <tbody id="f101-alertas-tbody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            
            <!-- PESTAÑA: ANÁLISIS RETENCIONES -->
            <div class="tab-pane fade" id="tab-ret" role="tabpanel" tabindex="0">
                <!-- Barra descarga -->
                <div class="d-flex justify-content-end gap-2 mb-3 mt-1">
                    <button class="btn btn-sm btn-outline-dark fw-semibold" onclick="exportarExcelRetenciones()">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </button>
                    <button class="btn btn-sm btn-dark fw-semibold" onclick="exportarPdfRetenciones()">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </button>
                </div>
                <!-- FILA KPIs -->
                <div class="row g-3 mb-4">
                    <div class="col-3"><div class="kpi-card p-3 glass-card text-center"><h6 class="text-muted small">Total Comprobantes</h6><h4 class="text-primary kpi-val" id="ret-kpi-docs">0</h4></div></div>
                    <div class="col-3"><div class="kpi-card p-3 glass-card text-center"><h6 class="text-muted small">Total Retenido</h6><h4 class="text-success kpi-val" id="ret-kpi-total">$0.00</h4></div></div>
                    <div class="col-3"><div class="kpi-card p-3 glass-card text-center"><h6 class="text-muted small">Agentes Únicos</h6><h4 class="text-warning kpi-val" id="ret-kpi-agentes">0</h4></div></div>
                    <div class="col-3"><div class="kpi-card p-3 glass-card text-center"><h6 class="text-muted small">Promedio Mensual</h6><h4 class="text-info kpi-val" id="ret-kpi-promedio">$0.00</h4></div></div>
                </div>
                
                <!-- FILA 2: Renta vs IVA -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="glass-card p-4">
                            <h5 class="text-primary"><i class="bi bi-wallet2"></i> Impuesto a la Renta</h5>
                            <h3 class="mb-2" id="ret-kpi-renta-total">$0.00</h3>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-primary" id="ret-bar-renta" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted" id="ret-pct-renta">0% del total retenido</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="glass-card p-4">
                            <h5 class="text-info"><i class="bi bi-receipt"></i> Retenciones IVA</h5>
                            <h3 class="mb-2" id="ret-kpi-iva-total">$0.00</h3>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-info" id="ret-bar-iva" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted" id="ret-pct-iva">0% del total retenido</small>
                        </div>
                    </div>
                </div>
                
                <!-- TABLA EVOLUCIÓN MENSUAL -->
                <div class="card glass-card mb-4">
                    <div class="card-header bg-transparent border-bottom border-secondary">
                        <h6 class="mb-0"><i class="bi bi-calendar3"></i> Evolución Mensual</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0 text-center" style="font-size: 0.9rem;">
                            <thead>
                                <tr class="th-gris">
                                    <th>Mes</th>
                                    <th>Docs</th>
                                    <th class="text-end">Renta</th>
                                    <th class="text-end">IVA</th>
                                    <th class="text-end fw-bold">Total</th>
                                    <th style="width: 30%;">Proporción</th>
                                </tr>
                            </thead>
                            <tbody id="ret-evolucion-tbody"></tbody>
                            <tfoot id="ret-evolucion-tfoot"></tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- CÓDIGOS DE RETENCIÓN -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card glass-card h-100">
                            <div class="card-header bg-transparent border-bottom border-secondary">
                                <h6 class="mb-0 text-primary"><i class="bi bi-list-ol"></i> Códigos RENTA</h6>
                            </div>
                            <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
                                    <thead>
                                        <tr class="th-azul">
                                            <th>Código</th>
                                            <th class="text-center">Veces</th>
                                            <th class="text-end">Base</th>
                                            <th class="text-end">Retenido</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ret-codigos-renta-tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card glass-card h-100">
                            <div class="card-header bg-transparent border-bottom border-secondary">
                                <h6 class="mb-0 text-info"><i class="bi bi-list-ol"></i> Códigos IVA</h6>
                            </div>
                            <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
                                    <thead>
                                        <tr class="th-naranja">
                                            <th>Código</th>
                                            <th class="text-center">Veces</th>
                                            <th class="text-end">Base</th>
                                            <th class="text-end">Retenido</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ret-codigos-iva-tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- TOP 10 AGENTES -->
                <div class="card glass-card">
                    <div class="card-header bg-transparent border-bottom border-secondary">
                        <h6 class="mb-0 text-success"><i class="bi bi-building"></i> Top 10 Agentes de Retención</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0" style="font-size: 0.9rem;">
                            <thead>
                                <tr class="th-verde">
                                    <th>Agente</th>
                                    <th>RUC</th>
                                    <th class="text-center">Docs</th>
                                    <th class="text-end">Total Retenido</th>
                                    <th style="width: 30%;">Proporción</th>
                                </tr>
                            </thead>
                            <tbody id="ret-agentes-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

    <!-- Modal Mapeo -->
    <div class="modal fade" id="modalMapeo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content glass-card">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title text-primary"><i class="bi bi-diagram-3"></i> Mapear Producto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Datos del Proveedor (XML)</strong><br>
                        Emisor: <span id="map-emisor" class="fw-bold"></span> (RUC: <span id="map-ruc"></span>)<br>
                        Código: <span id="map-cod" class="fw-bold"></span><br>
                        Descripción: <span id="map-desc"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-white">Buscar en catálogo EXA (Producto/Servicio)</label>
                        <select id="map-exa-select" class="form-select bg-dark text-white border-secondary">
                            <option value="">-- Seleccione el producto o servicio contable --</option>
                            <option value="1">Suministros de Oficina (Gasto)</option>
                            <option value="2">Inventario Mercadería (Activo)</option>
                            <option value="3">Honorarios Profesionales (Gasto)</option>
                            <option value="4">Servicios Básicos (Gasto)</option>
                            <option value="5">Mantenimiento Equipos (Gasto)</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="map-save" checked>
                        <label class="form-check-label text-white" for="map-save">
                            Recordar este mapeo automáticamente para futuras compras
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarMapeo()">Guardar y Continuar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL UNIVERSAL DE CARGA SRI (WIZARD 3 PASOS) -->
    <div class="modal fade" id="modalCargaSRI" tabindex="-1" aria-labelledby="modalCargaSRITitle" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content glass-effect border-0 shadow-lg" style="background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(15px); border-radius: 16px;">
                <div class="modal-header border-bottom border-secondary-subtle px-4 py-3" style="background: #ffffff; color: #0f172a; border-radius: 16px 16px 0 0;">
                    <div>
                        <h5 class="modal-title fw-bold d-flex align-items-center gap-2 mb-1" id="modalCargaSRITitle">
                            <i class="bi bi-file-earmark-check-fill fs-4" style="color: #334155;"></i> <span>Carga de Documentos SRI</span>
                        </h5>
                        <p class="mb-0 text-muted small" id="modalCargaSRISubtitle">Seleccione el método de carga para continuar</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <!-- Barra de Progreso Wizard -->
                <div class="bg-light px-4 py-2 border-bottom d-flex justify-content-between align-items-center small fw-semibold">
                    <span id="step-indicator-1" class="text-primary"><i class="bi bi-1-circle-fill me-1"></i> 1. Modalidad</span>
                    <span class="text-muted">➔</span>
                    <span id="step-indicator-2" class="text-muted"><i class="bi bi-2-circle me-1"></i> 2. Subida o Conexión</span>
                    <span class="text-muted">➔</span>
                    <span id="step-indicator-3" class="text-muted"><i class="bi bi-3-circle me-1"></i> 3. Informe y Validación</span>
                </div>

                <div class="modal-body p-4">
                    <input type="hidden" id="wizard-tipo-doc" value="104">

                    <!-- PASO 1: SELECCIÓN DE MODALIDAD -->
                    <div id="wizard-step-1" class="step-pane">
                        <h6 class="fw-bold mb-3 text-dark text-center">¿Cómo desea cargar los documentos de este período?</h6>
                        <div class="row g-4 mt-1">
                            <!-- Tarjeta Manual -->
                            <div class="col-md-6" id="wizard-card-manual">
                                <div class="card h-100 border-2 option-card p-3 text-center cursor-pointer" onclick="seleccionarModalidadWizard('manual')" style="transition: all 0.2s; border-color: #cbd5e1; border-radius: 12px; cursor: pointer;">
                                    <div class="py-3">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3" style="width: 64px; height: 64px;">
                                            <i class="bi bi-folder2-open fs-2"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark">Carga Manual Masiva</h6>
                                        <p class="text-muted small mb-0 px-2">Suba archivos PDF o XML locales. El sistema validará mes, RUC y aplicará prioridad automática a declaraciones <strong>Sustitutivas</strong>.</p>
                                    </div>
                                    <div class="mt-auto pt-3">
                                        <button type="button" class="btn btn-outline-primary w-100 fw-bold">Seleccionar Manual</button>
                                    </div>
                                </div>
                            </div>
                            <!-- Tarjeta Automática -->
                            <div class="col-md-6" id="wizard-card-auto">
                                <div class="card h-100 border-2 option-card p-3 text-center cursor-pointer" onclick="seleccionarModalidadWizard('auto')" style="transition: all 0.2s; border-color: #cbd5e1; border-radius: 12px; cursor: pointer;">
                                    <div class="py-3">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle p-3 mb-3" style="width: 64px; height: 64px;">
                                            <i class="bi bi-robot fs-2"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark d-flex align-items-center justify-content-center gap-1">
                                            <span>Carga Automática</span>
                                            <span class="badge bg-success small" style="font-size:0.65rem;">Scraper Nativo</span>
                                        </h6>
                                        <p class="text-muted small mb-0 px-2" id="wizard-auto-card-desc">Conexión directa al portal SRI en Línea vía Playwright interno. Descarga y sincroniza las declaraciones del mes automáticamente.</p>
                                    </div>
                                    <div class="mt-auto pt-3">
                                        <button type="button" class="btn btn-outline-success w-100 fw-bold">Seleccionar Automática</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PASO 2: CARGA MANUAL -->
                    <div id="wizard-step-2-manual" class="step-pane d-none">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-cloud-arrow-up text-primary"></i> Subir Archivos Físicos por Mes</h6>
                            <button type="button" class="btn btn-sm btn-link text-decoration-none" onclick="volverAPaso1()"><i class="bi bi-arrow-left"></i> Cambiar modalidad</button>
                        </div>

                        <div class="alert alert-info py-2 px-3 small mb-3 d-flex align-items-center gap-2" style="background-color: #e8f4fd; border-color: #bde0fe; color: #0366d6;">
                            <i class="bi bi-info-circle-fill fs-5"></i>
                            <div><strong>Detección Automática:</strong> No es necesario seleccionar mes ni año. El sistema leerá el período exacto automáticamente desde el contenido del archivo subido.</div>
                        </div>

                        <div class="row g-2 mb-3 d-none">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Año Fiscal</label>
                                <select id="wizard-manual-anio" class="form-select form-select-sm">
                                    <option value="2026">2026</option>
                                    <option value="2025">2025</option>
                                    <option value="2024">2024</option>
                                    <option value="2023">2023</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Mes del Documento</label>
                                <select id="wizard-manual-mes" class="form-select form-select-sm">
                                    <option value="todos" class="fw-bold text-primary">⚡ Todos los meses (Año completo)</option>
                                    <option value="sem1" class="fw-bold">📅 1er Semestre (Enero - Junio)</option>
                                    <option value="sem2" class="fw-bold">📅 2do Semestre (Julio - Diciembre)</option>
                                    <option disabled>──────────</option>
                                    <option value="1">Enero (01)</option>
                                    <option value="2">Febrero (02)</option>
                                    <option value="3">Marzo (03)</option>
                                    <option value="4">Abril (04)</option>
                                    <option value="5">Mayo (05)</option>
                                    <option value="6" selected>Junio (06)</option>
                                    <option value="7">Julio (07)</option>
                                    <option value="8">Agosto (08)</option>
                                    <option value="9">Septiembre (09)</option>
                                    <option value="10">Octubre (10)</option>
                                    <option value="11">Noviembre (11)</option>
                                    <option value="12">Diciembre (12)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Dropzone Masivo -->
                        <div class="border border-2 border-dashed rounded-3 p-4 text-center mb-3" style="background: #f8fafc; border-color: #94a3b8 !important;" id="wizard-dropzone">
                            <i class="bi bi-file-earmark-zip text-primary" style="font-size: 2.5rem;"></i>
                            <p class="mb-1 fw-bold text-dark mt-2">Arrastre los archivos XML, ZIP o PDF del período aquí</p>
                            <p class="text-muted small mb-3">Soporta carga masiva (comprobantes XML sueltos, archivo ZIP consolidado o PDFs).</p>
                            <label class="btn btn-primary btn-sm px-4">
                                <i class="bi bi-folder2-open me-1"></i> Seleccionar archivos
                                <input type="file" id="wizard-manual-files" multiple accept=".pdf,.xml,.xls,.xlsx,.zip" class="d-none">
                            </label>
                        </div>
                        <div id="wizard-manual-files-list" class="small text-muted mb-3"></div>
                    </div>

                    <!-- PASO 2: CARGA AUTOMÁTICA (SCRAPER NATIVO) -->
                    <div id="wizard-step-2-auto" class="step-pane d-none">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-robot text-success"></i> <span id="wizard-auto-title">Conexión Directa al SRI en Línea</span></h6>
                            <button type="button" class="btn btn-sm btn-link text-decoration-none" onclick="volverAPaso1()"><i class="bi bi-arrow-left"></i> Cambiar modalidad</button>
                        </div>

                        <div class="alert alert-info py-2 px-3 small mb-3 d-flex align-items-center gap-2" style="background: #e0f2fe; border-color: #bae6fd; color: #0369a1;">
                            <i class="bi bi-shield-lock-fill fs-5"></i>
                            <div id="wizard-auto-info-desc">El scraper nativo de este proyecto se ejecutará localmente para navegar, autenticarse y descargar los archivos del mes sin intermediarios.</div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold" id="wizard-auto-user-label">RUC del Contribuyente</label>
                                <input type="text" id="wizard-auto-ruc" class="form-control form-control-sm" placeholder="Ej. 0703703413001">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold" id="wizard-auto-pass-label">Contraseña SRI en Línea</label>
                                <input type="password" id="wizard-auto-password" class="form-control form-control-sm" placeholder="Clave del SRI">
                            </div>
                            
                            <!-- Campo EXA Empresa (Oculto) -->
                            <div class="col-md-12 d-none" id="contenedor-exa-empresa">
                                <label class="form-label small fw-bold">Empresa (Opcional, texto parcial)</label>
                                <input type="text" id="wizard-auto-empresa" class="form-control form-control-sm" placeholder="Ej. capacitacion videos">
                            </div>

                            <!-- Campos EXA: Año, Mes Desde, Mes Hasta -->
                            <div id="contenedor-exa-periodo" class="col-12 d-none">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Año Fiscal</label>
                                        <select id="wizard-exa-anio" class="form-select form-select-sm">
                                            <option value="2026">2026</option>
                                            <option value="2025">2025</option>
                                            <option value="2024">2024</option>
                                            <option value="2023">2023</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Mes Inicio</label>
                                        <select id="wizard-exa-mes-desde" class="form-select form-select-sm">
                                            <option value="1">Enero</option>
                                            <option value="2">Febrero</option>
                                            <option value="3">Marzo</option>
                                            <option value="4">Abril</option>
                                            <option value="5">Mayo</option>
                                            <option value="6">Junio</option>
                                            <option value="7">Julio</option>
                                            <option value="8">Agosto</option>
                                            <option value="9">Septiembre</option>
                                            <option value="10">Octubre</option>
                                            <option value="11">Noviembre</option>
                                            <option value="12">Diciembre</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Mes Fin</label>
                                        <select id="wizard-exa-mes-hasta" class="form-select form-select-sm">
                                            <option value="1">Enero</option>
                                            <option value="2">Febrero</option>
                                            <option value="3">Marzo</option>
                                            <option value="4">Abril</option>
                                            <option value="5">Mayo</option>
                                            <option value="6">Junio</option>
                                            <option value="7">Julio</option>
                                            <option value="8">Agosto</option>
                                            <option value="9">Septiembre</option>
                                            <option value="10">Octubre</option>
                                            <option value="11">Noviembre</option>
                                            <option value="12">Diciembre</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos SRI: Año + Mes (ocultos para IESS) -->
                            <div id="contenedor-sri-periodo" class="col-12">
                                <div class="row g-3">
                                    <div class="col-md-6" id="contenedor-wizard-auto-anio">
                                        <label class="form-label small fw-bold">Año Fiscal</label>
                                        <select id="wizard-auto-anio" class="form-select form-select-sm">
                                            <option value="2026">2026</option>
                                            <option value="2025">2025</option>
                                            <option value="2024">2024</option>
                                            <option value="2023">2023</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="contenedor-wizard-auto-mes">
                                        <label class="form-label small fw-bold">Mes a Descargar</label>
                                        <select id="wizard-auto-mes" class="form-select form-select-sm">
                                            <option value="todos" class="fw-bold text-primary">⚡ Todos los meses (Año completo)</option>
                                            <option value="sem1" class="fw-bold">📅 1er Semestre (Enero - Junio)</option>
                                            <option value="sem2" class="fw-bold">📅 2do Semestre (Julio - Diciembre)</option>
                                            <option disabled>──────────</option>
                                            <option value="1">Enero (01)</option>
                                            <option value="2">Febrero (02)</option>
                                            <option value="3">Marzo (03)</option>
                                            <option value="4">Abril (04)</option>
                                            <option value="5">Mayo (05)</option>
                                            <option value="6" selected>Junio (06)</option>
                                            <option value="7">Julio (07)</option>
                                            <option value="8">Agosto (08)</option>
                                            <option value="9">Septiembre (09)</option>
                                            <option value="10">Octubre (10)</option>
                                            <option value="11">Noviembre (11)</option>
                                            <option value="12">Diciembre (12)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos IESS: Período Desde/Hasta (ocultos para SRI) -->
                            <div id="contenedor-iess-periodo" class="col-12 d-none">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">
                                            <i class="bi bi-calendar-event text-primary me-1"></i>Período Desde
                                            <span class="text-muted fw-normal ms-1">(AAAA-MM)</span>
                                        </label>
                                        <input type="text" id="iess-periodo-desde" class="form-control form-control-sm font-monospace"
                                               placeholder="2025-01" maxlength="7"
                                               pattern="\d{4}-(0[1-9]|1[0-2])"
                                               style="letter-spacing: 1px; font-size: 0.95rem;">
                                        <div class="form-text text-muted" style="font-size:0.72rem;">Ejemplo: 2025-01 para Enero 2025</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">
                                            <i class="bi bi-calendar-check text-success me-1"></i>Período Hasta
                                            <span class="text-muted fw-normal ms-1">(AAAA-MM)</span>
                                        </label>
                                        <input type="text" id="iess-periodo-hasta" class="form-control form-control-sm font-monospace"
                                               placeholder="2025-12" maxlength="7"
                                               pattern="\d{4}-(0[1-9]|1[0-2])"
                                               style="letter-spacing: 1px; font-size: 0.95rem;">
                                        <div class="form-text text-muted" style="font-size:0.72rem;">Ejemplo: 2025-12 para Diciembre 2025</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="iessSetPeriodo(new Date().getFullYear(), 1, 12)">
                                                <i class="bi bi-lightning-charge-fill me-1 text-warning"></i>Año completo <?= date('Y') ?>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="iessSetPeriodo(<?= date('Y')-1 ?>, 1, 12)">
                                                <i class="bi bi-calendar me-1"></i>Año <?= date('Y')-1 ?>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="iessSetPeriodo(new Date().getFullYear(), 1, 6)">
                                                <i class="bi bi-calendar-range me-1"></i>1er Semestre <?= date('Y') ?>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="iessSetPeriodo(new Date().getFullYear(), 7, 12)">
                                                <i class="bi bi-calendar-range me-1"></i>2do Semestre <?= date('Y') ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Campos EXA se movieron arriba -->

                        </div><!-- /row g-3 mb-3 -->

                        <!-- Estado del Scraper en tiempo real (Diseño Dark y Vista en Vivo) -->
                        <div id="wizard-auto-spinner" class="d-none text-start p-3 rounded-4 border mb-3 shadow-lg" style="background: #0f172a; border-color: #1e293b !important; color: #f8fafc;">
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom" style="border-color: #1e293b !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="spinner-border text-info" role="status" style="width: 1.8rem; height: 1.8rem;"></div>
                                    <div>
                                        <h6 class="fw-bold text-white mb-0 fs-6" id="wizard-auto-status-title">Conectando con srienlinea.sri.gob.ec...</h6>
                                        <p class="text-light small mb-0" style="opacity: 0.8;" id="wizard-auto-status-desc">Abriendo navegador y preparando motor de descarga...</p>
                                    </div>
                                </div>
                                <span class="badge bg-success px-3 py-2 fs-6 fw-bold shadow-sm" id="wizard-auto-status-step-badge">Paso 1 de 5</span>
                            </div>
                            
                            <!-- Lista de pasos horizontal con iconos y flechas (Dark Mode) -->
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border" id="wizard-auto-steps-list" style="background: #1e293b; border-color: #334155 !important;">
                                <!-- Paso 1 -->
                                <div class="d-flex flex-column align-items-center text-center px-1" id="auto-step-item-1" style="flex: 1; min-width: 0;">
                                    <div class="step-icon d-flex align-items-center justify-content-center rounded-circle bg-dark border text-muted shadow-sm mb-1 transition-all" style="width:34px;height:34px; border-color: #475569 !important;" id="auto-step-icon-1">
                                        <i class="bi bi-person-badge fs-6 text-light"></i>
                                    </div>
                                    <span class="fw-bold text-white text-truncate w-100" style="font-size: 0.72rem;" id="auto-step-title-1">1. Acceso</span>
                                    <span class="text-muted" style="font-size: 0.65rem;" id="auto-step-status-1">Pendiente</span>
                                </div>

                                <!-- Flecha 1-2 -->
                                <div class="text-secondary px-1"><i class="bi bi-arrow-right fs-6"></i></div>

                                <!-- Paso 2 -->
                                <div class="d-flex flex-column align-items-center text-center px-1" id="auto-step-item-2" style="flex: 1; min-width: 0;">
                                    <div class="step-icon d-flex align-items-center justify-content-center rounded-circle bg-dark border text-muted shadow-sm mb-1 transition-all" style="width:34px;height:34px; border-color: #475569 !important;" id="auto-step-icon-2">
                                        <i class="bi bi-ui-checks fs-6 text-light"></i>
                                    </div>
                                    <span class="fw-bold text-white text-truncate w-100" style="font-size: 0.72rem;" id="auto-step-title-2">2. Obligación</span>
                                    <span class="text-muted" style="font-size: 0.65rem;" id="auto-step-status-2">Pendiente</span>
                                </div>

                                <!-- Flecha 2-3 -->
                                <div class="text-secondary px-1"><i class="bi bi-arrow-right fs-6"></i></div>

                                <!-- Paso 3 -->
                                <div class="d-flex flex-column align-items-center text-center px-1" id="auto-step-item-3" style="flex: 1; min-width: 0;">
                                    <div class="step-icon d-flex align-items-center justify-content-center rounded-circle bg-dark border text-muted shadow-sm mb-1 transition-all" style="width:34px;height:34px; border-color: #475569 !important;" id="auto-step-icon-3">
                                        <i class="bi bi-calendar-check fs-6 text-light"></i>
                                    </div>
                                    <span class="fw-bold text-white text-truncate w-100" style="font-size: 0.72rem;" id="auto-step-title-3">3. Período</span>
                                    <span class="text-muted" style="font-size: 0.65rem;" id="auto-step-status-3">Pendiente</span>
                                </div>

                                <!-- Flecha 3-4 -->
                                <div class="text-secondary px-1"><i class="bi bi-arrow-right fs-6"></i></div>

                                <!-- Paso 4 -->
                                <div class="d-flex flex-column align-items-center text-center px-1" id="auto-step-item-4" style="flex: 1; min-width: 0;">
                                    <div class="step-icon d-flex align-items-center justify-content-center rounded-circle bg-dark border text-muted shadow-sm mb-1 transition-all" style="width:34px;height:34px; border-color: #475569 !important;" id="auto-step-icon-4">
                                        <i class="bi bi-search fs-6 text-light"></i>
                                    </div>
                                    <span class="fw-bold text-white text-truncate w-100" style="font-size: 0.72rem;" id="auto-step-title-4">4. Prioridad</span>
                                    <span class="text-muted" style="font-size: 0.65rem;" id="auto-step-status-4">Pendiente</span>
                                </div>

                                <!-- Flecha 4-5 -->
                                <div class="text-secondary px-1"><i class="bi bi-arrow-right fs-6"></i></div>

                                <!-- Paso 5 -->
                                <div class="d-flex flex-column align-items-center text-center px-1" id="auto-step-item-5" style="flex: 1; min-width: 0;">
                                    <div class="step-icon d-flex align-items-center justify-content-center rounded-circle bg-dark border text-muted shadow-sm mb-1 transition-all" style="width:34px;height:34px; border-color: #475569 !important;" id="auto-step-icon-5">
                                        <i class="bi bi-cloud-download fs-6 text-light"></i>
                                    </div>
                                    <span class="fw-bold text-white text-truncate w-100" style="font-size: 0.72rem;" id="auto-step-title-5">5. Descarga</span>
                                    <span class="text-muted" style="font-size: 0.65rem;" id="auto-step-status-5">Pendiente</span>
                                </div>
                            </div>

                            <!-- Live View / Simulación de Scraper Nativo -->
                            <div class="mt-3 border rounded-3 overflow-hidden shadow" id="wizard-live-preview-box" style="background: #020617; border-color: #334155 !important;">
                                <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center" style="background: #0f172a; border-color: #334155 !important;">
                                    <div>
                                        <h6 class="text-white fw-bold mb-0" style="font-size: 0.85rem;"><i class="bi bi-laptop me-2 text-info"></i><span id="wizard-live-title">Vista del SRI: Ejecutando Scraper Nativo - Local</span></h6>
                                        <span class="text-light" style="font-size: 0.72rem; opacity: 0.8;">Esta vista simula las acciones locales del scraper para su revisión y seguridad.</span>
                                    </div>
                                    <span class="badge bg-primary px-2 py-1 shadow-sm" style="font-size: 0.7rem;"><i class="bi bi-broadcast me-1"></i>En Vivo</span>
                                </div>
                                <div class="p-0 text-center position-relative" style="min-height: 240px; background: #1e293b; display: flex; align-items: center; justify-content: center;">
                                    <img id="wizard-live-img" src="" alt="Live Scraper View" style="max-width: 100%; max-height: 350px; width: 100%; object-fit: contain; display: none;">
                                    <div id="wizard-live-placeholder" class="p-4 text-center text-light">
                                        <div class="spinner-border text-info mb-2" role="status"></div>
                                        <p class="mb-0 fw-bold small text-info" id="wizard-live-placeholder-text">Conectando con el navegador al portal oficial del SRI...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="button" id="btn-iniciar-scrape" class="btn btn-success fw-bold px-4" onclick="ejecutarScrapeNativo()">
                                <i class="bi bi-play-fill me-1"></i> Iniciar Descarga Automática
                            </button>
                        </div>
                    </div>

                    <!-- PASO 3: INFORME Y VALIDACIÓN -->
                    <div id="wizard-step-3" class="step-pane d-none">
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle p-3 mb-2" style="width: 56px; height: 56px;">
                                <i class="bi bi-check-lg fs-2"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Carga y Validación Completadas</h5>
                            <p class="text-muted small">Los datos se han verificado y están listos para alimentar el motor de cálculo tributario.</p>
                        </div>

                        <!-- Resumen Técnico -->
                        <div class="card border-0 bg-light p-3 mb-4" style="border-radius: 12px;">
                            <div class="row text-center g-3">
                                <div class="col-md-4 border-end">
                                    <span class="text-muted small d-block">Período Sincronizado</span>
                                    <strong class="text-dark fs-6" id="informe-periodo">Junio 2026</strong>
                                </div>
                                <div class="col-md-4 border-end">
                                    <span class="text-muted small d-block">Tipo de Declaración</span>
                                    <span id="informe-badge-tipo" class="badge bg-primary px-3 py-2 mt-1">ORIGINAL</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted small d-block">Estado de Validación</span>
                                    <strong class="text-success fs-6"><i class="bi bi-shield-check me-1"></i> Sincronizado</strong>
                                </div>
                            </div>
                            <div id="informe-alerta-sustitutiva" class="alert alert-warning mt-3 mb-0 py-2 px-3 small d-none d-flex align-items-center gap-2">
                                <i class="bi bi-exclamation-triangle-fill fs-5 text-warning"></i>
                                <div><strong>Regla de Prioridad Aplicada:</strong> Se detectó una declaración <strong>SUSTITUTIVA</strong> en el período. Esta ha reemplazado automáticamente los montos de la declaración original para el cálculo exacto.</div>
                            </div>
                            <!-- Archivos y PDF descargados para comprobación del usuario -->
                            <div id="informe-descargas-box" class="mt-3 text-start d-none">
                                <h6 class="fw-bold small text-muted mb-2"><i class="bi bi-folder2-open me-1"></i> Comprobantes / Archivos Descargados:</h6>
                                <div id="lista-archivos-pdf" class="d-flex flex-wrap gap-2"></div>
                            </div>
                        </div>

                        <div class="text-end border-top pt-3">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-primary fw-bold px-4" onclick="confirmarYCargarCalculo()">
                                <i class="bi bi-calculator-fill me-1"></i> Cargar al Sistema y Proceder al Cálculo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL VISOR ARCHIVOS VALIDADOS -->
    <div class="modal fade" id="modalVisorArchivo" tabindex="-1" aria-labelledby="modalVisorLabel" aria-hidden="true" style="z-index: 1065;">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header bg-dark text-white px-4 py-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="modalVisorLabel">
                        <i class="bi bi-file-earmark-text-fill text-info"></i> <span id="visor-titulo">Vista Previa de Archivo Validado</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="background-color: #525659; min-height: 68vh; display: flex; align-items: center; justify-content: center;">
                    <iframe id="iframe-visor-archivo" src="" style="width: 100%; height: 68vh; border: none; background: #fff;"></iframe>
                </div>
                <div class="modal-footer bg-light px-4 py-2 d-flex justify-content-between align-items-center">
                    <span class="small text-muted"><i class="bi bi-info-circle me-1"></i> Puedes revisar los casilleros validados directamente antes de proceder al cálculo.</span>
                    <div>
                        <a id="btn-visor-descargar" href="" download target="_blank" class="btn btn-outline-primary me-2">
                                            <i class="bi bi-download me-1"></i> Guardar Copia en Computadora
                        </a>
                        <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Cerrar Visor</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script> window.BASE_URL = "<?=$base_url?>"; </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="<?=$base_url?>assets/js/app.js?v=4.7"></script>
    <script src="<?=$base_url?>assets/js/xml-app.js?v=4.1"></script>
    <script src="<?=$base_url?>assets/js/exportar.js?v=20"></script>
    <!-- JS Logic for Retenciones Recibidas -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const fileInput = document.getElementById('file-retenciones');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                if(!this.files.length) return;
                
                const formData = new FormData();
                formData.append('file', this.files[0]);
                formData.append('tipo', 'retenciones_rec');
                
                document.getElementById('chips-retenciones').innerHTML = `<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Procesando...</span>`;
                
                fetch('ajax/upload_retenciones.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        document.getElementById('chips-retenciones').innerHTML = `<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> ZIP Procesado</span>`;
                        // Forzar a app.js?v=2.0 a regenerar el informe invocando updateState
                        if (typeof updateState === 'function') {
                            updateState();
                        } else {
                            document.getElementById('btn-generar').click();
                        }
                    } else {
                        throw new Error(data.error || 'Error desconocido');
                    }
                })
                .catch(err => {
                    console.error("Error retenciones:", err);
                    document.getElementById('chips-retenciones').innerHTML = `<span class="badge bg-danger"><i class="bi bi-x-circle-fill"></i> Error</span>`;
                    alert("Error al procesar archivo: " + err.message);
                })
                .finally(() => {
                    this.value = '';
                });
            });
        }
    });
    </script>


    </div>
</div>

<!-- Modal Detalle Descuadre Auditoría -->
<div class="modal fade" id="modalAuditoriaDetalle" tabindex="-1" aria-labelledby="modalAuditoriaDetalleLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content glass-card border-0">
      <div class="modal-header border-bottom border-secondary">
        <h5 class="modal-title text-primary" id="modalAuditoriaDetalleLabel"><i class="bi bi-search"></i> Detalle de Descuadre - <span id="modal-aud-mes"></span> (<span id="modal-aud-tipo"></span>)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0" style="font-size: 0.9rem;">
                <thead>
                    <tr>
                        <th class="th-gris text-center">Concepto</th>
                        <th class="th-azul text-center">Declaración (SRI)</th>
                        <th class="th-azul text-center">EXA ERP</th>
                        <th class="th-azul text-center">ATS XML</th>
                    </tr>
                </thead>
                <tbody id="modal-aud-tbody">
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer border-top border-secondary">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

</body>
</html>

