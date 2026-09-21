<?php
/**
 * Módulo de Mapeo Interactivo - Relavera Comunitaria El Tablón
 * Frontend Principal con Canva Vectorial, Capas de Relieve/Tránsito/Satélite, Tracking GPS Móvil,
 * Playback Histórico, Alertas de Microzonas, Botón SOS y Conexión con Base de Datos ERP
 * 
 * @package mapeo.FRONT
 * @version 2.2 (Ultra Zoom 22 & Calidad Satélite HD)
 */
if (!isset($_SESSION)) {
    session_start();
}

$empresaNombre = isset($_SESSION['Ses_Emp_Nom']) ? (string)$_SESSION['Ses_Emp_Nom'] : '';
$esCapacitacionVideos = false;

// Verificación de empresa "Capacitación Videos"
if (stripos($empresaNombre, 'capacitacion') !== false || 
    stripos($empresaNombre, 'video') !== false || 
    stripos($empresaNombre, 'capacitación') !== false) {
    $esCapacitacionVideos = true;
}

// Permitir acceso en entorno local / desarrollo o si no hay sesión activa directa
$esDevLocal = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false || empty($empresaNombre)));
$accesoPermitido = $esCapacitacionVideos || $esDevLocal;

if (!$accesoPermitido) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Módulo en Desarrollo - Acceso Restringido</title>
        <link rel="shortcut icon" type="image/x-icon" href="../../imagenes/ingresar/favicon.png" />
        <link rel="icon" type="image/png" href="../../imagenes/ingresar/favicon.png" />
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/bootstrap-3.3.5/css/bootstrap.min.css" />
        <link rel="stylesheet" href="../../framework/plugins/fonts/font-awesome/font-awesome-4.4.0/css/font-awesome.min.css" />
        <style>
            body { background: #f1f5f9; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: 'Segoe UI', sans-serif; }
            .restricted-box { background: white; padding: 40px; border-radius: 12px; max-width: 500px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        </style>
    </head>
    <body>
        <div class="restricted-box">
            <i class="fa fa-lock text-warning" style="font-size: 54px; margin-bottom: 20px;"></i>
            <h3 style="color: #1e293b; font-weight: 700; margin-top: 0;">Módulo en Desarrollo</h3>
            <p style="color: #64748b; font-size: 14px;">
                El módulo de <b>Mapeo Interactivo y Registro GPS de Relavera</b> se encuentra actualmente en fase de desarrollo y está habilitado exclusivamente para la empresa <b>Capacitación Videos</b>.
            </p>
            <p style="font-size: 12px; color: #94a3b8;">Empresa actual: <?= htmlspecialchars($empresaNombre ? $empresaNombre : 'Sin sesión'); ?></p>
            <a href="../../skins/php/index_button.php" class="btn btn-primary" style="margin-top: 15px;">
                <i class="fa fa-arrow-left"></i> Volver al Inicio
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>ERP Locator &bull; Sistema GPS y Vectores</title>
    <link rel="shortcut icon" type="image/x-icon" href="../../imagenes/ingresar/favicon.png" />
    <link rel="icon" type="image/png" href="../../imagenes/ingresar/favicon.png" />

    <!-- Leaflet 1.9.4 Core CSS & JS (Librería Libre BSD-2-Clause) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Leaflet-Geoman (Canva de Dibujo Vectorial Libre) -->
    <link rel="stylesheet" href="https://unpkg.com/@geoman-io/leaflet-geoman-free@2.14.2/dist/leaflet-geoman.css" />
    <script src="https://unpkg.com/@geoman-io/leaflet-geoman-free@2.14.2/dist/leaflet-geoman.min.js"></script>

    <!-- Turf.js (Cálculos Geodésicos, Áreas, Distancias y Geocercas) -->
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6.5.0/turf.min.js"></script>

    <!-- FontAwesome & Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet" />

    <!-- Estilos del Módulo -->
    <link rel="stylesheet" href="../RECURSOS/estilos_mapeo.css?v=2.2" />
    <!-- Estilos Específicos: Evidencias Fotográficas, Contadores de Sector y Lightbox -->
    <link rel="stylesheet" href="../RECURSOS/locator_evidencias.css?v=1.0" />
</head>
<body>

<div class="mapeo-container">
    
    <!-- Topbar Superior -->
    <header class="mapeo-topbar">
        <div class="topbar-brand">
            <i class="fa fa-globe" style="font-size: 20px; color: #a7f3d0;"></i>
            <span>Mapeo Interactivo Relavera</span>
            <span class="badge-dev">Desarrollo &bull; Capacitación Videos</span>
            <span class="offline-status-pill" id="offline-pill" title="Estado de conectividad y sincronización">
                <i class="fa fa-circle text-success" id="offline-dot"></i> <span id="offline-text">En Línea</span>
            </span>
        </div>
        <div class="topbar-actions">
            <button class="btn btn-sm" onclick="centrarRelavera()" title="Centrar Mapa en el Frente de Descarga (-3.738659, -79.630349)" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; padding: 5px 10px; cursor:pointer;">
                <i class="fa fa-crosshairs"></i> Frente Descarga
            </button>
            <button class="btn btn-sm" onclick="togglePlaybackBar()" title="Reproductor Histórico de Recorridos" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; padding: 5px 10px; cursor:pointer;">
                <i class="fa fa-history"></i> Playback
            </button>
            <button class="btn btn-sm" onclick="abrirModalRegistroActividad()" title="Registrar Nueva Actividad" style="background: #10b981; color: #fff; border: none; border-radius: 6px; padding: 5px 12px; font-weight: 600; cursor:pointer;">
                <i class="fa fa-plus-circle"></i> Nueva Actividad
            </button>
            <button class="btn btn-sm" onclick="abrirModalExportacion()" title="Exportar Mapa (GeoJSON / KML / CSV)" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; padding: 5px 10px; cursor:pointer;">
                <i class="fa fa-download"></i> Exportar
            </button>
            <button class="btn btn-sm" onclick="toggleSidebar()" title="Mostrar/Ocultar Panel Lateral" style="background: #0f172a; color: #fff; border: none; border-radius: 6px; padding: 5px 10px; cursor:pointer;">
                <i class="fa fa-bars"></i>
            </button>
        </div>
    </header>

    <!-- Workspace: Mapa + HUD + Sidebar -->
    <div class="mapeo-workspace sidebar-collapsed" id="mapeo-workspace">
        
        <!-- Mapa Leaflet Principal -->
        <div id="mapa-relavera"></div>

        <!-- Banner de Alerta de Microzona -->
        <div class="microzone-alert-banner" id="microzone-banner">
            <i class="fa fa-exclamation-triangle" style="font-size:18px;"></i>
            <span id="microzone-banner-text">ALERTA DE VELOCIDAD EN ZONA</span>
        </div>

        <!-- Banner de Modo Enfoque de Sector (Breadcrumb Desacoplado) -->
        <div class="sector-focus-banner" id="sector-focus-banner" style="display:none;">
            <span class="sec-title" id="sector-focus-title"><i class="fa fa-map-marker"></i> Sector: Seleccionado</span>
            <span class="sec-stats-badge" id="sector-focus-count">0 eventos</span>
            <button type="button" class="btn-exit-focus" onclick="salirDeModoEnfoqueSector()" title="Restaurar vista macro de todos los sectores">
                <i class="fa fa-times"></i> Ver Todos los Sectores
            </button>
        </div>

        <!-- HUD Flotante: Coordenadas y Elevación en tiempo real -->
        <div class="floating-coords-badge">
            <div class="coords-item">
                <span class="coords-label">LAT:</span>
                <span id="hud-lat">-3.738659</span>
            </div>
            <div class="coords-item">
                <span class="coords-label">LNG:</span>
                <span id="hud-lng">-79.630349</span>
            </div>
            <div class="coords-item">
                <span class="coords-label">ELEV:</span>
                <span id="hud-elev">680.0 m</span>
            </div>
            <div class="coords-item">
                <span class="coords-label">ZONA:</span>
                <span style="color:#38bdf8;" id="hud-microzone-name">Frente de Descarga</span>
            </div>
            <div class="coords-item">
                <span class="coords-label">ZOOM:</span>
                <span style="color:#a7f3d0;" id="hud-zoom-level">18x</span>
            </div>
        </div>

        <!-- Botón Flotante de Emergencia / SOS -->
        <button class="floating-sos-btn collapsed" id="btn-sos-flotante" onclick="handleSosClick(event)" title="SOS Emergencia (Clic para emitir alerta)">
            <i class="fa fa-bullhorn"></i> <span class="sos-text-short">SOS</span><span class="sos-text-full"> EMERGENCIA</span>
            <span class="btn-toggle-sos" onclick="toggleSosBtn(event)" title="Contraer / Expandir bot&oacute;n SOS"><i class="fa fa-chevron-left"></i></span>
        </button>

        <!-- HUD Flotante: Telemetría GPS en Vivo de Volquetero -->
        <div class="floating-gps-hud collapsed" id="hud-gps-telemetria">
            <div class="gps-hud-header" onclick="toggleGpsHud()" style="cursor:pointer;" title="Clic para expandir / contraer telemetr&iacute;a GPS">
                <div style="display:flex; align-items:center;">
                    <span class="gps-status-dot" id="gps-status-indicator"></span>
                    <span id="gps-status-text">GPS M&Oacute;VIL</span>
                </div>
                <div style="display:flex; align-items:center; gap:6px;">
                    <span class="badge badge-success" id="hud-gps-geofence" style="font-size:10px; background:#10b981; padding:2px 6px; border-radius:4px;">DENTRO DE RELAVERA</span>
                    <button type="button" class="btn-toggle-gps-hud" id="btn-toggle-gps-hud" style="background:none; border:none; color:#cbd5e1; font-size:11px; cursor:pointer; padding:0 2px;" title="Minimizar / Expandir">
                        <i class="fa fa-chevron-up"></i>
                    </button>
                </div>
            </div>
            <div class="gps-stats-grid" id="gps-stats-grid">
                <div class="gps-stat-box">
                    <div class="gps-stat-val" id="hud-gps-speed">0.0 km/h</div>
                    <div class="gps-stat-lbl">Velocidad Actual</div>
                </div>
                <div class="gps-stat-box">
                    <div class="gps-stat-val" id="hud-gps-distance">0.00 km</div>
                    <div class="gps-stat-lbl">Distancia Total</div>
                </div>
                <div class="gps-stat-box">
                    <div class="gps-stat-val" id="hud-gps-accuracy" style="color:#a7f3d0;">&plusmn;0 m</div>
                    <div class="gps-stat-lbl">Precisi&oacute;n GPS</div>
                </div>
                <div class="gps-stat-box">
                    <div class="gps-stat-val" id="hud-gps-limite" style="color:#f59e0b;">10 km/h</div>
                    <div class="gps-stat-lbl">L&iacute;mite en Zona</div>
                </div>
            </div>
        </div>

        <!-- Barra Inferior de Reproducción Histórica (Playback) -->
        <div class="playback-bottom-bar" id="playback-bar">
            <div class="playback-controls-row">
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="fa fa-history text-success" style="font-size:16px;"></i>
                    <strong style="font-size:13px;">Histórico de Recorrido:</strong>
                    <select id="playback-vehicle-select" style="background:#1e293b; color:#fff; border:1px solid #475569; border-radius:4px; padding:3px 8px; font-size:12px;">
                        <option value="VOL-04">VOL-04 (OBA-7821) - Manuel Carrión</option>
                        <option value="VOL-09">VOL-09 (PBC-3419) - Luis Espinoza</option>
                        <option value="VOL-12">VOL-12 (LBA-9023) - Jorge Aguilar</option>
                    </select>
                </div>
                <div class="playback-btn-group">
                    <button class="playback-ctrl-btn" onclick="reiniciarPlayback()" title="Reiniciar"><i class="fa fa-backward"></i></button>
                    <button class="playback-ctrl-btn" id="btn-playback-play" onclick="togglePlaybackPlay()" style="background:#10b981;"><i class="fa fa-play"></i> Reproducir</button>
                    <span style="font-size:11px; margin-left:6px;">Velocidad:</span>
                    <button class="playback-ctrl-btn" onclick="setPlaybackSpeed(1)">1x</button>
                    <button class="playback-ctrl-btn" onclick="setPlaybackSpeed(3)">3x</button>
                    <button class="playback-ctrl-btn" onclick="setPlaybackSpeed(8)">8x</button>
                    <button class="playback-ctrl-btn" onclick="cerrarPlaybackBar()" style="background:#ef4444; margin-left:6px;"><i class="fa fa-times"></i></button>
                </div>
            </div>
            <div>
                <input type="range" min="0" max="100" value="0" class="playback-slider" id="playback-time-slider" oninput="onPlaybackSliderChange(this.value)">
                <div style="display:flex; justify-content:space-between; font-size:11px; color:#94a3b8; margin-top:2px;">
                    <span id="playback-cur-time">Paso: 0/0</span>
                    <span id="playback-cur-speed">Velocidad: 0.0 km/h</span>
                    <span><i class="fa fa-map-marker"></i> Frente de Descarga</span>
                </div>
            </div>
        </div>

        <!-- Sidebar Lateral con Pestañas -->
        <aside class="mapeo-sidebar collapsed" id="sidebar-mapeo">
            
            <div class="sidebar-header">
                <div style="font-weight:700; color:#1e293b; font-size:14px;">
                    <i class="fa fa-map text-success"></i> Panel de Control Geográfico
                </div>
                <button onclick="toggleSidebar()" style="background:none; border:none; color:#64748b; font-size:16px; cursor:pointer;">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <!-- Navegación por Pestañas -->
            <nav class="sidebar-tabs">
                <button class="sidebar-tab-btn" data-tab="actividades">
                    <i class="fa fa-clipboard"></i> Actividades (<span id="total-actividades-count">0</span>)
                </button>
                <button class="sidebar-tab-btn active" data-tab="sectores">
                    <i class="fa fa-map-marker"></i> Sectores (<span id="total-sectores-count">0</span>)
                </button>
                <button class="sidebar-tab-btn" data-tab="manifiestos">
                    <i class="fa fa-database"></i> Manifiestos
                </button>
                <button class="sidebar-tab-btn" data-tab="gps">
                    <i class="fa fa-location-arrow"></i> GPS Móvil
                </button>
                <button class="sidebar-tab-btn" data-tab="simulador">
                    <i class="fa fa-truck"></i> Simulación
                </button>
                <button class="sidebar-tab-btn" data-tab="capas">
                    <i class="fa fa-clone"></i> Capas
                </button>
            </nav>

            <div class="sidebar-content">
                
                <!-- TAB 1: ACTIVIDADES Y FILTROS -->
                <div class="tab-pane" id="tab-pane-actividades">
                    
                    <!-- KPI Banner de Métricas -->
                    <div class="kpi-metrics-grid">
                        <div class="kpi-metric-card">
                            <div class="val" id="kpi-total-acts">0</div>
                            <div class="lbl">Actividades</div>
                        </div>
                        <div class="kpi-metric-card">
                            <div class="val" id="kpi-total-m3">0 m³</div>
                            <div class="lbl">Cubicaje Vertido</div>
                        </div>
                        <div class="kpi-metric-card">
                            <div class="val" id="kpi-total-km">0.0 km</div>
                            <div class="lbl">Recorrido</div>
                        </div>
                    </div>

                    <!-- Filtros Rápidos -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px; margin-bottom:12px;">
                        <div style="font-size:11px; font-weight:700; color:#475569; margin-bottom:6px; display:flex; justify-content:space-between; align-items:center;">
                            <span><i class="fa fa-filter"></i> Filtros de Operación</span>
                            <button onclick="limpiarFiltrosActividades()" class="btn btn-xs" style="background:none; border:none; color:#0284c7; cursor:pointer; font-size:11px;">Limpiar</button>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:6px;">
                            <select id="filtro-act-tipo" onchange="aplicarFiltrosActividades()" style="width:100%; padding:5px; border:1px solid #cbd5e1; border-radius:4px; font-size:11px;">
                                <option value="">Todos los tipos</option>
                                <option value="descarga_relave">Descarga de Relave</option>
                                <option value="compactacion_dique">Compactación de Dique</option>
                                <option value="monitoreo_piezometro">Monitoreo Piezómetros</option>
                                <option value="reporte_alerta">Alertas SOS</option>
                            </select>
                            <select id="filtro-act-estado" onchange="aplicarFiltrosActividades()" style="width:100%; padding:5px; border:1px solid #cbd5e1; border-radius:4px; font-size:11px;">
                                <option value="">Todos los estados</option>
                                <option value="Completada">Completada</option>
                                <option value="En Progreso">En Progreso</option>
                                <option value="Supervisado">Supervisado</option>
                                <option value="Alerta Crítica">Alerta Crítica</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="lista-actividades-container">
                        <!-- Render dinámico desde JS -->
                    </div>
                </div>

                <!-- TAB: SECTORES Y UBICACIONES DE REFERENCIA (finca_actividad) -->
                <div class="tab-pane active" id="tab-pane-sectores">
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; margin-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-weight:700; font-size:13px; color:#1e293b;">
                                <i class="fa fa-map-marker text-success"></i> Sectores y Referencias
                            </span>
                            <button onclick="cargarSectores()" class="btn btn-xs" style="background:#fff; border:1px solid #cbd5e1; padding:3px 8px; border-radius:4px; font-size:11px; cursor:pointer;" title="Recargar desde BD">
                                <i class="fa fa-refresh"></i>
                            </button>
                        </div>
                        <p style="font-size:11px; color:#64748b; margin:6px 0 0 0; line-height:1.3;">
                            Ubicaciones físicas permanentes registradas en <code>finca_actividad</code> (Edificios, Garitas, Diques, Sectores de descarga).
                        </p>
                    </div>
                    <div id="lista-sectores-container">
                        <!-- Render dinámico desde JS -->
                    </div>
                </div>

                <!-- TAB 2: MANIFIESTOS REALES DESDE BASE DE DATOS -->
                <div class="tab-pane" id="tab-pane-manifiestos">
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; margin-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-weight:700; font-size:13px; color:#1e293b;"><i class="fa fa-database text-success"></i> Manifiestos del Día (ERP)</span>
                            <button onclick="cargarManifiestosBD()" class="btn btn-xs" style="background:#0284c7; color:#fff; border:none; border-radius:4px; padding:3px 8px; font-size:11px; cursor:pointer;">
                                <i class="fa fa-refresh"></i> Actualizar
                            </button>
                        </div>
                        <p style="font-size:11px; color:#64748b; margin:4px 0 0 0;">
                            Viajes y pesajes registrados en balanza sincronizados con la Relavera.
                        </p>
                    </div>

                    <div id="lista-manifiestos-container">
                        <!-- Render dinámico desde JS -->
                    </div>
                </div>

                <!-- TAB 3: GPS MÓVIL (TELÉFONO DE VOLQUETERO) -->
                <div class="tab-pane" id="tab-pane-gps">
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-bottom:14px;">
                        <h4 style="margin-top:0; font-size:14px; font-weight:700; color:#1e293b;">
                            <i class="fa fa-mobile" style="font-size:18px;"></i> Registro GPS desde Smartphone
                        </h4>
                        <p style="font-size:12px; color:#64748b; line-height:1.4;">
                            Utiliza el sensor GPS de alta precisión del teléfono para registrar en tiempo real la ubicación, velocidad y recorrido exacto dentro de la relavera.
                        </p>
                        
                        <div style="margin-top:12px; display:flex; flex-direction:column; gap:8px;">
                            <button id="btn-toggle-gps" onclick="toggleLiveGpsTracking()" style="width:100%; padding:10px; background:#10b981; color:#fff; font-weight:700; border:none; border-radius:8px; cursor:pointer; font-size:13px; display:flex; align-items:center; justify-content:center; gap:8px;">
                                <i class="fa fa-play-circle"></i> Iniciar GPS Móvil
                            </button>
                            <button onclick="forzarSincronizacionOffline()" style="width:100%; padding:8px; background:#f1f5f9; color:#475569; font-weight:600; border:1px solid #cbd5e1; border-radius:8px; cursor:pointer; font-size:12px; display:flex; align-items:center; justify-content:center; gap:6px;">
                                <i class="fa fa-cloud-upload"></i> Sincronizar Cola Offline
                            </button>
                        </div>
                    </div>

                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:14px;">
                        <h5 style="margin:0 0 10px 0; font-size:13px; font-weight:700; color:#334155;">
                            <i class="fa fa-tachometer text-primary"></i> Microzonas y Límites de Velocidad
                        </h5>
                        <ul style="font-size:12px; color:#64748b; padding-left:18px; margin:0; line-height:1.6;">
                            <li><b>Punto de Vertido:</b> Lat <code>-3.738659</code>, Lng <code>-79.630349</code> (Máx: 10 km/h)</li>
                            <li><b>Coronación de Dique:</b> Máx 15 km/h</li>
                            <li><b>Vía de Acarreo / Garita:</b> Máx 25 km/h</li>
                            <li><b>Buffer Offline:</b> Almacenamiento local automático si se pierde cobertura celular.</li>
                        </ul>
                    </div>
                </div>

                <!-- TAB 4: SIMULADOR REALISTA DE FLOTA EN VÍAS -->
                <div class="tab-pane" id="tab-pane-simulador">
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-bottom:14px;">
                        <h4 style="margin-top:0; font-size:14px; font-weight:700; color:#1e293b;">
                            <i class="fa fa-truck text-warning"></i> Simulación de Flota en Vías Mineras
                        </h4>
                        <p style="font-size:12px; color:#64748b; line-height:1.4;">
                            Simulación precisa por las vías de acarreo hacia el frente de vertido (<code>-3.738659, -79.630349</code>) con giros de orientación, basculado y control de velocidad.
                        </p>

                        <button id="btn-sim-toggle" onclick="toggleSimuladorVolquetas()" style="width:100%; padding:10px; background:#3b82f6; color:#fff; font-weight:700; border:none; border-radius:8px; cursor:pointer; font-size:13px; display:flex; align-items:center; justify-content:center; gap:8px;">
                            <i class="fa fa-play"></i> Iniciar Simulación Flota
                        </button>

                        <div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between;">
                            <span style="font-size:12px; font-weight:600; color:#475569;">Velocidad Simulación:</span>
                            <div style="display:flex; gap:4px;">
                                <button class="btn btn-xs btn-sim-speed active" data-speed="1" onclick="setSimSpeed(1)" style="padding:2px 8px; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer; background:#fff;">1x</button>
                                <button class="btn btn-xs btn-sim-speed" data-speed="2" onclick="setSimSpeed(2)" style="padding:2px 8px; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer; background:#fff;">2x</button>
                                <button class="btn btn-xs btn-sim-speed" data-speed="4" onclick="setSimSpeed(4)" style="padding:2px 8px; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer; background:#fff;">4x</button>
                                <button class="btn btn-xs btn-sim-speed" data-speed="8" onclick="setSimSpeed(8)" style="padding:2px 8px; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer; background:#fff;">8x</button>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Volquetas en Simulación con Estado en Tiempo Real -->
                    <div style="display:flex; flex-direction:column; gap:8px;" id="sim-volquetas-status-list">
                        <div style="background:#fff; border-left:4px solid #f59e0b; border:1px solid #e2e8f0; border-left-width:4px; padding:10px; border-radius:6px; font-size:12px;">
                            <b><i class="fa fa-truck text-warning"></i> VOL-04 (OBA-7821)</b> &bull; Manuel Carrión<br>
                            <span style="color:#64748b;" id="sim-vol-04-status">Ruta: Vía Principal de Acarreo &rarr; Frente Vertido</span>
                        </div>
                        <div style="background:#fff; border-left:4px solid #3b82f6; border:1px solid #e2e8f0; border-left-width:4px; padding:10px; border-radius:6px; font-size:12px;">
                            <b><i class="fa fa-truck text-primary"></i> VOL-09 (PBC-3419)</b> &bull; Luis Espinoza<br>
                            <span style="color:#64748b;" id="sim-vol-09-status">Ruta: Garita Balanza &rarr; Rampa Dique</span>
                        </div>
                        <div style="background:#fff; border-left:4px solid #10b981; border:1px solid #e2e8f0; border-left-width:4px; padding:10px; border-radius:6px; font-size:12px;">
                            <b><i class="fa fa-truck text-success"></i> VOL-12 (LBA-9023)</b> &bull; Jorge Aguilar<br>
                            <span style="color:#64748b;" id="sim-vol-12-status">Ruta: Descarga en Berma Activa &rarr; Retorno</span>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: CAPAS Y SATÉLITE ULTRA HD -->
                <div class="tab-pane" id="tab-pane-capas">
                    <h5 style="margin:0 0 10px 0; font-size:13px; font-weight:700; color:#334155;">Capas Satelitales y Base (Zoom Máximo 22)</h5>
                    
                    <div class="layer-card-item selected" data-layer="google_hybrid" onclick="switchBaseLayer('google_hybrid')">
                        <div>
                            <div style="font-weight:700; font-size:12px; color:#1e293b;"><i class="fa fa-globe text-success"></i> Google Híbrido Satélite HD (Recomendado)</div>
                            <div style="font-size:11px; color:#64748b;">Fotografía satelital nítida con nombres de vías y relieve</div>
                        </div>
                    </div>

                    <div class="layer-card-item" data-layer="google_satelite" onclick="switchBaseLayer('google_satelite')">
                        <div>
                            <div style="font-weight:700; font-size:12px; color:#1e293b;"><i class="fa fa-globe text-primary"></i> Google Satélite Puro HD</div>
                            <div style="font-size:11px; color:#64748b;">Imagen satelital directa sin etiquetas</div>
                        </div>
                    </div>

                    <div class="layer-card-item" data-layer="esri_satelite" onclick="switchBaseLayer('esri_satelite')">
                        <div>
                            <div style="font-weight:700; font-size:12px; color:#1e293b;"><i class="fa fa-crosshairs text-info"></i> Esri World Imagery (Claridad Máxima)</div>
                            <div style="font-size:11px; color:#64748b;">Fotografía aérea de alta precisión cartográfica</div>
                        </div>
                    </div>

                    <div class="layer-card-item" data-layer="google_roads" onclick="switchBaseLayer('google_roads')">
                        <div>
                            <div style="font-weight:700; font-size:12px; color:#1e293b;"><i class="fa fa-road text-secondary"></i> Google Maps Calles y Tránsito</div>
                            <div style="font-size:11px; color:#64748b;">Plano vial claro y rutas de acceso terrestre</div>
                        </div>
                    </div>

                    <div class="layer-card-item" data-layer="relieve" onclick="switchBaseLayer('relieve')">
                        <div>
                            <div style="font-weight:700; font-size:12px; color:#1e293b;"><i class="fa fa-area-chart text-warning"></i> Relieve y Curvas de Nivel (OpenTopoMap)</div>
                            <div style="font-size:11px; color:#64748b;">Topografía, sombras de colina y cotas de nivel</div>
                        </div>
                    </div>

                    <div class="layer-card-item" data-layer="oscuro" onclick="switchBaseLayer('oscuro')">
                        <div>
                            <div style="font-weight:700; font-size:12px; color:#1e293b;"><i class="fa fa-moon-o text-dark"></i> Modo Oscuro (CartoDB Dark Matter)</div>
                            <div style="font-size:11px; color:#64748b;">Visualización de contraste nocturno para radares</div>
                        </div>
                    </div>

                    <h5 style="margin:16px 0 10px 0; font-size:13px; font-weight:700; color:#334155;">Capas Superpuestas (Overlays)</h5>
                    <div style="display:flex; flex-direction:column; gap:8px; font-size:12px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" checked onchange="toggleLayerGroup('zonas', this.checked)">
                            <span><i class="fa fa-shield text-danger"></i> Zonificación Oficial Relavera y Geocerca</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" checked id="chk-capa-sectores" onchange="toggleLayerGroup('sectores', this.checked)">
                            <span><i class="fa fa-map-marker" style="color:#8b5cf6;"></i> Sectores y Ubicaciones de Referencia (finca_actividad)</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" checked onchange="toggleLayerGroup('actividades', this.checked)">
                            <span><i class="fa fa-clipboard text-success"></i> Actividades y Trabajos Registrados</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" checked onchange="toggleLayerGroup('vectores', this.checked)">
                            <span><i class="fa fa-pencil text-primary"></i> Canva de Dibujos Vectoriales de Usuario</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" checked onchange="toggleLayerGroup('gps', this.checked)">
                            <span><i class="fa fa-location-arrow text-info"></i> Tracking GPS Móvil en Vivo</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" checked onchange="toggleLayerGroup('simulacion', this.checked)">
                            <span><i class="fa fa-truck text-warning"></i> Simulación de Volquetas</span>
                        </label>
                    </div>
                </div>

            </div>
        </aside>

    </div>

</div>

<!-- MODAL: REGISTRO DE UBICACIÓN O ACTIVIDAD EN MAPA -->
<div class="modal-mapeo" id="modal-registro-actividad">
    <div class="modal-mapeo-dialog" style="max-width:560px;">
        <div class="modal-mapeo-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
            <div style="font-weight:700; font-size:15px; color:#1e293b;" id="modal-mapeo-titulo-wrap">
                <i class="fa fa-map-pin text-success" id="modal-registro-icon"></i> 
                <span id="modal-registro-titulo">Registrar Elemento en Mapa de Relavera</span>
            </div>
            <button type="button" onclick="cerrarModalActividad(false)" style="background:none; border:none; font-size:18px; color:#94a3b8; cursor:pointer;" title="Cancelar y descartar del mapa">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="modal-mapeo-body" style="padding:16px;">
            
            <!-- Selector de Modo: 1. Ubicación/Sector vs 2. Evento Operativo -->
            <div style="display:flex; gap:8px; margin-bottom:16px; background:#f1f5f9; padding:4px; border-radius:8px;">
                <button type="button" id="btn-tab-modo-ubicacion" onclick="setModoRegistro('ubicacion')" style="flex:1; padding:9px 12px; border:none; border-radius:6px; font-weight:700; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.2s; background:#10b981; color:#fff;">
                    <i class="fa fa-map-marker" style="font-size:14px;"></i>
                    <span>1. Punto de Referencia / Sector</span>
                </button>
                <button type="button" id="btn-tab-modo-evento" onclick="setModoRegistro('evento')" style="flex:1; padding:9px 12px; border:none; border-radius:6px; font-weight:700; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.2s; background:transparent; color:#64748b;">
                    <i class="fa fa-truck" style="font-size:14px;"></i>
                    <span>2. Evento Operativo / Labor</span>
                </button>
            </div>

            <!-- PANEL 1: MODO PUNTO DE REFERENCIA / UBICACIÓN (SECTOR EN FINCA_ACTIVIDAD) -->
            <div id="panel-modo-ubicacion">
                <div style="font-size:11px; color:#065f46; margin-bottom:12px; padding:8px 12px; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:6px; line-height:1.4;">
                    <i class="fa fa-info-circle text-success"></i> Guarda este punto o polígono como <b>Sector o Referencia Fija</b> (Edificio, Sector Operativo, Garita, Dique) para que aparezca en el catálogo de Labores de Relavera (<code>finca_actividad</code>).
                </div>

                <div style="margin-bottom:12px;">
                    <label style="font-size:12px; font-weight:700; color:#1e293b; display:block; margin-bottom:4px;">
                        Nombre del Sector / Edificio / Referencia <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="text" id="form-sec-nombre" placeholder="Ej. Edificio Administrativo, Sector A1 - Dique Frontal, Garita..." style="width:100%; padding:9px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-weight:600;" />
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Categoría / Tipo de Lugar</label>
                        <select id="form-sec-categoria" onchange="onCategoriaSectorChange(this)" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                            <option value="Sector Operativo">Sector Operativo de Relave</option>
                            <option value="Frente de Vertido">Frente de Vertido / Descarga</option>
                            <option value="Edificio / Infraestructura">Edificio / Infraestructura</option>
                            <option value="Garita / Balanza">Garita / Balanza / Control</option>
                            <option value="Dique de Contención">Dique de Contención / Talud</option>
                            <option value="Punto de Monitoreo">Punto de Monitoreo / Piezómetro</option>
                            <option value="Taller / Maestranza">Taller / Maestranza</option>
                            <option value="Instalación General">Instalación General</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Dirección / Referencia Adicional</label>
                        <input type="text" id="form-sec-dir" placeholder="Ej. Margen derecho, km 1.2 vía de acarreo..." style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;" />
                    </div>
                </div>

                <!-- Personalización de Marcador / Pin (Icono y Color) -->
                <div class="pin-customizer-box">
                    <div style="font-size:11px; font-weight:700; color:#334155; margin-bottom:8px; display:flex; align-items:center; justify-content:space-between;">
                        <span><i class="fa fa-paint-brush text-primary"></i> Personalizar Icono y Color del Pin</span>
                        <span style="font-size:10px; color:#64748b; font-weight:normal;">Identificación visual en el mapa</span>
                    </div>
                    <div class="pin-customizer-grid">
                        <div class="pin-preview-card">
                            <span class="preview-title">Vista Pin</span>
                            <div id="preview-sec-pin" style="margin-top:2px;"></div>
                        </div>
                        <div class="pin-controls-panel">
                            <div>
                                <span style="font-size:10px; font-weight:600; color:#64748b; display:block; margin-bottom:3px;">Icono:</span>
                                <div class="pin-icons-row" id="sec-icon-swatches">
                                    <button type="button" class="pin-icon-btn selected" data-icon="fa-map-marker" onclick="seleccionarIconoPin('sec', 'fa-map-marker')" title="Marcador estándar"><i class="fa fa-map-marker"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-building" onclick="seleccionarIconoPin('sec', 'fa-building')" title="Edificio / Admin"><i class="fa fa-building"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-industry" onclick="seleccionarIconoPin('sec', 'fa-industry')" title="Planta / Beneficio"><i class="fa fa-industry"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-shield" onclick="seleccionarIconoPin('sec', 'fa-shield')" title="Garita / Control"><i class="fa fa-shield"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-tint" onclick="seleccionarIconoPin('sec', 'fa-tint')" title="Dique / Piscina"><i class="fa fa-tint"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-wrench" onclick="seleccionarIconoPin('sec', 'fa-wrench')" title="Taller / Maestranza"><i class="fa fa-wrench"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-flag" onclick="seleccionarIconoPin('sec', 'fa-flag')" title="Hito / Límite"><i class="fa fa-flag"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-eye" onclick="seleccionarIconoPin('sec', 'fa-eye')" title="Monitoreo"><i class="fa fa-eye"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-cubes" onclick="seleccionarIconoPin('sec', 'fa-cubes')" title="Frente de Vertido"><i class="fa fa-cubes"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-road" onclick="seleccionarIconoPin('sec', 'fa-road')" title="Vía de Acceso"><i class="fa fa-road"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-database" onclick="seleccionarIconoPin('sec', 'fa-database')" title="Depósito / Tanque"><i class="fa fa-database"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-hospital-o" onclick="seleccionarIconoPin('sec', 'fa-hospital-o')" title="Dispensario / Salud"><i class="fa fa-hospital-o"></i></button>
                                </div>
                            </div>
                            <div>
                                <span style="font-size:10px; font-weight:600; color:#64748b; display:block; margin-bottom:3px;">Color:</span>
                                <div class="pin-colors-row" id="sec-color-swatches">
                                    <button type="button" class="pin-color-chip selected" data-color="#8b5cf6" style="background:#8b5cf6;" onclick="seleccionarColorPin('sec', '#8b5cf6')" title="Morado Sector"></button>
                                    <button type="button" class="pin-color-chip" data-color="#10b981" style="background:#10b981;" onclick="seleccionarColorPin('sec', '#10b981')" title="Verde Esmeralda"></button>
                                    <button type="button" class="pin-color-chip" data-color="#3b82f6" style="background:#3b82f6;" onclick="seleccionarColorPin('sec', '#3b82f6')" title="Azul"></button>
                                    <button type="button" class="pin-color-chip" data-color="#06b6d4" style="background:#06b6d4;" onclick="seleccionarColorPin('sec', '#06b6d4')" title="Cian Lodos/Agua"></button>
                                    <button type="button" class="pin-color-chip" data-color="#f59e0b" style="background:#f59e0b;" onclick="seleccionarColorPin('sec', '#f59e0b')" title="Ámbar"></button>
                                    <button type="button" class="pin-color-chip" data-color="#ef4444" style="background:#ef4444;" onclick="seleccionarColorPin('sec', '#ef4444')" title="Rojo"></button>
                                    <button type="button" class="pin-color-chip" data-color="#64748b" style="background:#64748b;" onclick="seleccionarColorPin('sec', '#64748b')" title="Gris Pizarra"></button>
                                    <button type="button" class="pin-color-chip" data-color="#ec4899" style="background:#ec4899;" onclick="seleccionarColorPin('sec', '#ec4899')" title="Rosa"></button>
                                    <button type="button" class="pin-color-chip" data-color="#ea580c" style="background:#ea580c;" onclick="seleccionarColorPin('sec', '#ea580c')" title="Naranja"></button>
                                    <div class="pin-color-custom-wrap" title="Elegir otro color personalizado">
                                        <input type="color" id="picker-sec-color-custom" class="pin-color-custom-input" value="#8b5cf6" onchange="seleccionarColorPin('sec', this.value)" />
                                        <span class="pin-color-custom-btn"><i class="fa fa-plus"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="form-sec-icono" value="fa-map-marker" />
                    <input type="hidden" id="form-sec-color" value="#8b5cf6" />
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Latitud</label>
                        <input type="text" id="form-sec-lat" readonly style="width:100%; padding:8px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:6px; font-size:12px;" />
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Longitud</label>
                        <input type="text" id="form-sec-lng" readonly style="width:100%; padding:8px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:6px; font-size:12px;" />
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Área Calculada (Hectáreas)</label>
                        <input type="number" id="form-sec-hec" step="0.0001" value="0.0000" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;" />
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Perímetro / Longitud (m)</label>
                        <input type="text" id="form-sec-longitud" readonly style="width:100%; padding:8px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:6px; font-size:12px;" />
                    </div>
                </div>
            </div>

            <!-- PANEL 2: MODO EVENTO OPERATIVO (ACTIVIDAD RELAVERA) -->
            <div id="panel-modo-evento" style="display:none;">
                <div style="font-size:11px; color:#1e40af; margin-bottom:12px; padding:8px 12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; line-height:1.4;">
                    <i class="fa fa-info-circle text-primary"></i> Registra un <b>Evento o Trabajo Operativo</b> en este punto (descarga de volqueta, conformación de berma, monitoreo). Ahora incluye el <b>Nombre de la Ubicación / Sector</b> para identificarlo sin depender solo de coordenadas numéricas.
                </div>

                <!-- Nombre de la Ubicación / Sector del Evento -->
                <div style="margin-bottom: 12px;">
                    <label style="font-size:12px; font-weight:700; color:#1e293b; display:block; margin-bottom:4px;">
                        <i class="fa fa-map-marker text-danger"></i> Nombre de la Ubicación / Sector del Evento <span style="color:#ef4444;">*</span>
                    </label>
                    <div style="display:flex; gap:8px;">
                        <input type="text" id="form-act-ubicacion-nombre" placeholder="Ej. Frente de Vertido Central, Sector A1, Garita Balanza..." style="flex:1; padding:9px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-weight:600;" />
                        <select id="form-act-sector" onchange="onSectorSelectChange(this)" style="max-width:210px; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px; background:#f8fafc;" title="Vincular a un Sector oficial registrado">
                            <option value="">(Elegir Sector...)</option>
                        </select>
                    </div>
                    <span style="font-size:11px; color:#64748b; margin-top:3px; display:block;">
                        Puede seleccionar un sector de <code>finca_actividad</code> o escribir el nombre específico del lugar.
                    </span>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Tipo de Actividad</label>
                        <select id="form-act-tipo" onchange="onTipoActividadChange(this)" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                            <option value="descarga_relave">Descarga de Relave Seco</option>
                            <option value="descarga_humeda">Descarga de Relave Húmedo / Lodos</option>
                            <option value="compactacion_dique">Compactación y Nivelación de Dique</option>
                            <option value="acarreo_material">Acarreo de Material de Préstamo / Enrocado</option>
                            <option value="monitoreo_piezometro">Monitoreo de Piezómetro / Nivel Freático</option>
                            <option value="mantenimiento_vias">Mantenimiento de Vía de Acarreo</option>
                            <option value="reporte_alerta">Reporte de Alerta / Grieta / Filtración</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Estado Operativo</label>
                        <select id="form-act-estado" onchange="onEstadoActividadChange(this)" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                            <option value="En Progreso">En Progreso</option>
                            <option value="Completada">Completada</option>
                            <option value="Supervisado">Supervisado</option>
                            <option value="Alerta Crítica">Alerta Crítica</option>
                        </select>
                    </div>
                </div>

                <!-- Personalización de Pin de Evento / Labor -->
                <div class="pin-customizer-box">
                    <div style="font-size:11px; font-weight:700; color:#334155; margin-bottom:8px; display:flex; align-items:center; justify-content:space-between;">
                        <span><i class="fa fa-paint-brush text-primary"></i> Personalizar Icono y Color del Evento</span>
                        <span style="font-size:10px; color:#64748b; font-weight:normal;">Personaliza el pin de esta labor</span>
                    </div>
                    <div class="pin-customizer-grid">
                        <div class="pin-preview-card">
                            <span class="preview-title">Vista Pin</span>
                            <div id="preview-act-pin" style="margin-top:2px;"></div>
                        </div>
                        <div class="pin-controls-panel">
                            <div>
                                <span style="font-size:10px; font-weight:600; color:#64748b; display:block; margin-bottom:3px;">Icono:</span>
                                <div class="pin-icons-row" id="act-icon-swatches">
                                    <button type="button" class="pin-icon-btn selected" data-icon="fa-truck" onclick="seleccionarIconoPin('act', 'fa-truck')" title="Volqueta / Transporte"><i class="fa fa-truck"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-cubes" onclick="seleccionarIconoPin('act', 'fa-cubes')" title="Descarga de Relave Seco"><i class="fa fa-cubes"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-tint" onclick="seleccionarIconoPin('act', 'fa-tint')" title="Descarga Húmeda / Lodos"><i class="fa fa-tint"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-cogs" onclick="seleccionarIconoPin('act', 'fa-cogs')" title="Compactación / Maquinaria"><i class="fa fa-cogs"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-road" onclick="seleccionarIconoPin('act', 'fa-road')" title="Mantenimiento Vías / Acarreo"><i class="fa fa-road"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-eye" onclick="seleccionarIconoPin('act', 'fa-eye')" title="Monitoreo / Piezómetro"><i class="fa fa-eye"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-exclamation-triangle" onclick="seleccionarIconoPin('act', 'fa-exclamation-triangle')" title="Alerta / Grieta / Filtración"><i class="fa fa-exclamation-triangle"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-clipboard" onclick="seleccionarIconoPin('act', 'fa-clipboard')" title="Control / Inspección"><i class="fa fa-clipboard"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-check-circle" onclick="seleccionarIconoPin('act', 'fa-check-circle')" title="Concluido / Verificado"><i class="fa fa-check-circle"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-fire-extinguisher" onclick="seleccionarIconoPin('act', 'fa-fire-extinguisher')" title="Seguridad / Incidente"><i class="fa fa-fire-extinguisher"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-bullhorn" onclick="seleccionarIconoPin('act', 'fa-bullhorn')" title="Aviso / Novedad"><i class="fa fa-bullhorn"></i></button>
                                    <button type="button" class="pin-icon-btn" data-icon="fa-map-pin" onclick="seleccionarIconoPin('act', 'fa-map-pin')" title="Punto Fijo"><i class="fa fa-map-pin"></i></button>
                                </div>
                            </div>
                            <div>
                                <span style="font-size:10px; font-weight:600; color:#64748b; display:block; margin-bottom:3px;">Color:</span>
                                <div class="pin-colors-row" id="act-color-swatches">
                                    <button type="button" class="pin-color-chip selected" data-color="#10b981" style="background:#10b981;" onclick="seleccionarColorPin('act', '#10b981')" title="Verde Esmeralda"></button>
                                    <button type="button" class="pin-color-chip" data-color="#3b82f6" style="background:#3b82f6;" onclick="seleccionarColorPin('act', '#3b82f6')" title="Azul Transporte"></button>
                                    <button type="button" class="pin-color-chip" data-color="#06b6d4" style="background:#06b6d4;" onclick="seleccionarColorPin('act', '#06b6d4')" title="Cian Lodos/Agua"></button>
                                    <button type="button" class="pin-color-chip" data-color="#f59e0b" style="background:#f59e0b;" onclick="seleccionarColorPin('act', '#f59e0b')" title="Ámbar En Progreso"></button>
                                    <button type="button" class="pin-color-chip" data-color="#ef4444" style="background:#ef4444;" onclick="seleccionarColorPin('act', '#ef4444')" title="Rojo Alerta"></button>
                                    <button type="button" class="pin-color-chip" data-color="#8b5cf6" style="background:#8b5cf6;" onclick="seleccionarColorPin('act', '#8b5cf6')" title="Morado"></button>
                                    <button type="button" class="pin-color-chip" data-color="#ea580c" style="background:#ea580c;" onclick="seleccionarColorPin('act', '#ea580c')" title="Naranja Maquinaria"></button>
                                    <button type="button" class="pin-color-chip" data-color="#64748b" style="background:#64748b;" onclick="seleccionarColorPin('act', '#64748b')" title="Gris Vías"></button>
                                    <button type="button" class="pin-color-chip" data-color="#059669" style="background:#059669;" onclick="seleccionarColorPin('act', '#059669')" title="Verde Bosque"></button>
                                    <div class="pin-color-custom-wrap" title="Elegir otro color personalizado">
                                        <input type="color" id="picker-act-color-custom" class="pin-color-custom-input" value="#10b981" onchange="seleccionarColorPin('act', this.value)" />
                                        <span class="pin-color-custom-btn"><i class="fa fa-plus"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="form-act-icono" value="fa-truck" />
                    <input type="hidden" id="form-act-color" value="#10b981" />
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">
                            <i class="fa fa-truck text-success"></i> Volqueta / Maquinaria
                        </label>
                        <select id="form-act-volqueta" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                            <option value="">Cargando vehículos desde BD...</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">
                            <i class="fa fa-user text-primary"></i> Chofer / Operador
                        </label>
                        <select id="form-act-chofer" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                            <option value="">Cargando choferes desde BD...</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Fecha y Hora del Evento</label>
                    <input type="datetime-local" id="form-act-fecha" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;" />
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <label style="font-size:12px; font-weight:600; color:#475569; margin:0;">Coordenadas Geográficas</label>
                    <button type="button" class="btn-gps-direct-capture" onclick="capturarGpsActualDispositivo()" title="Tomar posición GPS actual en tiempo real">
                        <i class="fa fa-crosshairs"></i> 🎯 Capturar GPS del Dispositivo
                    </button>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Latitud</label>
                        <input type="text" id="form-act-lat" readonly style="width:100%; padding:8px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:6px; font-size:12px;" />
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Longitud</label>
                        <input type="text" id="form-act-lng" readonly style="width:100%; padding:8px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:6px; font-size:12px;" />
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 12px;">
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Cubicaje (m³)</label>
                        <input type="number" id="form-act-volumen" value="16.0" step="0.5" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;" />
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Área (m²)</label>
                        <input type="text" id="form-act-area" readonly style="width:100%; padding:8px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:6px; font-size:12px;" />
                    </div>
                    <div>
                        <label style="font-size:11px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Longitud (m)</label>
                        <input type="text" id="form-act-longitud" readonly style="width:100%; padding:8px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:6px; font-size:12px;" />
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Observaciones y Detalles Operativos</label>
                    <textarea id="form-act-obs" rows="2" placeholder="Ej. Descarga en frente de vertido sector 1, relave con 18% de humedad..." style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;"></textarea>
                </div>

                <!-- Sección de Carga de Evidencias Fotográficas (Optimizadas con Canvas) -->
                <div class="evidencias-upload-box">
                    <div class="evidencias-upload-header">
                        <span class="evidencias-upload-title">
                            <i class="fa fa-camera text-primary"></i> Evidencias Fotográficas / Documentos
                        </span>
                        <div class="evidencias-upload-btns">
                            <label for="form-act-evidencias-camara" class="btn-evidencia-action btn-camara" title="Tomar foto directa con la cámara">
                                <i class="fa fa-camera"></i> Tomar Foto
                            </label>
                            <input type="file" id="form-act-evidencias-camara" accept="image/*" capture="environment" style="display:none;" onchange="procesarEvidenciasSeleccionadas(this.files)" />

                            <label for="form-act-evidencias-archivo" class="btn-evidencia-action" title="Seleccionar fotos de la galería o archivos">
                                <i class="fa fa-paperclip"></i> Galería / Archivos
                            </label>
                            <input type="file" id="form-act-evidencias-archivo" accept="image/*" multiple style="display:none;" onchange="procesarEvidenciasSeleccionadas(this.files)" />
                        </div>
                    </div>
                    <div style="font-size:10px; color:#64748b; margin-bottom:6px;">
                        Fotos de la labor, boleta de pesaje o novedades. Se comprimen automáticamente en el navegador a ~150KB antes de subir a <code>mapeo/RECURSOS/locator/</code>.
                    </div>
                    <div id="evidencias-preview-container" class="evidencias-preview-grid"></div>
                </div>
            </div>

        </div>
        <div class="modal-mapeo-footer" style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border-top:1px solid #e2e8f0; padding:12px 16px;">
            <button type="button" onclick="cerrarModalActividad(false)" style="padding:8px 14px; background:#e2e8f0; color:#475569; border:none; border-radius:6px; font-weight:600; cursor:pointer;" title="Cancelar y descartar del mapa">
                <i class="fa fa-times"></i> Cancelar
            </button>
            <div style="display:flex; gap:8px;">
                <button id="btn-guardar-sector" onclick="guardarSectorFormulario()" style="padding:9px 18px; background:#10b981; color:#fff; border:none; border-radius:6px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fa fa-save"></i> Guardar Ubicación / Sector
                </button>
                <button id="btn-guardar-actividad" onclick="guardarActividadFormulario()" style="padding:9px 18px; background:#1b7a4a; color:#fff; border:none; border-radius:6px; font-weight:700; cursor:pointer; display:none; align-items:center; gap:6px;">
                    <i class="fa fa-truck"></i> Guardar Evento Operativo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: ALERTA SOS / BOTÓN DE PÁNICO -->
<div class="modal-mapeo" id="modal-sos">
    <div class="modal-mapeo-dialog" style="max-width:480px; border-top: 5px solid #dc2626;">
        <div class="modal-mapeo-header" style="background:#fef2f2;">
            <div style="font-weight:800; font-size:16px; color:#991b1b;">
                <i class="fa fa-exclamation-circle text-danger"></i> EMITIR ALERTA SOS DE EMERGENCIA
            </div>
            <button onclick="cerrarModalSOS()" style="background:none; border:none; font-size:18px; color:#94a3b8; cursor:pointer;">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="modal-mapeo-body">
            <p style="font-size:13px; color:#475569; margin-top:0;">
                Se transmitirá una señal de auxilio georreferenciada inmediata a la Sala de Supervisión y se notificará a todas las unidades activas.
            </p>
            
            <div style="margin-bottom:12px;">
                <label style="font-size:12px; font-weight:700; color:#1e293b; display:block; margin-bottom:4px;">Tipo de Incidente / Emergencia</label>
                <select id="form-sos-motivo" style="width:100%; padding:9px; border:1px solid #dc2626; border-radius:6px; font-size:13px; font-weight:600;">
                    <option value="Derrumbe o Deslizamiento de Talud">Derrumbe / Deslizamiento de Talud</option>
                    <option value="Falla Mecánica / Frenos en Berma">Falla Mecánica / Frenos de Volqueta</option>
                    <option value="Filtración Crítica o Grieta en Dique">Filtración Crítica o Grieta en Dique</option>
                    <option value="Accidente o Volcamiento de Equipo">Accidente / Volcamiento de Equipo</option>
                    <option value="Emergencia Médica de Operador">Emergencia Médica de Operador</option>
                </select>
            </div>

            <div style="margin-bottom:12px;">
                <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Detalles Rápidos</label>
                <textarea id="form-sos-detalles" rows="2" placeholder="Ubicación precisa, personas involucradas..." style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;"></textarea>
            </div>
        </div>
        <div class="modal-mapeo-footer" style="background:#fef2f2;">
            <button onclick="cerrarModalSOS()" style="padding:8px 14px; background:#e2e8f0; color:#475569; border:none; border-radius:6px; font-weight:600; cursor:pointer;">
                Cancelar
            </button>
            <button onclick="enviarAlertaSOS()" style="padding:10px 20px; background:#dc2626; color:#fff; border:none; border-radius:6px; font-weight:800; cursor:pointer;">
                <i class="fa fa-bell"></i> TRANSMITIR SOS AHORA
            </button>
        </div>
    </div>
</div>

<!-- MODAL: EXPORTACIÓN MULTIFORMATO -->
<div class="modal-mapeo" id="modal-exportacion">
    <div class="modal-mapeo-dialog" style="max-width:440px;">
        <div class="modal-mapeo-header">
            <div style="font-weight:700; font-size:15px; color:#1e293b;">
                <i class="fa fa-download text-success"></i> Exportar Capas y Operaciones
            </div>
            <button onclick="cerrarModalExportacion()" style="background:none; border:none; font-size:18px; color:#94a3b8; cursor:pointer;">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="modal-mapeo-body" style="display:flex; flex-direction:column; gap:10px;">
            <button onclick="exportarGeoJSON()" style="padding:12px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; text-align:left; cursor:pointer; display:flex; align-items:center; gap:12px;">
                <i class="fa fa-globe text-primary" style="font-size:22px;"></i>
                <div>
                    <strong style="font-size:13px; color:#1e293b; display:block;">GeoJSON Estándar (.geojson)</strong>
                    <span style="font-size:11px; color:#64748b;">Compatible con QGIS, ArcGIS y Leaflet</span>
                </div>
            </button>

            <button onclick="exportarKML()" style="padding:12px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; text-align:left; cursor:pointer; display:flex; align-items:center; gap:12px;">
                <i class="fa fa-map text-warning" style="font-size:22px;"></i>
                <div>
                    <strong style="font-size:13px; color:#1e293b; display:block;">Google Earth KML (.kml)</strong>
                    <span style="font-size:11px; color:#64748b;">Visualización 3D satelital en Google Earth</span>
                </div>
            </button>

            <button onclick="exportarCSV()" style="padding:12px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; text-align:left; cursor:pointer; display:flex; align-items:center; gap:12px;">
                <i class="fa fa-file-excel-o text-success" style="font-size:22px;"></i>
                <div>
                    <strong style="font-size:13px; color:#1e293b; display:block;">Planilla CSV / Excel (.csv)</strong>
                    <span style="font-size:11px; color:#64748b;">Resumen tabular de pesajes, cubicajes y coordenadas</span>
                </div>
            </button>
        </div>
        <div class="modal-mapeo-footer">
            <button onclick="cerrarModalExportacion()" style="padding:8px 14px; background:#e2e8f0; color:#475569; border:none; border-radius:6px; font-weight:600; cursor:pointer;">
                Cerrar
            </button>
        </div>
    </div>
<!-- MODAL LIGHTBOX: VISOR DE EVIDENCIAS A PANTALLA COMPLETA -->
<div class="locator-lightbox-modal" id="locator-lightbox" onclick="cerrarLightbox()">
    <div class="locator-lightbox-content" onclick="event.stopPropagation()">
        <button type="button" class="locator-lightbox-close" onclick="cerrarLightbox()" title="Cerrar visor">
            <i class="fa fa-times"></i>
        </button>
        <img id="locator-lightbox-img" src="" alt="Evidencia Ampliada" />
        <div class="locator-lightbox-caption" id="locator-lightbox-caption"></div>
    </div>
</div>

<!-- Lógica JavaScript del Módulo -->
<script src="../VALIDACIONES/map_val_mapeo.js?v=4.5"></script>

</body>
</html>
