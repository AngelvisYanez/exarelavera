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
    <title>Mapeo Interactivo Relavera &bull; Sistema GPS y Vectores</title>

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
    <div class="mapeo-workspace">
        
        <!-- Mapa Leaflet Principal -->
        <div id="mapa-relavera"></div>

        <!-- Banner de Alerta de Microzona -->
        <div class="microzone-alert-banner" id="microzone-banner">
            <i class="fa fa-exclamation-triangle" style="font-size:18px;"></i>
            <span id="microzone-banner-text">ALERTA DE VELOCIDAD EN ZONA</span>
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
        <button class="floating-sos-btn" onclick="abrirModalSOS()" title="Emitir Alerta SOS de Emergencia Georreferenciada">
            <i class="fa fa-bullhorn"></i> SOS EMERGENCIA
        </button>

        <!-- HUD Flotante: Telemetría GPS en Vivo de Volquetero -->
        <div class="floating-gps-hud" id="hud-gps-telemetria">
            <div class="gps-hud-header">
                <div>
                    <span class="gps-status-dot" id="gps-status-indicator"></span>
                    <span id="gps-status-text">GPS MÓVIL DISPONIBLE</span>
                </div>
                <span class="badge badge-success" id="hud-gps-geofence" style="font-size:10px; background:#10b981; padding:2px 6px; border-radius:4px;">DENTRO DE RELAVERA</span>
            </div>
            <div class="gps-stats-grid">
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
                    <div class="gps-stat-lbl">Precisión GPS</div>
                </div>
                <div class="gps-stat-box">
                    <div class="gps-stat-val" id="hud-gps-limite" style="color:#f59e0b;">10 km/h</div>
                    <div class="gps-stat-lbl">Límite en Zona</div>
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
        <aside class="mapeo-sidebar" id="sidebar-mapeo">
            
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
                <button class="sidebar-tab-btn active" data-tab="actividades">
                    <i class="fa fa-clipboard"></i> Actividades (<span id="total-actividades-count">0</span>)
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
                <div class="tab-pane active" id="tab-pane-actividades">
                    
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

<!-- MODAL: REGISTRO DE ACTIVIDAD EN MAPA -->
<div class="modal-mapeo" id="modal-registro-actividad">
    <div class="modal-mapeo-dialog">
        <div class="modal-mapeo-header">
            <div style="font-weight:700; font-size:15px; color:#1e293b;">
                <i class="fa fa-clipboard text-success"></i> Registrar Actividad en Relavera
            </div>
            <button onclick="cerrarModalActividad()" style="background:none; border:none; font-size:18px; color:#94a3b8; cursor:pointer;">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="modal-mapeo-body">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Tipo de Actividad</label>
                    <select id="form-act-tipo" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
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
                    <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Estado</label>
                    <select id="form-act-estado" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                        <option value="En Progreso">En Progreso</option>
                        <option value="Completada">Completada</option>
                        <option value="Supervisado">Supervisado</option>
                        <option value="Alerta Crítica">Alerta Crítica</option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Volqueta / Maquinaria</label>
                    <select id="form-act-volqueta" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">
                        <option value="VOL-04 (OBA-7821)">VOL-04 (OBA-7821) - Manuel Carrión</option>
                        <option value="VOL-09 (PBC-3419)">VOL-09 (PBC-3419) - Luis Espinoza</option>
                        <option value="VOL-12 (LBA-9023)">VOL-12 (LBA-9023) - Jorge Aguilar</option>
                        <option value="VOL-15 (PBA-6124)">VOL-15 (PBA-6124) - Carlos Morales</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Chofer / Operador</label>
                    <input type="text" id="form-act-chofer" value="Manuel Carrión" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;" />
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Latitud</label>
                    <input type="text" id="form-act-lat" readonly style="width:100%; padding:8px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:6px; font-size:12px;" />
                </div>
                <div>
                    <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Longitud</label>
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
                <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Fecha y Hora</label>
                <input type="datetime-local" id="form-act-fecha" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;" />
            </div>

            <div>
                <label style="font-size:12px; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Observaciones y Detalles Operativos</label>
                <textarea id="form-act-obs" rows="3" placeholder="Ej. Descarga en frente de vertido sector 1, relave con 18% de humedad..." style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;"></textarea>
            </div>

        </div>
        <div class="modal-mapeo-footer">
            <button onclick="cerrarModalActividad()" style="padding:8px 14px; background:#e2e8f0; color:#475569; border:none; border-radius:6px; font-weight:600; cursor:pointer;">
                Cancelar
            </button>
            <button onclick="guardarActividadFormulario()" style="padding:8px 16px; background:#1b7a4a; color:#fff; border:none; border-radius:6px; font-weight:600; cursor:pointer;">
                <i class="fa fa-save"></i> Guardar Actividad
            </button>
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
</div>

<!-- Lógica JavaScript del Módulo -->
<script src="../VALIDACIONES/map_val_mapeo.js?v=2.2"></script>

</body>
</html>
