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
            --primary: #4F46E5;
            --primary-light: #818CF8;
            --primary-dark: #3730A3;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --info: #3B82F6;
            --dark: #0F172A;
            --dark-light: #1E293B;
            --light: #F8FAFC;
            --gray: #64748B;
            --gray-light: #E2E8F0;
            --border: #CBD5E1;
            --alta: #EF4444;
            --media-alta: #F97316;
            --media: #EAB308;
            --baja: #10B981;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.5);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #cbebff 50%, #a1c4fd 100%);
            background-attachment: fixed;
            min-height: 100vh;
            padding: 30px 20px;
            color: var(--dark);
            line-height: 1.5;
        }
        
        .container { max-width: 1400px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px; }
        
        .card, .header, .project-selector {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .header {
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .header h1 { color: var(--dark); font-size: 32px; font-weight: 800; letter-spacing: -0.5px; }
        .header h1 i { color: var(--primary); margin-right: 12px; font-size: 28px; }
        
        .header-info { text-align: right; color: var(--gray); display: flex; flex-direction: column; align-items: flex-end; }
        .header-info .date { font-size: 14px; font-weight: 500; margin-bottom: 8px; color: var(--dark-light); }
        
        .header-actions { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        
        .btn {
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn i { font-size: 16px; }
        
        .btn-pdf {
            background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.4);
        }
        
        .btn-pdf:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(225, 29, 72, 0.6); }
        
        .btn-scan {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);
        }
        
        .btn-scan:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6); }
        
        .btn-config {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
        }
        
        .btn-config:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6); }
        
        .btn:disabled { opacity: 0.6; cursor: not-allowed; filter: grayscale(50%); }
        
        .header-info .version {
            font-size: 13px;
            background: var(--success);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        
        /* Selector de proyecto */
        .project-selector {
            padding: 24px 32px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .project-selector label {
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
        }
        
        .project-selector label i { color: var(--primary); font-size: 20px; }
        
        .project-input {
            flex: 1;
            min-width: 300px;
            padding: 14px 20px;
            border: 2px solid var(--gray-light);
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            color: var(--dark);
            background: white;
            transition: all 0.3s;
            box-shadow: var(--shadow-sm) inset;
        }
        
        .project-input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }
        
        .project-list {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            width: 100%;
            margin-top: 8px;
        }
        
        .project-chip {
            padding: 8px 18px;
            background: white;
            border: 1px solid var(--border);
            color: var(--gray);
            border-radius: 24px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }
        
        .project-chip:hover, .project-chip.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
        }
        
        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 28px 24px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 120px; height: 120px;
            background: radial-gradient(circle, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            transform: translate(30%, -30%);
            pointer-events: none;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            width: 100%;
            height: 6px;
        }
        
        .stat-card.total::before { background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
        .stat-card.php::before { background: linear-gradient(90deg, #4F5B93, #8892BF); }
        .stat-card.js::before { background: linear-gradient(90deg, #F0DB4F, #F8E71C); }
        .stat-card.html::before { background: linear-gradient(90deg, #E34F26, #F16529); }
        .stat-card.horas::before { background: linear-gradient(90deg, var(--info), #60A5FA); }
        .stat-card.completado::before { background: linear-gradient(90deg, var(--success), #34D399); }
        
        .stat-card .icon {
            width: 54px; height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }
        
        .stat-card.total .icon { background: linear-gradient(135deg, #EEF2FF, #E0E7FF); color: var(--primary); }
        .stat-card.php .icon { background: linear-gradient(135deg, #F3F0FF, #E9E4FF); color: #4F5B93; }
        .stat-card.js .icon { background: linear-gradient(135deg, #FEF9C3, #FEF08A); color: #CA8A04; }
        .stat-card.html .icon { background: linear-gradient(135deg, #FEE2E2, #FECACA); color: #E34F26; }
        .stat-card.horas .icon { background: linear-gradient(135deg, #DBEAFE, #BFDBFE); color: var(--info); }
        .stat-card.completado .icon { background: linear-gradient(135deg, #DCFCE7, #BBF7D0); color: var(--success); }
        
        .stat-card .label {
            font-size: 14px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .stat-card .value { font-size: 36px; font-weight: 800; color: var(--dark); line-height: 1; letter-spacing: -1px; }
        .stat-card .sub { font-size: 13px; color: var(--gray); margin-top: 8px; font-weight: 500; }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
        }
        
        @media (max-width: 1200px) { .main-grid { grid-template-columns: 1fr; } }
        
        .card-header {
            padding: 24px 32px;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.4);
        }
        
        .card-header h2 { font-size: 20px; font-weight: 700; color: var(--dark); display: flex; align-items: center; }
        .card-header h2 i { margin-right: 12px; color: var(--primary); background: #EEF2FF; padding: 10px; border-radius: 12px; font-size: 16px; }
        .card-body { padding: 32px; }
        
        .filters { display: flex; gap: 12px; flex-wrap: wrap; }
        
        .filter-btn {
            padding: 10px 18px;
            border: 1px solid transparent;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
            color: var(--gray);
            box-shadow: var(--shadow-sm);
        }
        
        .filter-btn:hover { border-color: var(--border); color: var(--dark); }
        .filter-btn.active { background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25); }
        
        .files-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        
        .files-table td {
            padding: 16px 24px;
            border-bottom: 1px solid var(--gray-light);
            font-size: 15px;
            color: var(--dark-light);
            vertical-align: middle;
        }
        
        .files-table tr { transition: background-color 0.2s; }
        .files-table tr:hover { background-color: rgba(255, 255, 255, 0.6); }
        .files-table tr:last-child td { border-bottom: none; }
        
        .file-name { display: flex; align-items: center; gap: 16px; }
        
        .file-icon {
            width: 40px; height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: var(--shadow-sm);
        }
        
        .file-icon.php { background: linear-gradient(135deg, #F3F0FF, #E9E4FF); color: #4F5B93; }
        .file-icon.js { background: linear-gradient(135deg, #FEF9C3, #FEF08A); color: #CA8A04; }
        .file-icon.html { background: linear-gradient(135deg, #FEE2E2, #FECACA); color: #E34F26; }
        .file-icon.css { background: linear-gradient(135deg, #DBEAFE, #BFDBFE); color: #2563EB; }
        
        .file-name strong { font-weight: 600; color: var(--dark); font-size: 15px; }
        .file-name div div { font-size: 12px; color: var(--gray); margin-top: 4px; font-weight: 500; }
        
        .complexity-select {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            background: white;
            color: var(--dark);
            outline: none;
            transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }
        
        .complexity-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        
        .sidebar-card { margin-bottom: 24px; }
        
        .progress-ring-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 32px 24px;
        }
        
        .progress-ring { position: relative; width: 200px; height: 200px; }
        .progress-ring svg { transform: rotate(-90deg); width: 100%; height: 100%; }
        .progress-ring circle { fill: none; stroke-width: 14; }
        .progress-ring .bg { stroke: var(--gray-light); }
        .progress-ring .progress { stroke: url(#gradient); stroke-linecap: round; transition: stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1); }
        
        .progress-ring .percentage {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .progress-ring .percentage .value { font-size: 48px; font-weight: 800; color: var(--dark); line-height: 1; }
        .progress-ring .percentage .label { font-size: 14px; font-weight: 600; color: var(--success); margin-top: 4px; text-transform: uppercase; letter-spacing: 1px;}
        
        .work-summary {
            background: linear-gradient(135deg, rgba(220, 252, 231, 0.7) 0%, rgba(187, 247, 208, 0.7) 100%);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin-bottom: 32px;
            width: 100%;
        }
        
        .work-summary h3 { color: #15803D; font-size: 18px; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .work-summary p { color: #166534; font-size: 14px; font-weight: 500; }
        
        .complexity-list { display: flex; flex-direction: column; gap: 20px; }
        
        .complexity-item { display: flex; align-items: flex-start; gap: 16px; }
        .complexity-item .dot { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px; flex-shrink: 0; box-shadow: var(--shadow-sm); }
        .complexity-item .dot.alta { background: linear-gradient(135deg, #EF4444, #B91C1C); }
        .complexity-item .dot.media-alta { background: linear-gradient(135deg, #F97316, #C2410C); }
        .complexity-item .dot.media { background: linear-gradient(135deg, #EAB308, #A16207); }
        .complexity-item .dot.baja { background: linear-gradient(135deg, #10B981, #047857); }
        
        .complexity-item .info { flex: 1; }
        .complexity-item .name { font-size: 15px; font-weight: 600; color: var(--dark); margin-bottom: 8px; display: flex; justify-content: space-between; }
        .complexity-item .name span { color: var(--gray); font-size: 13px; font-weight: 500; }
        
        .complexity-item .bar {
            height: 8px;
            background: var(--gray-light);
            border-radius: 4px;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .complexity-item .bar-fill { height: 100%; border-radius: 4px; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); }
        .complexity-item .bar-fill.alta { background: linear-gradient(90deg, #FCA5A5, #EF4444); }
        .complexity-item .bar-fill.media-alta { background: linear-gradient(90deg, #FDBA74, #F97316); }
        .complexity-item .bar-fill.media { background: linear-gradient(90deg, #FDE047, #EAB308); }
        .complexity-item .bar-fill.baja { background: linear-gradient(90deg, #6EE7B7, #10B981); }
        
        .complexity-item .stats { margin-top: 8px; font-size: 13px; display: flex; justify-content: space-between; color: var(--gray); font-weight: 500; }
        .complexity-item .stats .hours { font-weight: 700; color: var(--dark); }
        
        .time-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        .time-item {
            background: white;
            padding: 24px;
            border-radius: 16px;
            text-align: center;
            border: 1px solid var(--gray-light);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s;
        }
        
        .time-item:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        
        .time-item .value { font-size: 32px; font-weight: 800; color: var(--primary); line-height: 1; margin-bottom: 8px; }
        .time-item .label { font-size: 13px; font-weight: 600; color: var(--gray); text-transform: uppercase; letter-spacing: 0.5px; }
        
        .table-container { 
            height: 650px;
            overflow-y: auto; 
            border-radius: 0 0 20px 20px;
        }
        
        .table-container::-webkit-scrollbar { width: 10px; }
        .table-container::-webkit-scrollbar-track { background: var(--light); }
        .table-container::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; border: 2px solid var(--light); }
        .table-container::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

        .files-table th {
            position: sticky;
            top: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            z-index: 10;
            text-align: left;
            padding: 16px 24px;
            font-size: 13px;
            font-weight: 700;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid var(--gray-light);
        }
        
        /* Loading overlay */
        .loading {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .loading.show { display: flex; animation: fadeIn 0.3s ease; }
        
        .loading-content {
            background: white;
            padding: 40px 60px;
            border-radius: 24px;
            text-align: center;
            box-shadow: var(--shadow-xl);
            animation: scaleIn 0.3s ease;
        }
        
        .loading-content i { font-size: 56px; color: var(--primary); margin-bottom: 24px; }
        .loading-content p { color: var(--dark); font-size: 18px; font-weight: 600; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 32px; right: 32px;
            background: var(--dark);
            color: white;
            padding: 16px 24px;
            border-radius: 16px;
            font-size: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            transform: translateY(150px) scale(0.9);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
        }
        
        .toast.show { transform: translateY(0) scale(1); opacity: 1; }
        .toast i { font-size: 20px; color: var(--success); }
        .toast.error i { color: var(--danger); }
        .toast.warning i { color: var(--warning); }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        
        .empty-state i { font-size: 72px; margin-bottom: 24px; color: var(--border); }
        .empty-state h3 { color: var(--dark); margin-bottom: 12px; font-size: 24px; font-weight: 700; }
        .empty-state p { color: var(--gray); font-size: 16px; }
        
        /* SVG Gradient definition container */
        .svg-defs { width: 0; height: 0; position: absolute; }
        .no-print { }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <svg class="svg-defs">
        <defs>
            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#10b981" />
                <stop offset="100%" stop-color="#34d399" />
            </linearGradient>
        </defs>
    </svg>
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
                            <select id="complexityFilter" onchange="setFilter('complexity', this.value)" class="complexity-select">
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
                                <div class="dot alta">A</div>
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
                                <div class="dot media-alta">MA</div>
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
                                <div class="dot media">M</div>
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
                                <div class="dot baja">B</div>
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
