<?php
$isWindows  = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$docRoot    = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$sepChar    = $isWindows ? '\\' : '/';

if ($isWindows) {
    // Convertir la raíz del documento a formato Windows para el ejemplo
    $docRootWin  = str_replace('/', '\\', $docRoot);
    $examplePath = $docRootWin . '\\mi_proyecto';
} else {
    $examplePath = $docRoot . '/mi_proyecto';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Proyectos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --border: #e2e8f0;
            --alta: #ef4444;
            --media-alta: #f97316;
            --media: #eab308;
            --baja: #22c55e;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header {
            background: white;
            border-radius: 16px;
            padding: 24px 32px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .header h1 { color: var(--dark); font-size: 28px; font-weight: 700; }
        .header h1 i { color: var(--primary); margin-right: 12px; }
        
        .header-info { text-align: right; color: var(--gray); }
        .header-info .date { font-size: 14px; }
        
        .header-actions { display: flex; gap: 12px; align-items: center; margin-top: 12px; flex-wrap: wrap; }
        
        .btn {
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-pdf {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .btn-pdf:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4); }
        
        .btn-scan {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }
        
        .btn-scan:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4); }
        
        .btn-config {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .btn-config:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4); }
        
        .btn:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
        
        .header-info .version {
            font-size: 12px;
            background: var(--success);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }
        
        /* Selector de proyecto */
        .project-selector {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .project-selector label {
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .project-selector label i { color: var(--primary); }
        
        .project-input {
            flex: 1;
            min-width: 300px;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .project-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .project-list {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .project-chip {
            padding: 8px 16px;
            background: var(--light);
            border: 2px solid var(--border);
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .project-chip:hover, .project-chip.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 4px;
            height: 100%;
        }
        
        .stat-card.total::before { background: var(--primary); }
        .stat-card.php::before { background: #777BB4; }
        .stat-card.js::before { background: #F7DF1E; }
        .stat-card.html::before { background: #E34F26; }
        .stat-card.horas::before { background: var(--info); }
        .stat-card.completado::before { background: var(--success); }
        
        .stat-card .icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
        }
        
        .stat-card.total .icon { background: #eef2ff; color: var(--primary); }
        .stat-card.php .icon { background: #f3f0ff; color: #777BB4; }
        .stat-card.js .icon { background: #fef9c3; color: #ca8a04; }
        .stat-card.html .icon { background: #fee2e2; color: #E34F26; }
        .stat-card.horas .icon { background: #dbeafe; color: var(--info); }
        .stat-card.completado .icon { background: #dcfce7; color: var(--success); }
        
        .stat-card .label {
            font-size: 13px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .stat-card .value { font-size: 28px; font-weight: 700; color: var(--dark); }
        .stat-card .sub { font-size: 12px; color: var(--gray); margin-top: 4px; }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 24px;
        }
        
        @media (max-width: 1100px) { .main-grid { grid-template-columns: 1fr; } }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 { font-size: 18px; font-weight: 600; color: var(--dark); }
        .card-header h2 i { margin-right: 10px; color: var(--primary); }
        .card-body { padding: 24px; }
        
        .filters { display: flex; gap: 10px; flex-wrap: wrap; }
        
        .filter-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--light);
            color: var(--gray);
        }
        
        .filter-btn:hover, .filter-btn.active { background: var(--primary); color: white; }
        
        .files-table { width: 100%; border-collapse: collapse; }
        
        
        /* .files-table th moved to sticky definition above */
        
        .files-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }
        
        .files-table tr:hover { background: var(--light); }
        
        .file-name { display: flex; align-items: center; gap: 10px; }
        
        .file-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .file-icon.php { background: #f3f0ff; color: #777BB4; }
        .file-icon.js { background: #fef9c3; color: #ca8a04; }
        .file-icon.html { background: #fee2e2; color: #E34F26; }
        .file-icon.css { background: #dbeafe; color: #2563eb; }
        
        .complexity-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .complexity-badge:hover { transform: scale(1.05); }
        
        .complexity-badge.alta { background: #fef2f2; color: var(--alta); }
        .complexity-badge.media-alta { background: #fff7ed; color: var(--media-alta); }
        .complexity-badge.media { background: #fefce8; color: #a16207; }
        .complexity-badge.baja { background: #f0fdf4; color: var(--baja); }
        
        .complexity-select {
            padding: 4px 8px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            background: white;
        }
        
        .sidebar-card { margin-bottom: 24px; }
        
        .progress-ring-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }
        
        .progress-ring { position: relative; width: 180px; height: 180px; }
        .progress-ring svg { transform: rotate(-90deg); }
        .progress-ring circle { fill: none; stroke-width: 12; }
        .progress-ring .bg { stroke: var(--border); }
        .progress-ring .progress { stroke: var(--success); stroke-linecap: round; transition: stroke-dashoffset 0.5s ease; }
        
        .progress-ring .percentage {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .progress-ring .percentage .value { font-size: 42px; font-weight: 700; color: var(--success); }
        .progress-ring .percentage .label { font-size: 14px; color: var(--gray); }
        
        .complexity-list { display: flex; flex-direction: column; gap: 12px; }
        
        .complexity-item { display: flex; align-items: center; gap: 12px; }
        .complexity-item .dot { width: 12px; height: 12px; border-radius: 50%; }
        .complexity-item .dot.alta { background: var(--alta); }
        .complexity-item .dot.media-alta { background: var(--media-alta); }
        .complexity-item .dot.media { background: var(--media); }
        .complexity-item .dot.baja { background: var(--baja); }
        .complexity-item .info { flex: 1; }
        .complexity-item .name { font-size: 14px; font-weight: 500; color: var(--dark); }
        
        .complexity-item .bar {
            height: 6px;
            background: var(--border);
            border-radius: 3px;
            margin-top: 6px;
            overflow: hidden;
        }
        
        .complexity-item .bar-fill { height: 100%; border-radius: 3px; transition: width 0.5s ease; }
        .complexity-item .bar-fill.alta { background: var(--alta); }
        .complexity-item .bar-fill.media-alta { background: var(--media-alta); }
        .complexity-item .bar-fill.media { background: var(--media); }
        .complexity-item .bar-fill.baja { background: var(--baja); }
        
        .complexity-item .stats { text-align: right; font-size: 13px; }
        .complexity-item .stats .hours { font-weight: 600; color: var(--dark); }
        .complexity-item .stats .files { color: var(--gray); }
        
        .time-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        
        .time-item {
            background: var(--light);
            padding: 16px;
            border-radius: 12px;
            text-align: center;
        }
        
        .time-item .value { font-size: 24px; font-weight: 700; color: var(--primary); }
        .time-item .label { font-size: 12px; color: var(--gray); margin-top: 4px; }
        
        .table-container { 
            height: 600px;
            overflow-y: auto; 
            border-bottom: 1px solid var(--border);
        }
        .table-container::-webkit-scrollbar { width: 8px; }
        .table-container::-webkit-scrollbar-track { background: var(--light); border-radius: 4px; }
        .table-container::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
        .table-container::-webkit-scrollbar-thumb:hover { background: var(--gray); }

        .files-table th {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border);
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        
        .work-summary {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .work-summary h3 { color: var(--success); font-size: 16px; margin-bottom: 8px; }
        .work-summary p { color: #166534; font-size: 13px; }
        
        /* Loading */
        .loading {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .loading.show { display: flex; }
        
        .loading-content {
            background: white;
            padding: 40px;
            border-radius: 16px;
            text-align: center;
        }
        
        .loading-content i { font-size: 48px; color: var(--primary); margin-bottom: 16px; }
        .loading-content p { color: var(--dark); font-size: 16px; }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 24px; right: 24px;
            background: var(--dark);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 14px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast i { margin-right: 10px; color: var(--success); }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        
        .empty-state i { font-size: 64px; margin-bottom: 20px; color: var(--border); }
        .empty-state h3 { color: var(--dark); margin-bottom: 10px; }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; text-align: center; }
            .header-info { text-align: center; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .files-table { display: block; overflow-x: auto; }
        }
        
        .no-print { }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="container">
        <!-- Selector de Proyecto -->
        <div class="project-selector no-print">
            <label><i class="fas fa-folder-open"></i> Proyecto:</label>
            <input type="text" class="project-input" id="projectPath" placeholder="Ej: <?php echo htmlspecialchars($examplePath); ?>" value="">
            
            <select id="scanMode" class="project-input" style="max-width: 200px; min-width: auto;">
                <option value="normal">Conteo Normal (Todas)</option>
                <option value="no_empty">Sin Espacios (No vacías)</option>
            </select>

            <button class="btn btn-scan" onclick="escanearProyecto()">
                <i class="fas fa-search"></i> Escanear
            </button>
            <div class="project-list" id="projectList">
                <!-- Proyectos guardados -->
            </div>
        </div>
        
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-layer-group"></i><span id="projectName">Selecciona un Proyecto</span></h1>
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
                </div>
            </div>
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
        
        <!-- Main Content -->
        <div class="main-grid">
            <!-- Tabla de archivos -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-folder-open"></i>Archivos del Proyecto</h2>
                    <div class="filters no-print" style="align-items: center;">
                        <div class="type-filters">
                            <button class="filter-btn active" onclick="setFilter('type', 'all', this)">Todos</button>
                            <button class="filter-btn" onclick="setFilter('type', 'php', this)">PHP</button>
                            <button class="filter-btn" onclick="setFilter('type', 'js', this)">JS</button>
                            <button class="filter-btn" onclick="setFilter('type', 'html', this)">HTML</button>
                        </div>
                        <div style="width: 1px; height: 24px; background: #e2e8f0; margin: 0 10px;"></div>
                        <div class="complexity-filters">
                            <select id="complexityFilter" onchange="setFilter('complexity', this.value)" style="padding: 8px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; color: #64748b; outline: none; cursor: pointer;">
                                <option value="all">Todas las complejidades</option>
                                <option value="alta">Alta</option>
                                <option value="media-alta">Media-Alta</option>
                                <option value="media">Media</option>
                                <option value="baja">Baja</option>
                            </select>
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
                                <th>Complejidad</th>
                            </tr>
                        </thead>
                        <tbody id="filesTableBody">
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="fas fa-folder-open"></i>
                                        <h3>Sin proyecto seleccionado</h3>
                                        <p>Ingresa la ruta de un proyecto y haz clic en "Escanear"</p>
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
                    <div class="card-header">
                        <h2><i class="fas fa-calendar-check"></i>Tiempo (3 devs)</h2>
                    </div>
                    <div class="card-body">
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
        let currentTypeFilter = 'all';
        let currentComplexityFilter = 'all';
        const STORAGE_KEY = 'dashboard_projects';
        
        // Tasas de lineas por hora segun complejidad
        const RATES = {
            'alta': 25,
            'media-alta': 35,
            'media': 45,
            'baja': 60
        };
        
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
                const name = path.split(/[\\\/]/).pop();
                html += `<div class="project-chip" onclick="cargarProyecto('${path.replace(/\\/g, '\\\\')}')">${name}</div>`;
            });
            
            list.innerHTML = html;
        }
        
        // Escanear proyecto
        function escanearProyecto() {
            const path = document.getElementById('projectPath').value.trim();
            const mode = document.getElementById('scanMode').value;
            
            if (!path) {
                showToast('Ingresa una ruta de proyecto', 'warning');
                return;
            }
            
            document.getElementById('loading').classList.add('show');
            
            // Llamar al backend PHP para escanear
            fetch('dashboard_scan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ path: path, mode: mode })
            })
            .then(response => response.json())
            .then(async data => {
                document.getElementById('loading').classList.remove('show');
                
                if (data.error) {
                    showToast(data.error, 'error');
                    return;
                }
                
                currentProject = path;
                projectFiles = data.files;
                
                // Cargar complejidades guardadas si existen
                const projects = await loadSavedProjects();
                if (projects[path]) {
                    projectFiles.forEach(file => {
                        const key = (file.folder !== 'ROOT' ? file.folder + '/' : '') + file.name;
                        if (projects[path].complexities && projects[path].complexities[key]) {
                            file.complexity = projects[path].complexities[key];
                        }
                    });
                }
                
                // Actualizar UI
                const projectName = path.split(/[\\\/]/).pop();
                document.getElementById('projectName').textContent = 'Proyecto ' + projectName;
                document.getElementById('projectStatus').textContent = 'Escaneado';
                document.getElementById('btnSave').disabled = false;
                document.getElementById('btnPdf').disabled = false;
                
                renderTable();
                updateStats();
                showToast('Proyecto escaneado: ' + data.files.length + ' archivos');
            })
            .catch(error => {
                document.getElementById('loading').classList.remove('show');
                showToast('Error al escanear: ' + error.message, 'error');
            });
        }
        
        // Cargar proyecto guardado
        function cargarProyecto(path) {
            document.getElementById('projectPath').value = path;
            escanearProyecto();
        }
        
        // Guardar configuracion
        async function guardarConfiguracion() {
            if (!currentProject || projectFiles.length === 0) return;
            
            const projects = await loadSavedProjects();
            
            // Guardar complejidades usando folder/name como clave para evitar colisiones
            const complexities = {};
            projectFiles.forEach(file => {
                const key = (file.folder !== 'ROOT' ? file.folder + '/' : '') + file.name;
                complexities[key] = file.complexity;
            });
            
            projects[currentProject] = {
                name: currentProject.split(/[\\\/]/).pop(),
                complexities: complexities,
                lastScan: new Date().toISOString()
            };
            
            const success = await saveProjects(projects);
            if (success) {
                renderProjectList();
                showToast('Configuracion guardada');
            }
        }
        
        // Cambiar complejidad
        function cambiarComplejidad(index, newComplexity) {
            projectFiles[index].complexity = newComplexity;
            projectFiles[index].hours = (projectFiles[index].lines / RATES[newComplexity]).toFixed(2);
            renderTable();
            updateStats();
        }
        
        // Set Filter
        function setFilter(type, value, element) {
            if (type === 'type') {
                currentTypeFilter = value;
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                if (element) element.classList.add('active');
            } else if (type === 'complexity') {
                currentComplexityFilter = value;
            }
            renderTable();
        }

        // Renderizar tabla
        function renderTable() {
            const tbody = document.getElementById('filesTableBody');
            
            if (projectFiles.length === 0) {
                tbody.innerHTML = `
                    <tr><td colspan="4">
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <h3>Sin proyecto seleccionado</h3>
                            <p>Ingresa la ruta de un proyecto y haz clic en "Escanear"</p>
                        </div>
                    </td></tr>`;
                return;
            }
            
            let html = '';
            projectFiles.forEach((file, index) => {
                // Apply filters
                if (currentTypeFilter !== 'all' && file.type !== currentTypeFilter) return;
                if (currentComplexityFilter !== 'all' && file.complexity !== currentComplexityFilter) return;

                const iconClass = file.type === 'php' ? 'fab fa-php' : 
                                  file.type === 'js' ? 'fab fa-js' : 
                                  file.type === 'css' ? 'fab fa-css3-alt' : 
                                  file.type === 'sql' ? 'fas fa-database' : 'fab fa-html5';
                
                html += `
                    <tr>
                        <td>
                            <div class="file-name">
                                <div class="file-icon ${file.type}">
                                    <i class="${iconClass}"></i>
                                </div>
                                <div>
                                    <strong>${file.name}</strong>
                                    <div style="font-size: 11px; color: var(--gray);">${file.folder}/</div>
                                </div>
                            </div>
                        </td>
                        <td><strong>${file.lines.toLocaleString()}</strong></td>
                        <td><strong>${file.hours} h</strong></td>
                        <td>
                            <select class="complexity-select" onchange="cambiarComplejidad(${index}, this.value)">
                                <option value="alta" ${file.complexity === 'alta' ? 'selected' : ''}>Alta</option>
                                <option value="media-alta" ${file.complexity === 'media-alta' ? 'selected' : ''}>Media-Alta</option>
                                <option value="media" ${file.complexity === 'media' ? 'selected' : ''}>Media</option>
                                <option value="baja" ${file.complexity === 'baja' ? 'selected' : ''}>Baja</option>
                            </select>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html || '<tr><td colspan="4" style="text-align:center;padding:20px;">No hay archivos con este filtro</td></tr>';
        }
        
        // Actualizar estadisticas
        function updateStats() {
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
            
            projectFiles.forEach(file => {
                totalLines += file.lines;
                totalHours += file.lines / RATES[file.complexity];
                
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
            document.getElementById('totalFiles').textContent = projectFiles.length + ' archivos';
            document.getElementById('phpLines').textContent = php.lines.toLocaleString();
            document.getElementById('phpFiles').textContent = php.files + ' archivos';
            document.getElementById('jsLines').textContent = js.lines.toLocaleString();
            document.getElementById('jsFiles').textContent = js.files + ' archivos';
            document.getElementById('htmlLines').textContent = html.lines.toLocaleString();
            document.getElementById('htmlFiles').textContent = html.files + ' archivos';
            document.getElementById('totalHours').textContent = totalHours.toFixed(2);
            
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
            
            // Tiempo
            const hoursPerDay = 16; // 3 devs x 8h / dia = 24h, pero usamos 16h efectivas
            document.getElementById('timeHours').textContent = totalHours.toFixed(2);
            document.getElementById('timeDays').textContent = (totalHours / hoursPerDay).toFixed(1);
            document.getElementById('timeWeeks').textContent = (totalHours / (hoursPerDay * 5)).toFixed(2);
            document.getElementById('timeMonths').textContent = (totalHours / (hoursPerDay * 20)).toFixed(2);
        }
        
        // Generar PDF
        function generarPDF() {
            if (projectFiles.length === 0) return;
            
            const btn = document.getElementById('btnPdf');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            
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
            
            projectFiles.forEach(file => {
                totalLines += file.lines;
                totalHours += file.lines / RATES[file.complexity];
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
            totalHours = projectFiles.reduce((s, f) => s + (f.lines / RATES[f.complexity]), 0);
            
            const projectName = currentProject.split(/[\\\/]/).pop();
            const dateStr = new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            
            // Funcion para generar descripcion de funcionalidad
            function getFileDescription(filename, folder, type) {
                const name = filename.toLowerCase();
                let desc = '';
                
                // Por tipo de archivo
                if (type === 'html') return 'Plantilla de visualizacion e impresion';
                if (type === 'css') return 'Estilos y diseño visual';
                
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
            const filesForPdf = [...projectFiles].sort((a, b) => complexityOrder[a.complexity] - complexityOrder[b.complexity]);
            filesForPdf.forEach((file, i) => {
                const bg = i % 2 === 0 ? '#f8fafc' : 'white';
                const compColor = colors[file.complexity];
                filesRows += `<tr style="background:${bg};page-break-inside:avoid;"><td style="padding:8px;border:1px solid #e2e8f0;">${file.folder}/${file.name}</td><td style="padding:8px;border:1px solid #e2e8f0;text-align:center;font-weight:600;">${file.lines.toLocaleString()}</td><td style="padding:8px;border:1px solid #e2e8f0;text-align:center;">${file.hours} h</td><td style="padding:8px;border:1px solid #e2e8f0;text-align:center;color:${compColor};font-weight:600;">${file.complexity.toUpperCase()}</td></tr>`;
            });
            
            // Agrupar funcionalidades por modulo
            let funcionalidades = {};
            projectFiles.forEach(file => {
                const folder = file.folder || 'ROOT';
                if (!funcionalidades[folder]) funcionalidades[folder] = [];
                const desc = getFileDescription(file.name, file.folder, file.type);
                if (!funcionalidades[folder].includes(desc)) funcionalidades[folder].push(desc);
            });
            
            let funcRows = '';
            Object.keys(funcionalidades).forEach((folder, i) => {
                const bg = i % 2 === 0 ? '#f8fafc' : 'white';
                funcRows += `<tr style="background:${bg};"><td style="padding:5px;border:1px solid #e2e8f0;font-weight:600;color:#6366f1;">${folder}</td><td style="padding:5px;border:1px solid #e2e8f0;">${funcionalidades[folder].join(', ')}</td></tr>`;
            });
            
            const pdfHtml = `
            <div style="font-family:'Helvetica',sans-serif;padding:25px;max-width:800px;margin:0 auto;font-size:11px;line-height:1.3;">
                <div style="text-align:center;margin-bottom:20px;border-bottom:2px solid #6366f1;padding-bottom:15px;">
                    <h1 style="color:#1e293b;font-size:24px;margin:0 0 8px 0;">INFORME GERENCIAL</h1>
                    <h2 style="color:#6366f1;font-size:18px;margin:0 0 10px 0;">Proyecto ${projectName}</h2>
                    <p style="color:#64748b;font-size:11px;margin:0 0 10px 0;">Fecha: ${dateStr}</p>
                    <span style="background:#22c55e;color:white;padding:4px 12px;border-radius:12px;font-size:10px;font-weight:600;">PROYECTO COMPLETADO</span>
                </div>
                
                <div style="margin-bottom:25px;">
                    <h3 style="color:#1e293b;font-size:12px;margin:0 0 8px 0;border-left:3px solid #6366f1;padding-left:8px;">METRICAS PRINCIPALES</h3>
                    <table style="width:100%;border-collapse:collapse;font-size:10px;">
                        <tr style="background:#f8fafc;"><td style="padding:5px 8px;border:1px solid #e2e8f0;font-weight:600;">Total Lineas</td><td style="padding:5px 8px;border:1px solid #e2e8f0;color:#6366f1;font-weight:700;">${totalLines.toLocaleString()}</td></tr>
                        <tr><td style="padding:5px 8px;border:1px solid #e2e8f0;font-weight:600;">Archivos</td><td style="padding:5px 8px;border:1px solid #e2e8f0;">${projectFiles.length}</td></tr>
                        <tr style="background:#f8fafc;"><td style="padding:5px 8px;border:1px solid #e2e8f0;font-weight:600;">Total Horas</td><td style="padding:5px 8px;border:1px solid #e2e8f0;color:#3b82f6;font-weight:700;">${totalHours.toFixed(2)} h</td></tr>
                    </table>
                </div>
                
                <div style="margin-bottom:25px;">
                    <h3 style="color:#1e293b;font-size:12px;margin:0 0 8px 0;border-left:3px solid #6366f1;padding-left:8px;">POR TECNOLOGIA</h3>
                    <table style="width:100%;border-collapse:collapse;font-size:10px;">
                        <tr style="background:#6366f1;color:white;"><th style="padding:5px;text-align:left;">Tipo</th><th style="padding:5px;text-align:center;">Archivos</th><th style="padding:5px;text-align:center;">Lineas</th></tr>
                        <tr style="background:#f8fafc;"><td style="padding:5px;border:1px solid #e2e8f0;">PHP</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${php.files}</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${php.lines.toLocaleString()}</td></tr>
                        <tr><td style="padding:5px;border:1px solid #e2e8f0;">JavaScript</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${js.files}</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${js.lines.toLocaleString()}</td></tr>
                        <tr style="background:#f8fafc;"><td style="padding:5px;border:1px solid #e2e8f0;">HTML</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${html.files}</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${html.lines.toLocaleString()}</td></tr>
                        <tr style="background:#1e293b;color:white;"><td style="padding:5px;font-weight:700;">TOTAL</td><td style="padding:5px;text-align:center;font-weight:700;">${projectFiles.length}</td><td style="padding:5px;text-align:center;font-weight:700;">${totalLines.toLocaleString()}</td></tr>
                    </table>
                </div>
                
                <div style="margin-bottom:25px;">
                    <h3 style="color:#1e293b;font-size:12px;margin:0 0 8px 0;border-left:3px solid #6366f1;padding-left:8px;">POR COMPLEJIDAD</h3>
                    <table style="width:100%;border-collapse:collapse;font-size:10px;">
                        <tr style="background:#6366f1;color:white;"><th style="padding:5px;text-align:left;">Nivel</th><th style="padding:5px;text-align:center;">Arch.</th><th style="padding:5px;text-align:center;">Lineas</th><th style="padding:5px;text-align:center;">Horas</th></tr>
                        <tr style="background:#fef2f2;"><td style="padding:5px;border:1px solid #e2e8f0;color:#ef4444;font-weight:600;">ALTA</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${complexity.alta.files}</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${complexity.alta.lines.toLocaleString()}</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${complexity.alta.hours.toFixed(2)} h</td></tr>
                        <tr style="background:#fff7ed;"><td style="padding:5px;border:1px solid #e2e8f0;color:#f97316;font-weight:600;">MEDIA-ALTA</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${complexity['media-alta'].files}</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${complexity['media-alta'].lines.toLocaleString()}</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${complexity['media-alta'].hours.toFixed(2)} h</td></tr>
                        <tr style="background:#fefce8;"><td style="padding:5px;border:1px solid #e2e8f0;color:#ca8a04;font-weight:600;">MEDIA</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${complexity.media.files}</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${complexity.media.lines.toLocaleString()}</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${complexity.media.hours.toFixed(2)} h</td></tr>
                        <tr style="background:#f0fdf4;"><td style="padding:5px;border:1px solid #e2e8f0;color:#22c55e;font-weight:600;">BAJA</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${complexity.baja.files}</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${complexity.baja.lines.toLocaleString()}</td><td style="padding:5px;border:1px solid #e2e8f0;text-align:center;">${complexity.baja.hours.toFixed(2)} h</td></tr>
                        <tr style="background:#1e293b;color:white;"><td style="padding:5px;font-weight:700;">TOTAL</td><td style="padding:5px;text-align:center;font-weight:700;">${projectFiles.length}</td><td style="padding:5px;text-align:center;font-weight:700;">${totalLines.toLocaleString()}</td><td style="padding:5px;text-align:center;font-weight:700;">${totalHours.toFixed(2)} h</td></tr>
                    </table>
                </div>
                
                <div style="margin-bottom:25px;">
                    <h3 style="color:#1e293b;font-size:12px;margin:0 0 8px 0;border-left:3px solid #6366f1;padding-left:8px;">FUNCIONALIDADES POR MODULO</h3>
                    <table style="width:100%;border-collapse:collapse;font-size:10px;">
                        <tr style="background:#6366f1;color:white;"><th style="padding:6px;text-align:left;width:20%;">Modulo</th><th style="padding:6px;text-align:left;">Funcionalidades</th></tr>
                        ${funcRows}
                    </table>
                </div>
                
                <div style="page-break-before:always;padding-top:15px;">
                    <h3 style="color:#1e293b;font-size:12px;margin:0 0 8px 0;border-left:3px solid #6366f1;padding-left:8px;">DETALLE DE ARCHIVOS DESARROLLADOS</h3>
                    <table style="width:100%;border-collapse:collapse;font-size:10px;">
                        <tr style="background:#6366f1;color:white;"><th style="padding:8px;text-align:left;">ARCHIVO</th><th style="padding:8px;text-align:center;width:80px;">LINEAS</th><th style="padding:8px;text-align:center;width:80px;">HORAS</th><th style="padding:8px;text-align:center;width:100px;">COMPLEJIDAD</th></tr>
                        ${filesRows}
                    </table>
                </div>
                
                <div style="margin-top:20px;padding-top:10px;border-top:1px solid #e2e8f0;text-align:center;">
                    <p style="color:#64748b;font-size:9px;margin:0;">Documento generado automaticamente | Dashboard de Proyectos | ${dateStr}</p>
                </div>
            </div>`;
            
            const element = document.getElementById('pdfContent');
            element.innerHTML = pdfHtml;
            element.style.display = 'block';
            
            const opt = {
                margin: [10, 10, 10, 10],
                filename: 'Informe_' + projectName + '_' + new Date().toISOString().split('T')[0] + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };
            
            html2pdf().set(opt).from(element).save().then(() => {
                element.style.display = 'none';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-pdf"></i> PDF Gerencial';
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
        
        // Fecha
        function setCurrentDate() {
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('currentDate').textContent = new Date().toLocaleDateString('es-ES', options);
        }
        
        // Init
        setCurrentDate();
        renderProjectList();
    </script>
</body>
</html>
