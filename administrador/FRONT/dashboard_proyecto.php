<?php
$dashboardPathHint = '';
$allowPath = __DIR__ . DIRECTORY_SEPARATOR . 'dashboard_scan_allow.json';
if (file_exists($allowPath)) {
    $rawAllow = @file_get_contents($allowPath);
    if ($rawAllow !== false) {
        $dj = json_decode($rawAllow, true);
        if (is_array($dj) && isset($dj['roots'][0]) && is_string($dj['roots'][0])) {
            $dashboardPathHint = trim($dj['roots'][0]);
        }
    }
}
if ($dashboardPathHint === '') {
    $dashboardPathHint = dirname(dirname(__DIR__));
}
$dashboardPathHintEsc = htmlspecialchars($dashboardPathHint, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Proyectos</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard_proyecto.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
</head>
<body>
    <div class="container">
        <!-- Selector de Proyecto(s) -->
        <div class="project-selector no-print">
            <label><i class="fas fa-folder-open"></i> Agregar carpeta (ruta en el <strong>servidor</strong>):</label>
            <input type="text" class="project-input" id="projectPath" placeholder="Absoluta (Linux/Windows) o relativa, ej. relavera / /var/www/html/relavera" value="">
            
            <select id="scanMode" class="project-input" style="max-width: 240px; min-width: auto;" title="Tipo de conteo para esta carpeta al agregarla">
                <option value="normal">Conteo Normal (Todas)</option>
                <option value="no_empty">Sin Espacios (No vac&iacute;as)</option>
                <option value="no_comments">Sin Comentarios</option>
            </select>

            <button class="btn btn-add-folder" type="button" onclick="agregarCarpetaACola()" title="A&ntilde;adir a la lista con el tipo de conteo elegido">
                <i class="fas fa-plus"></i> Agregar
            </button>
            <button class="btn btn-scan" type="button" onclick="escanearProyecto()">
                <i class="fas fa-search"></i> Escanear todo
            </button>
            <div class="project-selector-extra">
                <p class="project-path-hint">
                    Puede agregar <strong>varias carpetas</strong>; cada una con su propio tipo de conteo
                    (normal, sin espacios o sin comentarios).
                    Luego pulse <strong>Escanear todo</strong> para obtener las estad&iacute;sticas combinadas.
                    La ruta es la que ve <strong>PHP en el servidor</strong>. Ej.: <code>relavera</code> o
                    <code><?php echo $dashboardPathHintEsc; ?></code>.
                </p>
                <label for="serverProjectPick" style="font-size:12px;font-weight:600;color:var(--dark);display:block;margin-bottom:6px;">Elegir carpeta detectada en el servidor</label>
                <select id="serverProjectPick" title="Agrega un proyecto detectado a la cola de escaneo">
                    <option value="">&mdash; Cargando lista&hellip; &mdash;</option>
                </select>
            </div>
            <div class="scan-queue-wrap">
                <div class="scan-queue-title">
                    <span><i class="fas fa-list-check"></i> Carpetas a escanear (<span id="scanQueueCount">0</span>)</span>
                    <button type="button" class="btn-clear-queue" onclick="limpiarColaEscaneo()" title="Quitar todas">Vaciar lista</button>
                </div>
                <div class="scan-queue" id="scanQueue"></div>
            </div>
            <div class="project-list" id="projectList">
                <!-- Proyectos guardados -->
            </div>
        </div>
        
        <!-- Header -->
        <div class="header">
            <div>
                <p class="header-kicker">An&aacute;lisis de c&oacute;digo &middot; EXA</p>
                <h1><i class="fas fa-layer-group"></i><span id="projectName">Selecciona un Proyecto</span></h1>
            </div>
            <div class="header-info">
                <div class="date" id="currentDate"></div>
                <div class="header-actions">
                    <span class="version" id="projectStatus">Sin proyecto</span>
                    <button class="btn btn-config no-print" onclick="guardarConfiguracion()" id="btnSave" disabled>
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button class="btn btn-pdf no-print" onclick="generarPDF()" id="btnPdf" disabled>
                        <i class="fas fa-file-pdf"></i> PDF Gerencial
                    </button>
                    <button class="btn btn-outline no-print" onclick="exportarJsonComparativa()" id="btnExportBaseline" disabled title="Archivo peque&ntilde;o para comparar despu&eacute;s con un nuevo escaneo o PDF">
                        <i class="fas fa-file-code"></i> JSON comparativa
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Vista por carpeta (Todas + cada proyecto escaneado) -->
        <div class="project-view-bar no-print" id="projectViewBar">
            <span class="pv-label"><i class="fas fa-filter"></i> Ver m&eacute;tricas de:</span>
            <div class="project-view-tabs" id="projectViewTabs"></div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card total">
                <div class="icon"><i class="fas fa-code"></i></div>
                <div class="label">Total L&iacute;neas</div>
                <div class="value" id="totalLines">0</div>
                <div class="sub" id="totalFiles">0 archivos</div>
            </div>
            <div class="stat-card php">
                <div class="icon"><i class="fab fa-php"></i></div>
                <div class="label">PHP</div>
                <div class="value" id="phpLines">0</div>
                <div class="sub" id="phpFiles">0 archivos</div>
            </div>
            <div class="stat-card js">
                <div class="icon"><i class="fab fa-js"></i></div>
                <div class="label">JavaScript</div>
                <div class="value" id="jsLines">0</div>
                <div class="sub" id="jsFiles">0 archivos</div>
            </div>
            <div class="stat-card html">
                <div class="icon"><i class="fab fa-html5"></i></div>
                <div class="label">HTML</div>
                <div class="value" id="htmlLines">0</div>
                <div class="sub" id="htmlFiles">0 archivos</div>
            </div>
            <div class="stat-card horas">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="label">Total Horas</div>
                <div class="value" id="totalHours">0</div>
                <div class="sub">Estimadas</div>
            </div>
            <div class="stat-card completado">
                <div class="icon"><i class="fas fa-check-double"></i></div>
                <div class="label">Estado</div>
                <div class="value">100%</div>
                <div class="sub">Completado</div>
            </div>
        </div>
        
        <div class="no-print" id="clientCompareWrap" style="margin-bottom: 24px;">
            <div class="analytics-card client-compare-wide" style="margin:0;">
                <h2><i class="fas fa-balance-scale"></i> Comparativa con informe al cliente</h2>
                <p class="compare-hint">Sube un <strong>PDF gerencial</strong> generado desde este dashboard (o un <strong>JSON comparativa</strong> exportado aqu&iacute;) para mostrar diferencias frente al escaneo actual cuando el cliente lo pida. Los PDF antiguos sin marca interna se intentan leer por texto; si falla, usa el JSON.</p>
                <div class="baseline-toolbar">
                    <input type="file" id="clientBaselineInput" class="no-print" accept=".pdf,.json,application/pdf,application/json">
                    <input type="file" id="baselineFilesInput" class="no-print" accept=".pdf,.json,application/pdf,application/json" style="display:none;">
                    <button type="button" class="btn btn-outline no-print" id="btnEnrichBaselineFiles" onclick="document.getElementById('baselineFilesInput').click()" title="Adjunta un PDF gerencial o JSON con listado para activar el filtro de diffs" style="display:none;">
                        <i class="fas fa-list"></i> A&ntilde;adir listado (activar filtro)
                    </button>
                    <button type="button" class="btn btn-outline no-print" id="btnUsePreviousFiles" onclick="usarListadoEscaneoAnterior()" title="Usa el listado del escaneo anterior guardado" style="display:none;">
                        <i class="fas fa-history"></i> Usar escaneo anterior
                    </button>
                    <button type="button" class="btn btn-outline no-print" onclick="limpiarReferenciaCliente()"><i class="fas fa-times"></i> Quitar referencia</button>
                </div>
                <p id="clientBaselineStatus" style="font-size:13px;margin:12px 0 0 0;color:var(--dark);min-height:1.2em;"></p>
                <div id="baselineFilesPreview" class="ref-preview no-print" style="display:none;"></div>
                <div id="clientBaselineCompare" style="margin-top:14px;"></div>
                <div class="compare-pdf-option no-print" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);">
                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:13px;color:var(--dark);margin:0;">
                        <input type="checkbox" id="chkPdfIncludeHoursCompare" disabled style="margin-top:3px;flex-shrink:0;">
                        <span>Incluir en el <strong>PDF gerencial</strong> la comparativa de horas, l&iacute;neas y archivos frente al informe de referencia (solo si hay referencia cargada).</span>
                    </label>
                    <p id="chkPdfIncludeHoursCompareHint" class="compare-hint" style="margin:8px 0 0 28px;display:block;">Carga un PDF o JSON de referencia y escanea el proyecto para activar esta opci&oacute;n.</p>
                </div>
            </div>
        </div>
        
        <!-- Datos y analisis (desglose por carpeta; la comparativa con cliente va arriba) -->
        <div class="analytics-section no-print" id="analyticsSection" style="display: none;">
            <div class="analytics-card folder-analytics">
                <div class="folder-analytics-head">
                    <h2><i class="fas fa-folder-tree"></i>Por carpeta / m&oacute;dulo</h2>
                    <p class="analytics-hint">Agrupado por <strong>proyecto</strong> (carpeta global). Pulse un m&oacute;dulo para filtrar la tabla de archivos.</p>
                </div>
                <table class="folder-breakdown">
                    <thead>
                        <tr>
                            <th>Carpeta</th>
                            <th style="text-align:center;">Arch.</th>
                            <th style="text-align:right;">L&iacute;neas</th>
                            <th style="text-align:right;">Horas</th>
                            <th class="hours-delta-col" style="display:none;text-align:right;" title="Horas del escaneo menos horas del comparativo (JSON/PDF)">Dif. horas</th>
                            <th style="text-align:right;">% l&iacute;neas</th>
                            <th class="folder-bar-cell"></th>
                        </tr>
                    </thead>
                    <tbody id="folderBreakdownBody"></tbody>
                </table>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-grid">
            <!-- Tabla de archivos -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-folder-open"></i>Archivos del Proyecto</h2>
                    <div class="filters no-print">
                        <div class="type-filters">
                            <button class="filter-btn active" onclick="setFilter('type', 'all', this)">Todos</button>
                            <button class="filter-btn" onclick="setFilter('type', 'php', this)">PHP</button>
                            <button class="filter-btn" onclick="setFilter('type', 'js', this)">JS</button>
                            <button class="filter-btn" onclick="setFilter('type', 'html', this)">HTML</button>
                        </div>
                        <span class="filter-sep"></span>
                        <div class="folder-filters">
                            <select id="folderFilter" class="dash-select" title="Filtrar por m&oacute;dulo (flujo, relavera...) o por subcarpeta (FRONT, LOGICA...)" onchange="setFilter('folder', this.value)" style="min-width: 200px; max-width: 360px;">
                                <option value="all">Todas las carpetas</option>
                            </select>
                        </div>
                        <span class="filter-sep"></span>
                        <div class="complexity-filters">
                            <select id="complexityFilter" class="dash-select" onchange="setFilter('complexity', this.value)">
                                <option value="all">Todas las complejidades</option>
                                <option value="alta">Alta</option>
                                <option value="media-alta">Media-Alta</option>
                                <option value="media">Media</option>
                                <option value="baja">Baja</option>
                            </select>
                        </div>
                        <span class="filter-sep"></span>
                        <div class="diff-filters">
                            <select id="diffFilter" class="dash-select" title="Requiere referencia con detalle de archivos (JSON nuevo o PDF)" onchange="setFilter('diff', this.value)" style="min-width: 200px; max-width: 280px;" disabled>
                                <option value="all">Todos (sin filtro diff)</option>
                                <option value="changed">Solo nuevos / modificados</option>
                                <option value="added">Solo nuevos</option>
                                <option value="modified">Solo modificados</option>
                                <option value="increased">Solo con m&aacute;s l&iacute;neas</option>
                            </select>
                        </div>
                        <span class="filter-sep"></span>
                        <div class="inclusion-filters" style="display:flex;align-items:center;gap:8px;">
                            <select id="inclusionFilter" class="dash-select" title="Archivos excluidos no entran en totales ni en el PDF" onchange="setFilter('inclusion', this.value)" style="min-width: 170px;">
                                <option value="active">En el conteo</option>
                                <option value="excluded">Solo excluidos</option>
                                <option value="all">Todos</option>
                            </select>
                            <span id="excludedCountBadge" class="excluded-count-badge" style="display:none;"></span>
                        </div>
                    </div>
                </div>
                <div class="table-container">
                    <table class="files-table">
                        <thead>
                            <tr>
                                <th>Archivo</th>
                                <th>L&iacute;neas</th>
                                <th>Horas</th>
                                <th class="hours-delta-col" style="display:none;" title="Horas del escaneo menos horas del comparativo">Dif. horas</th>
                                <th>Sugerida</th>
                                <th>Complejidad</th>
                                <th class="no-print" style="width:52px;">Acc.</th>
                            </tr>
                        </thead>
                        <tbody id="filesTableBody">
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-folder-open"></i>
                                        <h3>Sin proyecto seleccionado</h3>
                                        <p>Agrega una o m&aacute;s carpetas y haz clic en &laquo;Escanear todo&raquo;</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Panel lateral -->
            <div class="sidebar">
                <div class="card sidebar-card">
                    <div class="card-header">
                        <h2><i class="fas fa-trophy"></i>Resumen</h2>
                    </div>
                    <div class="progress-ring-container">
                        <div class="work-summary">
                            <h3><i class="fas fa-check-circle"></i> Proyecto Analizado</h3>
                            <p>Escaneo autom&aacute;tico de archivos</p>
                            <p id="adjustSummary" style="margin-top:10px;font-size:12px;font-weight:600;color:#166534;"></p>
                        </div>
                        <div class="progress-ring">
                            <svg width="180" height="180">
                                <circle class="bg" cx="90" cy="90" r="78"></circle>
                                <circle class="progress" cx="90" cy="90" r="78" stroke-dasharray="490" stroke-dashoffset="0"></circle>
                            </svg>
                            <div class="percentage">
                                <div class="value">100%</div>
                                <div class="label">Completado</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card sidebar-card hours-delta-card no-print" id="hoursDeltaCard" style="display: none;">
                    <div class="card-header">
                        <h2><i class="fas fa-scale-balanced"></i>Horas aplicadas vs sugeridas</h2>
                    </div>
                    <div class="card-body">
                        <div class="hours-delta-rows">
                            <div class="hours-delta-row">
                                <span class="lbl">Aplicadas (complejidad elegida)</span>
                                <span class="val" id="hoursDeltaApplied">0 h</span>
                            </div>
                            <div class="hours-delta-row">
                                <span class="lbl">Sugeridas (heur&iacute;stica)</span>
                                <span class="val" id="hoursDeltaSuggested">0 h</span>
                            </div>
                        </div>
                        <div class="hours-delta-sep"></div>
                        <div class="hours-delta-diff zero" id="hoursDeltaDiff">Diferencia: 0 h</div>
                        <p class="hours-delta-hint">La diferencia es la suma de cambios por archivo respecto a la complejidad sugerida. No se incluye en el PDF.</p>
                    </div>
                </div>
                
                <div class="card sidebar-card">
                    <div class="card-header">
                        <h2><i class="fas fa-signal"></i>Por Complejidad</h2>
                    </div>
                    <div class="card-body">
                        <div class="complexity-list" id="complexityList">
                            <div class="complexity-item">
                                <div class="dot alta"></div>
                                <div class="info">
                                    <div class="name">Alta (25 l/h)</div>
                                    <div class="bar"><div class="bar-fill alta" id="barAlta" style="width: 0%"></div></div>
                                </div>
                                <div class="stats">
                                    <div class="hours" id="hoursAlta">0 h</div>
                                    <div class="files" id="filesAlta">0 archivos</div>
                                </div>
                            </div>
                            <div class="complexity-item">
                                <div class="dot media-alta"></div>
                                <div class="info">
                                    <div class="name">Media-Alta (35 l/h)</div>
                                    <div class="bar"><div class="bar-fill media-alta" id="barMediaAlta" style="width: 0%"></div></div>
                                </div>
                                <div class="stats">
                                    <div class="hours" id="hoursMediaAlta">0 h</div>
                                    <div class="files" id="filesMediaAlta">0 archivos</div>
                                </div>
                            </div>
                            <div class="complexity-item">
                                <div class="dot media"></div>
                                <div class="info">
                                    <div class="name">Media (45 l/h)</div>
                                    <div class="bar"><div class="bar-fill media" id="barMedia" style="width: 0%"></div></div>
                                </div>
                                <div class="stats">
                                    <div class="hours" id="hoursMedia">0 h</div>
                                    <div class="files" id="filesMedia">0 archivos</div>
                                </div>
                            </div>
                            <div class="complexity-item">
                                <div class="dot baja"></div>
                                <div class="info">
                                    <div class="name">Baja (60 l/h)</div>
                                    <div class="bar"><div class="bar-fill baja" id="barBaja" style="width: 0%"></div></div>
                                </div>
                                <div class="stats">
                                    <div class="hours" id="hoursBaja">0 h</div>
                                    <div class="files" id="filesBaja">0 archivos</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card sidebar-card">
                    <div class="card-header time-card-header">
                        <h2><i class="fas fa-calendar-check"></i>Tiempo</h2>
                        <label class="dev-count-wrap" title="Horas efectivas: 16 h/d&iacute;a con 3 personas (escala lineal)">
                            <span>Devs</span>
                            <input type="number" id="devCountInput" class="dev-count-input" min="1" max="30" step="1" value="3" onchange="cambiarNumeroDevs(this.value)" oninput="cambiarNumeroDevs(this.value)">
                        </label>
                    </div>
                    <div class="card-body">
                        <p class="dev-count-hint" id="devCountHint">3 desarrolladores &middot; 16.0 h efectivas / d&iacute;a</p>
                        <div class="time-grid">
                            <div class="time-item">
                                <div class="value" id="timeDays">0</div>
                                <div class="label">D&iacute;as</div>
                            </div>
                            <div class="time-item">
                                <div class="value" id="timeWeeks">0</div>
                                <div class="label">Semanas</div>
                            </div>
                            <div class="time-item">
                                <div class="value" id="timeMonths">0</div>
                                <div class="label">Meses</div>
                            </div>
                            <div class="time-item">
                                <div class="value" id="timeHours">0</div>
                                <div class="label">Horas</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading -->
    <div class="loading" id="loading">
        <div class="loading-content">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Escaneando proyecto...</p>
        </div>
    </div>
    
    <!-- Toast -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage">Guardado correctamente</span>
    </div>
    
    <!-- PDF Content (hidden) -->
    <div id="pdfContent" style="display: none;"></div>

    <script>
        // Variables globales
        let projectFiles = [];
        let currentProject = '';
        let currentScanTargets = [];
        let scanQueue = [];
        let currentProjectView = 'all';
        let currentTypeFilter = 'all';
        let currentFolderFilter = 'all';
        let currentComplexityFilter = 'all';
        let currentDiffFilter = 'all';
        let currentInclusionFilter = 'active'; // active | excluded | all
        let clientBaseline = null;
        let clientBaselineLoadError = null;
        let folderBreakdownOpen = {};
        let devCount = 3;
        const DEV_COUNT_KEY = 'dashboard_dev_count';
        const HOURS_PER_DAY_3DEVS = 16;
        const STORAGE_KEY = 'dashboard_projects';
        const MAX_HISTORY_POINTS = 20;
        const BASELINE_SESSION_KEY = 'dashboard_client_baseline_v1';
        
        if (typeof pdfjsLib !== 'undefined') {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
        
        function pathBasename(p) {
            const parts = String(p || '').split(/[\\\/]/).filter(Boolean);
            return parts.length ? parts[parts.length - 1] : p;
        }
        
        function normalizePathKey(p) {
            return String(p || '').replace(/\//g, '\\').replace(/\\+$/, '').toLowerCase();
        }
        
        function fileFolderRelative(file) {
            let folder = String(file.folder || 'ROOT').replace(/\\/g, '/');
            const label = String(file.project || pathBasename(file.projectPath || '')).replace(/\\/g, '/');
            if (!label || folder === 'ROOT' || folder === '') return folder || 'ROOT';
            if (folder === label) return 'ROOT';
            const prefix = label + '/';
            if (folder.toLowerCase().indexOf(prefix.toLowerCase()) === 0) {
                folder = folder.slice(prefix.length) || 'ROOT';
            }
            return folder || 'ROOT';
        }

        /** Clave estable (misma en solo-carpeta y multi) para complejidad / exclusiones. */
        function fileComplexityKey(file) {
            const proj = file.projectPath || file.project || '';
            return proj + '|' + fileFolderRelative(file) + '|' + file.name;
        }

        /** Variantes de clave; nameOnly=false evita choques entre carpetas en el mapa combinado. */
        function fileComplexityKeyVariants(file, nameOnly) {
            const proj = file.projectPath || file.project || '';
            const rel = fileFolderRelative(file);
            const raw = file.folder || 'ROOT';
            const name = file.name;
            const keys = [
                proj + '|' + rel + '|' + name,
                proj + '|' + raw + '|' + name
            ];
            if (nameOnly !== false) keys.push(name);
            return keys.filter((k, i, arr) => k && arr.indexOf(k) === i);
        }

        function lookupInSavedMap(map, file, nameOnly) {
            if (!map) return null;
            const keys = fileComplexityKeyVariants(file, nameOnly);
            for (let i = 0; i < keys.length; i++) {
                if (map[keys[i]] != null) return map[keys[i]];
            }
            return null;
        }

        function findSavedProjectKeys(projects, path) {
            if (!path || !projects) return [];
            const want = normalizePathKey(path);
            return Object.keys(projects).filter(pk => normalizePathKey(pk) === want);
        }

        /**
         * Aplica complejidad/exclusiones guardadas.
         * Prioridad: config de la carpeta individual (flujo solo, relavera solo)
         * y solo si no hay, la del proyecto combinado (relavera||flujo).
         */
        function applySavedFileSettings(file, projects, primaryProjectKey) {
            const individualKeys = findSavedProjectKeys(projects, file.projectPath);
            const keysToTry = individualKeys.slice();
            if (primaryProjectKey && projects[primaryProjectKey]) {
                const primNorm = normalizePathKey(primaryProjectKey);
                if (!individualKeys.some(k => normalizePathKey(k) === primNorm)) {
                    keysToTry.push(primaryProjectKey);
                }
            }

            const individualSet = {};
            individualKeys.forEach(k => { individualSet[normalizePathKey(k)] = true; });

            const seen = {};
            let complexitySet = false;
            let excludedSet = false;
            keysToTry.forEach(pk => {
                if (!pk || seen[normalizePathKey(pk)] || !projects[pk]) return;
                seen[normalizePathKey(pk)] = true;
                const saved = projects[pk];
                const isIndividual = !!individualSet[normalizePathKey(pk)];
                // En el mapa combinado no usar solo el nombre del archivo (colisiona entre carpetas)
                const allowNameOnly = isIndividual;

                if (!complexitySet) {
                    const c = lookupInSavedMap(saved.complexities, file, allowNameOnly);
                    if (c != null) {
                        file.complexity = c;
                        complexitySet = true;
                    }
                }
                if (!excludedSet && saved.excludedFiles) {
                    const keys = fileComplexityKeyVariants(file, allowNameOnly);
                    for (let i = 0; i < keys.length; i++) {
                        if (saved.excludedFiles[keys[i]]) {
                            file.excluded = true;
                            excludedSet = true;
                            break;
                        }
                    }
                }
            });
        }

        function isFileExcluded(file) {
            return !!(file && file.excluded);
        }

        function getVisibleFiles() {
            if (!projectFiles.length) return [];
            let list = projectFiles;
            if (currentProjectView !== 'all' && currentProjectView) {
                const key = normalizePathKey(currentProjectView);
                list = list.filter(f => {
                    if (f.projectPath && normalizePathKey(f.projectPath) === key) return true;
                    if (f.project && normalizePathKey(f.project) === key) return true;
                    return false;
                });
            }
            return list.filter(fileHasLines);
        }

        /** Archivos que entran en metricas / PDF / horas (no excluidos). */
        function getActiveFiles() {
            return getVisibleFiles().filter(f => !isFileExcluded(f));
        }

        function getExcludedFiles() {
            return projectFiles.filter(f => isFileExcluded(f));
        }

        function countExcludedFiles() {
            return getExcludedFiles().length;
        }

        function setFileExcluded(index, excluded) {
            if (index < 0 || index >= projectFiles.length) return;
            projectFiles[index].excluded = !!excluded;
            renderTable();
            updateStats();
            const n = countExcludedFiles();
            showToast(excluded
                ? 'Archivo excluido del conteo' + (n > 1 ? ' (' + n + ' excluidos)' : '')
                : 'Archivo vuelto al conteo');
        }

        function excluirArchivo(index) {
            setFileExcluded(index, true);
        }

        function incluirArchivo(index) {
            setFileExcluded(index, false);
        }
        
        function normalizeScanMode(mode) {
            if (mode === 'no_empty' || mode === 'no_comments') return mode;
            return 'normal';
        }

        function modeLabel(mode) {
            if (mode === 'no_empty') return 'Sin espacios';
            if (mode === 'no_comments') return 'Sin comentarios';
            return 'Conteo normal';
        }

        function scanModeOptionsHtml(selected) {
            const modes = [
                { v: 'normal', t: 'Conteo Normal (Todas)' },
                { v: 'no_empty', t: 'Sin Espacios (No vac\u00edas)' },
                { v: 'no_comments', t: 'Sin Comentarios' }
            ];
            return modes.map(m =>
                '<option value="' + m.v + '"' + (selected === m.v ? ' selected' : '') + '>' + m.t + '</option>'
            ).join('');
        }
        
        function renderScanQueue() {
            const box = document.getElementById('scanQueue');
            const countEl = document.getElementById('scanQueueCount');
            if (countEl) countEl.textContent = String(scanQueue.length);
            if (!box) return;
            if (!scanQueue.length) {
                box.innerHTML = '<div class="scan-queue-empty">Ninguna carpeta en la lista. Escriba una ruta o elija del desplegable y pulse <strong>Agregar</strong>.</div>';
                return;
            }
            box.innerHTML = scanQueue.map((item, idx) => {
                const label = pathBasename(item.path);
                const escPath = String(item.path).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                return '<div class="scan-queue-item" data-idx="' + idx + '">' +
                    '<div class="sq-label">' + label.replace(/</g, '&lt;') +
                    '<span class="sq-path">' + escPath + '</span></div>' +
                    '<select class="sq-mode" onchange="cambiarModoCola(' + idx + ', this.value)" title="Tipo de conteo de esta carpeta">' +
                    scanModeOptionsHtml(item.mode) +
                    '</select>' +
                    '<button type="button" class="sq-remove" onclick="quitarDeCola(' + idx + ')" title="Quitar"><i class="fas fa-times"></i></button>' +
                    '</div>';
            }).join('');
        }
        
        function agregarCarpetaACola(pathOverride, modeOverride) {
            const pathEl = document.getElementById('projectPath');
            const modeEl = document.getElementById('scanMode');
            const path = (pathOverride != null ? pathOverride : (pathEl ? pathEl.value : '')).trim();
            const mode = modeOverride || (modeEl ? modeEl.value : 'normal') || 'normal';
            if (!path) {
                showToast('Ingresa o elige una ruta de carpeta', 'warning');
                return;
            }
            const key = normalizePathKey(path);
            const existing = scanQueue.findIndex(t => normalizePathKey(t.path) === key);
            if (existing >= 0) {
                scanQueue[existing].mode = normalizeScanMode(mode);
                renderScanQueue();
                showToast('Carpeta ya estaba en la lista; se actualiz\u00f3 el tipo de conteo', 'warning');
                return;
            }
            scanQueue.push({
                path: path,
                mode: normalizeScanMode(mode)
            });
            renderScanQueue();
            if (pathEl && !pathOverride) pathEl.value = '';
            showToast('Carpeta agregada: ' + pathBasename(path));
        }
        
        function quitarDeCola(idx) {
            if (idx < 0 || idx >= scanQueue.length) return;
            scanQueue.splice(idx, 1);
            renderScanQueue();
        }
        
        function cambiarModoCola(idx, mode) {
            if (idx < 0 || idx >= scanQueue.length) return;
            const m = normalizeScanMode(mode);
            scanQueue[idx].mode = m;
            // Mantener sincronizado el estado del ultimo escaneo (lo que Guardar persistia antes)
            const key = normalizePathKey(scanQueue[idx].path);
            const t = currentScanTargets.find(x => normalizePathKey(x.path) === key);
            if (t) t.mode = m;
        }

        /** Targets a persistir: modos actuales de la cola + labels del ultimo escaneo. */
        function buildTargetsFromQueue() {
            if (!scanQueue.length) return currentScanTargets.slice();
            return scanQueue.map(q => {
                const key = normalizePathKey(q.path);
                const prev = currentScanTargets.find(t => normalizePathKey(t.path) === key);
                return {
                    path: q.path,
                    mode: normalizeScanMode(q.mode),
                    label: (prev && prev.label) || pathBasename(q.path)
                };
            });
        }

        function targetsModesChanged(a, b) {
            if (!a || !b || a.length !== b.length) return true;
            for (let i = 0; i < a.length; i++) {
                const ka = normalizePathKey(a[i].path);
                const kb = normalizePathKey(b[i].path);
                if (ka !== kb) return true;
                if (normalizeScanMode(a[i].mode) !== normalizeScanMode(b[i].mode)) return true;
            }
            return false;
        }
        
        function limpiarColaEscaneo() {
            scanQueue = [];
            renderScanQueue();
        }
        
        function displayProjectTitle(targets) {
            if (!targets || !targets.length) return 'Selecciona un Proyecto';
            const names = targets.map(t => t.label || pathBasename(t.path));
            if (names.length === 1) return 'Proyecto ' + names[0];
            if (names.length <= 3) return 'Proyectos ' + names.join(' + ');
            return 'Proyectos combinados (' + names.length + ')';
        }
        
        function compositeProjectKey(targets) {
            if (!targets || !targets.length) return '';
            return targets.map(t => t.path).join('||');
        }

        // getVisibleFiles / getActiveFiles definidos arriba junto a fileComplexityKey

        function renderProjectViewTabs() {
            const bar = document.getElementById('projectViewBar');
            const tabs = document.getElementById('projectViewTabs');
            if (!bar || !tabs) return;

            const multi = currentScanTargets.length > 1;
            if (!multi || !projectFiles.length) {
                bar.classList.remove('show');
                tabs.innerHTML = '';
                currentProjectView = 'all';
                return;
            }

            bar.classList.add('show');
            const valid = new Set(currentScanTargets.map(t => normalizePathKey(t.path)));
            if (currentProjectView !== 'all' && !valid.has(normalizePathKey(currentProjectView))) {
                currentProjectView = 'all';
            }

            let html = '<button type="button" class="project-view-tab' +
                (currentProjectView === 'all' ? ' active' : '') +
                '" onclick="setProjectView(\'all\')">Todas</button>';

            currentScanTargets.forEach(t => {
                const label = (t.label || pathBasename(t.path)).replace(/</g, '&lt;');
                const pathEsc = String(t.path).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                const active = normalizePathKey(currentProjectView) === normalizePathKey(t.path) ? ' active' : '';
                const modeHint = modeLabel(t.mode);
                html += '<button type="button" class="project-view-tab' + active +
                    '" onclick="setProjectView(\'' + pathEsc + '\')" title="' +
                    String(t.path).replace(/"/g, '&quot;') + ' Â· ' + modeHint + '">' + label + '</button>';
            });
            tabs.innerHTML = html;
        }

        function setProjectView(key) {
            currentProjectView = key || 'all';
            currentFolderFilter = 'all';
            renderProjectViewTabs();
            renderFolderBreakdown();
            renderTable();
            updateStats();
        }
        
        // Tasas de lineas por hora segun complejidad
        const RATES = {
            'alta': 25,
            'media-alta': 35,
            'media': 45,
            'baja': 60
        };
        
        /** Tasa (l/h) segun complejidad; evita NaN si falta clave. */
        function tasaComplejidad(complexity) {
            const r = RATES[complexity];
            return r != null ? r : RATES.alta;
        }
        
        /** Horas coherentes con lines + complexity (misma formula que los totales del dashboard). */
        function horasArchivoStr(file) {
            return (file.lines / tasaComplejidad(file.complexity)).toFixed(2);
        }

        function horasDeArchivoNum(file) {
            const stored = Number(file && file.hours);
            if (file && file.hours != null && file.hours !== '' && !isNaN(stored) && stored >= 0) {
                const fromLines = (Number(file.lines) || 0) / tasaComplejidad((file && file.complexity) || 'media');
                if (stored > 0 || fromLines === 0) return stored;
            }
            return (Number(file && file.lines) || 0) / tasaComplejidad((file && file.complexity) || 'media');
        }

        function formatHoursDeltaHtml(delta) {
            if (delta == null || Number.isNaN(Number(delta))) {
                return '<span class="hours-delta-cell delta-na">\u2014</span>';
            }
            const d = Number(delta);
            if (Math.abs(d) < 0.005) {
                return '<span class="hours-delta-cell delta-zero">0.00 h</span>';
            }
            const cls = d > 0 ? 'delta-up' : 'delta-down';
            return '<span class="hours-delta-cell ' + cls + '">' + (d > 0 ? '+' : '') + d.toFixed(2) + ' h</span>';
        }

        function hasHoursCompare() {
            return baselineHasFileDetail();
        }

        function syncHoursDeltaColumns() {
            const show = hasHoursCompare();
            document.querySelectorAll('.hours-delta-col').forEach(el => {
                el.style.display = show ? '' : 'none';
            });
            return show;
        }

        function aggregateBaselineHours() {
            const exact = {};
            const global = {};
            if (!baselineHasFileDetail()) return { exact: exact, global: global };
            clientBaseline.files.forEach(f => {
                if (!fileHasLines(f)) return;
                const h = horasDeArchivoNum(f);
                const folder = f.folder || 'ROOT';
                exact[folder] = (exact[folder] || 0) + h;
                const g = globalFolderOf(folder);
                global[g] = (global[g] || 0) + h;
            });
            return { exact: exact, global: global };
        }

        function hoursScanForRefParts(canon, scanByBase) {
            const list = scanByBase[canon.base] || [];
            if (!list.length) return null;
            const same = list.find(pf => canonicalFileParts(pf.folder, pf.name).folder === canon.folder);
            return horasDeArchivoNum(same || list[0]);
        }
        
        function etiquetaComplejidad(key) {
            const m = { alta: 'Alta', 'media-alta': 'M.-Alta', media: 'Media', baja: 'Baja' };
            return m[key] || key || '\u2014';
        }
        
        function computeMetrics(files) {
            let totalLines = 0, totalHours = 0;
            files.forEach(f => {
                totalLines += f.lines;
                totalHours += f.lines / tasaComplejidad(f.complexity);
            });
            return { totalLines, totalHours, fileCount: files.length };
        }
        
        function aggregateByFolder(files) {
            const map = {};
            files.forEach(f => {
                if (!fileHasLines(f)) return;
                const k = f.folder || 'ROOT';
                if (!map[k]) map[k] = { lines: 0, files: 0, hours: 0 };
                map[k].lines += f.lines;
                map[k].files += 1;
                map[k].hours += f.lines / tasaComplejidad(f.complexity);
            });
            const totalLines = files.reduce((s, f) => s + f.lines, 0);
            return Object.keys(map).map(folder => ({
                folder,
                lines: map[folder].lines,
                files: map[folder].files,
                hours: map[folder].hours,
                pct: totalLines ? (map[folder].lines / totalLines * 100) : 0
            })).sort((a, b) => b.lines - a.lines);
        }

        function globalFolderOf(folder) {
            const parts = normalizeFolderParts(folder);
            return parts.length ? parts[0] : 'ROOT';
        }

        function subfolderLabel(folder, globalKey) {
            const f = String(folder || 'ROOT');
            if (f === globalKey || f === 'ROOT') return '(ra\u00edz del m\u00f3dulo)';
            if (globalKey && f.indexOf(globalKey + '/') === 0) return f.slice(globalKey.length + 1);
            return f;
        }

        function groupRowsByGlobal(rows) {
            const groups = {};
            (rows || []).forEach(r => {
                const g = globalFolderOf(r.folder);
                if (!groups[g]) {
                    groups[g] = { global: g, lines: 0, files: 0, hours: 0, pct: 0, children: [] };
                }
                groups[g].children.push(r);
                groups[g].lines += r.lines;
                groups[g].files += r.files;
                groups[g].hours += r.hours;
                groups[g].pct += r.pct;
            });
            return Object.keys(groups)
                .sort((a, b) => groups[b].lines - groups[a].lines || a.localeCompare(b, 'es'))
                .map(k => {
                    groups[k].children.sort((a, b) => b.lines - a.lines || a.folder.localeCompare(b.folder, 'es'));
                    return groups[k];
                });
        }
        
        function formatDeltaNum(n, decimals, suffix) {
            if (n === 0 || (typeof n === 'number' && Math.abs(n) < 1e-9)) return '0' + (suffix || '');
            const sign = n > 0 ? '+' : '';
            const d = decimals != null ? n.toFixed(decimals) : n.toLocaleString('es-ES');
            return sign + d + (suffix || '');
        }
        
        function escapeHtmlPdf(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }
        
        function syncHoursComparePdfOption() {
            const chk = document.getElementById('chkPdfIncludeHoursCompare');
            const hint = document.getElementById('chkPdfIncludeHoursCompareHint');
            if (!chk) return;
            const ok = !!(clientBaseline && getActiveFiles().length);
            chk.disabled = !ok;
            if (!ok) chk.checked = false;
            if (hint) {
                hint.style.display = ok ? 'none' : 'block';
            }
        }
        
        function parseIntlInteger(raw) {
            if (raw == null) return null;
            let s = String(raw).replace(/\s/g, '').trim();
            if (!s) return null;
            if (s.indexOf(',') >= 0 && s.indexOf('.') >= 0) {
                if (s.lastIndexOf(',') > s.lastIndexOf('.')) s = s.replace(/\./g, '').replace(',', '.');
                else s = s.replace(/,/g, '');
            } else if (s.indexOf('.') >= 0) {
                const parts = s.split('.');
                if (parts.length > 2 || (parts[1] && parts[1].length === 3)) s = s.replace(/\./g, '');
            }
            const n = parseInt(s.replace(/[^\d]/g, ''), 10);
            return isNaN(n) ? null : n;
        }
        
        function parseIntlFloat(raw) {
            if (raw == null) return null;
            let s = String(raw).replace(/\s/g, '').trim();
            if (!s) return null;
            if (s.indexOf(',') >= 0 && s.indexOf('.') >= 0) {
                if (s.lastIndexOf(',') > s.lastIndexOf('.')) s = s.replace(/\./g, '').replace(',', '.');
                else s = s.replace(/,/g, '');
            } else if (s.indexOf(',') >= 0) s = s.replace(',', '.');
            const n = parseFloat(s);
            return isNaN(n) ? null : n;
        }
        
        /** Metricas desde texto de PDF (marca DASHCMP o tablas METRICAS PRINCIPALES). */
        function parseBaselineFromPdfText(text) {
            const dash = text.match(/DASHCMP\|LINEAS:(\d+)\|HORAS:([\d.,]+)\|ARCH:(\d+)\|/i);
            if (dash) {
                return {
                    totalLines: parseInt(dash[1], 10),
                    totalHours: parseIntlFloat(dash[2]),
                    fileCount: parseInt(dash[3], 10),
                    sourceDetail: 'Marca embebida en PDF (dashboard reciente)'
                };
            }
            const flat = text.replace(/\s+/g, ' ');
            let totalLines = null;
            let totalHours = null;
            let fileCount = null;
            let mL = flat.match(/Total\s*L[i\u00edI][neas]*\s*\D{0,40}?([\d][\d.,\s]*\d|\d+)/i);
            if (!mL) mL = flat.match(/Total\s*Lineas\D{0,40}?([\d][\d.,\s]*\d|\d+)/i);
            if (mL) totalLines = parseIntlInteger(mL[1]);
            let mH = flat.match(/Total\s*Horas\D{0,40}?([\d][\d.,]*[\d]|\d+)\s*h/i);
            if (mH) totalHours = parseIntlFloat(mH[1]);
            const metricsBlock = flat.match(/METRICAS\s*PRINCIPALES[\s\S]{0,2200}?(?=POR\s|HISTORIAL|FUNCIONALIDADES|DETALLE|$)/i);
            if (metricsBlock) {
                const mArch = metricsBlock[0].match(/Archivos\D{0,24}(\d{1,5})/i);
                if (mArch) fileCount = parseInt(mArch[1], 10);
            }
            if (totalLines == null && totalHours == null) return null;
            return {
                totalLines: totalLines != null ? totalLines : 0,
                totalHours: totalHours != null ? totalHours : 0,
                fileCount: fileCount != null ? fileCount : 0,
                sourceDetail: 'Texto del PDF (revisar si los numeros cuadran)'
            };
        }
        
        const PDF_PATH_RE = /\b([A-Za-z][A-Za-z0-9_]*\s*\/\s*[A-Za-z0-9_.-]+\.(?:php|js|html|htm|css|sql))\b/g;

        function normalizePdfExtractedText(text) {
            let t = String(text || '');
            t = t.replace(/\u00a0/g, ' ');
            t = t.replace(/([A-Za-z0-9_])[ \t]*[\r\n]+[ \t]*\//g, '$1/');
            t = t.replace(/\/[ \t]*[\r\n]+[ \t]*([A-Za-z0-9_])/g, '/$1');
            t = t.replace(/([A-Za-z0-9_])\s*\/\s*([A-Za-z0-9_])/g, '$1/$2');
            // man_ant_1 . 0 . php
            t = t.replace(/(\d)\s*\.\s*(\d)\s*\.\s*(php|js|html|htm|css|sql)\b/gi, '$1.$2.$3');
            t = t.replace(/([A-Za-z0-9_])\s*\.\s*(php|js|html|htm|css|sql)\b/gi, '$1.$2');
            t = t.replace(/([A-Za-z0-9_])\s*\.\s*(\d)/g, '$1.$2');
            t = t.replace(/(\d)\s*\.\s*(\d{3})\b/g, '$1.$2');
            t = t.replace(/(\d)\s*\.\s*(\d{1,2})\s*h\b/gi, '$1.$2 h');
            return t;
        }

        /** Nombres de archivo mencionados en el PDF (aunque falle la fila completa). */
        function extractPdfBasenameSet(text) {
            const t = normalizePdfExtractedText(text);
            const set = {};
            let m;
            const rePath = /\b([A-Za-z][A-Za-z0-9_]*)\s*\/\s*([A-Za-z0-9_.-]+\.(?:php|js|html|htm|css|sql))\b/gi;
            while ((m = rePath.exec(t)) !== null) {
                set[String(m[2]).replace(/\s+/g, '').toLowerCase()] = {
                    folder: m[1],
                    name: m[2].replace(/\s+/g, '')
                };
            }
            const reBare = /\b([A-Za-z][A-Za-z0-9_.-]*\.(?:php|js|html|htm|css|sql))\b/gi;
            while ((m = reBare.exec(t)) !== null) {
                const base = String(m[1]).replace(/\s+/g, '').toLowerCase();
                if (!set[base]) set[base] = { folder: 'ROOT', name: m[1].replace(/\s+/g, '') };
            }
            return set;
        }

        /**
         * Si el PDF menciona un archivo del escaneo pero no se pudo armar la fila,
         * lo agrega para que no salga como "Nuevo".
         */
        function mergeScanFilesMentionedInPdf(rows, pdfText, scanFiles) {
            const basenameSet = extractPdfBasenameSet(pdfText);
            const existing = {};
            (rows || []).forEach(r => {
                const c = canonicalFileParts(r.folder, r.name);
                if (c.base) existing[c.base] = true;
            });
            const out = (rows || []).slice();
            const textNorm = normalizePdfExtractedText(pdfText);
            (scanFiles || []).forEach(sf => {
                const c = canonicalFileParts(sf.folder, sf.name);
                if (!c.base || existing[c.base]) return;
                if (!basenameSet[c.base]) return;
                let lines = Number(sf.lines) || 0;
                const esc = c.name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const re = new RegExp(esc + '[\\s\\S]{0,80}?', 'i');
                const chunkM = textNorm.match(re);
                if (chunkM) {
                    const after = textNorm.slice(chunkM.index + chunkM[0].length, chunkM.index + chunkM[0].length + 60);
                    const lm = after.match(/((?:\d{1,3}(?:[.\s]\d{3})+)|\d{2,6})/);
                    if (lm) {
                        const parsed = parseIntlInteger(lm[1]);
                        if (parsed != null && parsed >= 1) lines = parsed;
                    }
                }
                const meta = basenameSet[c.base];
                const folder = (meta && meta.folder && meta.folder !== 'ROOT')
                    ? meta.folder
                    : (c.parts.length ? c.parts[c.parts.length - 1] : 'ROOT');
                out.push({
                    name: c.name,
                    folder: folder,
                    type: sf.type || 'php',
                    lines: lines,
                    complexity: sf.complexity || 'media',
                    suggestedComplexity: sf.complexity || 'media',
                    fromPdfMention: true
                });
                existing[c.base] = true;
            });
            return { rows: out, pdfBasenames: Object.keys(basenameSet) };
        }
        
        function normalizePdfComplexityTail(tail) {
            if (!tail) return 'media';
            const u = tail.toUpperCase().replace(/\s+/g, ' ');
            if (u.includes('MEDIA-ALTA') || u.includes('M.-ALTA')) return 'media-alta';
            if (/\bMEDIA\b/.test(u) && !u.includes('MEDIA-ALTA')) return 'media';
            if (u.includes('BAJA')) return 'baja';
            if (u.includes('ALTA')) return 'alta';
            return 'media';
        }
        
        function extToFileType(ext) {
            const e = (ext || '').toLowerCase();
            if (e === 'htm') return 'html';
            if (e === 'php' || e === 'js' || e === 'html' || e === 'css' || e === 'sql') return e;
            return 'php';
        }
        
        /**
         * Extrae filas tipo FRONT/archivo.php lineas horas ... complejidad desde texto del PDF.
         */
        function parsePdfFileTable(text) {
            text = normalizePdfExtractedText(text);
            const rows = [];
            const seen = new Set();
            let m;
            const re = /\b([A-Za-z][A-Za-z0-9_]*)\s*\/\s*([A-Za-z0-9_.-]+\.(?:php|js|html|htm|css|sql))\b/gi;
            const matches = [];
            while ((m = re.exec(text)) !== null) {
                matches.push({
                    path: (m[1] + '/' + m[2]).replace(/\s+/g, ''),
                    end: m.index + m[0].length,
                    index: m.index
                });
            }
            for (let i = 0; i < matches.length; i++) {
                const path = matches[i].path;
                const end = matches[i].end;
                const nextIdx = i + 1 < matches.length ? matches[i + 1].index : text.length;
                const chunk = text.slice(end, Math.min(end + 220, nextIdx));
                // No exigir que el numero este pegado al path (PDF.js a veces mete basura)
                const linesM = chunk.match(/((?:\d{1,3}(?:[.\u00a0\s]\d{3})+|\d{2,6}))/);
                if (!linesM) continue;
                const lines = parseIntlInteger(linesM[1]);
                if (lines == null || lines < 1) continue;
                let rest = chunk.slice(chunk.indexOf(linesM[0]) + linesM[0].length).trim();
                let hoursPdf = null;
                const hoursM = rest.match(/^([\d]+[.,]\d+|\d+)\s*h?/i);
                if (hoursM) {
                    hoursPdf = parseIntlFloat(hoursM[1]);
                    rest = rest.slice(hoursM[0].length).trim();
                }
                const complexity = normalizePdfComplexityTail(rest);
                const canon = canonicalFileParts('', path);
                const key = (canon.folder + '/' + canon.name).toLowerCase();
                if (seen.has(key)) continue;
                seen.add(key);
                rows.push({
                    name: canon.name,
                    folder: canon.folder,
                    type: extToFileType((canon.name.match(/\.([a-z]+)$/i) || [, 'php'])[1]),
                    lines,
                    complexity,
                    suggestedComplexity: complexity,
                    hours: hoursPdf != null ? hoursPdf.toFixed(2) : (lines / tasaComplejidad(complexity)).toFixed(2)
                });
            }
            if (rows.length >= 1) return rows;
            return parsePdfFileTableByLines(text);
        }
        
        function tryParseRowChunk(chunk) {
            const linesM = chunk.match(/^\s*((?:\d{1,3}(?:[.\u00a0\s]\d{3})+|\d{1,3}(?:\s\d{3})+|\d+))/);
            if (!linesM) return null;
            const lines = parseIntlInteger(linesM[1]);
            if (lines == null || lines < 1) return null;
            let rest = chunk.slice(linesM[0].length).trim();
            const hoursM = rest.match(/^([\d]+[.,]\d+|\d+)\s*h/i);
            if (!hoursM) return null;
            const hoursPdf = parseIntlFloat(hoursM[1]);
            rest = rest.slice(hoursM[0].length).trim();
            const complexity = normalizePdfComplexityTail(rest);
            return { lines, complexity, hoursPdf };
        }
        
        function parsePdfFileTableByLines(text) {
            text = normalizePdfExtractedText(text);
            const rows = [];
            const seen = new Set();
            const pathPatLine = /\b([A-Za-z][A-Za-z0-9_]*)\s*\/\s*([A-Za-z0-9_.-]+\.(?:php|js|html|htm|css|sql))\b/i;
            for (const rawLine of text.split(/\n/)) {
                const line = rawLine.trim();
                if (!line || line.length < 8) continue;
                const pm = line.match(pathPatLine);
                if (!pm) continue;
                const path = (pm[1] + '/' + pm[2]).replace(/\s+/g, '');
                const idx = line.search(pathPatLine);
                const after = line.slice(idx + pm[0].length).trim();
                const linesM = after.match(/^((?:\d{1,3}(?:[.\u00a0\s]\d{3})+|\d{1,3}(?:\s\d{3})+|\d+))/);
                if (!linesM) continue;
                const lines = parseIntlInteger(linesM[1]);
                if (lines == null || lines < 1) continue;
                let rest = after.slice(linesM[0].length).trim();
                let hoursPdf = null;
                const hoursM = rest.match(/^([\d]+[.,]\d+|\d+)\s*h?/i);
                if (hoursM) {
                    hoursPdf = parseIntlFloat(hoursM[1]);
                    rest = rest.slice(hoursM[0].length).trim();
                }
                const complexity = normalizePdfComplexityTail(rest);
                const canon = canonicalFileParts('', path);
                const key = (canon.folder + '/' + canon.name).toLowerCase();
                if (seen.has(key)) continue;
                seen.add(key);
                rows.push({
                    name: canon.name,
                    folder: canon.folder,
                    type: extToFileType((canon.name.match(/\.([a-z]+)$/i) || [, 'php'])[1]),
                    lines,
                    complexity,
                    suggestedComplexity: complexity,
                    hours: hoursPdf != null ? hoursPdf.toFixed(2) : (lines / tasaComplejidad(complexity)).toFixed(2)
                });
            }
            return rows;
        }
        
        function parseProjectNameFromPdf(text) {
            const flat = text.replace(/\s+/g, ' ');
            const m = flat.match(/Proyecto\s+([A-Za-z0-9_\-\u00f1\u00d1]+(?:\s+[A-Za-z0-9_\-\u00f1\u00d1]+){0,3}?)(?:\s+Fecha|\s+INFORME|$)/i);
            if (m) return m[1].trim();
            const m2 = flat.match(/Informe[_\s]+([A-Za-z0-9_\-]+)/i);
            return m2 ? m2[1].trim() : '';
        }
        
        function parseBaselineFromJsonText(jsonStr) {
            let o;
            try {
                o = JSON.parse(jsonStr);
            } catch (e) {
                return null;
            }
            const b = o.baseline || o;
            if (b.totalLines == null && b.totalHours == null) return null;
            let files = null;
            if (Array.isArray(b.files) && b.files.length) {
                files = b.files.map(f => ({
                    name: f.name || '',
                    folder: f.folder || 'ROOT',
                    type: f.type || 'php',
                    lines: Number(f.lines) || 0,
                    complexity: f.complexity || 'media',
                    hours: f.hours != null ? String(f.hours) : null
                })).filter(f => f.name);
            }
            return {
                totalLines: Number(b.totalLines) || 0,
                totalHours: Number(b.totalHours) || 0,
                fileCount: Number(b.fileCount) || (files ? files.length : 0),
                projectName: b.projectName || '',
                exportedAt: b.exportedAt || '',
                sourceDetail: files
                    ? ('JSON con detalle de ' + files.length + ' archivos')
                    : 'Archivo JSON exportado (solo totales; reexporte para filtrar diffs)',
                files: files
            };
        }

        function serializeFilesForStore(files) {
            return (files || []).map(f => ({
                name: f.name,
                folder: f.folder || 'ROOT',
                type: f.type || 'php',
                lines: Number(f.lines) || 0,
                complexity: f.complexity || 'media',
                hours: Number(horasArchivoStr(f))
            }));
        }

        function baselineHasFileDetail() {
            return !!(clientBaseline && Array.isArray(clientBaseline.files) && clientBaseline.files.length);
        }

        function fileHasLines(file) {
            return (Number(file && file.lines) || 0) > 0;
        }

        function countBaselineMatches() {
            if (!baselineHasFileDetail() || !projectFiles.length) {
                return { matched: 0, total: projectFiles.length, refCount: baselineHasFileDetail() ? clientBaseline.files.length : 0 };
            }
            const idx = buildBaselineFileIndex();
            let matched = 0;
            projectFiles.forEach(pf => { if (findBaselineFile(pf, idx)) matched++; });
            return { matched: matched, total: projectFiles.length, refCount: clientBaseline.files.length };
        }

        async function persistScanFileLists(projectPath, files) {
            if (!projectPath || !files || !files.length) return;
            const projects = await loadSavedProjects();
            const prev = projects[projectPath] || {};
            const snapshot = {
                at: new Date().toISOString(),
                files: serializeFilesForStore(files)
            };
            const previousFiles = Array.isArray(prev.lastFiles) && prev.lastFiles.length
                ? prev.lastFiles
                : (Array.isArray(prev.previousFiles) ? prev.previousFiles : null);
            projects[projectPath] = {
                ...prev,
                previousFiles: previousFiles,
                lastFiles: snapshot.files,
                lastFilesAt: snapshot.at
            };
            await saveProjects(projects);
        }

        async function hasPreviousScanFiles() {
            if (!currentProject) return false;
            const projects = await loadSavedProjects();
            const prev = projects[currentProject] || {};
            return Array.isArray(prev.previousFiles) && prev.previousFiles.length > 0;
        }

        function applyBaselineFileList(files, sourceLabel, meta) {
            if (!clientBaseline) {
                showToast('Primero carga una referencia (JSON o PDF)', 'warning');
                return false;
            }
            if (!files || !files.length) {
                showToast('No se encontr\u00f3 listado de archivos en el archivo', 'error');
                return false;
            }
            clientBaseline.files = files.map(f => {
                const c = canonicalFileParts(f.folder, f.name);
                return {
                    name: c.name,
                    folder: c.folder,
                    type: f.type || 'php',
                    lines: Number(f.lines) || 0,
                    complexity: f.complexity || 'media',
                    hours: f.hours != null ? f.hours : null,
                    fromPdfMention: !!f.fromPdfMention
                };
            }).filter(f => f.name);
            if (meta && Array.isArray(meta.pdfBasenames)) {
                clientBaseline.pdfBasenames = meta.pdfBasenames;
            }
            clientBaseline.listSourceFileName = sourceLabel || clientBaseline.listSourceFileName || clientBaseline.fileName || '';
            clientBaseline.sourceDetail = (clientBaseline.sourceDetail || 'Referencia') +
                ' + listado (' + clientBaseline.files.length + ' archivos desde ' + sourceLabel + ')';
            persistClientBaseline();
            renderClientBaselinePanel();
            syncDiffFilterControl();
            let matched = 0;
            if (projectFiles.length) {
                const idx = buildBaselineFileIndex();
                projectFiles.forEach(pf => { if (findBaselineFile(pf, idx)) matched++; });
            }
            renderTable();
            updateStats();
            showToast('Listado: ' + clientBaseline.files.length + ' archivos | casados con escaneo: ' + matched + '/' + projectFiles.length);
            return true;
        }

        async function enriquecerReferenciaConListado(file) {
            if (!file) return;
            if (!clientBaseline) {
                showToast('Carga primero el JSON/PDF de referencia', 'warning');
                return;
            }
            const name = file.name || '';
            const low = name.toLowerCase();
            try {
                if (low.endsWith('.json')) {
                    const txt = await file.text();
                    const m = parseBaselineFromJsonText(txt);
                    if (!m || !m.files || !m.files.length) {
                        throw new Error('Ese JSON no trae listado de archivos. Use un JSON comparativa nuevo (bot\u00f3n JSON comparativa) o un PDF gerencial.');
                    }
                    applyBaselineFileList(m.files, name);
                } else {
                    const buf = await file.arrayBuffer();
                    const text = await extraerTextoPdf(buf);
                    let imported = parsePdfFileTable(text);
                    const merged = mergeScanFilesMentionedInPdf(imported, text, projectFiles);
                    imported = merged.rows;
                    if (!imported.length) {
                        throw new Error('No se pudo leer la tabla de archivos del PDF. Use un PDF gerencial de este dashboard.');
                    }
                    applyBaselineFileList(imported, name, { pdfBasenames: merged.pdfBasenames });
                }
            } catch (err) {
                showToast(err.message || String(err), 'error');
            }
        }

        async function usarListadoEscaneoAnterior() {
            if (!clientBaseline) {
                showToast('Carga primero una referencia', 'warning');
                return;
            }
            if (!currentProject) {
                showToast('Escanea el proyecto primero', 'warning');
                return;
            }
            const projects = await loadSavedProjects();
            const prev = projects[currentProject] || {};
            if (!Array.isArray(prev.previousFiles) || !prev.previousFiles.length) {
                showToast('No hay escaneo anterior guardado. Escanee otra vez (se guardar\u00e1 el listado) o adjunte un PDF/JSON con listado.', 'warning');
                return;
            }
            applyBaselineFileList(prev.previousFiles, 'escaneo anterior guardado');
        }

        async function updateBaselineEnrichButtons() {
            const btnEnrich = document.getElementById('btnEnrichBaselineFiles');
            const btnPrev = document.getElementById('btnUsePreviousFiles');
            const needFiles = !!(clientBaseline && !baselineHasFileDetail());
            if (btnEnrich) btnEnrich.style.display = needFiles ? '' : 'none';
            if (btnPrev) {
                const hasPrev = needFiles && await hasPreviousScanFiles();
                btnPrev.style.display = hasPrev ? '' : 'none';
            }
        }

        function normalizeFolderParts(folder) {
            return String(folder == null || folder === '' ? 'ROOT' : folder)
                .replace(/[\\ï¼âˆ•]/g, '/')
                .replace(/\s*\/\s*/g, '/')
                .replace(/^\/+|\/+$/g, '')
                .split('/')
                .map(p => p.trim())
                .filter(p => p && p.toUpperCase() !== 'ROOT');
        }

        /** Coincide carpeta exacta o cualquier descendiente (flujo incluye flujo/FRONT). */
        function folderMatchesFilter(folder, filter) {
            if (!filter || filter === 'all') return true;
            const f = String(folder == null || folder === '' ? 'ROOT' : folder).replace(/\\/g, '/');
            if (filter === 'ROOT') return f === 'ROOT';
            return f === filter || f.indexOf(filter + '/') === 0;
        }

        /** Carpetas hoja + ancestros (relavera, relavera/FRONT, ...). */
        function collectFolderFilterKeys(files) {
            const folders = {};
            (files || []).forEach(f => {
                const parts = normalizeFolderParts(f.folder);
                if (!parts.length) {
                    folders.ROOT = true;
                    return;
                }
                let acc = '';
                parts.forEach(p => {
                    acc = acc ? acc + '/' + p : p;
                    folders[acc] = true;
                });
            });
            return folders;
        }

        function normalizeFileName(name) {
            return String(name || '')
                .replace(/[\u200b-\u200d\ufeff]/g, '')
                .replace(/[\\ï¼âˆ•]/g, '/')
                .trim();
        }

        /** Separa carpeta/nombre aunque el path venga todo en "name" o con espacios. */
        function canonicalFileParts(folder, name) {
            let n = normalizeFileName(name);
            let fParts = normalizeFolderParts(folder);
            if (n.indexOf('/') >= 0) {
                const segs = n.split('/').map(s => s.trim()).filter(Boolean);
                n = segs.pop() || n;
                if (segs.length) fParts = segs;
            }
            return {
                name: n,
                folder: fParts.length ? fParts.join('/') : 'ROOT',
                parts: fParts,
                base: n.toLowerCase()
            };
        }

        function fileRefKey(folder, name) {
            const c = canonicalFileParts(folder, name);
            const f = c.parts.length ? c.parts.join('/') : 'ROOT';
            return (f + '/' + c.name).toLowerCase();
        }

        function buildBaselineFileIndex() {
            const byKey = {};
            const byName = {};
            const byBase = {};
            if (!baselineHasFileDetail()) return { byKey, byName, byBase };
            clientBaseline.files.forEach(raw => {
                const c = canonicalFileParts(raw.folder, raw.name);
                if (!c.name) return;
                const f = { ...raw, name: c.name, folder: c.folder, lines: Number(raw.lines) || 0 };
                // Todas las sufijos: relavera/FRONT y FRONT
                for (let i = 0; i <= c.parts.length; i++) {
                    const suffix = i < c.parts.length ? c.parts.slice(i).join('/') : 'ROOT';
                    byKey[(suffix + '/' + c.name).toLowerCase()] = f;
                }
                const nk = c.base;
                if (!byName[nk]) byName[nk] = [];
                byName[nk].push(f);
                if (!byBase[nk]) byBase[nk] = [];
                byBase[nk].push(f);
            });
            return { byKey, byName, byBase };
        }

        function findBaselineFile(file, index) {
            if (!baselineHasFileDetail()) return null;
            const cur = canonicalFileParts(file.folder, file.name);
            if (!cur.name) return null;
            const curLines = Number(file.lines) || 0;
            const curLeaf = cur.parts.length ? cur.parts[cur.parts.length - 1].toLowerCase() : '';

            const candidates = [];
            for (let i = 0; i < clientBaseline.files.length; i++) {
                const raw = clientBaseline.files[i];
                const b = canonicalFileParts(raw.folder, raw.name);
                if (!b.name) continue;
                if (b.base === cur.base) {
                    candidates.push({
                        raw: raw,
                        parts: b.parts,
                        lines: Number(raw.lines) || 0,
                        leaf: b.parts.length ? b.parts[b.parts.length - 1].toLowerCase() : ''
                    });
                }
            }

            if (candidates.length === 1) return candidates[0].raw;

            if (candidates.length > 1) {
                let pool = candidates;
                if (curLeaf) {
                    const sameLeaf = candidates.filter(c =>
                        c.leaf === curLeaf ||
                        cur.parts.join('/').toLowerCase().endsWith('/' + c.parts.join('/').toLowerCase()) ||
                        c.parts.join('/').toLowerCase() === curLeaf
                    );
                    if (sameLeaf.length) pool = sameLeaf;
                }
                const sameLines = pool.filter(c => c.lines === curLines);
                if (sameLines.length) return sameLines[0].raw;
                pool.sort((a, b) => Math.abs(a.lines - curLines) - Math.abs(b.lines - curLines));
                return pool[0].raw;
            }

            // Fallback: mismo numero de lineas unico en la referencia (nombres rotos del PDF)
            if (curLines >= 30) {
                const byLines = [];
                for (let i = 0; i < clientBaseline.files.length; i++) {
                    const raw = clientBaseline.files[i];
                    if ((Number(raw.lines) || 0) === curLines) byLines.push(raw);
                }
                if (byLines.length === 1) return byLines[0];
                if (byLines.length > 1 && curLeaf) {
                    const leafHits = byLines.filter(raw => {
                        const b = canonicalFileParts(raw.folder, raw.name);
                        const leaf = b.parts.length ? b.parts[b.parts.length - 1].toLowerCase() : '';
                        return leaf === curLeaf;
                    });
                    if (leafHits.length === 1) return leafHits[0];
                }
            }

            if (!index) index = buildBaselineFileIndex();
            for (let i = 0; i <= cur.parts.length; i++) {
                const suffix = i < cur.parts.length ? cur.parts.slice(i).join('/') : 'ROOT';
                const hit = index.byKey[(suffix + '/' + cur.name).toLowerCase()];
                if (hit) return hit;
            }

            // Si el PDF menciona el nombre del archivo, no marcarlo como Nuevo
            const bn = clientBaseline.pdfBasenames;
            if (bn && bn.length) {
                for (let i = 0; i < bn.length; i++) {
                    if (String(bn[i]).toLowerCase() === cur.base) {
                        return {
                            name: cur.name,
                            folder: cur.folder,
                            lines: curLines,
                            _fromPdfBasename: true
                        };
                    }
                }
            }
            return null;
        }

        let refPreviewGlobalFilter = 'all';

        function setRefPreviewGlobalFilter(value) {
            refPreviewGlobalFilter = value || 'all';
            renderBaselineFilesPreview();
        }

        function renderBaselineFilesPreview() {
            const box = document.getElementById('baselineFilesPreview');
            if (!box) return;
            if (!clientBaseline) {
                box.style.display = 'none';
                box.innerHTML = '';
                refPreviewGlobalFilter = 'all';
                return;
            }
            const esc = (s) => String(s == null ? '' : s).replace(/</g, '&lt;');
            const totalsName = clientBaseline.fileName || '(sin nombre)';
            const listName = clientBaseline.listSourceFileName || clientBaseline.fileName || '';
            const src = (clientBaseline.source === 'pdf') ? 'PDF' : 'JSON';
            let html = '<div class="ref-preview-meta">' +
                '<div class="ref-preview-file"><i class="fas fa-file-code"></i> <strong>Referencia</strong> ' +
                '<code>' + esc(totalsName) + '</code> <span class="ref-src">' + src + '</span></div>';
            if (clientBaseline.listSourceFileName && clientBaseline.listSourceFileName !== totalsName) {
                html += '<div class="ref-preview-file"><i class="fas fa-list"></i> <strong>Listado</strong> <code>' + esc(listName) + '</code></div>';
            }
            html += '</div>';

            if (!baselineHasFileDetail()) {
                html += '<div class="ref-preview-warn">Este archivo no trae listado archivo-por-archivo. Use &laquo;A&ntilde;adir listado&raquo; con el PDF gerencial.</div>';
                box.innerHTML = html;
                box.style.display = 'block';
                return;
            }

            const groups = {};
            let listedRef = 0;
            let hoursRefTotal = 0;
            let linesRefTotal = 0;
            clientBaseline.files.forEach(f => {
                if (!fileHasLines(f)) return;
                listedRef++;
                const c = canonicalFileParts(f.folder, f.name);
                const g = globalFolderOf(c.folder);
                if (!groups[g]) groups[g] = { files: [], lines: 0, hoursRef: 0 };
                const hoursRef = horasDeArchivoNum(f);
                const lines = Number(f.lines) || 0;
                groups[g].files.push({ c: c, lines: lines, hoursRef: hoursRef });
                groups[g].lines += lines;
                groups[g].hoursRef += hoursRef;
                linesRefTotal += lines;
                hoursRefTotal += hoursRef;
            });
            Object.keys(groups).forEach(g => {
                groups[g].files.sort((a, b) =>
                    a.c.folder.localeCompare(b.c.folder, 'es') || a.c.name.localeCompare(b.c.name, 'es')
                );
            });
            const globals = Object.keys(groups).sort((a, b) => groups[b].lines - groups[a].lines || a.localeCompare(b, 'es'));
            if (refPreviewGlobalFilter !== 'all' && !groups[refPreviewGlobalFilter]) {
                refPreviewGlobalFilter = 'all';
            }
            const visible = refPreviewGlobalFilter === 'all' ? globals : [refPreviewGlobalFilter];

            html += '<div class="ref-preview-stats ok">' +
                '<i class="fas fa-file-alt"></i> Resumen del comparativo: <strong>' + listedRef + '</strong> archivos' +
                ' &middot; <strong>' + linesRefTotal.toLocaleString() + '</strong> l&iacute;neas' +
                ' &middot; <strong>' + hoursRefTotal.toFixed(2) + ' h</strong>' +
                '</div>';
            html += '<div class="ref-preview-chips">';
            html += '<button type="button" class="ref-chip' + (refPreviewGlobalFilter === 'all' ? ' active' : '') + '" onclick="setRefPreviewGlobalFilter(\'all\')">Todos</button>';
            globals.forEach(g => {
                const n = groups[g].files.length;
                html += '<button type="button" class="ref-chip' + (refPreviewGlobalFilter === g ? ' active' : '') + '" onclick="setRefPreviewGlobalFilter(' + JSON.stringify(g) + ')">' +
                    esc(g) + ' <span>' + n + '</span></button>';
            });
            html += '</div>';

            html += '<div class="ref-preview-groups">';
            visible.forEach(g => {
                const grp = groups[g];
                html += '<details class="ref-group">' +
                    '<summary>' +
                    '<span class="ref-group-name"><i class="fas fa-folder"></i> ' + esc(g) + '</span>' +
                    '<span class="ref-group-meta">' + grp.files.length + ' arch. &middot; ' +
                    grp.lines.toLocaleString() + ' l&iacute;n. &middot; ' + grp.hoursRef.toFixed(2) + ' h</span>' +
                    '<span class="ref-group-toggle" aria-hidden="true">' +
                    '<span class="ref-toggle-closed"><i class="fas fa-chevron-down"></i> Mostrar</span>' +
                    '<span class="ref-toggle-open"><i class="fas fa-chevron-up"></i> Ocultar</span>' +
                    '</span></summary>' +
                    '<div class="ref-group-table-wrap"><table class="ref-files-table"><thead><tr>' +
                    '<th>Subcarpeta</th><th>Archivo</th><th class="num">L&iacute;neas</th><th class="num">Horas</th>' +
                    '</tr></thead><tbody>';
                grp.files.forEach(item => {
                    html += '<tr>' +
                        '<td class="ref-sub">' + esc(subfolderLabel(item.c.folder, g)) + '</td>' +
                        '<td><strong>' + esc(item.c.name) + '</strong></td>' +
                        '<td class="num">' + item.lines.toLocaleString() + '</td>' +
                        '<td class="num">' + item.hoursRef.toFixed(2) + '</td></tr>';
                });
                html += '</tbody></table></div></details>';
            });
            html += '</div>';
            html += '<p class="ref-preview-foot">Inventario del JSON/PDF de referencia. La diferencia de horas vs el escaneo actual se muestra en &laquo;Por carpeta / m&oacute;dulo&raquo; y en la tabla de archivos.</p>';
            box.innerHTML = html;
            box.style.display = 'block';
        }

        /** Estado vs referencia: nuevo | modificado | aumentado | igual | null */
        function getFileDiffInfo(file, index) {
            if (!baselineHasFileDetail()) return null;
            const ref = findBaselineFile(file, index || buildBaselineFileIndex());
            if (!ref) {
                return { status: 'nuevo', deltaLines: file.lines, refLines: 0 };
            }
            const delta = (Number(file.lines) || 0) - (Number(ref.lines) || 0);
            if (delta === 0) {
                return { status: 'igual', deltaLines: 0, refLines: ref.lines };
            }
            return {
                status: delta > 0 ? 'aumentado' : 'modificado',
                deltaLines: delta,
                refLines: ref.lines
            };
        }

        function syncDiffFilterControl() {
            const sel = document.getElementById('diffFilter');
            if (!sel) return;
            const hasScan = projectFiles.length > 0;
            const hasRef = !!clientBaseline;
            const hasFiles = baselineHasFileDetail();
            // Habilitado si hay escaneo + referencia; si faltan archivos, al elegir filtro pedir&aacute; listado
            sel.disabled = !(hasScan && hasRef);
            if (!hasRef || !hasScan) {
                currentDiffFilter = 'all';
                sel.value = 'all';
            } else if (!hasFiles && currentDiffFilter !== 'all') {
                currentDiffFilter = 'all';
                sel.value = 'all';
            } else {
                sel.value = currentDiffFilter || 'all';
            }
            updateBaselineEnrichButtons();
        }

        function fileMatchesDiffFilter(file, index) {
            if (currentDiffFilter === 'all' || !currentDiffFilter) return true;
            if (!baselineHasFileDetail()) return true;
            const info = getFileDiffInfo(file, index);
            if (!info) return true;
            if (currentDiffFilter === 'changed') {
                return info.status === 'nuevo' || info.status === 'modificado' || info.status === 'aumentado';
            }
            if (currentDiffFilter === 'added') return info.status === 'nuevo';
            if (currentDiffFilter === 'modified') {
                return info.status === 'modificado' || info.status === 'aumentado';
            }
            if (currentDiffFilter === 'increased') {
                return info.status === 'nuevo' || info.status === 'aumentado';
            }
            return true;
        }
        
        function persistClientBaseline() {
            try {
                if (clientBaseline) sessionStorage.setItem(BASELINE_SESSION_KEY, JSON.stringify(clientBaseline));
                else sessionStorage.removeItem(BASELINE_SESSION_KEY);
            } catch (e) { /* ignore */ }
        }
        
        function loadClientBaselineFromSession() {
            try {
                const s = sessionStorage.getItem(BASELINE_SESSION_KEY);
                if (!s) return;
                clientBaseline = JSON.parse(s);
                if (clientBaseline && Array.isArray(clientBaseline.files)) {
                    clientBaseline.files = clientBaseline.files.map(f => {
                        const c = canonicalFileParts(f.folder, f.name);
                        return {
                            ...f,
                            name: c.name,
                            folder: c.folder,
                            lines: Number(f.lines) || 0
                        };
                    }).filter(f => f.name);
                }
            } catch (e) { clientBaseline = null; }
        }
        
        async function extraerTextoPdf(arrayBuffer) {
            if (typeof pdfjsLib === 'undefined') throw new Error('pdf.js no cargado');
            const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
            let full = '';
            for (let i = 1; i <= pdf.numPages; i++) {
                const page = await pdf.getPage(i);
                const content = await page.getTextContent();
                let lastY = null;
                for (let j = 0; j < content.items.length; j++) {
                    const it = content.items[j];
                    if (!it || it.str === undefined) continue;
                    const tr = it.transform;
                    const ty = tr && tr.length >= 6 ? tr[5] : null;
                    if (lastY != null && ty != null && Math.abs(ty - lastY) > 2.5) {
                        full += '\n';
                    }
                    lastY = ty;
                    full += it.str;
                    const w = it.width != null ? it.width : (it.str.length * 4);
                    if (w > 2 && it.str.length > 0 && !/\s$/.test(it.str)) full += ' ';
                }
                full += '\n\n';
            }
            return full;
        }
        
        async function procesarArchivoReferenciaCliente(file) {
            if (!file) return;
            const name = file.name || '';
            const low = name.toLowerCase();
            const stEl = document.getElementById('clientBaselineStatus');
            clientBaselineLoadError = null;
            if (stEl) stEl.innerHTML = '<span style="color:var(--primary);"><i class="fas fa-spinner fa-spin"></i> Leyendo archivo...</span>';
            let pdfImportedCount = 0;
            try {
                if (low.endsWith('.json')) {
                    const txt = await file.text();
                    const m = parseBaselineFromJsonText(txt);
                    if (!m) throw new Error('JSON invalido o sin totalLines/totalHours');
                    clientBaseline = {
                        source: 'json',
                        fileName: name,
                        listSourceFileName: (m.files && m.files.length) ? name : '',
                        loadedAt: new Date().toISOString(),
                        ...m
                    };
                    if (m.files && m.files.length) {
                        applyBaselineFileList(m.files, name);
                        return;
                    }
                } else {
                    const buf = await file.arrayBuffer();
                    const text = await extraerTextoPdf(buf);
                    let imported = parsePdfFileTable(text);
                    const merged = mergeScanFilesMentionedInPdf(imported, text, projectFiles);
                    imported = merged.rows;
                    let m = parseBaselineFromPdfText(text);
                    if (imported.length >= 1 && projectFiles.length > 0) {
                        const metricsFromFiles = computeMetrics(imported.map(f => ({
                            ...f,
                            complexity: f.complexity || 'media'
                        })));
                        // Conservar totales del JSON previo si ya habia referencia solo-totales
                        const keepTotals = clientBaseline && !baselineHasFileDetail();
                        clientBaseline = {
                            source: keepTotals ? (clientBaseline.source || 'pdf') : 'pdf',
                            fileName: keepTotals ? (clientBaseline.fileName || name) : name,
                            loadedAt: new Date().toISOString(),
                            totalLines: keepTotals && clientBaseline.totalLines != null
                                ? Number(clientBaseline.totalLines)
                                : ((m && m.totalLines != null) ? Number(m.totalLines) : metricsFromFiles.totalLines),
                            totalHours: keepTotals && clientBaseline.totalHours != null
                                ? Number(clientBaseline.totalHours)
                                : ((m && m.totalHours != null) ? Number(m.totalHours) : metricsFromFiles.totalHours),
                            fileCount: keepTotals && clientBaseline.fileCount
                                ? clientBaseline.fileCount
                                : imported.length,
                            sourceDetail: (keepTotals ? 'Totales JSON + ' : '') + 'PDF con ' + imported.length + ' archivos',
                            files: imported,
                            pdfBasenames: merged.pdfBasenames
                        };
                        applyBaselineFileList(imported, name, { pdfBasenames: merged.pdfBasenames });
                        return;
                    } else if (imported.length >= 1) {
                        pdfImportedCount = imported.length;
                        projectFiles = imported.map(f => ({
                            ...f,
                            hours: horasArchivoStr(f)
                        }));
                        const metricsImp = computeMetrics(projectFiles);
                        let guessedPath = parseProjectNameFromPdf(text);
                        if (guessedPath) guessedPath = guessedPath.split(/\s+/)[0];
                        if (guessedPath) {
                            const base = 'C:\\xampp\\htdocs\\' + guessedPath.replace(/[^\w\-]/g, '_');
                            currentProject = base;
                            document.getElementById('projectPath').value = base;
                        }
                        document.getElementById('projectName').textContent = guessedPath
                            ? 'Proyecto ' + guessedPath
                            : document.getElementById('projectName').textContent;
                        document.getElementById('projectStatus').textContent = 'Importado desde PDF';
                        document.getElementById('btnSave').disabled = false;
                        document.getElementById('btnPdf').disabled = false;
                        const btnBl = document.getElementById('btnExportBaseline');
                        if (btnBl) btnBl.disabled = false;
                        const analyticsEl = document.getElementById('analyticsSection');
                        if (analyticsEl) analyticsEl.style.display = 'block';
                        clientBaseline = {
                            source: 'pdf',
                            fileName: name,
                            listSourceFileName: name,
                            loadedAt: new Date().toISOString(),
                            totalLines: metricsImp.totalLines,
                            totalHours: metricsImp.totalHours,
                            fileCount: metricsImp.fileCount,
                            sourceDetail: 'Listado importado (' + imported.length + ' archivos desde tabla del PDF)',
                            files: imported
                        };
                        renderFolderBreakdown();
                        renderTable();
                    } else if (m) {
                        clientBaseline = {
                            source: 'pdf',
                            fileName: name,
                            loadedAt: new Date().toISOString(),
                            ...m
                        };
                    } else {
                        throw new Error('No se encontraron archivos ni metricas en el PDF. Usa un informe generado con este dashboard, o exporta JSON comparativa.');
                    }
                }
                persistClientBaseline();
                renderClientBaselinePanel();
                syncDiffFilterControl();
                updateStats();
                renderClientBaselineCompare();
                renderTable();
                showToast(pdfImportedCount >= 1
                    ? 'Importados ' + pdfImportedCount + ' archivos desde el PDF'
                    : (baselineHasFileDetail()
                        ? 'Referencia cargada con detalle de archivos: ' + name
                        : 'Referencia cargada: ' + name));
            } catch (err) {
                clientBaseline = null;
                clientBaselineLoadError = err.message || String(err);
                persistClientBaseline();
                renderClientBaselinePanel();
                syncDiffFilterControl();
                showToast(clientBaselineLoadError, 'error');
            }
        }
        
        function limpiarReferenciaCliente() {
            clientBaseline = null;
            clientBaselineLoadError = null;
            currentDiffFilter = 'all';
            persistClientBaseline();
            const inp = document.getElementById('clientBaselineInput');
            if (inp) inp.value = '';
            renderClientBaselinePanel();
            syncDiffFilterControl();
            updateStats();
            renderTable();
            showToast('Referencia eliminada');
        }
        
        function renderClientBaselinePanel() {
            const st = document.getElementById('clientBaselineStatus');
            const row = document.getElementById('clientBaselineCompare');
            if (!st || !row) return;
            if (!clientBaseline) {
                if (clientBaselineLoadError) {
                    st.innerHTML = '<span style="color:#dc2626;"><strong>No se pudo usar el archivo.</strong> ' + clientBaselineLoadError.replace(/</g, '&lt;') + '</span>';
                } else {
                    st.innerHTML = '<span style="color:var(--gray);">Sin archivo de referencia. El PDF debe incluir la tabla &laquo;Detalle de archivos desarrollados&raquo; (informe de este dashboard).</span>';
                }
                row.innerHTML = '';
                syncHoursComparePdfOption();
                syncDiffFilterControl();
                updateBaselineEnrichButtons();
                renderBaselineFilesPreview();
                return;
            }
            const b = clientBaseline;
            const refLines = b.totalLines != null ? Number(b.totalLines).toLocaleString('es-ES') : '\u2014';
            const refH = b.totalHours != null ? Number(b.totalHours).toFixed(2) : '\u2014';
            const refF = b.fileCount != null ? String(b.fileCount) : '\u2014';
            const loadedLabel = b.listSourceFileName && b.listSourceFileName !== b.fileName
                ? (b.fileName + ' + listado ' + b.listSourceFileName)
                : (b.fileName || 'archivo');
            st.innerHTML = '<strong>Referencia cargada:</strong> <code style="background:#e2e8f0;padding:2px 8px;border-radius:6px;">' +
                String(b.fileName || 'archivo').replace(/</g, '&lt;') + '</code>' +
                ' <span style="color:var(--gray);">(' + (b.source === 'pdf' ? 'PDF' : 'JSON') + ')</span>' +
                (b.listSourceFileName && b.listSourceFileName !== b.fileName
                    ? '<br><strong>Listado de archivos:</strong> <code style="background:#e2e8f0;padding:2px 8px;border-radius:6px;">' +
                      String(b.listSourceFileName).replace(/</g, '&lt;') + '</code>'
                    : '') +
                '<br><span style="font-size:12px;color:var(--gray);">En informe: ' + refLines + ' l&iacute;neas &middot; ' + refH + ' h &middot; ' + refF + ' archivos. ' +
                (b.sourceDetail ? '<em>' + b.sourceDetail + '</em>' : '') + '</span>' +
                (baselineHasFileDetail()
                    ? (function() {
                        const mc = countBaselineMatches();
                        const ok = mc.matched > 0;
                        return '<br><span style="font-size:12px;color:' + (ok ? '#166534' : '#b45309') + ';">' +
                            (ok ? '<i class="fas fa-check"></i> ' : '<i class="fas fa-exclamation-triangle"></i> ') +
                            'Listado ref.: ' + mc.refCount + ' archivos &middot; Casados con escaneo: <strong>' +
                            mc.matched + '/' + mc.total + '</strong>' +
                            (ok ? '. Revise la tabla de abajo para ver qu&eacute; archivos se leyeron del PDF.' : '. Vuelva a subir el PDF gerencial (Ctrl+F5 antes).') +
                            '</span>';
                    })()
                    : '<br><span style="font-size:12px;color:#b45309;"><i class="fas fa-info-circle"></i> Sin listado de archivos: pulse <strong>&laquo;A&ntilde;adir listado (activar filtro)&raquo;</strong> y suba el <strong>PDF gerencial</strong>.</span>');
            row.innerHTML = '';
            syncHoursComparePdfOption();
            syncDiffFilterControl();
            updateBaselineEnrichButtons();
            renderBaselineFilesPreview();
        }
        
        function renderClientBaselineCompare() {
            const row = document.getElementById('clientBaselineCompare');
            const files = getActiveFiles();
            if (!row || !clientBaseline || !files.length) {
                if (row && (!clientBaseline || !files.length)) row.innerHTML = '';
                return;
            }
            const cur = computeMetrics(files);
            const b = clientBaseline;
            const bLines = Number(b.totalLines) || 0;
            const bHours = Number(b.totalHours) || 0;
            const bFiles = Number(b.fileCount) || 0;
            const dL = cur.totalLines - bLines;
            const dH = cur.totalHours - bHours;
            const dF = cur.fileCount - bFiles;
            const cls = (v) => v > 0 ? 'positive' : v < 0 ? 'negative' : 'neutral';
            const tdCls = (v) => 'compare-td-' + cls(v);
            const pctVsRef = Math.abs(bHours) >= 1e-6
                ? '<span class="compare-hero-pct">' + (dH >= 0 ? '+' : '') + ((100 * dH) / bHours).toFixed(1) + '% respecto al informe de referencia</span>'
                : '';
            const heroClass = cls(dH);
            row.innerHTML =
                '<div class="compare-inner">' +
                '<div class="compare-hours-hero ' + heroClass + '">' +
                '<div class="compare-hero-item"><span class="compare-hero-label">Horas escaneo actual</span><strong class="compare-hero-val">' + cur.totalHours.toFixed(2) + ' h</strong><span style="font-size:10px;color:var(--gray);">Complejidad elegida por archivo</span></div>' +
                '<div class="compare-hero-sep" aria-hidden="true"></div>' +
                '<div class="compare-hero-item"><span class="compare-hero-label">Horas en referencia</span><strong class="compare-hero-val muted">' + bHours.toFixed(2) + ' h</strong><span style="font-size:10px;color:var(--gray);">PDF o JSON cargado</span></div>' +
                '<div class="compare-hero-sep" aria-hidden="true"></div>' +
                '<div class="compare-hero-item"><span class="compare-hero-label">Diferencia</span><strong class="compare-hero-delta">' + formatDeltaNum(dH, 2, ' h') + '</strong>' + pctVsRef + '</div>' +
                '</div>' +
                '<table class="compare-metrics-table">' +
                '<thead><tr><th>M&eacute;trica</th><th>Actual</th><th>Referencia</th><th>Diferencia</th></tr></thead>' +
                '<tbody>' +
                '<tr><td>L&iacute;neas</td><td>' + cur.totalLines.toLocaleString('es-ES') + '</td><td>' + bLines.toLocaleString('es-ES') + '</td><td class="' + tdCls(dL) + '">' + formatDeltaNum(dL, 0, '') + '</td></tr>' +
                '<tr><td>Archivos</td><td>' + cur.fileCount + '</td><td>' + bFiles + '</td><td class="' + tdCls(dF) + '">' + formatDeltaNum(dF, 0, '') + '</td></tr>' +
                '</tbody></table>' +
                '</div>';
        }
        
        function exportarJsonComparativa() {
            if (!projectFiles.length || !currentProject) return;
            const projectName = currentScanTargets.length
                ? currentScanTargets.map(t => t.label || pathBasename(t.path)).join('+')
                : pathBasename(currentProject);
            const active = projectFiles.filter(f => !isFileExcluded(f));
            const m = computeMetrics(active);
            const payload = {
                version: 2,
                type: 'dashboard_baseline',
                exportedAt: new Date().toISOString(),
                projectPath: currentProject,
                projectName: projectName,
                scanMode: currentScanTargets.length === 1 ? currentScanTargets[0].mode : 'mixed',
                targets: currentScanTargets,
                totalLines: m.totalLines,
                totalHours: Math.round(m.totalHours * 10000) / 10000,
                fileCount: m.fileCount,
                excludedCount: projectFiles.length - active.length,
                files: active.map(f => ({
                    name: f.name,
                    folder: f.folder || 'ROOT',
                    type: f.type,
                    lines: f.lines,
                    complexity: f.complexity,
                    hours: Number(horasArchivoStr(f))
                }))
            };
            const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'Comparativa_' + projectName.replace(/[^\w\-+]+/g, '_') + '_' + new Date().toISOString().split('T')[0] + '.json';
            a.click();
            URL.revokeObjectURL(a.href);
            showToast('JSON listo (incluye detalle de archivos para control de diffs)');
        }
        
        function renderFolderBreakdown() {
            const tbody = document.getElementById('folderBreakdownBody');
            if (!tbody) return;
            const files = getActiveFiles();
            if (!files.length) {
                tbody.innerHTML = '';
                return;
            }
            const rows = aggregateByFolder(files);
            const groups = groupRowsByGlobal(rows);
            const esc = (s) => String(s == null ? '' : s).replace(/</g, '&lt;');
            const showDelta = syncHoursDeltaColumns();
            const refHours = showDelta ? aggregateBaselineHours() : { exact: {}, global: {} };
            const deltaCell = (curH, refH) => showDelta
                ? '<td class="hours-delta-col" style="text-align:right;">' + formatHoursDeltaHtml(curH - (Number(refH) || 0)) + '</td>'
                : '';
            tbody.innerHTML = groups.map(g => {
                const gid = String(g.global);
                const isOpen = !!folderBreakdownOpen[gid];
                const head = '<tr class="folder-group-head' + (isOpen ? ' is-open' : '') + '" data-group="' + gid.replace(/"/g, '&quot;') + '" data-folder="' + gid.replace(/"/g, '&quot;') + '" onclick="setFilter(\'folder\', this.dataset.folder)" title="Filtrar archivos de este m&oacute;dulo">' +
                    '<td><span class="folder-global-name"><i class="fas fa-layer-group"></i> ' + esc(g.global) + '</span>' +
                    '<button type="button" class="folder-group-toggle" onclick="toggleFolderGroup(event, this.closest(\'tr\').dataset.group)" title="Mostrar u ocultar subcarpetas">' +
                    (isOpen
                        ? '<i class="fas fa-chevron-up"></i> Ocultar'
                        : '<i class="fas fa-chevron-down"></i> Mostrar') +
                    '</button></td>' +
                    '<td style="text-align:center;">' + g.files + '</td>' +
                    '<td style="text-align:right;">' + g.lines.toLocaleString() + '</td>' +
                    '<td style="text-align:right;">' + g.hours.toFixed(2) + ' h</td>' +
                    deltaCell(g.hours, refHours.global[g.global]) +
                    '<td style="text-align:right;">' + g.pct.toFixed(1) + '%</td>' +
                    '<td class="folder-bar-cell"><div class="folder-bar"><div style="width:' + Math.min(100, g.pct).toFixed(1) + '%"></div></div></td>' +
                    '</tr>';
                const kids = g.children.map(r =>
                    '<tr class="folder-sub-row' + (isOpen ? ' is-open' : '') + '" data-parent="' + gid.replace(/"/g, '&quot;') + '" data-folder="' + String(r.folder).replace(/"/g, '&quot;') + '" onclick="setFilter(\'folder\', this.dataset.folder)" title="Filtrar esta subcarpeta">' +
                    '<td><span class="folder-sub-name">' + esc(subfolderLabel(r.folder, g.global)) + '</span></td>' +
                    '<td style="text-align:center;">' + r.files + '</td>' +
                    '<td style="text-align:right;">' + r.lines.toLocaleString() + '</td>' +
                    '<td style="text-align:right;">' + r.hours.toFixed(2) + ' h</td>' +
                    deltaCell(r.hours, refHours.exact[r.folder]) +
                    '<td style="text-align:right;">' + r.pct.toFixed(1) + '%</td>' +
                    '<td class="folder-bar-cell"><div class="folder-bar folder-bar-sub"><div style="width:' + Math.min(100, r.pct).toFixed(1) + '%"></div></div></td>' +
                    '</tr>'
                ).join('');
                return head + kids;
            }).join('');
        }

        function toggleFolderGroup(event, key) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            if (!key) return;
            folderBreakdownOpen[key] = !folderBreakdownOpen[key];
            const open = !!folderBreakdownOpen[key];
            const tbody = document.getElementById('folderBreakdownBody');
            if (!tbody) return;
            tbody.querySelectorAll('.folder-group-head').forEach(tr => {
                if (tr.getAttribute('data-group') !== key) return;
                tr.classList.toggle('is-open', open);
                const btn = tr.querySelector('.folder-group-toggle');
                if (btn) {
                    btn.innerHTML = open
                        ? '<i class="fas fa-chevron-up"></i> Ocultar'
                        : '<i class="fas fa-chevron-down"></i> Mostrar';
                }
            });
            tbody.querySelectorAll('.folder-sub-row').forEach(tr => {
                if (tr.getAttribute('data-parent') !== key) return;
                tr.classList.toggle('is-open', open);
            });
        }
        
        function updateAdjustmentSummary() {
            const el = document.getElementById('adjustSummary');
            if (!el) return;
            const files = getActiveFiles();
            const excludedN = countExcludedFiles();
            if (!files.length && !excludedN) {
                el.textContent = '';
                return;
            }
            let n = 0;
            files.forEach(f => {
                const sug = f.suggestedComplexity != null ? f.suggestedComplexity : f.complexity;
                if (f.complexity !== sug) n++;
            });
            let msg = '';
            if (n === 0) {
                msg = 'Complejidad acorde a la sugerencia autom\u00e1tica en todos los archivos del conteo.';
            } else {
                msg = n + ' archivo(s) con complejidad distinta a la sugerida por heur\u00edstica.';
            }
            if (excludedN > 0) {
                msg += ' ' + excludedN + ' excluido(s) del conteo.';
            }
            el.textContent = msg;
        }

        function updateExcludedCountBadge() {
            const badge = document.getElementById('excludedCountBadge');
            if (!badge) return;
            const excluded = getExcludedFiles();
            const n = excluded.length;
            if (n <= 0) {
                badge.style.display = 'none';
                badge.textContent = '';
                badge.removeAttribute('title');
                return;
            }
            const hours = excluded.reduce((s, f) => s + (f.lines / tasaComplejidad(f.complexity)), 0);
            const hoursTxt = hours.toFixed(2);
            badge.style.display = '';
            badge.innerHTML = '<i class="fas fa-eye-slash"></i> ' + n + ' excluido' + (n === 1 ? '' : 's');
            badge.title = 'Horas excluidas del conteo: ' + hoursTxt + ' h\n'
                + n + ' archivo' + (n === 1 ? '' : 's') + '\n'
                + 'Filtro \u00abSolo excluidos\u00bb para verlos y reactivarlos';
        }
        
        async function persistScanSnapshot(projectPath, metrics, scanMode) {
            const projects = await loadSavedProjects();
            const prev = projects[projectPath] || {};
            const history = Array.isArray(prev.history) ? prev.history.slice() : [];
            const snap = {
                at: new Date().toISOString(),
                totalLines: metrics.totalLines,
                totalHours: metrics.totalHours,
                fileCount: metrics.fileCount,
                scanMode: scanMode
            };
            history.push(snap);
            while (history.length > MAX_HISTORY_POINTS) history.shift();
            projects[projectPath] = {
                ...prev,
                history,
                lastSnapshot: snap
            };
            const ok = await saveProjects(projects);
            return ok ? snap : null;
        }
        
        // Cargar proyectos guardados
        async function loadSavedProjects() {
            try {
                const response = await fetch('dashboard_api.php?action=load');
                return await response.json();
            } catch (e) {
                console.error('Error loading projects:', e);
                return {};
            }
        }
        
        // Guardar proyectos
        async function saveProjects(projects) {
            try {
                const response = await fetch('dashboard_api.php?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(projects)
                });
                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }
                return true;
            } catch (e) {
                console.error('Error saving projects:', e);
                showToast('Error al guardar: ' + e.message, 'error');
                return false;
            }
        }
        
        // Mostrar proyectos guardados
        async function renderProjectList() {
            const projects = await loadSavedProjects();
            const list = document.getElementById('projectList');

            let html = '';
            Object.keys(projects).forEach(path => {
                const saved = projects[path] || {};
                const name = saved.name || pathBasename(path.split('||')[0] || path);
                const esc = path.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                html += `<div class="project-chip" onclick="cargarProyectoGuardado('${esc}')" title="${String(path).replace(/"/g, '&quot;')}">${name}</div>`;
            });

            list.innerHTML = html;
        }

        function cargarProyectoGuardado(pathKey) {
            loadSavedProjects().then(projects => {
                const saved = projects[pathKey];
                if (saved && Array.isArray(saved.targets) && saved.targets.length) {
                    scanQueue = saved.targets.map(t => ({
                        path: t.path,
                        mode: normalizeScanMode(t.mode)
                    }));
                } else if (pathKey.indexOf('||') >= 0) {
                    scanQueue = pathKey.split('||').filter(Boolean).map(p => ({
                        path: p,
                        mode: 'normal'
                    }));
                } else {
                    scanQueue = [{ path: pathKey, mode: document.getElementById('scanMode').value || 'normal' }];
                }
                renderScanQueue();
                escanearProyecto();
            });
        }
        
        // Escanear proyecto(s)
        function escanearProyecto() {
            const pathEl = document.getElementById('projectPath');
            const modeEl = document.getElementById('scanMode');
            const pendingPath = pathEl ? pathEl.value.trim() : '';
            if (pendingPath && scanQueue.length === 0) {
                agregarCarpetaACola(pendingPath, modeEl ? modeEl.value : 'normal');
            } else if (pendingPath && scanQueue.length > 0) {
                const key = normalizePathKey(pendingPath);
                if (!scanQueue.some(t => normalizePathKey(t.path) === key)) {
                    agregarCarpetaACola(pendingPath, modeEl ? modeEl.value : 'normal');
                }
            }

            if (!scanQueue.length) {
                showToast('Agrega al menos una carpeta a la lista', 'warning');
                return;
            }

            const targets = scanQueue.map(t => ({ path: t.path, mode: t.mode }));
            const loadingEl = document.getElementById('loading');
            if (loadingEl) {
                loadingEl.classList.add('show');
                const p = loadingEl.querySelector('p');
                if (p) p.textContent = targets.length > 1
                    ? 'Escaneando ' + targets.length + ' carpetas...'
                    : 'Escaneando proyecto...';
            }

            fetch('dashboard_scan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ targets: targets })
            })
            .then(response => response.json())
            .then(async data => {
                document.getElementById('loading').classList.remove('show');

                if (data.error) {
                    showToast(data.error, 'error');
                    return;
                }

                const resolvedTargets = (data.targets && data.targets.length)
                    ? data.targets
                    : [{
                        path: data.resolvedPath || targets[0].path,
                        label: pathBasename(data.resolvedPath || targets[0].path),
                        mode: targets[0].mode
                    }];

                currentScanTargets = resolvedTargets;
                currentProject = compositeProjectKey(resolvedTargets);
                currentProjectView = 'all';
                scanQueue = resolvedTargets.map(t => ({ path: t.path, mode: t.mode }));
                renderScanQueue();
                if (pathEl) pathEl.value = '';

                projectFiles = data.files.map(f => ({
                    ...f,
                    excluded: false,
                    suggestedComplexity: f.suggestedComplexity != null ? f.suggestedComplexity : f.complexity
                }));

                const projects = await loadSavedProjects();
                projectFiles.forEach(file => {
                    applySavedFileSettings(file, projects, currentProject);
                    file.hours = horasArchivoStr(file);
                });

                const metrics = computeMetrics(projectFiles.filter(f => !isFileExcluded(f)));
                const modesSummary = resolvedTargets.map(t => t.mode).join(',');
                await persistScanSnapshot(currentProject, metrics, modesSummary);
                await persistScanFileLists(currentProject, projectFiles);

                document.getElementById('projectName').textContent = displayProjectTitle(resolvedTargets);
                document.getElementById('projectStatus').textContent = resolvedTargets.length > 1
                    ? 'Escaneado (' + resolvedTargets.length + ' carpetas)'
                    : 'Escaneado';
                document.getElementById('btnSave').disabled = false;
                document.getElementById('btnPdf').disabled = false;
                const btnBl = document.getElementById('btnExportBaseline');
                if (btnBl) btnBl.disabled = false;

                const analyticsEl = document.getElementById('analyticsSection');
                if (analyticsEl) analyticsEl.style.display = 'block';

                renderProjectViewTabs();
                renderFolderBreakdown();
                renderTable();
                updateStats();
                const modeNote = resolvedTargets.map(t =>
                    (t.label || pathBasename(t.path)) + ': ' + modeLabel(t.mode)
                ).join(' Â· ');
                showToast('Escaneado: ' + data.files.length + ' archivos (' + modeNote + ')');
            })
            .catch(error => {
                document.getElementById('loading').classList.remove('show');
                showToast('Error al escanear: ' + error.message, 'error');
            });
        }

        // Cargar proyecto guardado (anade a la cola y escanea)
        function cargarProyecto(path) {
            scanQueue = [{ path: path, mode: document.getElementById('scanMode').value || 'normal' }];
            renderScanQueue();
            escanearProyecto();
        }

        // Guardar configuracion
        async function guardarConfiguracion() {
            if (!currentProject || projectFiles.length === 0) return;

            const projects = await loadSavedProjects();

            const complexities = {};
            const excludedFiles = {};
            projectFiles.forEach(file => {
                const ck = fileComplexityKey(file);
                complexities[ck] = file.complexity;
                if (isFileExcluded(file)) excludedFiles[ck] = true;
            });

            const prevTargets = currentScanTargets.slice();
            const targetsToSave = buildTargetsFromQueue();
            currentScanTargets = targetsToSave;

            const labels = targetsToSave.length
                ? targetsToSave.map(t => t.label || pathBasename(t.path))
                : [pathBasename(currentProject)];

            projects[currentProject] = {
                ...(projects[currentProject] || {}),
                name: labels.join(' + '),
                targets: targetsToSave,
                complexities: complexities,
                excludedFiles: excludedFiles,
                lastScan: new Date().toISOString()
            };

            // Sincronizar cada carpeta individual para que "solo relavera" y el filtro
            // en relavera+flujo usen las mismas complejidades / exclusiones.
            targetsToSave.forEach(t => {
                const pathKey = t.path;
                if (!pathKey) return;
                const perComplexities = { ...((projects[pathKey] && projects[pathKey].complexities) || {}) };
                const perExcluded = { ...((projects[pathKey] && projects[pathKey].excludedFiles) || {}) };
                projectFiles.forEach(file => {
                    if (!file.projectPath || normalizePathKey(file.projectPath) !== normalizePathKey(pathKey)) return;
                    const ck = fileComplexityKey(file);
                    perComplexities[ck] = file.complexity;
                    fileComplexityKeyVariants(file).forEach(k => {
                        if (isFileExcluded(file)) perExcluded[k] = true;
                        else delete perExcluded[k];
                    });
                });
                const prev = projects[pathKey] || {};
                projects[pathKey] = {
                    ...prev,
                    name: t.label || pathBasename(pathKey),
                    targets: [{ path: pathKey, mode: t.mode, label: t.label || pathBasename(pathKey) }],
                    complexities: perComplexities,
                    excludedFiles: perExcluded,
                    lastScan: new Date().toISOString()
                };
            });

            const success = await saveProjects(projects);
            if (success) {
                renderProjectList();
                renderScanQueue();
                if (targetsModesChanged(prevTargets, targetsToSave)) {
                    showToast('Tipo de conteo guardado. Pulse \u00abEscanear todo\u00bb para recalcular lineas/horas.', 'warning');
                } else {
                    showToast('Configuracion guardada');
                }
            }
        }
        
        // Cambiar complejidad
        function cambiarComplejidad(index, newComplexity) {
            projectFiles[index].complexity = newComplexity;
            projectFiles[index].hours = horasArchivoStr(projectFiles[index]);
            renderTable();
            updateStats();
        }
        
        // Set Filter
        function setFilter(type, value, element) {
            if (type === 'type') {
                currentTypeFilter = value;
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                if (element) element.classList.add('active');
            } else if (type === 'folder') {
                currentFolderFilter = value;
            } else if (type === 'complexity') {
                currentComplexityFilter = value;
            } else if (type === 'diff') {
                if (!baselineHasFileDetail() && value !== 'all') {
                    showToast('Falta el listado de archivos. Suba el PDF gerencial con \u00abA\u00f1adir listado\u00bb.', 'warning');
                    currentDiffFilter = 'all';
                    const sel = document.getElementById('diffFilter');
                    if (sel) sel.value = 'all';
                    const btn = document.getElementById('btnEnrichBaselineFiles');
                    if (btn && clientBaseline) btn.click();
                    return;
                }
                currentDiffFilter = value;
            } else if (type === 'inclusion') {
                currentInclusionFilter = value || 'active';
            }
            renderTable();
        }
        
        function syncFolderFilterOptions() {
            const sel = document.getElementById('folderFilter');
            if (!sel) return;
            if (!projectFiles.length) {
                sel.innerHTML = '';
                const o0 = document.createElement('option');
                o0.value = 'all';
                o0.textContent = 'Todas las carpetas';
                sel.appendChild(o0);
                currentFolderFilter = 'all';
                return;
            }
            const folders = collectFolderFilterKeys(getVisibleFiles());
            const list = Object.keys(folders).sort((a, b) => {
                if (a === 'ROOT') return 1;
                if (b === 'ROOT') return -1;
                return a.localeCompare(b, 'es', { sensitivity: 'base' });
            });
            const globales = list.filter(f => f !== 'ROOT' && f.indexOf('/') < 0);
            const subcarpetas = list.filter(f => f === 'ROOT' || f.indexOf('/') >= 0);
            const prev = currentFolderFilter;
            sel.innerHTML = '';
            const oAll = document.createElement('option');
            oAll.value = 'all';
            oAll.textContent = 'Todas las carpetas';
            sel.appendChild(oAll);
            function appendFolderOption(parent, folder) {
                const o = document.createElement('option');
                o.value = folder;
                o.textContent = folder === 'ROOT'
                    ? 'ROOT'
                    : (folder.indexOf('/') < 0 ? folder + ' (todo el m\u00f3dulo)' : folder);
                parent.appendChild(o);
            }
            if (globales.length) {
                const og = document.createElement('optgroup');
                og.label = 'M\u00f3dulos (carpeta global)';
                globales.forEach(folder => appendFolderOption(og, folder));
                sel.appendChild(og);
            }
            if (subcarpetas.length) {
                const os = document.createElement('optgroup');
                os.label = 'Subcarpetas';
                subcarpetas.forEach(folder => appendFolderOption(os, folder));
                sel.appendChild(os);
            }
            if (prev !== 'all' && folders[prev]) {
                sel.value = prev;
                currentFolderFilter = prev;
            } else {
                sel.value = 'all';
                currentFolderFilter = 'all';
            }
        }

        function positionAdjustPop(wrap) {
            const pop = wrap.querySelector('.adjust-popover');
            if (!pop) return;
            pop.classList.add('is-open');
            const r = wrap.getBoundingClientRect();
            const pw = pop.offsetWidth || 280;
            const ph = pop.offsetHeight || 100;
            let left = r.right - pw;
            if (left < 10) left = 10;
            if (left + pw > window.innerWidth - 10) left = Math.max(10, window.innerWidth - pw - 10);
            let top = r.bottom + 8;
            if (top + ph > window.innerHeight - 12) top = Math.max(12, r.top - ph - 8);
            pop.style.left = left + 'px';
            pop.style.top = top + 'px';
        }
        
        function hideAdjustPop(wrap) {
            const pop = wrap.querySelector('.adjust-popover');
            if (!pop) return;
            pop.classList.remove('is-open');
            pop.style.left = '';
            pop.style.top = '';
        }
        
        function bindAdjustPopovers(tbody) {
            if (!tbody) return;
            const tc = tbody.closest('.table-container');
            if (tc && !tc._adjustScrollBound) {
                tc._adjustScrollBound = true;
                tc.addEventListener('scroll', function() {
                    tbody.querySelectorAll('.adjust-hint-wrap').forEach(w => hideAdjustPop(w));
                }, { passive: true });
            }
            tbody.querySelectorAll('.adjust-hint-wrap').forEach(wrap => {
                wrap.addEventListener('mouseenter', () => positionAdjustPop(wrap));
                wrap.addEventListener('mouseleave', (e) => {
                    const rt = e.relatedTarget;
                    if (!rt || !wrap.contains(rt)) hideAdjustPop(wrap);
                });
                wrap.addEventListener('focusin', () => positionAdjustPop(wrap));
                wrap.addEventListener('focusout', (e) => {
                    const rt = e.relatedTarget;
                    if (!rt || !wrap.contains(rt)) hideAdjustPop(wrap);
                });
            });
        }
        
        // Renderizar tabla
        function renderTable() {
            const tbody = document.getElementById('filesTableBody');
            syncFolderFilterOptions();
            syncDiffFilterControl();
            updateExcludedCountBadge();

            const showHoursDelta = syncHoursDeltaColumns();
            const colCount = showHoursDelta ? 7 : 6;

            if (projectFiles.length === 0) {
                tbody.innerHTML = `
                    <tr><td colspan="${colCount}">
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <h3>Sin proyecto seleccionado</h3>
                            <p>Agrega una o m&aacute;s carpetas y haz clic en &laquo;Escanear todo&raquo;</p>
                        </div>
                    </td></tr>`;
                return;
            }

            const baselineIndex = baselineHasFileDetail() ? buildBaselineFileIndex() : null;
            const rows = [];
            projectFiles.forEach((file, index) => {
                if (!fileHasLines(file)) return;
                if (currentProjectView !== 'all') {
                    const key = normalizePathKey(currentProjectView);
                    const matchPath = file.projectPath && normalizePathKey(file.projectPath) === key;
                    const matchLabel = file.project && normalizePathKey(file.project) === key;
                    if (!matchPath && !matchLabel) return;
                }
                if (currentInclusionFilter === 'active' && isFileExcluded(file)) return;
                if (currentInclusionFilter === 'excluded' && !isFileExcluded(file)) return;
                if (currentTypeFilter !== 'all' && file.type !== currentTypeFilter) return;
                if (currentComplexityFilter !== 'all' && file.complexity !== currentComplexityFilter) return;
                const folder = file.folder || 'ROOT';
                if (currentFolderFilter !== 'all' && !folderMatchesFilter(folder, currentFolderFilter)) return;
                if (!fileMatchesDiffFilter(file, baselineIndex)) return;
                rows.push({ file: file, index: index });
            });
            rows.sort((a, b) => {
                if (baselineIndex && currentDiffFilter !== 'all') {
                    const da = getFileDiffInfo(a.file, baselineIndex);
                    const db = getFileDiffInfo(b.file, baselineIndex);
                    const rank = { nuevo: 0, aumentado: 1, modificado: 2, igual: 3 };
                    const ra = da ? (rank[da.status] != null ? rank[da.status] : 9) : 9;
                    const rb = db ? (rank[db.status] != null ? rank[db.status] : 9) : 9;
                    if (ra !== rb) return ra - rb;
                    const dLa = da ? Math.abs(da.deltaLines) : 0;
                    const dLb = db ? Math.abs(db.deltaLines) : 0;
                    if (dLa !== dLb) return dLb - dLa;
                }
                const fa = a.file.folder || 'ROOT';
                const fb = b.file.folder || 'ROOT';
                const c = fa.localeCompare(fb, 'es', { sensitivity: 'base' });
                if (c !== 0) return c;
                return (a.file.name || '').localeCompare(b.file.name || '', 'es', { sensitivity: 'base', numeric: true });
            });

            let html = '';
            rows.forEach(({ file, index }) => {
                const iconClass = file.type === 'php' ? 'fab fa-php' :
                                  file.type === 'js' ? 'fab fa-js' :
                                  file.type === 'css' ? 'fab fa-css3-alt' :
                                  file.type === 'sql' ? 'fas fa-database' : 'fab fa-html5';
                const sug = file.suggestedComplexity != null ? file.suggestedComplexity : file.complexity;
                const adjusted = file.complexity !== sug;
                let adjustHtml = '';
                if (adjusted) {
                    const hSug = file.lines / tasaComplejidad(sug);
                    const hEleg = file.lines / tasaComplejidad(file.complexity);
                    const dHoras = hEleg - hSug;
                    const dTxt = (dHoras >= 0 ? '+' : '') + dHoras.toFixed(2) + ' h';
                    const tasaS = tasaComplejidad(sug);
                    const tasaE = tasaComplejidad(file.complexity);
                    adjustHtml = '<div class="adjust-hint-wrap no-print" tabindex="0" title="Pasa el cursor o pulsa Tab+Entrar para ver el detalle">' +
                        '<span class="adjust-trigger" aria-label="Detalle ajuste de complejidad"><i class="fas fa-info"></i></span>' +
                        '<div class="adjust-popover">' +
                        '<strong>Sug.: ' + etiquetaComplejidad(sug) + ' \u2192 Elegida: ' + etiquetaComplejidad(file.complexity) + '</strong>' +
                        '<span class="adjust-delta">Solo autom.: ' + hSug.toFixed(2) + ' h \u00b7 Ahora: ' + hEleg.toFixed(2) + ' h (' + dTxt + ')</span>' +
                        '<span class="adjust-delta" style="font-size:9px;">Tasas: ' + tasaS + ' vs ' + tasaE + ' l/h</span>' +
                        '</div></div>';
                }

                let diffBadge = '';
                let linesDeltaHtml = '';
                if (baselineIndex) {
                    const diff = getFileDiffInfo(file, baselineIndex);
                    if (diff) {
                        if (diff.status === 'nuevo') {
                            diffBadge = '<span class="diff-badge diff-badge-nuevo">Nuevo</span>';
                            linesDeltaHtml = '<span class="diff-lines-delta up">+' + file.lines.toLocaleString() + ' vs ref.</span>';
                        } else if (diff.status === 'aumentado') {
                            diffBadge = '<span class="diff-badge diff-badge-aumentado">+' + diff.deltaLines.toLocaleString() + ' l&iacute;n.</span>';
                            linesDeltaHtml = '<span class="diff-lines-delta up">era ' + Number(diff.refLines).toLocaleString() + '</span>';
                        } else if (diff.status === 'modificado') {
                            diffBadge = '<span class="diff-badge diff-badge-modificado">' + diff.deltaLines.toLocaleString() + ' l&iacute;n.</span>';
                            linesDeltaHtml = '<span class="diff-lines-delta down">era ' + Number(diff.refLines).toLocaleString() + '</span>';
                        } else if (currentDiffFilter === 'all') {
                            diffBadge = '';
                        }
                    }
                }

                const excludedBadge = isFileExcluded(file)
                    ? '<span class="diff-badge excluded-badge">Excluido</span>'
                    : '';
                const toggleBtn = isFileExcluded(file)
                    ? '<button type="button" class="btn-file-toggle include no-print" onclick="incluirArchivo(' + index + ')" title="Volver al conteo"><i class="fas fa-eye"></i></button>'
                    : '<button type="button" class="btn-file-toggle exclude no-print" onclick="excluirArchivo(' + index + ')" title="Excluir del conteo"><i class="fas fa-eye-slash"></i></button>';

                let hoursDeltaTd = '';
                if (showHoursDelta) {
                    const refFile = baselineIndex ? findBaselineFile(file, baselineIndex) : null;
                    const curH = file.lines / tasaComplejidad(file.complexity);
                    const refH = refFile ? horasDeArchivoNum(refFile) : 0;
                    hoursDeltaTd = '<td class="hours-delta-col">' + formatHoursDeltaHtml(curH - refH) + '</td>';
                }

                html += `
                    <tr class="${isFileExcluded(file) ? 'file-row-excluded' : ''}">
                        <td>
                            <div class="file-name">
                                <div class="file-icon ${file.type}">
                                    <i class="${iconClass}"></i>
                                </div>
                                <div>
                                    <strong>${file.name}</strong>
                                    <div style="font-size: 11px; color: var(--gray);">${file.folder}/</div>
                                    ${diffBadge}${excludedBadge}
                                </div>
                            </div>
                        </td>
                        <td><strong>${file.lines.toLocaleString()}</strong>${linesDeltaHtml}</td>
                        <td><strong>${horasArchivoStr(file)} h</strong></td>
                        ${hoursDeltaTd}
                        <td><span class="complexity-label-short">${etiquetaComplejidad(sug)}</span></td>
                        <td>
                            <select class="complexity-select" onchange="cambiarComplejidad(${index}, this.value)">
                                <option value="alta" ${file.complexity === 'alta' ? 'selected' : ''}>Alta</option>
                                <option value="media-alta" ${file.complexity === 'media-alta' ? 'selected' : ''}>Media-Alta</option>
                                <option value="media" ${file.complexity === 'media' ? 'selected' : ''}>Media</option>
                                <option value="baja" ${file.complexity === 'baja' ? 'selected' : ''}>Baja</option>
                            </select>
                            ${adjustHtml}
                        </td>
                        <td class="no-print" style="text-align:center;">${toggleBtn}</td>
                    </tr>
                `;
            });

            let emptyMsg = 'No hay archivos con este filtro (prueba otra carpeta, tipo o complejidad)';
            if (currentInclusionFilter === 'excluded') {
                emptyMsg = 'No hay archivos excluidos. Use el icono del ojo tachado en cada fila para excluir.';
            } else if (currentDiffFilter !== 'all') {
                emptyMsg = 'No hay archivos nuevos o modificados con este filtro';
            }
            tbody.innerHTML = html || '<tr><td colspan="' + colCount + '" style="text-align:center;padding:20px;">' + emptyMsg + '</td></tr>';
            bindAdjustPopovers(tbody);
        }
        
        // Actualizar estadisticas
        function updateStats() {
            let totalLines = 0, totalHours = 0, suggestedHoursTotal = 0;
            let php = { lines: 0, files: 0 };
            let js = { lines: 0, files: 0 };
            let html = { lines: 0, files: 0 };
            let complexity = {
                alta: { files: 0, lines: 0, hours: 0 },
                'media-alta': { files: 0, lines: 0, hours: 0 },
                media: { files: 0, lines: 0, hours: 0 },
                baja: { files: 0, lines: 0, hours: 0 }
            };

            const visibleFiles = getActiveFiles();
            visibleFiles.forEach(file => {
                totalLines += file.lines;
                totalHours += file.lines / tasaComplejidad(file.complexity);
                const sugKey = file.suggestedComplexity != null ? file.suggestedComplexity : file.complexity;
                suggestedHoursTotal += file.lines / tasaComplejidad(sugKey);

                if (file.type === 'php') { php.lines += file.lines; php.files++; }
                else if (file.type === 'js') { js.lines += file.lines; js.files++; }
                else if (file.type === 'html') { html.lines += file.lines; html.files++; }

                complexity[file.complexity].files++;
                complexity[file.complexity].lines += file.lines;
            });

            // Horas por complejidad: lineas / tasa (evita error por redondeo por archivo)
            complexity.alta.hours = complexity.alta.lines / RATES.alta;
            complexity['media-alta'].hours = complexity['media-alta'].lines / RATES['media-alta'];
            complexity.media.hours = complexity.media.lines / RATES.media;
            complexity.baja.hours = complexity.baja.lines / RATES.baja;

            // Actualizar cards
            document.getElementById('totalLines').textContent = totalLines.toLocaleString();
            document.getElementById('totalFiles').textContent = visibleFiles.length + ' archivos';
            document.getElementById('phpLines').textContent = php.lines.toLocaleString();
            document.getElementById('phpFiles').textContent = php.files + ' archivos';
            document.getElementById('jsLines').textContent = js.lines.toLocaleString();
            document.getElementById('jsFiles').textContent = js.files + ' archivos';
            document.getElementById('htmlLines').textContent = html.lines.toLocaleString();
            document.getElementById('htmlFiles').textContent = html.files + ' archivos';
            document.getElementById('totalHours').textContent = totalHours.toFixed(2);

            const hdc = document.getElementById('hoursDeltaCard');
            const hApp = document.getElementById('hoursDeltaApplied');
            const hSug = document.getElementById('hoursDeltaSuggested');
            const hDiff = document.getElementById('hoursDeltaDiff');
            if (hdc && hApp && hSug && hDiff) {
                if (visibleFiles.length === 0) {
                    hdc.style.display = 'none';
                } else {
                    hdc.style.display = '';
                    hApp.textContent = totalHours.toFixed(2) + ' h';
                    hSug.textContent = suggestedHoursTotal.toFixed(2) + ' h';
                    const deltaH = totalHours - suggestedHoursTotal;
                    const sign = deltaH > 0 ? '+' : '';
                    hDiff.textContent = 'Diferencia: ' + sign + deltaH.toFixed(2) + ' h';
                    hDiff.classList.remove('zero', 'positive', 'negative');
                    if (Math.abs(deltaH) < 0.005) hDiff.classList.add('zero');
                    else if (deltaH > 0) hDiff.classList.add('positive');
                    else hDiff.classList.add('negative');
                }
            }

            // Actualizar complejidad
            const maxHours = Math.max(complexity.alta.hours, complexity['media-alta'].hours, complexity.media.hours, complexity.baja.hours) || 1;

            document.getElementById('hoursAlta').textContent = complexity.alta.hours.toFixed(2) + ' h';
            document.getElementById('filesAlta').textContent = complexity.alta.files + ' archivos';
            document.getElementById('barAlta').style.width = (complexity.alta.hours / maxHours * 100) + '%';

            document.getElementById('hoursMediaAlta').textContent = complexity['media-alta'].hours.toFixed(2) + ' h';
            document.getElementById('filesMediaAlta').textContent = complexity['media-alta'].files + ' archivos';
            document.getElementById('barMediaAlta').style.width = (complexity['media-alta'].hours / maxHours * 100) + '%';

            document.getElementById('hoursMedia').textContent = complexity.media.hours.toFixed(2) + ' h';
            document.getElementById('filesMedia').textContent = complexity.media.files + ' archivos';
            document.getElementById('barMedia').style.width = (complexity.media.hours / maxHours * 100) + '%';

            document.getElementById('hoursBaja').textContent = complexity.baja.hours.toFixed(2) + ' h';
            document.getElementById('filesBaja').textContent = complexity.baja.files + ' archivos';
            document.getElementById('barBaja').style.width = (complexity.baja.hours / maxHours * 100) + '%';

            // Tiempo: 3 devs = 16 h efectivas/d&iacute;a; el resto escala en l&iacute;nea
            const nDevs = Math.max(1, Math.min(30, parseInt(devCount, 10) || 3));
            const hoursPerDay = HOURS_PER_DAY_3DEVS * (nDevs / 3);
            document.getElementById('timeHours').textContent = totalHours.toFixed(2);
            document.getElementById('timeDays').textContent = hoursPerDay ? (totalHours / hoursPerDay).toFixed(1) : '0';
            document.getElementById('timeWeeks').textContent = hoursPerDay ? (totalHours / (hoursPerDay * 5)).toFixed(2) : '0';
            document.getElementById('timeMonths').textContent = hoursPerDay ? (totalHours / (hoursPerDay * 20)).toFixed(2) : '0';
            const hint = document.getElementById('devCountHint');
            if (hint) {
                hint.textContent = nDevs + ' desarrollador' + (nDevs === 1 ? '' : 'es') +
                    ' \u00b7 ' + hoursPerDay.toFixed(1) + ' h efectivas / d\u00eda';
            }

            const analyticsEl = document.getElementById('analyticsSection');
            if (analyticsEl) analyticsEl.style.display = projectFiles.length ? 'block' : 'none';
            renderFolderBreakdown();
            updateAdjustmentSummary();
            updateExcludedCountBadge();
            renderClientBaselineCompare();
            syncHoursComparePdfOption();
        }
        
        // Generar PDF
        function generarPDF() {
            if (projectFiles.length === 0) return;
            
            const btn = document.getElementById('btnPdf');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            
            const pdfFiles = getActiveFiles();

            // Calcular totales
            let totalLines = 0, totalHours = 0;
            let php = { lines: 0, files: 0 };
            let js = { lines: 0, files: 0 };
            let html = { lines: 0, files: 0 };
            let complexity = {
                alta: { files: 0, lines: 0, hours: 0 },
                'media-alta': { files: 0, lines: 0, hours: 0 },
                media: { files: 0, lines: 0, hours: 0 },
                baja: { files: 0, lines: 0, hours: 0 }
            };
            
            pdfFiles.forEach(file => {
                totalLines += file.lines;
                totalHours += file.lines / tasaComplejidad(file.complexity);
                if (file.type === 'php') { php.lines += file.lines; php.files++; }
                else if (file.type === 'js') { js.lines += file.lines; js.files++; }
                else if (file.type === 'html') { html.lines += file.lines; html.files++; }
                complexity[file.complexity].files++;
                complexity[file.complexity].lines += file.lines;
            });
            complexity.alta.hours = complexity.alta.lines / RATES.alta;
            complexity['media-alta'].hours = complexity['media-alta'].lines / RATES['media-alta'];
            complexity.media.hours = complexity.media.lines / RATES.media;
            complexity.baja.hours = complexity.baja.lines / RATES.baja;
            totalHours = pdfFiles.reduce((s, f) => s + (f.lines / tasaComplejidad(f.complexity)), 0);
            
            const ink = '#1c1712';
            const teal = '#1b6b63';
            const tealDeep = '#134e48';
            const cream = '#f3ebe0';
            const paper = '#fbf7f0';
            const line = '#e4d8c6';
            const mint = '#e7f3f1';
            const muted = '#6e655c';
            const ok = '#2f6b4f';
            const danger = '#b42318';
            const th = 'padding:7px 8px;text-align:left;font-size:9px;letter-spacing:0.08em;text-transform:uppercase;font-weight:700;';
            const td = 'padding:6px 8px;border-bottom:1px solid ' + line + ';';
            const pdfH3 = (t) => '<h3 style="color:' + tealDeep + ';font-size:11px;margin:0 0 10px 0;letter-spacing:0.16em;text-transform:uppercase;font-family:Georgia,\'Times New Roman\',serif;border-bottom:1px solid ' + line + ';padding-bottom:7px;page-break-inside:avoid;page-break-after:avoid;">' + t + '</h3>';
            const pdfDeltaTxt = (cur, refH) => {
                const d = cur - (Number(refH) || 0);
                if (Math.abs(d) < 0.005) return '<span style="color:' + muted + ';font-weight:600;">0.00 h</span>';
                const col = d > 0 ? ok : danger;
                return '<span style="color:' + col + ';font-weight:700;">' + (d > 0 ? '+' : '') + d.toFixed(2) + ' h</span>';
            };
            const showDeltaPdf = hasHoursCompare();
            const refHoursPdf = showDeltaPdf ? aggregateBaselineHours() : { exact: {}, global: {} };
            const folderRowsPdf = groupRowsByGlobal(aggregateByFolder(pdfFiles)).map(g => {
                const head = '<tr style="background:' + mint + ';">' +
                    '<td style="' + td + 'font-weight:700;font-family:Georgia,serif;color:' + tealDeep + ';font-size:12px;">' + escapeHtmlPdf(g.global) + '</td>' +
                    '<td style="' + td + 'text-align:center;">' + g.files + '</td>' +
                    '<td style="' + td + 'text-align:right;">' + g.lines.toLocaleString() + '</td>' +
                    '<td style="' + td + 'text-align:right;">' + g.hours.toFixed(2) + ' h</td>' +
                    (showDeltaPdf ? '<td style="' + td + 'text-align:right;">' + pdfDeltaTxt(g.hours, refHoursPdf.global[g.global]) + '</td>' : '') +
                    '<td style="' + td + 'text-align:right;">' + g.pct.toFixed(1) + '%</td></tr>';
                const kids = g.children.map(r =>
                    '<tr style="background:' + paper + ';">' +
                    '<td style="' + td + 'padding-left:18px;color:' + muted + ';">' + escapeHtmlPdf(subfolderLabel(r.folder, g.global)) + '</td>' +
                    '<td style="' + td + 'text-align:center;">' + r.files + '</td>' +
                    '<td style="' + td + 'text-align:right;">' + r.lines.toLocaleString() + '</td>' +
                    '<td style="' + td + 'text-align:right;">' + r.hours.toFixed(2) + ' h</td>' +
                    (showDeltaPdf ? '<td style="' + td + 'text-align:right;">' + pdfDeltaTxt(r.hours, refHoursPdf.exact[r.folder]) + '</td>' : '') +
                    '<td style="' + td + 'text-align:right;">' + r.pct.toFixed(1) + '%</td></tr>'
                ).join('');
                return head + kids;
            }).join('');
            const folderHeadPdf = '<tr style="background:' + teal + ';color:#f3fffc;">' +
                '<th style="' + th + '">Carpeta</th><th style="' + th + 'text-align:center;">Arch.</th>' +
                '<th style="' + th + 'text-align:right;">Lineas</th><th style="' + th + 'text-align:right;">Horas</th>' +
                (showDeltaPdf ? '<th style="' + th + 'text-align:right;">Dif. horas</th>' : '') +
                '<th style="' + th + 'text-align:right;">% lineas</th></tr>';
            
            const projectName = currentScanTargets.length
                ? currentScanTargets.map(t => t.label || pathBasename(t.path)).join(' + ')
                : pathBasename(currentProject);
            const dateStr = new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            const modesPdfNote = currentScanTargets.length
                ? currentScanTargets.map(t => escapeHtmlPdf((t.label || pathBasename(t.path)) + ': ' + modeLabel(t.mode))).join(' &middot; ')
                : '';
            
            // Funcion para generar descripcion de funcionalidad
            function getFileDescription(filename, folder, type) {
                const name = filename.toLowerCase();
                let desc = '';
                
                // Por tipo de archivo
                if (type === 'html') return 'Plantilla de visualizacion e impresion';
                if (type === 'css') return 'Estilos y diseno visual';
                
                // Por patron de nombre
                if (name.includes('sql_') || name.includes('_sql')) desc = 'Consultas y operaciones de base de datos';
                else if (name.includes('log_') || name.includes('_log')) desc = 'Clase de conexion y logica de negocio';
                else if (name.includes('val_') || name.includes('_val')) desc = 'Validaciones y logica del lado cliente';
                else if (name.includes('adm_') || name.includes('_adm')) desc = 'Modulo de administracion y configuracion';
                else if (name.includes('alt_') || name.includes('_alt')) desc = 'Formulario de alta/edicion de registros';
                else if (name.includes('busq_') || name.includes('_busq')) desc = 'Modulo de busqueda y consultas';
                else if (name.includes('rep_') || name.includes('_rep')) desc = 'Generacion de reportes';
                else if (name.includes('pri_') || name.includes('_pri')) desc = 'Formato de impresion';
                else if (name.includes('fac_') || name.includes('_fac')) desc = 'Facturacion y documentos tributarios';
                else if (name.includes('ant_') || name.includes('_ant')) desc = 'Gestion de anticipos y pagos';
                else if (name.includes('tec_') || name.includes('_tec')) desc = 'Evaluacion tecnica';
                else if (name.includes('reg_') || name.includes('_reg')) desc = 'Registro de informacion';
                else if (name.includes('dashboard')) desc = 'Panel de control y estadisticas';
                else if (name.includes('config')) desc = 'Configuracion del sistema';
                else if (name.includes('turno')) desc = 'Gestion de turnos y horarios';
                else if (name.includes('manifiesto')) desc = 'Gestion de manifiestos';
                else if (name.includes('param')) desc = 'Parametros del sistema';
                else if (name.includes('ticket')) desc = 'Generacion de tickets';
                else if (name.includes('qr')) desc = 'Integracion con codigos QR';
                else if (name.includes('index')) desc = 'Punto de entrada principal';
                else if (type === 'js') desc = 'Logica de interfaz de usuario';
                else if (type === 'php' && folder.includes('FRONT')) desc = 'Interfaz de usuario';
                else if (type === 'php' && folder.includes('LOGICA')) desc = 'Logica de negocio';
                else desc = 'Modulo del sistema';
                
                return desc;
            }
            
            // Generar filas de archivos ordenadas por complejidad (alta -> media-alta -> media -> baja)
            let filesRows = '';
            const colors = { 'alta': '#ef4444', 'media-alta': '#f97316', 'media': '#ca8a04', 'baja': '#22c55e' };
            const complexityOrder = { 'alta': 0, 'media-alta': 1, 'media': 2, 'baja': 3 };
            const filesForPdf = [...pdfFiles].sort((a, b) => complexityOrder[a.complexity] - complexityOrder[b.complexity]);
            filesForPdf.forEach((file, i) => {
                const bg = i % 2 === 0 ? cream : paper;
                const compColor = colors[file.complexity];
                const sug = file.suggestedComplexity != null ? file.suggestedComplexity : file.complexity;
                filesRows += '<tr style="background:' + bg + ';page-break-inside:avoid;">' +
                    '<td style="' + td + '">' + escapeHtmlPdf(file.folder + '/' + file.name) + '</td>' +
                    '<td style="' + td + 'text-align:center;font-weight:600;">' + file.lines.toLocaleString() + '</td>' +
                    '<td style="' + td + 'text-align:center;">' + horasArchivoStr(file) + ' h</td>' +
                    '<td style="' + td + 'text-align:center;font-size:9px;">' + etiquetaComplejidad(sug) + '</td>' +
                    '<td style="' + td + 'text-align:center;color:' + compColor + ';font-weight:700;">' + file.complexity.toUpperCase() + '</td></tr>';
            });
            
            // Agrupar funcionalidades por modulo
            let funcionalidades = {};
            pdfFiles.forEach(file => {
                const folder = globalFolderOf(file.folder || 'ROOT');
                if (!funcionalidades[folder]) funcionalidades[folder] = [];
                const desc = getFileDescription(file.name, file.folder, file.type);
                if (!funcionalidades[folder].includes(desc)) funcionalidades[folder].push(desc);
            });
            
            let funcRows = '';
            Object.keys(funcionalidades).sort((a, b) => a.localeCompare(b, 'es')).forEach((folder, i) => {
                const bg = i % 2 === 0 ? cream : paper;
                funcRows += '<tr style="background:' + bg + ';"><td style="' + td + 'font-weight:700;color:' + tealDeep + ';">' + escapeHtmlPdf(folder) + '</td><td style="' + td + 'color:' + ink + ';">' + escapeHtmlPdf(funcionalidades[folder].join(', ')) + '</td></tr>';
            });
            
            let pdfCompareBlock = '';
            const chkPdfCmp = document.getElementById('chkPdfIncludeHoursCompare');
            if (chkPdfCmp && chkPdfCmp.checked && clientBaseline && pdfFiles.length) {
                const curM = computeMetrics(pdfFiles);
                const br = clientBaseline;
                const bL = Number(br.totalLines) || 0;
                const bH = Number(br.totalHours) || 0;
                const bFi = Number(br.fileCount) || 0;
                const dLi = curM.totalLines - bL;
                const dHi = curM.totalHours - bH;
                const dFi = curM.fileCount - bFi;
                const pctNote = Math.abs(bH) >= 1e-6
                    ? ' (' + (dHi >= 0 ? '+' : '') + ((100 * dHi) / bH).toFixed(1) + '% vs ref.)'
                    : '';
                const refNm = escapeHtmlPdf(br.fileName || 'Referencia');
                const srcLbl = br.source === 'pdf' ? 'PDF' : 'JSON';
                pdfCompareBlock =
                    '<div style="margin-bottom:20px;padding:14px 16px;background:' + mint + ';border:1px solid ' + line + ';border-radius:10px;page-break-inside:avoid;break-inside:avoid;">' +
                    pdfH3('Comparativa vs informe de referencia') +
                    '<p style="font-size:9px;color:' + muted + ';margin:0 0 10px 0;">Archivo: <strong style="color:' + ink + ';">' + refNm + '</strong> (' + srcLbl + ').</p>' +
                    '<table style="width:100%;border-collapse:collapse;font-size:10px;">' +
                    '<tr style="background:' + teal + ';color:#f3fffc;"><th style="' + th + '">Metrica</th><th style="' + th + 'text-align:right;">Actual</th><th style="' + th + 'text-align:right;">Referencia</th><th style="' + th + 'text-align:right;">Diferencia</th></tr>' +
                    '<tr style="background:' + paper + ';"><td style="' + td + 'font-weight:700;">Horas</td><td style="' + td + 'text-align:right;">' + curM.totalHours.toFixed(2) + ' h</td><td style="' + td + 'text-align:right;">' + bH.toFixed(2) + ' h</td><td style="' + td + 'text-align:right;">' + pdfDeltaTxt(curM.totalHours, bH) + pctNote + '</td></tr>' +
                    '<tr style="background:' + cream + ';"><td style="' + td + '">Lineas</td><td style="' + td + 'text-align:right;">' + curM.totalLines.toLocaleString() + '</td><td style="' + td + 'text-align:right;">' + bL.toLocaleString() + '</td><td style="' + td + 'text-align:right;">' + formatDeltaNum(dLi, 0, '') + '</td></tr>' +
                    '<tr style="background:' + paper + ';"><td style="' + td + '">Archivos</td><td style="' + td + 'text-align:right;">' + curM.fileCount + '</td><td style="' + td + 'text-align:right;">' + bFi + '</td><td style="' + td + 'text-align:right;">' + formatDeltaNum(dFi, 0, '') + '</td></tr>' +
                    '</table>' +
                    '</div>';
            }
            
            const pdfHtml = `
            <div style="font-family:Helvetica,Arial,sans-serif;padding:10px 18px 18px;max-width:800px;margin:0 auto;font-size:11px;line-height:1.35;background:${paper};color:${ink};">
                <div style="text-align:center;margin-bottom:18px;padding:16px 12px 14px;background:${cream};border-radius:12px;border:1px solid ${line};page-break-inside:avoid;">
                    <p style="margin:0 0 6px 0;font-size:9px;letter-spacing:0.22em;text-transform:uppercase;color:#c45c26;font-weight:700;">Analisis de codigo &middot; EXA</p>
                    <h1 style="color:${ink};font-size:24px;margin:0 0 6px 0;line-height:1.15;font-family:Georgia,'Times New Roman',serif;font-weight:600;letter-spacing:-0.02em;">Informe gerencial</h1>
                    <h2 style="color:${tealDeep};font-size:15px;margin:0 0 8px 0;line-height:1.3;font-family:Georgia,'Times New Roman',serif;font-weight:600;">${escapeHtmlPdf(projectName)}</h2>
                    <p style="color:${muted};font-size:10px;margin:0 0 6px 0;">${dateStr}</p>
                    ${modesPdfNote ? '<p style="color:' + muted + ';font-size:9px;margin:0 0 10px 0;">Conteo: ' + modesPdfNote + '</p>' : ''}
                    <span style="display:inline-block;background:${teal};color:#f3fffc;padding:5px 14px;border-radius:999px;font-size:9px;font-weight:700;letter-spacing:0.08em;">PROYECTO COMPLETADO</span>
                </div>
                
                <div style="margin-bottom:20px;page-break-inside:avoid;">
                    ${pdfH3('Metricas principales')}
                    <table style="width:100%;border-collapse:separate;border-spacing:8px 0;font-size:10px;">
                        <tr>
                            <td style="width:33%;background:${cream};border:1px solid ${line};border-radius:10px;padding:12px 10px;text-align:center;">
                                <div style="font-size:8px;letter-spacing:0.12em;text-transform:uppercase;color:${muted};font-weight:700;">Lineas</div>
                                <div style="font-family:Georgia,serif;font-size:20px;color:${ink};font-weight:700;margin-top:4px;">${totalLines.toLocaleString()}</div>
                            </td>
                            <td style="width:33%;background:${cream};border:1px solid ${line};border-radius:10px;padding:12px 10px;text-align:center;">
                                <div style="font-size:8px;letter-spacing:0.12em;text-transform:uppercase;color:${muted};font-weight:700;">Archivos</div>
                                <div style="font-family:Georgia,serif;font-size:20px;color:${ink};font-weight:700;margin-top:4px;">${pdfFiles.length}</div>
                            </td>
                            <td style="width:33%;background:${mint};border:1px solid ${line};border-radius:10px;padding:12px 10px;text-align:center;">
                                <div style="font-size:8px;letter-spacing:0.12em;text-transform:uppercase;color:${tealDeep};font-weight:700;">Horas</div>
                                <div style="font-family:Georgia,serif;font-size:20px;color:${tealDeep};font-weight:700;margin-top:4px;">${totalHours.toFixed(2)} h</div>
                            </td>
                        </tr>
                    </table>
                </div>
                
                ${pdfCompareBlock}
                
                <div style="margin-bottom:20px;page-break-inside:avoid;">
                    ${pdfH3('Por carpeta / modulo')}
                    <table style="width:100%;border-collapse:collapse;font-size:10px;">
                        ${folderHeadPdf}
                        ${folderRowsPdf}
                    </table>
                </div>
                
                <div style="margin-bottom:20px;page-break-inside:avoid;">
                    ${pdfH3('Por tecnologia')}
                    <table style="width:100%;border-collapse:collapse;font-size:10px;">
                        <tr style="background:${teal};color:#f3fffc;"><th style="${th}">Tipo</th><th style="${th}text-align:center;">Archivos</th><th style="${th}text-align:right;">Lineas</th></tr>
                        <tr style="background:${paper};"><td style="${td}">PHP</td><td style="${td}text-align:center;">${php.files}</td><td style="${td}text-align:right;">${php.lines.toLocaleString()}</td></tr>
                        <tr style="background:${cream};"><td style="${td}">JavaScript</td><td style="${td}text-align:center;">${js.files}</td><td style="${td}text-align:right;">${js.lines.toLocaleString()}</td></tr>
                        <tr style="background:${paper};"><td style="${td}">HTML</td><td style="${td}text-align:center;">${html.files}</td><td style="${td}text-align:right;">${html.lines.toLocaleString()}</td></tr>
                        <tr style="background:${tealDeep};color:#fbf7f0;"><td style="padding:7px 8px;font-weight:700;">TOTAL</td><td style="padding:7px 8px;text-align:center;font-weight:700;">${pdfFiles.length}</td><td style="padding:7px 8px;text-align:right;font-weight:700;">${totalLines.toLocaleString()}</td></tr>
                    </table>
                </div>
                
                <div style="margin-bottom:20px;page-break-inside:avoid;">
                    ${pdfH3('Por complejidad')}
                    <table style="width:100%;border-collapse:collapse;font-size:10px;">
                        <tr style="background:${teal};color:#f3fffc;"><th style="${th}">Nivel</th><th style="${th}text-align:center;">Arch.</th><th style="${th}text-align:right;">Lineas</th><th style="${th}text-align:right;">Horas</th></tr>
                        <tr style="background:#f8e4de;"><td style="${td}color:#b42318;font-weight:700;">ALTA</td><td style="${td}text-align:center;">${complexity.alta.files}</td><td style="${td}text-align:right;">${complexity.alta.lines.toLocaleString()}</td><td style="${td}text-align:right;">${complexity.alta.hours.toFixed(2)} h</td></tr>
                        <tr style="background:#f6e6d8;"><td style="${td}color:#c45c26;font-weight:700;">MEDIA-ALTA</td><td style="${td}text-align:center;">${complexity['media-alta'].files}</td><td style="${td}text-align:right;">${complexity['media-alta'].lines.toLocaleString()}</td><td style="${td}text-align:right;">${complexity['media-alta'].hours.toFixed(2)} h</td></tr>
                        <tr style="background:#f6efd4;"><td style="${td}color:#8a7318;font-weight:700;">MEDIA</td><td style="${td}text-align:center;">${complexity.media.files}</td><td style="${td}text-align:right;">${complexity.media.lines.toLocaleString()}</td><td style="${td}text-align:right;">${complexity.media.hours.toFixed(2)} h</td></tr>
                        <tr style="background:#e4f0e8;"><td style="${td}color:#2f6b4f;font-weight:700;">BAJA</td><td style="${td}text-align:center;">${complexity.baja.files}</td><td style="${td}text-align:right;">${complexity.baja.lines.toLocaleString()}</td><td style="${td}text-align:right;">${complexity.baja.hours.toFixed(2)} h</td></tr>
                        <tr style="background:${tealDeep};color:#fbf7f0;"><td style="padding:7px 8px;font-weight:700;">TOTAL</td><td style="padding:7px 8px;text-align:center;font-weight:700;">${pdfFiles.length}</td><td style="padding:7px 8px;text-align:right;font-weight:700;">${totalLines.toLocaleString()}</td><td style="padding:7px 8px;text-align:right;font-weight:700;">${totalHours.toFixed(2)} h</td></tr>
                    </table>
                </div>
                
                <div style="margin-bottom:20px;page-break-inside:avoid;">
                    ${pdfH3('Funcionalidades por modulo')}
                    <table style="width:100%;border-collapse:collapse;font-size:10px;">
                        <tr style="background:${teal};color:#f3fffc;"><th style="${th}width:22%;">Modulo</th><th style="${th}">Funcionalidades</th></tr>
                        ${funcRows}
                    </table>
                </div>
                
                <div style="margin-bottom:18px;padding-top:4px;page-break-inside:auto;">
                    ${pdfH3('Detalle de archivos desarrollados')}
                    <table style="width:100%;border-collapse:collapse;font-size:10px;page-break-inside:auto;">
                        <tr style="background:${teal};color:#f3fffc;"><th style="${th}">Archivo</th><th style="${th}text-align:center;width:72px;">Lineas</th><th style="${th}text-align:center;width:64px;">Horas</th><th style="${th}text-align:center;width:56px;">Sug.</th><th style="${th}text-align:center;width:88px;">Complej.</th></tr>
                        ${filesRows}
                    </table>
                </div>
                
                <div style="margin-top:12px;padding-top:10px;border-top:1px solid ${line};text-align:center;page-break-inside:avoid;">
                    <p style="font-size:8px;color:${muted};margin:0 0 6px 0;word-break:break-all;">DASHCMP|LINEAS:${totalLines}|HORAS:${totalHours.toFixed(2)}|ARCH:${pdfFiles.length}|</p>
                    <p style="color:${muted};font-size:9px;margin:0;">Documento generado automaticamente &middot; Dashboard de Proyectos &middot; ${dateStr}</p>
                </div>
            </div>`;
            
            const element = document.getElementById('pdfContent');
            element.style.cssText = 'display:block;position:relative;margin:0;padding:0;width:794px;max-width:100%;background:#fbf7f0;box-sizing:border-box;overflow:visible;';
            element.innerHTML = pdfHtml;
            window.scrollTo(0, 0);
            
            const opt = {
                margin: [5, 8, 8, 8],
                filename: 'Informe_' + projectName + '_' + new Date().toISOString().split('T')[0] + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['css', 'legacy'] }
            };
            
            html2pdf().set(opt).from(element).save().then(function() {
                element.style.cssText = 'display:none;';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-pdf"></i> PDF Gerencial';
            }, function(err) {
                element.style.cssText = 'display:none;';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-pdf"></i> PDF Gerencial';
                showToast('Error PDF: ' + (err && err.message ? err.message : String(err)), 'error');
            });
        }
        
        // Toast
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const icon = toast.querySelector('i');
            document.getElementById('toastMessage').textContent = message;
            
            icon.className = type === 'success' ? 'fas fa-check-circle' : 
                             type === 'warning' ? 'fas fa-exclamation-circle' : 'fas fa-times-circle';
            icon.style.color = type === 'success' ? 'var(--success)' : 
                               type === 'warning' ? 'var(--warning)' : 'var(--danger)';
            
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
        
        // Initial setup
        // document.querySelectorAll('.filter-btn').forEach(btn => { ... }); // Removed old logic
        
        function loadDevCount() {
            try {
                const s = localStorage.getItem(DEV_COUNT_KEY);
                const n = parseInt(s, 10);
                if (n >= 1 && n <= 30) devCount = n;
            } catch (e) { /* ignore */ }
            const inp = document.getElementById('devCountInput');
            if (inp) inp.value = String(devCount);
        }

        function cambiarNumeroDevs(value) {
            const n = Math.max(1, Math.min(30, parseInt(value, 10) || 1));
            devCount = n;
            const inp = document.getElementById('devCountInput');
            if (inp && String(inp.value) !== String(n)) inp.value = String(n);
            try { localStorage.setItem(DEV_COUNT_KEY, String(n)); } catch (e) { /* ignore */ }
            updateStats();
        }

        // Fecha
        function setCurrentDate() {
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = new Date().toLocaleDateString('es-ES', options);
        }
        
        async function loadServerProjectPickList() {
            const sel = document.getElementById('serverProjectPick');
            if (!sel) return;
            sel.innerHTML = '<option value="">&mdash; Cargando&hellip; &mdash;</option>';
            try {
                const r = await fetch('dashboard_scan.php?action=list_allowed');
                const j = await r.json();
                sel.innerHTML = '';
                const o0 = document.createElement('option');
                o0.value = '';
                o0.textContent = '\u2014 Elegir carpeta (se agrega a la lista) \u2014';
                sel.appendChild(o0);
                if (j.success && j.projects && j.projects.length) {
                    j.projects.forEach(function(p) {
                        const o = document.createElement('option');
                        o.value = p.path;
                        o.textContent = p.label + ' \u2014 ' + p.path;
                        sel.appendChild(o);
                    });
                }
            } catch (e) {
                sel.innerHTML = '';
                const o = document.createElement('option');
                o.value = '';
                o.textContent = 'No se pudo cargar la lista; escriba la ruta a mano';
                sel.appendChild(o);
            }
        }
        
        // Init
        setCurrentDate();
        loadDevCount();
        renderProjectList();
        (function initServerProjectPick() {
            const sel = document.getElementById('serverProjectPick');
            if (sel) {
                sel.addEventListener('change', function() {
                    if (this.value) {
                        document.getElementById('projectPath').value = this.value;
                        agregarCarpetaACola(this.value);
                        this.value = '';
                    }
                });
            }
            loadServerProjectPickList();
        })();
        renderScanQueue();
        (function initPathEnterAdd() {
            const pathEl = document.getElementById('projectPath');
            if (!pathEl) return;
            pathEl.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    agregarCarpetaACola();
                }
            });
        })();
        (function initBaselineUpload() {
            const inp = document.getElementById('clientBaselineInput');
            if (inp) {
                inp.addEventListener('change', function() {
                    const f = this.files && this.files[0];
                    if (f) procesarArchivoReferenciaCliente(f);
                });
            }
            loadClientBaselineFromSession();
            renderClientBaselinePanel();
            syncHoursComparePdfOption();
            syncDiffFilterControl();
            updateBaselineEnrichButtons();
            const enrichInp = document.getElementById('baselineFilesInput');
            if (enrichInp) {
                enrichInp.addEventListener('change', function() {
                    const f = this.files && this.files[0];
                    if (f) enriquecerReferenciaConListado(f);
                    this.value = '';
                });
            }
        })();
    </script>
</body>
</html>
