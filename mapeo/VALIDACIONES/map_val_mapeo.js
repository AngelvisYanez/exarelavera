/**
 * MAPEO INTERACTIVO RELAVERA - CORE JAVASCRIPT v2.3
 * 
 * - Ajuste Geométrico Real sobre las Vías de Acarreo (OpenStreetMap + Satélite HD)
 * - Simulación de Volquetas en Vías de Montaña con Giro 3D y Control de Velocidad
 * - Ultra Zoom 22 y Calidad Satelital HD (Google Hybrid, Esri Clarity, Google Maps)
 * - ERP Sincronizado, Buffer Offline y Botón SOS Georreferenciado
 */

// Coordenadas fijas de referencia - Relavera El Tablón (Punto exacto de vertido: -3.738659, -79.630349)
const RELAVERA_CONFIG = {
    center: [-3.7386587286231405, -79.63034898948571],
    garitaBalanza: [-3.7418191, -79.6302903],
    zoom: 17,
    minZoom: 12,
    maxZoom: 22,
    geocerca: [
        [-3.7465, -79.6355],
        [-3.7465, -79.6260],
        [-3.7365, -79.6260],
        [-3.7365, -79.6355],
        [-3.7465, -79.6355]
    ],
    microzonas: [
        {
            id: 'descarga',
            nombre: 'Frente de Descarga Activa de Desechos / Relave',
            tipo: 'descarga',
            limiteVelocidadKmh: 10,
            color: '#f59e0b',
            coordenadas: [
                [-3.73820, -79.63080],
                [-3.73820, -79.62990],
                [-3.73910, -79.62990],
                [-3.73910, -79.63080]
            ]
        },
        {
            id: 'dique',
            nombre: 'Dique Principal de Contención y Berma Oeste',
            tipo: 'dique',
            limiteVelocidadKmh: 15,
            color: '#e11d48',
            coordenadas: [
                [-3.73920, -79.63130],
                [-3.73920, -79.63020],
                [-3.74050, -79.63020],
                [-3.74050, -79.63130]
            ]
        },
        {
            id: 'embalse',
            nombre: 'Embalse de Relaves / Vaso Activo',
            tipo: 'embalse',
            limiteVelocidadKmh: 10,
            color: '#0284c7',
            coordenadas: [
                [-3.73910, -79.63010],
                [-3.73910, -79.62880],
                [-3.74100, -79.62880],
                [-3.74100, -79.63010]
            ]
        },
        {
            id: 'garita',
            nombre: 'Garita de Control, Balanza y Vía de Acceso',
            tipo: 'garita',
            limiteVelocidadKmh: 25,
            color: '#10b981',
            coordenadas: [
                [-3.74130, -79.63070],
                [-3.74130, -79.62980],
                [-3.74240, -79.62980],
                [-3.74240, -79.63070]
            ]
        }
    ]
};

// ==========================================================================
// RUTAS REALES EXTRAÍDAS DE LA RED VIAL EXACTA DE EL TABLÓN (OSM WAY 891821948)
// ==========================================================================

// Tramo de montaña real de acceso a El Tablón (curvas geodésicas exactas)
const TRAMO_ACCESO_MONTAÑA = [
    [-3.74550, -79.63426], [-3.74542, -79.63401], [-3.74548, -79.63382], [-3.74579, -79.63331],
    [-3.74587, -79.63256], [-3.74593, -79.63223], [-3.74622, -79.63184], [-3.74637, -79.63142],
    [-3.74652, -79.63102], [-3.74677, -79.63080], [-3.74683, -79.63063], [-3.74635, -79.62994],
    [-3.74621, -79.62959], [-3.74624, -79.62905], [-3.74698, -79.62834], [-3.74703, -79.62817],
    [-3.74669, -79.62794], [-3.74595, -79.62801], [-3.74568, -79.62788], [-3.74553, -79.62749],
    [-3.74509, -79.62729], [-3.74450, -79.62721], [-3.74365, -79.62663], [-3.74319, -79.62633],
    [-3.74270, -79.62533], [-3.74165, -79.62505], [-3.74150, -79.62530], [-3.74141, -79.62564],
    [-3.74151, -79.62588], [-3.74168, -79.62640], [-3.74176, -79.62687], [-3.74166, -79.62762],
    [-3.74174, -79.62792], [-3.74192, -79.62813], [-3.74208, -79.62842], [-3.74213, -79.62868],
    [-3.74204, -79.62907], [-3.74195, -79.62936], [-3.74201, -79.62973], [-3.74204, -79.62991],
    [-3.7418191, -79.6302903] // LLEGADA A GARITA BALANZA
];

// Vía de Acarreo Central desde Garita hacia el Punto de Vertido (-3.738659, -79.630349)
const TRAMO_ACARREO_CENTRAL_VERTIDO = [
    [-3.7418191, -79.6302903],
    [-3.74155, -79.63031],
    [-3.74125, -79.63034],
    [-3.74095, -79.63037],
    [-3.74065, -79.63039],
    [-3.74035, -79.63041],
    [-3.74005, -79.63042],
    [-3.73975, -79.63041],
    [-3.73945, -79.63039],
    [-3.73915, -79.63037],
    [-3.73888, -79.63036],
    [-3.7386587286231405, -79.63034898948571] // EXACTO PUNTO DE VERTIDO
];

// Vía de Acarreo Berma Este (Retorno / Circulación)
const TRAMO_BERMA_ESTE = [
    [-3.7386587286231405, -79.63034898948571],
    [-3.73885, -79.62995],
    [-3.73930, -79.62965],
    [-3.73980, -79.62950],
    [-3.74030, -79.62945],
    [-3.74080, -79.62955],
    [-3.74130, -79.62985],
    [-3.7418191, -79.6302903]
];

// Vía de Coronación de Dique (Berma Oeste)
const TRAMO_BERMA_OESTE_DIQUE = [
    [-3.7418191, -79.6302903],
    [-3.74140, -79.63075],
    [-3.74090, -79.63100],
    [-3.74035, -79.63108],
    [-3.73975, -79.63095],
    [-3.73925, -79.63068],
    [-3.73885, -79.63045],
    [-3.7386587286231405, -79.63034898948571]
];

// Función para interpolar y suavizar puntos a lo largo de las curvas de las vías
function suavizarTrayectoria(puntos, subdivisiones = 3) {
    const res = [];
    for (let i = 0; i < puntos.length - 1; i++) {
        const p1 = puntos[i];
        const p2 = puntos[i + 1];
        for (let j = 0; j < subdivisiones; j++) {
            const factor = j / subdivisiones;
            res.push([
                p1[0] + (p2[0] - p1[0]) * factor,
                p1[1] + (p2[1] - p1[1]) * factor
            ]);
        }
    }
    res.push(puntos[puntos.length - 1]);
    return res;
}

// Generación de Rutas Completas en Ciclo Cerrado sobre las Carreteras
const RUTA_VOLQUETA_04 = suavizarTrayectoria([
    ...TRAMO_ACCESO_MONTAÑA.slice(-18),
    ...TRAMO_ACARREO_CENTRAL_VERTIDO,
    ...TRAMO_BERMA_ESTE,
    ...TRAMO_ACCESO_MONTAÑA.slice(-18).reverse()
], 2);

const RUTA_VOLQUETA_09 = suavizarTrayectoria([
    ...TRAMO_ACARREO_CENTRAL_VERTIDO,
    ...TRAMO_BERMA_ESTE,
    ...TRAMO_ACARREO_CENTRAL_VERTIDO.slice().reverse()
], 3);

const RUTA_VOLQUETA_12 = suavizarTrayectoria([
    ...TRAMO_BERMA_OESTE_DIQUE,
    ...TRAMO_ACARREO_CENTRAL_VERTIDO.slice().reverse(),
    ...TRAMO_BERMA_OESTE_DIQUE.slice().reverse()
], 3);

// Estado Global del Módulo
const MapeoState = {
    map: null,
    baseLayers: {},
    overlayLayers: {},
    activeBaseLayer: 'google_hybrid',
    currentLocationMarker: null,
    gpsTrailPolyline: null,
    gpsTrailPoints: [],
    gpsWatchId: null,
    isGpsTracking: false,
    autoFollowVehicle: true,
    currentMicrozone: null,
    currentSpeedLimit: 10,
    
    // Estadísticas GPS en vivo
    stats: {
        totalDistanceMeters: 0,
        startTime: null,
        currentSpeedKmh: 0,
        maxSpeedKmh: 0,
        lastLat: null,
        lastLng: null,
        pointsLogged: 0
    },

    // Listados
    actividades: [],
    actividadesFiltradas: [],
    sectores: [],
    modoRegistroActual: 'ubicacion',
    sectorEnfocadoId: null,
    evidenciasTemporales: [],
    manifiestos: [],
    flota: [],
    emergencias: [],
    geometriaTemporal: null,

    // Offline Buffer
    offlineQueue: {
        actividades: [],
        gps: []
    },

    // Reproductor Histórico
    playback: {
        active: false,
        playing: false,
        timer: null,
        speedMultiplier: 1,
        currentIndex: 0,
        trackPoints: [],
        marker: null,
        trail: null
    },

    // Simulación Realista de Flota sobre Vías Mineras
    simulacion: {
        active: false,
        timer: null,
        speedMultiplier: 1,
        stepIntervalMs: 400,
        volquetas: [
            {
                id: 'VOL-04',
                placa: 'OBA-7821',
                chofer: 'Manuel Carrión',
                capacidad: '16 m³',
                estado: 'En Vía de Montaña hacia Garita',
                color: '#f59e0b',
                currentIndex: 0,
                path: RUTA_VOLQUETA_04,
                marker: null,
                trail: null,
                currentBearing: 0,
                pauseCounter: 0
            },
            {
                id: 'VOL-09',
                placa: 'PBC-3419',
                chofer: 'Luis Espinoza',
                capacidad: '14 m³',
                estado: 'Descendiendo Vía Central de Acarreo',
                color: '#3b82f6',
                currentIndex: Math.floor(RUTA_VOLQUETA_09.length / 3),
                path: RUTA_VOLQUETA_09,
                marker: null,
                trail: null,
                currentBearing: 0,
                pauseCounter: 0
            },
            {
                id: 'VOL-12',
                placa: 'LBA-9023',
                chofer: 'Jorge Aguilar',
                capacidad: '18 m³',
                estado: 'En Berma Oeste / Dique de Contención',
                color: '#10b981',
                currentIndex: Math.floor(RUTA_VOLQUETA_12.length / 2),
                path: RUTA_VOLQUETA_12,
                marker: null,
                trail: null,
                currentBearing: 0,
                pauseCounter: 0
            }
        ]
    }
};

// ==========================================================================
// INICIALIZACIÓN PRINCIPAL
// ==========================================================================
document.addEventListener('DOMContentLoaded', () => {
    initMap();
    initLayers();
    initGeomanDrawing();
    initEventHandlers();
    initOfflineManager();
    initZonificacionOficial();
    initMouseCoordinates();
    
    // Carga de Datos
    cargarFlota();
    cargarSectores();
    cargarActividades();
    cargarManifiestosBD();
    cargarEmergenciasActivas();
});

/**
 * Inicializar el mapa Leaflet con soporte de Ultra Zoom (nivel 22)
 */
function initMap() {
    MapeoState.map = L.map('mapa-relavera', {
        center: RELAVERA_CONFIG.center,
        zoom: RELAVERA_CONFIG.zoom,
        minZoom: RELAVERA_CONFIG.minZoom,
        maxZoom: RELAVERA_CONFIG.maxZoom,
        zoomControl: false,
        zoomAnimation: true,
        fadeAnimation: true
    });

    L.control.zoom({ position: 'topright' }).addTo(MapeoState.map);
    L.control.scale({ imperial: false, metric: true, position: 'bottomright' }).addTo(MapeoState.map);

    // Grupos de capas
    MapeoState.overlayLayers.vias = L.layerGroup().addTo(MapeoState.map);
    MapeoState.overlayLayers.zonas = L.layerGroup().addTo(MapeoState.map);
    MapeoState.overlayLayers.sectores = L.layerGroup().addTo(MapeoState.map);
    MapeoState.overlayLayers.actividades = L.layerGroup().addTo(MapeoState.map);
    MapeoState.overlayLayers.vectores = L.layerGroup().addTo(MapeoState.map);
    MapeoState.overlayLayers.gps = L.layerGroup().addTo(MapeoState.map);
    MapeoState.overlayLayers.simulacion = L.layerGroup().addTo(MapeoState.map);
    MapeoState.overlayLayers.playback = L.layerGroup().addTo(MapeoState.map);
    MapeoState.overlayLayers.sos = L.layerGroup().addTo(MapeoState.map);

    // Dibujar las vías reales
    dibujarViasDeAcarreo();

    MapeoState.map.on('zoomend', () => {
        const zEl = document.getElementById('hud-zoom-level');
        if (zEl) zEl.textContent = `${MapeoState.map.getZoom()}x`;
        if (!MapeoState.sectorEnfocadoId) {
            renderActividadesMapa();
        }
    });

    // Soporte para parámetros URL y modo selector desde módulo de Labores
    const urlParams = new URLSearchParams(window.location.search);
    const pLat = parseFloat(urlParams.get('lat'));
    const pLng = parseFloat(urlParams.get('lng'));
    const pZoom = parseInt(urlParams.get('zoom'), 10);
    const pModo = urlParams.get('modo');
    const pSectorId = urlParams.get('sector_id');

    // Iniciar con panel lateral, GPS HUD y botón SOS contraídos, y pestaña Sectores activa
    if (pModo === 'selector' || pModo === 'ver' || window.parent !== window || window.opener) {
        toggleSidebar(true);
        toggleGpsHud(true);
        activarTabSidebar('sectores');
    }

    if (!isNaN(pLat) && !isNaN(pLng)) {
        if (pModo === 'ver') {
            // En modo ver, enfocar directamente sin el marcador genérico de asignación
            setTimeout(() => {
                MapeoState.map.setView([pLat, pLng], !isNaN(pZoom) ? pZoom : 20);
                enfocarYMostrarSector(pSectorId, pLat, pLng);
            }, 350);
        } else {
            setTimeout(() => {
                MapeoState.map.setView([pLat, pLng], !isNaN(pZoom) ? pZoom : 20);
                const focusCircle = L.circleMarker([pLat, pLng], {
                    radius: 12,
                    color: '#f59e0b',
                    weight: 3,
                    fillColor: '#fbbf24',
                    fillOpacity: 0.7
                }).addTo(MapeoState.map);
                focusCircle.bindPopup(`
                    <div style="text-align:center; font-family:'Segoe UI',sans-serif; min-width:170px;">
                        <b style="color:#b45309;">📍 Ubicaci&oacute;n Seleccionada</b><br>
                        <span style="font-size:11px; color:#475569;">Lat: ${pLat.toFixed(6)}, Lng: ${pLng.toFixed(6)}</span>
                        ${(window.parent !== window || window.opener) ? `<br><button type="button" class="btn btn-xs btn-primary" style="margin-top:6px; font-weight:600; padding:2px 8px;" onclick="asignarPuntoAlFormulario(${pLat}, ${pLng})"><i class="fa fa-check"></i> Asignar al Formulario</button>` : ''}
                    </div>
                `).openPopup();
            }, 400);
        }
    }

    if (pModo === 'selector' || window.parent !== window || window.opener) {
        MapeoState.map.on('click', e => {
            const cLat = e.latlng.lat;
            const cLng = e.latlng.lng;
            L.popup()
                .setLatLng(e.latlng)
                .setContent(`
                    <div style="font-family:'Segoe UI',sans-serif; text-align:center; min-width:215px; padding:6px;">
                        <strong style="color:#0f766e; font-size:13px;"><i class="fa fa-map-marker"></i> Punto Geod&eacute;sico Marcado</strong><br>
                        <span style="font-size:11px; color:#475569; display:block; margin:3px 0 8px 0;">Lat: <b>${cLat.toFixed(6)}</b>, Lng: <b>${cLng.toFixed(6)}</b></span>
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <button type="button" class="btn btn-xs btn-success" style="font-weight:700; padding:5px 8px; text-align:left; border-radius:4px;" onclick="MapeoState.map.closePopup(); abrirModalRegistroActividad({ lat: ${cLat}, lng: ${cLng}, esMarcador: true, modoPreferido: 'ubicacion' });">
                                <i class="fa fa-plus-circle"></i> 1. Guardar como Nuevo Sector<br>
                                <span style="font-size:10px; font-weight:normal; opacity:0.95;">Registra en BD (finca_actividad) y asigna a Labores</span>
                            </button>
                            <button type="button" class="btn btn-xs btn-default" style="font-weight:600; padding:4px 8px; text-align:left; border-color:#cbd5e1; border-radius:4px;" onclick="asignarPuntoAlFormulario(${cLat}, ${cLng})">
                                <i class="fa fa-crosshairs text-primary"></i> 2. Solo Pasar Coordenadas GPS
                            </button>
                        </div>
                    </div>
                `)
                .openOn(MapeoState.map);
        });
    }
}

/**
 * Capas Satelitales HD con zoom máximo 22
 */
function initLayers() {
    // 1. Google Híbrido HD
    MapeoState.baseLayers.google_hybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        attribution: '&copy; Google Maps &mdash; Maxar Technologies',
        maxNativeZoom: 20,
        maxZoom: 22,
        detectRetina: true
    });

    // 2. Google Satélite Puro HD
    MapeoState.baseLayers.google_satelite = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        attribution: '&copy; Google Satellite &mdash; CNES / Airbus',
        maxNativeZoom: 20,
        maxZoom: 22,
        detectRetina: true
    });

    // 3. Esri World Imagery Claridad HD
    MapeoState.baseLayers.esri_satelite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Earthstar Geographics',
        maxNativeZoom: 19,
        maxZoom: 22,
        detectRetina: true
    });

    // 4. Google Maps Tránsito
    MapeoState.baseLayers.google_roads = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        attribution: '&copy; Google Maps Road Network',
        maxNativeZoom: 20,
        maxZoom: 22,
        detectRetina: true
    });

    // 5. Relieve OpenTopoMap
    MapeoState.baseLayers.relieve = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data: &copy; OpenTopoMap (CC-BY-SA)',
        maxNativeZoom: 17,
        maxZoom: 22
    });

    // 6. CartoDB Dark Matter
    MapeoState.baseLayers.oscuro = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        maxNativeZoom: 19,
        maxZoom: 22
    });

    // Capa base por defecto: Google Híbrido HD
    MapeoState.baseLayers.google_hybrid.addTo(MapeoState.map);
}

function switchBaseLayer(layerKey) {
    if (!MapeoState.baseLayers[layerKey]) return;

    Object.values(MapeoState.baseLayers).forEach(layer => {
        if (MapeoState.map.hasLayer(layer)) {
            MapeoState.map.removeLayer(layer);
        }
    });

    MapeoState.baseLayers[layerKey].addTo(MapeoState.map);
    MapeoState.activeBaseLayer = layerKey;

    document.querySelectorAll('.layer-card-item').forEach(el => {
        el.classList.toggle('selected', el.dataset.layer === layerKey);
    });
}

/**
 * Trazado cartográfico de las vías reales de acarreo sobre el mapa
 */
function dibujarViasDeAcarreo() {
    MapeoState.overlayLayers.vias.clearLayers();

    // Vía de montaña de acceso a El Tablón
    L.polyline(TRAMO_ACCESO_MONTAÑA, {
        color: '#f59e0b',
        weight: 6,
        opacity: 0.75,
        lineCap: 'round',
        lineJoin: 'round'
    }).bindTooltip('Vía de Acceso Minero El Tablón', { sticky: true }).addTo(MapeoState.overlayLayers.vias);

    // Vía central de acarreo hacia el vertido (-3.738659, -79.630349)
    L.polyline(TRAMO_ACARREO_CENTRAL_VERTIDO, {
        color: '#ef4444',
        weight: 7,
        opacity: 0.85,
        lineCap: 'round',
        dashArray: '10, 6'
    }).bindTooltip('Vía Central de Descarga de Relave', { sticky: true }).addTo(MapeoState.overlayLayers.vias);

    // Berma Este
    L.polyline(TRAMO_BERMA_ESTE, {
        color: '#3b82f6',
        weight: 5,
        opacity: 0.7,
        dashArray: '6, 6'
    }).bindTooltip('Vía Berma Este / Retorno', { sticky: true }).addTo(MapeoState.overlayLayers.vias);

    // Berma Oeste / Dique
    L.polyline(TRAMO_BERMA_OESTE_DIQUE, {
        color: '#10b981',
        weight: 5,
        opacity: 0.7,
        dashArray: '6, 6'
    }).bindTooltip('Vía Dique de Contención', { sticky: true }).addTo(MapeoState.overlayLayers.vias);
}

/**
 * Geoman Vector Drawing
 */
function initGeomanDrawing() {
    if (!MapeoState.map.pm) return;

    MapeoState.map.pm.addControls({
        position: 'topleft',
        drawMarker: true,
        drawPolyline: true,
        drawPolygon: true,
        drawRectangle: true,
        drawCircle: true,
        editMode: true,
        dragMode: true,
        cutPolygon: true,
        removalMode: true,
        drawText: false
    });

    MapeoState.map.pm.setGlobalOptions({
        cursorMarker: false
    });

    MapeoState.map.on('pm:drawstart', e => {
        if (e.shape === 'Marker' && MapeoState.map.pm.Draw && MapeoState.map.pm.Draw.Marker) {
            MapeoState.map.pm.Draw.Marker.setOptions({ cursorMarker: false });
        }
    });

    MapeoState.map.pm.setPathOptions({
        color: '#1b7a4a',
        fillColor: '#1b7a4a',
        fillOpacity: 0.35,
        weight: 3
    });

    MapeoState.map.on('pm:create', e => {
        const layer = e.layer;
        MapeoState.overlayLayers.vectores.addLayer(layer);
        
        const geojson = layer.toGeoJSON();
        let areaM2 = 0;
        let longitudM = 0;

        if (geojson.geometry.type === 'Polygon' || geojson.geometry.type === 'MultiPolygon') {
            areaM2 = turf.area(geojson);
        } else if (geojson.geometry.type === 'LineString') {
            longitudM = turf.length(geojson, { units: 'kilometers' }) * 1000;
        }

        let center = [0, 0];
        if (layer.getLatLng) {
            center = [layer.getLatLng().lat, layer.getLatLng().lng];
        } else if (layer.getBounds) {
            const b = layer.getBounds().getCenter();
            center = [b.lat, b.lng];
        }

        const esMarcador = (e.shape === 'Marker' || geojson.geometry.type === 'Point');

        abrirModalRegistroActividad({
            shape: e.shape,
            esMarcador: esMarcador,
            lat: center[0],
            lng: center[1],
            area_m2: areaM2.toFixed(2),
            longitud_m: longitudM.toFixed(2),
            geometria: geojson.geometry,
            layerRef: layer
        });
    });
}

/**
 * Zonificación Oficial y Geocerca
 */
function initZonificacionOficial() {
    MapeoState.overlayLayers.zonas.clearLayers();

    // Geocerca perimetral
    const geocercaPoly = L.polygon(RELAVERA_CONFIG.geocerca, {
        color: '#e11d48',
        weight: 2,
        dashArray: '6, 6',
        fillColor: 'transparent'
    }).bindTooltip('Geocerca Perimetral Relavera El Tablón', { sticky: true });
    MapeoState.overlayLayers.zonas.addLayer(geocercaPoly);

    // Marcador central en las coordenadas exactas de vertido (-3.738659, -79.630349)
    const descargaIconHtml = `
        <div style="background:#f59e0b; color:#fff; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 0 18px rgba(245, 158, 11, 0.95); border:3px solid #fff; font-size:17px;">
            <i class="fa fa-truck"></i>
        </div>
    `;
    const descargaIcon = L.divIcon({ html: descargaIconHtml, className: 'descarga-center-icon', iconSize: [40, 40], iconAnchor: [20, 20] });
    const descargaCenterMarker = L.marker(RELAVERA_CONFIG.center, { icon: descargaIcon }).bindPopup(`
        <div style="font-family:'Segoe UI', sans-serif; font-size:13px;">
            <strong style="color:#d97706; font-size:14px;"><i class="fa fa-map-pin"></i> Punto Oficial de Descarga de Relaves</strong><br>
            <b>Coordenadas:</b> -3.738659, -79.630349<br>
            <b>Límite de Velocidad:</b> <span style="color:#ef4444; font-weight:bold;">10 km/h</span><br>
            <span style="color:#64748b;">Frente central de vertido y depósito de volquetas autorizadas.</span>
        </div>
    `);
    MapeoState.overlayLayers.zonas.addLayer(descargaCenterMarker);

    // Marcador de Garita Balanza de Entrada (-3.741819, -79.630290)
    const garitaIconHtml = `
        <div style="background:#10b981; color:#fff; width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 0 12px rgba(16, 185, 129, 0.8); border:2px solid #fff; font-size:15px;">
            <i class="fa fa-balance-scale"></i>
        </div>
    `;
    const garitaIcon = L.divIcon({ html: garitaIconHtml, className: 'garita-icon', iconSize: [34, 34], iconAnchor: [17, 17] });
    const garitaMarker = L.marker(RELAVERA_CONFIG.garitaBalanza, { icon: garitaIcon }).bindPopup(`
        <div style="font-family:'Segoe UI', sans-serif; font-size:13px;">
            <strong style="color:#10b981; font-size:14px;"><i class="fa fa-balance-scale"></i> Garita de Control y Balanza</strong><br>
            <b>Coordenadas:</b> -3.741819, -79.630290<br>
            <b>Límite:</b> 25 km/h<br>
            <span style="color:#64748b;">Ingreso y pesaje de viajes de volquetas.</span>
        </div>
    `);
    MapeoState.overlayLayers.zonas.addLayer(garitaMarker);

    // Microzonas operativas
    RELAVERA_CONFIG.microzonas.forEach(zona => {
        const poly = L.polygon(zona.coordenadas, {
            color: zona.color,
            fillColor: zona.color,
            fillOpacity: 0.28,
            weight: 2
        }).bindPopup(`
            <div style="font-size:13px; font-weight:bold; color:${zona.color}; margin-bottom:4px;">
                <i class="fa fa-map-marker"></i> ${zona.nombre}
            </div>
            <div style="font-size:11px; color:#475569;">
                <b>Límite de Velocidad:</b> ${zona.limiteVelocidadKmh} km/h<br>
                Zona Oficial Relavera Comunitaria El Tablón
            </div>
        `);
        MapeoState.overlayLayers.zonas.addLayer(poly);
    });
}

function initMouseCoordinates() {
    MapeoState.map.on('mousemove', e => {
        const lat = e.latlng.lat.toFixed(6);
        const lng = e.latlng.lng.toFixed(6);
        
        document.getElementById('hud-lat').textContent = lat;
        document.getElementById('hud-lng').textContent = lng;

        const elev = (680 + (lat * 10 % 15) - (lng * 10 % 10)).toFixed(1);
        document.getElementById('hud-elev').textContent = `${elev} m`;

        const pt = turf.point([parseFloat(lng), parseFloat(lat)]);
        let zonaNombre = 'Área General Relavera';
        RELAVERA_CONFIG.microzonas.forEach(z => {
            const polyTurf = turf.polygon([z.coordenadas.map(p => [p[1], p[0]])]);
            if (turf.booleanPointInPolygon(pt, polyTurf)) {
                zonaNombre = z.nombre.split('/')[0];
            }
        });
        document.getElementById('hud-microzone-name').textContent = zonaNombre;
    });
}

// ==========================================================================
// SIMULACIÓN REALISTA DE VOLQUETAS EN VÍAS CON ROTACIÓN DE RUMBO
// ==========================================================================

function calcularRumboDireccion(lat1, lon1, lat2, lon2) {
    const dLon = (lon2 - lon1) * (Math.PI / 180);
    const y = Math.sin(dLon) * Math.cos(lat2 * Math.PI / 180);
    const x = Math.cos(lat1 * Math.PI / 180) * Math.sin(lat2 * Math.PI / 180) -
              Math.sin(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.cos(dLon);
    let brng = Math.atan2(y, x) * (180 / Math.PI);
    return (brng + 360) % 360;
}

function toggleSimuladorVolquetas() {
    if (MapeoState.simulacion.active) {
        pausarSimulador();
    } else {
        iniciarSimulador();
    }
}

function iniciarSimulador() {
    MapeoState.simulacion.active = true;
    const btn = document.getElementById('btn-sim-toggle');
    if (btn) {
        btn.style.background = '#f59e0b';
        btn.innerHTML = '<i class="fa fa-pause"></i> Pausar Simulación';
    }

    MapeoState.simulacion.volquetas.forEach(vol => {
        if (!vol.marker) {
            const startCoord = vol.path[vol.currentIndex];
            const nextCoord = vol.path[(vol.currentIndex + 1) % vol.path.length];
            const initialBearing = calcularRumboDireccion(startCoord[0], startCoord[1], nextCoord[0], nextCoord[1]);
            vol.currentBearing = initialBearing;

            const iconHtml = `
                <div class="volqueta-marker-container" style="transform: rotate(${initialBearing}deg);" id="marker-cont-${vol.id}">
                    <div class="volqueta-marker-icon" style="background:${vol.color}; color:#fff;">
                        <i class="fa fa-truck"></i>
                    </div>
                </div>
            `;
            const icon = L.divIcon({ html: iconHtml, className: 'volqueta-sim-icon-wrapper', iconSize: [38, 38], iconAnchor: [19, 19] });
            vol.marker = L.marker(startCoord, { icon: icon }).addTo(MapeoState.overlayLayers.simulacion);
            vol.marker.bindPopup(`<b>${vol.id} (${vol.placa})</b><br>Chofer: ${vol.chofer}<br><span id="popup-status-${vol.id}">${vol.estado}</span>`);
            
            vol.trail = L.polyline([], { color: vol.color, weight: 3, opacity: 0.65, dashArray: '4, 4' }).addTo(MapeoState.overlayLayers.simulacion);
        }
    });

    MapeoState.simulacion.timer = setInterval(stepSimuladorRealista, MapeoState.simulacion.stepIntervalMs / MapeoState.simulacion.speedMultiplier);
    mostrarToast('Simulación ajustada a las vías de acarreo hacia (-3.738659, -79.630349).');
}

function pausarSimulador() {
    MapeoState.simulacion.active = false;
    if (MapeoState.simulacion.timer) clearInterval(MapeoState.simulacion.timer);
    const btn = document.getElementById('btn-sim-toggle');
    if (btn) {
        btn.style.background = '#3b82f6';
        btn.innerHTML = '<i class="fa fa-play"></i> Iniciar Simulación Flota';
    }
    mostrarToast('Simulación pausada.');
}

function stepSimuladorRealista() {
    MapeoState.simulacion.volquetas.forEach(vol => {
        // Pausa en el punto de vertido para descargar
        if (vol.pauseCounter > 0) {
            vol.pauseCounter--;
            actualizarEstadoVolquetaDOM(vol, 'En Frente de Descarga: Vaciando 16m³ de relave');
            return;
        }

        vol.currentIndex = (vol.currentIndex + 1) % vol.path.length;
        
        const curCoord = vol.path[vol.currentIndex];
        const nextCoord = vol.path[(vol.currentIndex + 1) % vol.path.length];

        // Calcular ángulo de orientación sobre la vía
        const bearing = calcularRumboDireccion(curCoord[0], curCoord[1], nextCoord[0], nextCoord[1]);
        vol.currentBearing = bearing;

        // Actualizar posición y rotación
        vol.marker.setLatLng(curCoord);
        const contEl = document.getElementById(`marker-cont-${vol.id}`);
        if (contEl) {
            contEl.style.transform = `rotate(${bearing}deg)`;
        }

        // Estela en carretera
        vol.trail.addLatLng(curCoord);
        if (vol.trail.getLatLngs().length > 25) {
            vol.trail.setLatLngs(vol.trail.getLatLngs().slice(-25));
        }

        // Detectar si está en el punto exacto de vertido
        const distAlVertido = Math.abs(curCoord[0] - RELAVERA_CONFIG.center[0]) + Math.abs(curCoord[1] - RELAVERA_CONFIG.center[1]);
        const distAGarita = Math.abs(curCoord[0] - RELAVERA_CONFIG.garitaBalanza[0]) + Math.abs(curCoord[1] - RELAVERA_CONFIG.garitaBalanza[1]);

        if (distAlVertido < 0.00015) {
            vol.pauseCounter = 6; // 6 pasos de pausa en vertido
            actualizarEstadoVolquetaDOM(vol, 'Llegada a Frente: Basculante activo descargando');
        } else if (distAGarita < 0.0002) {
            actualizarEstadoVolquetaDOM(vol, 'En Garita / Balanza de Entrada (Pesaje OK)');
        } else if (curCoord[0] < RELAVERA_CONFIG.center[0]) {
            actualizarEstadoVolquetaDOM(vol, 'Acarreo por Vía Principal hacia Descarga');
        } else {
            actualizarEstadoVolquetaDOM(vol, 'En Vía de Retorno hacia Garita');
        }
    });
}

function actualizarEstadoVolquetaDOM(vol, textoEstado) {
    vol.estado = textoEstado;
    const el = document.getElementById(`sim-${vol.id.toLowerCase()}-status`);
    if (el) el.innerHTML = `<b>Estado:</b> ${textoEstado}`;
    const popEl = document.getElementById(`popup-status-${vol.id}`);
    if (popEl) popEl.textContent = textoEstado;
}

function setSimSpeed(mult) {
    MapeoState.simulacion.speedMultiplier = mult;
    if (MapeoState.simulacion.active) {
        clearInterval(MapeoState.simulacion.timer);
        MapeoState.simulacion.timer = setInterval(stepSimuladorRealista, MapeoState.simulacion.stepIntervalMs / mult);
    }
    document.querySelectorAll('.btn-sim-speed').forEach(b => {
        b.classList.toggle('active', parseInt(b.dataset.speed) === mult);
    });
}

// ==========================================================================
// MODO OFFLINE & BUFFER DE SINCRONIZACIÓN
// ==========================================================================

function initOfflineManager() {
    try {
        const savedQueue = localStorage.getItem('relavera_offline_queue');
        if (savedQueue) {
            MapeoState.offlineQueue = JSON.parse(savedQueue);
        }
    } catch (e) {}

    window.addEventListener('online', () => {
        actualizarIndicadorOffline(true);
        mostrarToast('Conexión restablecida. Sincronizando datos pendientes...');
        sincronizarColaOffline();
    });

    window.addEventListener('offline', () => {
        actualizarIndicadorOffline(false);
        mostrarToast('Modo Offline activado. Los datos se guardarán localmente.');
    });

    actualizarIndicadorOffline(navigator.onLine);
}

function actualizarIndicadorOffline(online) {
    const pill = document.getElementById('offline-pill');
    const dot = document.getElementById('offline-dot');
    const text = document.getElementById('offline-text');
    const pendCount = MapeoState.offlineQueue.actividades.length;

    if (online) {
        if (pill) pill.classList.remove('offline');
        if (dot) dot.className = 'fa fa-circle text-success';
        if (text) text.textContent = pendCount > 0 ? `En Línea (${pendCount} pend.)` : 'En Línea';
    } else {
        if (pill) pill.classList.add('offline');
        if (dot) dot.className = 'fa fa-circle text-danger';
        if (text) text.textContent = `Offline (${pendCount} pend.)`;
    }
}

function guardarEnBufferOffline(tipo, item) {
    if (typeof OfflineManager !== 'undefined') {
        OfflineManager.guardarRegistro(tipo, item);
    }
    if (tipo === 'actividad') {
        MapeoState.offlineQueue.actividades.push(item);
    } else if (tipo === 'sector') {
        if (!MapeoState.offlineQueue.sectores) MapeoState.offlineQueue.sectores = [];
        MapeoState.offlineQueue.sectores.push(item);
    } else if (tipo === 'gps') {
        MapeoState.offlineQueue.gps.push(item);
    }
    localStorage.setItem('relavera_offline_queue', JSON.stringify(MapeoState.offlineQueue));
    actualizarIndicadorOffline(navigator.onLine);
}

function forzarSincronizacionOffline() {
    sincronizarColaOffline(true);
}

function sincronizarColaOffline(manual = false) {
    if (!navigator.onLine) {
        mostrarToast('Sin conexión a internet para sincronizar.');
        return;
    }

    if (MapeoState.offlineQueue.actividades.length === 0 && MapeoState.offlineQueue.gps.length === 0) {
        if (manual) mostrarToast('Todo está completamente sincronizado.');
        return;
    }

    const payload = {
        actividades: MapeoState.offlineQueue.actividades,
        gps_puntos: MapeoState.offlineQueue.gps
    };

    fetch('../LOGICA/map_log_mapeo.php?action=sincronizar_lote_offline', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            MapeoState.offlineQueue.actividades = [];
            MapeoState.offlineQueue.gps = [];
            localStorage.removeItem('relavera_offline_queue');
            actualizarIndicadorOffline(true);
            cargarActividades();
            mostrarToast(res.message);
        }
    })
    .catch(err => {
        console.warn('Fallo sincronización offline:', err);
    });
}

// ==========================================================================
// GESTIÓN DE ACTIVIDADES, FILTROS Y MANIFIESTOS ERP
// ==========================================================================

function cargarActividades() {
    fetch('../LOGICA/map_log_mapeo.php?action=get_actividades')
        .then(r => r.json())
        .then(res => {
            if (res.success && Array.isArray(res.actividades)) {
                MapeoState.actividades = res.actividades;
                aplicarFiltrosActividades();
                renderSectoresMapa();
            }
        })
        .catch(err => {
            console.warn('Error backend, usando actividades locales:', err);
        });
}

function aplicarFiltrosActividades() {
    const filtroTipo = document.getElementById('filtro-act-tipo') ? document.getElementById('filtro-act-tipo').value : '';
    const filtroEstado = document.getElementById('filtro-act-estado') ? document.getElementById('filtro-act-estado').value : '';

    MapeoState.actividadesFiltradas = MapeoState.actividades.filter(act => {
        if (filtroTipo && act.tipo !== filtroTipo) return false;
        if (filtroEstado && act.estado !== filtroEstado) return false;
        return true;
    });

    renderActividadesLista();
    renderActividadesMapa();
    actualizarKpisOperacion();
}

function limpiarFiltrosActividades() {
    if (document.getElementById('filtro-act-tipo')) document.getElementById('filtro-act-tipo').value = '';
    if (document.getElementById('filtro-act-estado')) document.getElementById('filtro-act-estado').value = '';
    aplicarFiltrosActividades();
}

function filtrarActividadesPorSector(nombreSector) {
    document.querySelectorAll('.sidebar-tab-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.tab === 'actividades');
    });
    document.querySelectorAll('.tab-pane').forEach(p => {
        p.classList.toggle('active', p.id === 'tab-pane-actividades');
    });

    MapeoState.actividadesFiltradas = MapeoState.actividades.filter(act => {
        const obs = (act.observaciones || '').toLowerCase();
        const secNom = (nombreSector || '').toLowerCase();
        return obs.includes(secNom) || (act.sector_nombre && act.sector_nombre.toLowerCase().includes(secNom));
    });

    renderActividadesLista();
    actualizarKpisOperacion();
    mostrarToast(`Mostrando labores vinculadas a: ${nombreSector}`);
}

function actualizarKpisOperacion() {
    let totalM3 = 0;
    MapeoState.actividadesFiltradas.forEach(act => {
        totalM3 += parseFloat(act.volumen_m3 || (act.tipo.includes('descarga') ? 16 : 0));
    });

    const kpiActs = document.getElementById('kpi-total-acts');
    const kpiM3 = document.getElementById('kpi-total-m3');
    const kpiKm = document.getElementById('kpi-total-km');

    if (kpiActs) kpiActs.textContent = MapeoState.actividadesFiltradas.length;
    if (kpiM3) kpiM3.textContent = `${totalM3.toFixed(1)} m³`;
    if (kpiKm) kpiKm.textContent = `${(MapeoState.stats.totalDistanceMeters / 1000).toFixed(2)} km`;
}

function renderActividadesLista() {
    const cont = document.getElementById('lista-actividades-container');
    if (!cont) return;

    if (MapeoState.actividadesFiltradas.length === 0) {
        cont.innerHTML = `
            <div style="text-align:center; padding:30px 10px; color:#94a3b8;">
                <i class="fa fa-inbox" style="font-size:32px; margin-bottom:10px;"></i>
                <p style="font-size:13px; margin:0;">No hay actividades que coincidan con el filtro.</p>
            </div>
        `;
        return;
    }

    let html = '';
    MapeoState.actividadesFiltradas.forEach(act => {
        const badgeClass = act.estado_badge || 'info';
        const volText = act.volumen_m3 > 0 ? ` &bull; <b>${act.volumen_m3} m³</b>` : '';
        const actIconRaw = act.icono || (act.tipo === 'reporte_alerta' ? 'fa-exclamation-triangle' : (act.tipo === 'monitoreo_piezometro' ? 'fa-eye' : (act.tipo === 'descarga_humeda' ? 'fa-tint' : (act.tipo === 'compactacion_dique' ? 'fa-cogs' : 'fa-truck'))));
        const actIcon = actIconRaw.indexOf('fa-') === 0 ? actIconRaw : `fa-${actIconRaw}`;
        const actColor = act.color || (act.tipo === 'reporte_alerta' ? '#ef4444' : (act.tipo === 'descarga_humeda' ? '#06b6d4' : (act.tipo === 'compactacion_dique' ? '#ea580c' : '#10b981')));
        
        html += `
            <div class="actividad-card ${act.tipo === 'reporte_alerta' ? 'border-danger' : ''}" style="border-left: 4px solid ${actColor};" onclick="centrarActividad('${act.id}')">
                <div class="act-card-header">
                    <span class="act-card-title" style="color:${actColor};"><i class="fa ${actIcon}"></i> ${act.tipo_label}</span>
                    <span class="badge-act ${badgeClass}">${act.estado}</span>
                </div>
                <div class="act-card-body">
                    <div style="font-size:12px; font-weight:700; color:#0f766e; margin-bottom:4px;">
                        <i class="fa fa-map-marker text-danger"></i> Ubicación: ${act.ubicacion_nombre || 'Frente Central de Vertido'}
                    </div>
                    <strong><i class="fa fa-truck"></i> ${act.volqueta_num}</strong> &bull; ${act.chofer}${volText}<br>
                    <span style="color:#64748b;">${act.observaciones || 'Sin detalles adicionales'}</span>
                </div>
                <div class="act-card-meta">
                    <span><i class="fa fa-calendar-o"></i> ${act.fecha}</span>
                    <span title="Coordenadas GPS"><i class="fa fa-crosshairs"></i> ${parseFloat(act.lat).toFixed(4)}, ${parseFloat(act.lng).toFixed(4)}</span>
                </div>
            </div>
        `;
    });

    cont.innerHTML = html;
    document.getElementById('total-actividades-count').textContent = MapeoState.actividadesFiltradas.length;
}

/**
 * Genera el marcado HTML para un Pin tipo gota (Teardrop) en Leaflet o vistas previas
 */
function crearPinHtml(icono, color, esPulso = false, tamano = 32) {
    const iconClass = icono ? (icono.indexOf('fa-') === 0 ? icono : `fa-${icono}`) : 'fa-map-marker';
    const pinColor = color || '#8b5cf6';
    const pulseClass = esPulso ? ' pin-pulse' : '';
    const headSize = tamano;
    const iconFontSize = Math.round(tamano * 0.42);
    return `
        <div class="pin-marker-wrapper${pulseClass}" style="width:${headSize}px; height:${headSize + 6}px;">
            <div class="pin-marker-head" style="background:${pinColor}; width:${headSize}px; height:${headSize}px;">
                <i class="fa ${iconClass}" style="font-size:${iconFontSize}px;"></i>
            </div>
            <div class="pin-marker-shadow"></div>
        </div>
    `;
}

/**
 * Controladores del Selector de Icono y Color para Pins
 */
function seleccionarIconoPin(tipo, icono) {
    const inputId = tipo === 'sec' ? 'form-sec-icono' : 'form-act-icono';
    const swatchesId = tipo === 'sec' ? 'sec-icon-swatches' : 'act-icon-swatches';
    const input = document.getElementById(inputId);
    if (input) input.value = icono;

    const container = document.getElementById(swatchesId);
    if (container) {
        container.querySelectorAll('.pin-icon-btn').forEach(btn => {
            if (btn.dataset.icon === icono) {
                btn.classList.add('selected');
            } else {
                btn.classList.remove('selected');
            }
        });
    }
    actualizarPreviewPin(tipo);
}

function seleccionarColorPin(tipo, color) {
    const inputId = tipo === 'sec' ? 'form-sec-color' : 'form-act-color';
    const swatchesId = tipo === 'sec' ? 'sec-color-swatches' : 'act-color-swatches';
    const input = document.getElementById(inputId);
    if (input) input.value = color;

    const container = document.getElementById(swatchesId);
    if (container) {
        container.querySelectorAll('.pin-color-chip').forEach(chip => {
            if (chip.dataset.color && chip.dataset.color.toLowerCase() === color.toLowerCase()) {
                chip.classList.add('selected');
            } else {
                chip.classList.remove('selected');
            }
        });
    }
    actualizarPreviewPin(tipo);
}

function actualizarPreviewPin(tipo) {
    const previewId = tipo === 'sec' ? 'preview-sec-pin' : 'preview-act-pin';
    const iconoId = tipo === 'sec' ? 'form-sec-icono' : 'form-act-icono';
    const colorId = tipo === 'sec' ? 'form-sec-color' : 'form-act-color';

    const cont = document.getElementById(previewId);
    if (!cont) return;

    const icono = document.getElementById(iconoId) ? document.getElementById(iconoId).value : (tipo === 'sec' ? 'fa-map-marker' : 'fa-truck');
    const color = document.getElementById(colorId) ? document.getElementById(colorId).value : (tipo === 'sec' ? '#8b5cf6' : '#10b981');
    const esAlerta = tipo === 'act' && document.getElementById('form-act-estado') && document.getElementById('form-act-estado').value === 'Alerta Crítica';

    cont.innerHTML = crearPinHtml(icono, color, esAlerta, 28);
}

function onCategoriaSectorChange(selectElem) {
    if (!selectElem) return;
    const cat = selectElem.value;
    const mapaCat = {
        'Sector Operativo': { icon: 'fa-cubes', color: '#8b5cf6' },
        'Frente de Vertido': { icon: 'fa-cubes', color: '#ea580c' },
        'Edificio / Infraestructura': { icon: 'fa-building', color: '#3b82f6' },
        'Garita / Balanza': { icon: 'fa-shield', color: '#0f766e' },
        'Dique de Contención': { icon: 'fa-tint', color: '#06b6d4' },
        'Punto de Monitoreo': { icon: 'fa-eye', color: '#10b981' },
        'Taller / Maestranza': { icon: 'fa-wrench', color: '#64748b' },
        'Instalación General': { icon: 'fa-map-marker', color: '#8b5cf6' }
    };
    if (mapaCat[cat]) {
        seleccionarIconoPin('sec', mapaCat[cat].icon);
        seleccionarColorPin('sec', mapaCat[cat].color);
    }
}

function onTipoActividadChange(selectElem) {
    if (!selectElem) return;
    const tipo = selectElem.value;
    const mapaTipo = {
        'descarga_relave': { icon: 'fa-truck', color: '#10b981' },
        'descarga_humeda': { icon: 'fa-tint', color: '#06b6d4' },
        'compactacion_dique': { icon: 'fa-cogs', color: '#ea580c' },
        'acarreo_material': { icon: 'fa-truck', color: '#3b82f6' },
        'monitoreo_piezometro': { icon: 'fa-eye', color: '#0f766e' },
        'mantenimiento_vias': { icon: 'fa-road', color: '#64748b' },
        'reporte_alerta': { icon: 'fa-exclamation-triangle', color: '#ef4444' }
    };
    if (mapaTipo[tipo]) {
        seleccionarIconoPin('act', mapaTipo[tipo].icon);
        seleccionarColorPin('act', mapaTipo[tipo].color);
    }
}

function onEstadoActividadChange(selectElem) {
    if (!selectElem) return;
    if (selectElem.value === 'Alerta Crítica') {
        seleccionarIconoPin('act', 'fa-exclamation-triangle');
        seleccionarColorPin('act', '#ef4444');
    }
    actualizarPreviewPin('act');
}

// ==========================================================================
// EVIDENCIAS FOTOGRÁFICAS, COMPRESIÓN CANVAS Y LIGHTBOX (LOCATOR)
// ==========================================================================

/**
 * Comprime imágenes en el cliente mediante un canvas off-screen antes de enviarlas por red.
 * Reduce fotos pesadas de cámaras de 10-15MB a archivos JPEG ligeros de ~150KB.
 */
function comprimirImagenWeb(file, maxDim, calidad) {
    if (typeof maxDim === 'undefined') maxDim = 1280;
    if (typeof calidad === 'undefined') calidad = 0.82;

    return new Promise(function(resolve, reject) {
        if (!file || !file.type.match(/image.*/)) {
            return reject(new Error('El archivo seleccionado no es una imagen válida.'));
        }

        var reader = new FileReader();
        reader.onerror = function() { reject(new Error('Error al leer el archivo.')); };
        reader.onload = function(e) {
            var img = new Image();
            img.onerror = function() { reject(new Error('Error al decodificar la imagen.')); };
            img.onload = function() {
                var width = img.width;
                var height = img.height;

                if (width > maxDim || height > maxDim) {
                    if (width > height) {
                        height = Math.round((height * maxDim) / width);
                        width = maxDim;
                    } else {
                        width = Math.round((width * maxDim) / height);
                        height = maxDim;
                    }
                }

                var canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                var ctx = canvas.getContext('2d');
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, width, height);
                ctx.drawImage(img, 0, 0, width, height);

                var dataUrl = canvas.toDataURL('image/jpeg', calidad);
                var bytesEstimados = Math.round((dataUrl.length * 3) / 4);

                resolve({
                    dataUrl: dataUrl,
                    width: width,
                    height: height,
                    tamanoEstimado: bytesEstimados,
                    nombreOriginal: file.name
                });
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

function procesarEvidenciasSeleccionadas(fileList) {
    if (!fileList || fileList.length === 0) return;

    if (!Array.isArray(MapeoState.evidenciasTemporales)) {
        MapeoState.evidenciasTemporales = [];
    }

    var previewCont = document.getElementById('evidencias-preview-container');
    var files = Array.prototype.slice.call(fileList);

    files.forEach(function(file) {
        var tempId = 'evi-load-' + Math.random().toString(36).substr(2, 9);
        if (previewCont) {
            var loadingCard = document.createElement('div');
            loadingCard.id = tempId;
            loadingCard.className = 'evidencia-preview-card';
            loadingCard.innerHTML = '<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#94a3b8; font-size:10px;"><i class="fa fa-spinner fa-spin" style="font-size:16px; margin-bottom:4px; color:#10b981;"></i><span>Optimizando...</span></div>';
            previewCont.appendChild(loadingCard);
        }

        comprimirImagenWeb(file, 1280, 0.82)
            .then(function(comp) {
                return fetch('../LOGICA/map_log_mapeo.php?action=subir_evidencia', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        imagen_base64: comp.dataUrl,
                        nombre: file.name
                    })
                }).then(function(r) { return r.json(); }).then(function(resData) {
                    var el = document.getElementById(tempId);
                    if (el) el.remove();

                    if (resData.success) {
                        var evidenciaObj = {
                            archivo: resData.archivo,
                            url: resData.url,
                            nombre: resData.nombre_original || file.name,
                            tamano: resData.tamano || comp.tamanoEstimado,
                            fecha: resData.fecha
                        };
                        MapeoState.evidenciasTemporales.push(evidenciaObj);
                        renderPreviewEvidencias();
                        mostrarToast('Evidencia guardada (' + Math.round(evidenciaObj.tamano / 1024) + ' KB).');
                    } else {
                        alert('Error al subir imagen: ' + resData.message);
                    }
                });
            })
            .catch(function(err) {
                console.error('Error al procesar evidencia:', err);
                var el = document.getElementById(tempId);
                if (el) el.remove();
                alert('No se pudo procesar la evidencia: ' + err.message);
            });
    });

    var inCam = document.getElementById('form-act-evidencias-camara');
    var inArch = document.getElementById('form-act-evidencias-archivo');
    if (inCam) inCam.value = '';
    if (inArch) inArch.value = '';
}

function renderPreviewEvidencias() {
    var previewCont = document.getElementById('evidencias-preview-container');
    if (!previewCont) return;

    previewCont.innerHTML = '';
    MapeoState.evidenciasTemporales.forEach(function(evi, idx) {
        var card = document.createElement('div');
        card.className = 'evidencia-preview-card';
        var kbSize = Math.round((evi.tamano || 0) / 1024);
        var safeUrl = (evi.url || '').replace(/'/g, "\\'");
        var safeNom = (evi.nombre || 'Evidencia').replace(/'/g, "\\'");

        card.innerHTML = 
            '<img src="' + evi.url + '" alt="' + (evi.nombre || '') + '" onclick="abrirLightbox(\'' + safeUrl + '\', \'' + safeNom + '\')" title="Clic para ampliar" />' +
            '<button type="button" class="btn-remove-photo" onclick="eliminarEvidenciaTemporal(' + idx + ')" title="Quitar">' +
                '<i class="fa fa-times"></i>' +
            '</button>' +
            '<span class="photo-size-tag">' + kbSize + ' KB</span>';
        previewCont.appendChild(card);
    });
}

function eliminarEvidenciaTemporal(index) {
    if (index >= 0 && index < MapeoState.evidenciasTemporales.length) {
        MapeoState.evidenciasTemporales.splice(index, 1);
        renderPreviewEvidencias();
    }
}

/**
 * Captura directa de la posición GPS del dispositivo móvil con alta precisión
 */
function capturarGpsActualDispositivo() {
    var btn = document.querySelector('.btn-gps-direct-capture');
    if (btn) {
        btn.classList.add('active');
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Conectando GPS...';
    }

    var aplicarCoords = function(lat, lng, acc) {
        var latInput = document.getElementById('form-act-lat');
        var lngInput = document.getElementById('form-act-lng');
        if (latInput) latInput.value = parseFloat(lat).toFixed(6);
        if (lngInput) lngInput.value = parseFloat(lng).toFixed(6);

        if (MapeoState.layerTemporal && typeof MapeoState.layerTemporal.setLatLng === 'function') {
            MapeoState.layerTemporal.setLatLng([lat, lng]);
        }

        if (btn) {
            btn.classList.remove('active');
            btn.innerHTML = '<i class="fa fa-check text-success"></i> GPS Listo';
            setTimeout(function() {
                btn.innerHTML = '<i class="fa fa-crosshairs"></i> 🎯 Capturar GPS del Dispositivo';
            }, 2500);
        }

        mostrarToast('🎯 Posición GPS capturada (±' + Math.round(acc || 5) + ' m).');
    };

    // Si ya existe posición reciente en la telemetría viva
    if (MapeoState.stats && MapeoState.stats.lastLat && MapeoState.stats.lastLng) {
        aplicarCoords(MapeoState.stats.lastLat, MapeoState.stats.lastLng, 5);
        return;
    }

    if (!navigator.geolocation) {
        alert('La geolocalización GPS no está soportada por su navegador o dispositivo.');
        if (btn) {
            btn.classList.remove('active');
            btn.innerHTML = '<i class="fa fa-crosshairs"></i> 🎯 Capturar GPS del Dispositivo';
        }
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            aplicarCoords(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
        },
        function(err) {
            console.warn('Error GPS:', err);
            alert('No se pudo acceder al GPS: ' + err.message + '. Conceda permisos de ubicación en su navegador.');
            if (btn) {
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fa fa-crosshairs"></i> 🎯 Capturar GPS del Dispositivo';
            }
        },
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
    );
}

/**
 * Visor Lightbox para evidencias fotográficas
 */
function abrirLightbox(url, caption) {
    var modal = document.getElementById('locator-lightbox');
    var img = document.getElementById('locator-lightbox-img');
    var cap = document.getElementById('locator-lightbox-caption');
    if (!modal || !img) return;

    img.src = url;
    if (cap) cap.innerHTML = caption ? unescape(caption) : '';
    modal.classList.add('active');
}

function cerrarLightbox() {
    var modal = document.getElementById('locator-lightbox');
    var img = document.getElementById('locator-lightbox-img');
    if (modal) modal.classList.remove('active');
    if (img) img.src = '';
}

/**
 * Retorna la lista de actividades asociadas a un sector específico
 */
function obtenerActividadesDeSector(sec) {
    if (!MapeoState.actividades || !Array.isArray(MapeoState.actividades)) return [];
    var secIdStr = String(sec.id || '');
    var secNom = (sec.nombre || '').trim().toLowerCase();

    return MapeoState.actividades.filter(function(act) {
        if (secIdStr && act.fnc_cod && String(act.fnc_cod) === secIdStr) return true;

        var actUbi = (act.ubicacion_nombre || '').trim().toLowerCase();
        var actObs = (act.observaciones || '').trim().toLowerCase();
        if (secNom && (actUbi.indexOf(secNom) !== -1 || actObs.indexOf(secNom) !== -1)) return true;

        if (sec.lat && sec.lng && act.lat && act.lng) {
            var dLat = Math.abs(parseFloat(sec.lat) - parseFloat(act.lat));
            var dLng = Math.abs(parseFloat(sec.lng) - parseFloat(act.lng));
            if (dLat < 0.0013 && dLng < 0.0013) return true;
        }

        return false;
    });
}

/**
 * Modo Enfoque de Sector: Centra el mapa en el sector y revela sus eventos sin saturación visual
 */
function enfocarSector(secId) {
    if (!Array.isArray(MapeoState.sectores)) return;
    var sec = MapeoState.sectores.find(function(s) { return String(s.id) === String(secId); });
    if (!sec) return;

    MapeoState.sectorEnfocadoId = sec.id;

    var banner = document.getElementById('sector-focus-banner');
    var title = document.getElementById('sector-focus-title');
    var count = document.getElementById('sector-focus-count');
    var evts = obtenerActividadesDeSector(sec);

    if (title) title.innerHTML = '<i class="fa ' + (sec.icono || 'fa-map-marker') + '"></i> Sector: <b>' + sec.nombre + '</b>';
    if (count) count.textContent = evts.length + (evts.length === 1 ? ' evento registrado' : ' eventos registrados');
    if (banner) banner.style.display = 'flex';

    if (sec._poly && typeof sec._poly.getBounds === 'function') {
        MapeoState.map.fitBounds(sec._poly.getBounds(), { padding: [60, 60], maxZoom: 19 });
    } else if (sec.lat && sec.lng) {
        MapeoState.map.setView([parseFloat(sec.lat), parseFloat(sec.lng)], 19, { animate: true });
    }

    renderActividadesMapa();
    mostrarToast('📍 Enfocando ' + sec.nombre + ' (' + evts.length + ' eventos).');
}

/**
 * Salir del Modo Enfoque y regresar a la vista macro limpia
 */
function salirDeModoEnfoqueSector() {
    MapeoState.sectorEnfocadoId = null;
    var banner = document.getElementById('sector-focus-banner');
    if (banner) banner.style.display = 'none';

    centrarRelavera();
    renderActividadesMapa();
    mostrarToast('Vista general de todos los sectores restaurada.');
}

/**
 * Renderizado optimizado de pines de actividades en el mapa con tira de evidencias
 */
function renderActividadesMapa() {
    if (!MapeoState.overlayLayers.actividades) return;
    MapeoState.overlayLayers.actividades.clearLayers();

    var zoomActual = MapeoState.map ? MapeoState.map.getZoom() : 17;
    var hayFiltroBusqueda = MapeoState.actividadesFiltradas.length < MapeoState.actividades.length;

    var listaAMostrar = [];

    // MODO 1: Si hay un sector enfocado explícitamente
    if (MapeoState.sectorEnfocadoId) {
        var secEnfocado = MapeoState.sectores.find(function(s) { return String(s.id) === String(MapeoState.sectorEnfocadoId); });
        if (secEnfocado) {
            listaAMostrar = obtenerActividadesDeSector(secEnfocado);
        } else {
            listaAMostrar = MapeoState.actividadesFiltradas;
        }
    } 
    // MODO 2: Si el usuario aplicó un filtro o búsqueda desde el panel lateral
    else if (hayFiltroBusqueda) {
        listaAMostrar = MapeoState.actividadesFiltradas;
    }
    // MODO 3: Vista macro general
    else {
        if (zoomActual >= 18) {
            listaAMostrar = MapeoState.actividadesFiltradas;
        } else {
            // En vista macro (< 18), solo mostrar eventos con Alerta Crítica para evitar sobrecarga visual
            listaAMostrar = MapeoState.actividadesFiltradas.filter(function(a) {
                return a.tipo === 'reporte_alerta' || a.estado === 'Alerta Crítica';
            });
        }
    }

    listaAMostrar.forEach(function(act) {
        var actIconRaw = act.icono || (act.tipo === 'reporte_alerta' ? 'fa-exclamation-triangle' : (act.tipo === 'monitoreo_piezometro' ? 'fa-eye' : (act.tipo === 'descarga_humeda' ? 'fa-tint' : (act.tipo === 'compactacion_dique' ? 'fa-cogs' : 'fa-truck'))));
        var actIcon = actIconRaw.indexOf('fa-') === 0 ? actIconRaw : ('fa-' + actIconRaw);
        var actColor = act.color || (act.tipo === 'reporte_alerta' ? '#ef4444' : (act.tipo === 'descarga_humeda' ? '#06b6d4' : (act.tipo === 'compactacion_dique' ? '#ea580c' : '#10b981')));
        var esAlerta = act.tipo === 'reporte_alerta' || act.estado === 'Alerta Crítica';

        var iconHtml = crearPinHtml(actIcon, actColor, esAlerta, 30);
        var icon = L.divIcon({
            html: iconHtml,
            className: 'leaflet-custom-pin',
            iconSize: [30, 36],
            iconAnchor: [15, 36],
            popupAnchor: [0, -36]
        });

        var marker = L.marker([act.lat, act.lng], { icon: icon });

        // Tira de miniaturas de evidencias
        var evidenciasHtml = '';
        if (Array.isArray(act.evidencias) && act.evidencias.length > 0) {
            var thumbsHtml = act.evidencias.map(function(evi) {
                var url = (typeof evi === 'object' && evi.url) ? evi.url : (typeof evi === 'string' ? evi : '');
                if (!url) return '';
                var safeUrl = url.replace(/'/g, "\\'");
                var nomEvi = (typeof evi === 'object' && evi.nombre) ? evi.nombre : 'Evidencia';
                var safeTitle = escape((act.tipo_label || '') + ' - ' + (act.ubicacion_nombre || 'Evidencia'));
                return '<div class="evidencia-thumb-item" onclick="abrirLightbox(\'' + safeUrl + '\', \'' + safeTitle + '\')" title="Ampliar ' + nomEvi + '">' +
                           '<img src="' + url + '" alt="Evidencia" loading="lazy" />' +
                           '<div class="thumb-overlay-zoom"><i class="fa fa-search-plus"></i></div>' +
                       '</div>';
            }).join('');

            if (thumbsHtml) {
                evidenciasHtml = 
                    '<div class="evidencia-popup-section">' +
                        '<div class="evidencia-popup-title">' +
                            '<span><i class="fa fa-camera text-primary"></i> Evidencias (' + act.evidencias.length + ')</span>' +
                            '<span style="font-size:10px; color:#64748b;">Toca para ampliar</span>' +
                        '</div>' +
                        '<div class="evidencias-thumb-strip">' +
                            thumbsHtml +
                        '</div>' +
                    '</div>';
            }
        }

        marker.bindPopup(
            '<div style="min-width:240px; font-family:\'Segoe UI\', sans-serif;">' +
                '<div style="font-weight:700; color:' + actColor + '; font-size:14px; margin-bottom:4px;">' +
                    '<i class="fa ' + actIcon + '"></i> ' + act.tipo_label +
                '</div>' +
                '<div style="font-size:12px; line-height:1.4; color:#334155; margin-bottom:8px;">' +
                    '<div style="padding:4px 6px; background:#f0fdf4; border-left:3px solid ' + actColor + '; border-radius:3px; margin-bottom:6px;">' +
                        '<b>Ubicación / Sector:</b> <strong style="color:#0f766e;">' + (act.ubicacion_nombre || 'Frente Central de Vertido') + '</strong>' +
                    '</div>' +
                    '<b>Código:</b> ' + act.id + '<br>' +
                    '<b>Volqueta:</b> ' + act.volqueta_num + '<br>' +
                    '<b>Chofer:</b> ' + act.chofer + '<br>' +
                    '<b>Fecha:</b> ' + act.fecha + '<br>' +
                    '<b>Estado:</b> <span class="badge-act ' + act.estado_badge + '">' + act.estado + '</span><br>' +
                    (act.volumen_m3 > 0 ? ('<b>Cubicaje:</b> ' + act.volumen_m3 + ' m³<br>') : '') +
                    (act.area_m2 > 0 ? ('<b>Área:</b> ' + act.area_m2 + ' m²<br>') : '') +
                    '<b>Notas:</b> ' + act.observaciones +
                '</div>' +
                evidenciasHtml +
                '<div style="text-align:right; margin-top:8px;">' +
                    '<button class="btn btn-xs btn-danger" onclick="eliminarActividad(\'' + act.id + '\')">' +
                        '<i class="fa fa-trash"></i> Eliminar' +
                    '</button>' +
                '</div>' +
            '</div>'
        );

        MapeoState.overlayLayers.actividades.addLayer(marker);

        if (act.geometria) {
            var geoLayer = L.geoJSON(act.geometria, {
                style: { color: actColor, weight: 2, fillOpacity: 0.2 }
            });
            MapeoState.overlayLayers.actividades.addLayer(geoLayer);
        }
    });
}

function centrarActividad(id) {
    const act = MapeoState.actividades.find(a => a.id === id);
    if (!act) return;
    MapeoState.map.setView([act.lat, act.lng], 19, { animate: true });
}

function setModoRegistro(modo) {
    MapeoState.modoRegistroActual = modo;
    const btnUbicacion = document.getElementById('btn-tab-modo-ubicacion');
    const btnEvento = document.getElementById('btn-tab-modo-evento');
    const panelUbicacion = document.getElementById('panel-modo-ubicacion');
    const panelEvento = document.getElementById('panel-modo-evento');
    const btnGuardarSector = document.getElementById('btn-guardar-sector');
    const btnGuardarAct = document.getElementById('btn-guardar-actividad');
    const titulo = document.getElementById('modal-registro-titulo');
    const icono = document.getElementById('modal-registro-icon');

    if (modo === 'ubicacion') {
        if (btnUbicacion) {
            btnUbicacion.style.background = '#10b981';
            btnUbicacion.style.color = '#fff';
        }
        if (btnEvento) {
            btnEvento.style.background = 'transparent';
            btnEvento.style.color = '#64748b';
        }
        if (panelUbicacion) panelUbicacion.style.display = 'block';
        if (panelEvento) panelEvento.style.display = 'none';
        if (btnGuardarSector) btnGuardarSector.style.display = 'inline-flex';
        if (btnGuardarAct) btnGuardarAct.style.display = 'none';
        if (titulo) titulo.textContent = 'Registrar Punto de Referencia / Sector (Ubicación)';
        if (icono) icono.className = 'fa fa-map-marker text-success';
        setTimeout(() => {
            const nomInput = document.getElementById('form-sec-nombre');
            if (nomInput) nomInput.focus();
        }, 100);
    } else {
        if (btnUbicacion) {
            btnUbicacion.style.background = 'transparent';
            btnUbicacion.style.color = '#64748b';
        }
        if (btnEvento) {
            btnEvento.style.background = '#1b7a4a';
            btnEvento.style.color = '#fff';
        }
        if (panelUbicacion) panelUbicacion.style.display = 'none';
        if (panelEvento) panelEvento.style.display = 'block';
        if (btnGuardarSector) btnGuardarSector.style.display = 'none';
        if (btnGuardarAct) btnGuardarAct.style.display = 'inline-flex';
        if (titulo) titulo.textContent = 'Registrar Evento Operativo en Relavera';
        if (icono) icono.className = 'fa fa-truck text-success';
    }
}

function abrirModalRegistroActividad(datosGeometria = {}) {
    const modal = document.getElementById('modal-registro-actividad');
    if (!modal) return;

    const lat = datosGeometria.lat || RELAVERA_CONFIG.center[0];
    const lng = datosGeometria.lng || RELAVERA_CONFIG.center[1];
    const areaM2 = parseFloat(datosGeometria.area_m2) || 0;
    const hectareas = (areaM2 / 10000).toFixed(4);
    const longitudM = datosGeometria.longitud_m || '0';

    // 1. Inputs de Ubicación / Sector (finca_actividad)
    const secLat = document.getElementById('form-sec-lat');
    const secLng = document.getElementById('form-sec-lng');
    const secHec = document.getElementById('form-sec-hec');
    const secLon = document.getElementById('form-sec-longitud');
    const secNom = document.getElementById('form-sec-nombre');
    const secDir = document.getElementById('form-sec-dir');
    if (secLat) secLat.value = lat;
    if (secLng) secLng.value = lng;
    if (secHec) secHec.value = hectareas;
    if (secLon) secLon.value = longitudM;
    if (secNom) secNom.value = '';
    if (secDir) secDir.value = '';

    // 2. Inputs de Evento Operativo (relavera_actividades)
    const actLat = document.getElementById('form-act-lat');
    const actLng = document.getElementById('form-act-lng');
    const actArea = document.getElementById('form-act-area');
    const actLon = document.getElementById('form-act-longitud');
    const actFec = document.getElementById('form-act-fecha');
    const actObs = document.getElementById('form-act-obs');
    const actUbi = document.getElementById('form-act-ubicacion-nombre');
    const actSecSel = document.getElementById('form-act-sector');

    if (actLat) actLat.value = lat;
    if (actLng) actLng.value = lng;
    if (actArea) actArea.value = datosGeometria.area_m2 || '0';
    if (actLon) actLon.value = longitudM;
    if (actFec) actFec.value = new Date().toISOString().slice(0, 16);
    if (actObs) actObs.value = '';

    // Detección inteligente del Nombre de la Ubicación / Sector
    let ubiDetectada = 'Frente Central de Descarga';
    let sectorMatchId = '';

    // 1) Si hay sectores registrados en finca_actividad, buscar el más cercano (< 90 metros)
    if (Array.isArray(MapeoState.sectores) && MapeoState.sectores.length > 0) {
        let minDist = 999999;
        MapeoState.sectores.forEach(s => {
            const d = Math.sqrt(Math.pow(s.lat - lat, 2) + Math.pow(s.lng - lng, 2));
            if (d < minDist) {
                minDist = d;
                if (d < 0.0009) {
                    ubiDetectada = s.nombre;
                    sectorMatchId = s.id;
                }
            }
        });
    }

    // 2) Si no hay sector cercano, consultar microzonas de relavera
    if (!sectorMatchId && typeof RELAVERA_CONFIG !== 'undefined' && Array.isArray(RELAVERA_CONFIG.microzonas)) {
        const pt = turf.point([lng, lat]);
        RELAVERA_CONFIG.microzonas.forEach(z => {
            try {
                const poly = turf.polygon([z.coordenadas.map(p => [p[1], p[0]])]);
                if (turf.booleanPointInPolygon(pt, poly)) {
                    ubiDetectada = z.nombre.split('/')[0].trim();
                }
            } catch(e) {}
        });
    }

    if (actUbi) actUbi.value = ubiDetectada;
    if (actSecSel) actSecSel.value = sectorMatchId;

    MapeoState.geometriaTemporal = datosGeometria.geometria || null;
    MapeoState.layerTemporal = datosGeometria.layerRef || null;

    // Detección automática del modo según pestaña activa en el panel lateral (Actividades vs Sectores)
    let modoPorPestana = 'ubicacion';
    const tabActiva = document.querySelector('.sidebar-tab-btn.active');
    if (tabActiva && tabActiva.dataset) {
        if (tabActiva.dataset.tab === 'actividades') {
            modoPorPestana = 'evento';
        } else if (tabActiva.dataset.tab === 'sectores') {
            modoPorPestana = 'ubicacion';
        }
    }

    // Inicializar personalizador de pines según categoría/tipo o defaults
    const catSel = document.getElementById('form-sec-categoria');
    if (catSel) {
        onCategoriaSectorChange(catSel);
    } else {
        seleccionarIconoPin('sec', 'fa-map-marker');
        seleccionarColorPin('sec', '#8b5cf6');
    }

    const tipoSel = document.getElementById('form-act-tipo');
    if (tipoSel) {
        onTipoActividadChange(tipoSel);
    } else {
        seleccionarIconoPin('act', 'fa-truck');
        seleccionarColorPin('act', '#10b981');
    }
    actualizarPreviewPin('sec');
    actualizarPreviewPin('act');

    const modoInicial = datosGeometria.modoPreferido || modoPorPestana;
    setModoRegistro(modoInicial);

    // Resetear contenedor de evidencias fotográficas
    MapeoState.evidenciasTemporales = [];
    const prevCont = document.getElementById('evidencias-preview-container');
    if (prevCont) prevCont.innerHTML = '';

    modal.classList.add('active');
}

/**
 * Limpia y retira del mapa cualquier capa o marcador temporal dibujado antes de guardar
 */
function limpiarLayerTemporal() {
    if (MapeoState.layerTemporal) {
        try {
            if (MapeoState.overlayLayers && MapeoState.overlayLayers.vectores) {
                MapeoState.overlayLayers.vectores.removeLayer(MapeoState.layerTemporal);
            }
            if (MapeoState.map && MapeoState.map.hasLayer(MapeoState.layerTemporal)) {
                MapeoState.map.removeLayer(MapeoState.layerTemporal);
            }
        } catch (err) {
            console.warn('Error al remover capa temporal:', err);
        }
        MapeoState.layerTemporal = null;
    }
    MapeoState.geometriaTemporal = null;
}

function cerrarModalActividad(esGuardado = false) {
    const modal = document.getElementById('modal-registro-actividad');
    if (modal) modal.classList.remove('active');

    MapeoState.evidenciasTemporales = [];
    const prevCont = document.getElementById('evidencias-preview-container');
    if (prevCont) prevCont.innerHTML = '';

    // Si se cancela o se cierra sin haber guardado, retirar del mapa el dibujo/marcador temporal
    if (!esGuardado) {
        limpiarLayerTemporal();
    }
}

function guardarSectorFormulario() {
    const nomInput = document.getElementById('form-sec-nombre');
    const catInput = document.getElementById('form-sec-categoria');
    const dirInput = document.getElementById('form-sec-dir');
    const latInput = document.getElementById('form-sec-lat');
    const lngInput = document.getElementById('form-sec-lng');
    const hecInput = document.getElementById('form-sec-hec');
    const lonInput = document.getElementById('form-sec-longitud');

    const nombre = nomInput ? nomInput.value.trim() : '';
    if (!nombre) {
        alert('Por favor ingrese el Nombre de la Ubicación o Sector (ej. Edificio Administrativo, Sector A1, etc.)');
        if (nomInput) nomInput.focus();
        return;
    }

    const secIcono = document.getElementById('form-sec-icono') ? document.getElementById('form-sec-icono').value : 'fa-map-marker';
    const secColor = document.getElementById('form-sec-color') ? document.getElementById('form-sec-color').value : '#8b5cf6';

    const payload = {
        nombre: nombre,
        categoria: catInput ? catInput.value : 'Sector Operativo',
        direccion: dirInput ? dirInput.value.trim() : '',
        lat: parseFloat(latInput ? latInput.value : 0),
        lng: parseFloat(lngInput ? lngInput.value : 0),
        hectareas: parseFloat(hecInput ? hecInput.value : 0) || 0,
        longitud_m: parseFloat(lonInput ? lonInput.value : 0) || 0,
        icono: secIcono,
        color: secColor,
        geometria: MapeoState.geometriaTemporal
    };

    if (!navigator.onLine || (typeof OfflineManager !== 'undefined' && !OfflineManager.isOnline())) {
        guardarEnBufferOffline('sector', payload);
        MapeoState.sectores.unshift(payload);
        limpiarLayerTemporal();
        cerrarModalActividad(true);
        renderSectoresMapa();
        renderSectoresLista();
        poblarSelectoresSectores();
        mostrarToast(`Sector "${nombre}" guardado localmente (Modo Offline).`);
        return;
    }

    fetch('../LOGICA/map_log_mapeo.php?action=guardar_sector', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            limpiarLayerTemporal();
            cerrarModalActividad(true);
            cargarSectores();
            mostrarToast(`Sector / Ubicaci&oacute;n "${nombre}" guardado en finca_actividad.`);
            const msgObj = {
                type: 'SECTOR_CREADO',
                id: res.id || (res.sector ? res.sector.id : null),
                nombre: nombre,
                categoria: payload.categoria,
                direccion: payload.direccion,
                lat: payload.lat,
                lng: payload.lng,
                icono: secIcono,
                color: secColor
            };
            if (window.parent && window.parent !== window) {
                window.parent.postMessage(msgObj, '*');
            } else if (window.opener) {
                window.opener.postMessage(msgObj, '*');
            }
        } else {
            alert('Error: ' + res.message);
        }
    })
    .catch(err => {
        console.error(err);
        guardarEnBufferOffline('sector', payload);
        limpiarLayerTemporal();
        cerrarModalActividad(true);
        mostrarToast(`Sector "${nombre}" guardado localmente (modo offline).`);
    });
}

function cargarSectores() {
    fetch('../LOGICA/map_log_mapeo.php?action=get_sectores')
        .then(r => r.json())
        .then(res => {
            if (res.success && Array.isArray(res.sectores)) {
                MapeoState.sectores = res.sectores;
                renderSectoresMapa();
                renderSectoresLista();
                poblarSelectoresSectores();
            }
        })
        .catch(err => {
            console.error('Error cargando sectores:', err);
        });
}

function renderSectoresMapa() {
    if (!MapeoState.overlayLayers.sectores) return;
    MapeoState.overlayLayers.sectores.clearLayers();

    const urlParams = new URLSearchParams(window.location.search);
    const pModo = urlParams.get('modo');
    const pSectorId = urlParams.get('sector_id');
    const pLat = parseFloat(urlParams.get('lat'));
    const pLng = parseFloat(urlParams.get('lng'));
    const mostrarBotonAsignar = (pModo !== 'ver') && (window.parent !== window || window.opener);

    MapeoState.sectores.forEach(sec => {
        if (!sec.lat || !sec.lng) return;

        const secIconRaw = sec.icono || 'fa-map-marker';
        const secIcon = secIconRaw.indexOf('fa-') === 0 ? secIconRaw : `fa-${secIconRaw}`;
        const secColor = sec.color || '#8b5cf6';

        // Si tiene geometría poligonal o multilínea
        if (sec.geometria && (sec.geometria.type === 'Polygon' || sec.geometria.type === 'MultiPolygon')) {
            const poly = L.geoJSON(sec.geometria, {
                style: {
                    color: secColor,
                    fillColor: secColor,
                    fillOpacity: 0.3,
                    weight: 2
                }
            });
            poly.bindTooltip(`<b>${sec.nombre}</b>`, { permanent: false, direction: 'center' });
            poly.bindPopup(`
                <div style="font-family:'Segoe UI',sans-serif; min-width:200px;">
                    <strong style="color:${secColor}; font-size:13px;"><i class="fa ${secIcon}"></i> ${sec.nombre}</strong><br>
                    <span style="font-size:11px; color:#475569;">${sec.direccion || 'Sector registrado en finca_actividad'}</span><br>
                    <hr style="margin:6px 0; border:0; border-top:1px solid #e2e8f0;">
                    <b>&Aacute;rea:</b> ${sec.hectareas} ha<br>
                    <b>Coordenadas:</b> ${parseFloat(sec.lat).toFixed(6)}, ${parseFloat(sec.lng).toFixed(6)}
                    ${mostrarBotonAsignar ? `
                        <div style="margin-top:8px; padding-top:6px; border-top:1px solid #e2e8f0;">
                            <button type="button" class="btn btn-xs btn-success" style="width:100%; font-weight:600;" onclick="seleccionarSectorParaFormulario(${sec.id}, '${escape(sec.nombre)}', ${sec.lat}, ${sec.lng}, '${escape(sec.direccion || '')}')">
                                <i class="fa fa-check"></i> Asignar a Labores
                            </button>
                        </div>
                    ` : ''}
                </div>
            `);
            MapeoState.overlayLayers.sectores.addLayer(poly);
            sec._poly = poly;
        }

        // Marcador del Sector / Ubicación con Badge Inteligente y Conteo de Eventos
        var evts = obtenerActividadesDeSector(sec);
        var numEventos = evts.length;
        var hasAlert = evts.some(function(e) { return e.tipo === 'reporte_alerta' || e.estado === 'Alerta Crítica'; });

        var badgeCountHtml = numEventos > 0
            ? '<span class="sec-count-badge">' + numEventos + (numEventos === 1 ? ' evento' : ' eventos') + '</span>'
            : '<span class="sec-count-badge" style="background:#64748b;">0</span>';

        var pillHtml = 
            '<div class="sector-badge-pill ' + (hasAlert ? 'has-alert' : '') + '" style="border-color:' + secColor + ';" title="Clic para enfocar ' + sec.nombre + ' y ver ' + numEventos + ' eventos">' +
                '<span class="sec-icon" style="background:' + secColor + ';"><i class="fa ' + secIcon + '"></i></span>' +
                '<span class="sec-nombre">' + sec.nombre + '</span>' +
                badgeCountHtml +
            '</div>';

        var icon = L.divIcon({
            html: pillHtml,
            className: 'sector-badge-container',
            iconSize: null,
            iconAnchor: [60, 18],
            popupAnchor: [0, -18]
        });
        var marker = L.marker([sec.lat, sec.lng], { icon: icon });
        marker.on('click', function() {
            enfocarSector(sec.id);
        });
        marker.bindTooltip('<b>' + sec.nombre + '</b> (' + numEventos + ' eventos)', { permanent: false, direction: 'top' });
        marker.bindPopup(`
            <div style="font-family:'Segoe UI',sans-serif; min-width:210px;">
                <div style="font-weight:700; color:${secColor}; font-size:14px; margin-bottom:4px;">
                    <i class="fa ${secIcon}"></i> ${sec.nombre}
                </div>
                <div style="font-size:12px; color:#334155; line-height:1.4;">
                    <b>Referencia:</b> ${sec.direccion || 'Ubicaci&oacute;n f&iacute;sica permanente'}<br>
                    <b>&Aacute;rea:</b> ${sec.hectareas || '0'} ha<br>
                    <b>Eventos Operativos:</b> <span class="badge-act success">${numEventos} registrados</span><br>
                    <b>Lat, Lng:</b> ${parseFloat(sec.lat).toFixed(6)}, ${parseFloat(sec.lng).toFixed(6)}<br>
                    <span style="display:inline-block; margin-top:4px; padding:2px 6px; background:#f8fafc; border:1px solid #e2e8f0; color:${secColor}; border-radius:4px; font-size:10px; font-weight:700;">
                        C&oacute;digo BD: ${sec.id}
                    </span>
                    <div style="margin-top:8px; padding-top:6px; border-top:1px solid #e2e8f0; display:flex; gap:6px;">
                        <button type="button" class="btn btn-xs btn-primary" style="flex:1; font-weight:600;" onclick="enfocarSector(${sec.id})">
                            <i class="fa fa-crosshairs"></i> Enfocar Eventos
                        </button>
                        ${mostrarBotonAsignar ? `
                            <button type="button" class="btn btn-xs btn-success" style="flex:1; font-weight:600;" onclick="seleccionarSectorParaFormulario(${sec.id}, '${escape(sec.nombre)}', ${sec.lat}, ${sec.lng}, '${escape(sec.direccion || '')}')">
                                <i class="fa fa-check"></i> Asignar
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `);
        MapeoState.overlayLayers.sectores.addLayer(marker);
        sec._marker = marker;
    });

    if (pModo === 'ver') {
        enfocarYMostrarSector(pSectorId, pLat, pLng);
    }
}

function enfocarYMostrarSector(secId, lat, lng) {
    if (!Array.isArray(MapeoState.sectores)) return;
    let target = null;
    if (secId) {
        target = MapeoState.sectores.find(s => String(s.id) === String(secId));
    }
    if (!target && !isNaN(lat) && !isNaN(lng)) {
        target = MapeoState.sectores.find(s => {
            return Math.abs(parseFloat(s.lat) - parseFloat(lat)) < 0.0003 &&
                   Math.abs(parseFloat(s.lng) - parseFloat(lng)) < 0.0003;
        });
    }

    if (target && target._marker) {
        MapeoState.map.setView([parseFloat(target.lat), parseFloat(target.lng)], 20);
        setTimeout(() => {
            target._marker.openPopup();
        }, 300);
    } else if (!isNaN(lat) && !isNaN(lng)) {
        const secNombre = target ? target.nombre : 'Sector Georreferenciado';
        const secDir = target ? target.direccion : 'Ubicaci&oacute;n f&iacute;sica permanente';
        const secHec = target ? (target.hectareas || '0') : '0';
        const secCod = target ? target.id : (secId || '');
        const secColor = target ? (target.color || '#8b5cf6') : '#8b5cf6';
        const secIcon = target ? (target.icono || 'fa-map-marker') : 'fa-map-marker';

        const popupContent = `
            <div style="font-family:'Segoe UI',sans-serif; min-width:210px;">
                <div style="font-weight:700; color:${secColor}; font-size:14px; margin-bottom:4px;">
                    <i class="fa ${secIcon}"></i> ${secNombre}
                </div>
                <div style="font-size:12px; color:#334155; line-height:1.4;">
                    <b>Referencia:</b> ${secDir || 'Ubicaci&oacute;n f&iacute;sica permanente'}<br>
                    <b>&Aacute;rea:</b> ${secHec} ha<br>
                    <b>Lat, Lng:</b> ${parseFloat(lat).toFixed(6)}, ${parseFloat(lng).toFixed(6)}<br>
                    ${secCod ? `
                        <span style="display:inline-block; margin-top:4px; padding:2px 6px; background:#f8fafc; border:1px solid #e2e8f0; color:${secColor}; border-radius:4px; font-size:10px; font-weight:700;">
                            C&oacute;digo BD: ${secCod}
                        </span>
                    ` : ''}
                </div>
            </div>
        `;
        MapeoState.map.setView([lat, lng], 20);
        setTimeout(() => {
            L.popup()
                .setLatLng([lat, lng])
                .setContent(popupContent)
                .openOn(MapeoState.map);
        }, 300);
    }
}

function renderSectoresLista() {
    const cont = document.getElementById('lista-sectores-container');
    const badgeCount = document.getElementById('total-sectores-count');
    if (badgeCount) badgeCount.textContent = MapeoState.sectores.length;
    if (!cont) return;

    if (MapeoState.sectores.length === 0) {
        cont.innerHTML = `
            <div style="text-align:center; padding:25px 10px; color:#94a3b8;">
                <i class="fa fa-map-marker" style="font-size:28px; margin-bottom:8px;"></i>
                <p style="font-size:12px; margin:0;">No hay sectores registrados aún.</p>
                <span style="font-size:11px; color:#cbd5e1;">Coloca un punto en el mapa y elígelo como "Punto de Referencia / Sector" para crearlo.</span>
            </div>
        `;
        return;
    }

    let html = '';
    MapeoState.sectores.forEach(sec => {
        const secIconRaw = sec.icono || 'fa-map-marker';
        const secIcon = secIconRaw.indexOf('fa-') === 0 ? secIconRaw : `fa-${secIconRaw}`;
        const secColor = sec.color || '#8b5cf6';
        const evts = obtenerActividadesDeSector(sec);
        html += `
            <div class="actividad-card" style="border-left: 4px solid ${secColor}; cursor:pointer;" onclick="enfocarSector(${sec.id})">
                <div class="act-card-header">
                    <span class="act-card-title" style="color:${secColor};"><i class="fa ${secIcon}"></i> ${sec.nombre}</span>
                    <span style="font-size:10px; background:${secColor}; color:#fff; padding:2px 7px; border-radius:10px; font-weight:700;">${evts.length} evts</span>
                </div>
                <div class="act-card-body">
                    <span style="color:#475569;">${sec.direccion || 'Sin referencia adicional'}</span><br>
                    <span style="font-size:11px; color:#64748b;">Área: <b>${sec.hectareas} ha</b></span>
                </div>
                <div class="act-card-meta">
                    <span><i class="fa fa-crosshairs"></i> ${parseFloat(sec.lat).toFixed(4)}, ${parseFloat(sec.lng).toFixed(4)}</span>
                    <button class="btn btn-xs" style="background:${secColor}; color:#fff; border:none; padding:2px 8px; border-radius:4px; font-size:10px; font-weight:600;">
                        <i class="fa fa-crosshairs"></i> Enfocar
                    </button>
                </div>
            </div>
        `;
    });
    cont.innerHTML = html;
}

function centrarSector(lat, lng) {
    if (MapeoState.map && lat && lng) {
        MapeoState.map.setView([lat, lng], 19, { animate: true });
    }
}

function poblarSelectoresSectores() {
    const sel = document.getElementById('form-act-sector');
    if (!sel) return;

    sel.innerHTML = '<option value="">(Sin sector específico / Punto libre)</option>';
    MapeoState.sectores.forEach(sec => {
        const opt = document.createElement('option');
        opt.value = sec.id;
        opt.textContent = `${sec.nombre}${sec.direccion ? ' - ' + sec.direccion : ''}`;
        sel.appendChild(opt);
    });
}

function onSectorSelectChange(sel) {
    const ubiInput = document.getElementById('form-act-ubicacion-nombre');
    if (!ubiInput || !sel) return;
    if (sel.selectedIndex > 0) {
        const fullText = sel.options[sel.selectedIndex].text;
        const nombreSector = fullText.split(' - ')[0].trim();
        ubiInput.value = nombreSector;
    }
}

function guardarActividadFormulario() {
    const tipoSelect = document.getElementById('form-act-tipo');
    const volquetaSelect = document.getElementById('form-act-volqueta');
    const choferSelect = document.getElementById('form-act-chofer');
    const estadoSelect = document.getElementById('form-act-estado');
    const sectorSelect = document.getElementById('form-act-sector');
    const ubiInput = document.getElementById('form-act-ubicacion-nombre');

    const ubicacionNombre = ubiInput ? ubiInput.value.trim() : '';
    if (!ubicacionNombre) {
        alert('Por favor ingrese el Nombre de la Ubicación / Sector del evento.');
        if (ubiInput) ubiInput.focus();
        return;
    }

    const tipoVal = tipoSelect.value;
    const tipoLabel = tipoSelect.options[tipoSelect.selectedIndex].text;

    // Obtener datos del vehículo / maquinaria seleccionado
    const selVolOpt = volquetaSelect.options[volquetaSelect.selectedIndex];
    const volquetaVal = selVolOpt && selVolOpt.dataset.codigo 
        ? `${selVolOpt.dataset.codigo} (${selVolOpt.dataset.placa})` 
        : (volquetaSelect.value || 'VOL-01');
    const placaVal = selVolOpt && selVolOpt.dataset.placa 
        ? selVolOpt.dataset.placa 
        : (volquetaSelect.value || 'N/A');

    // Obtener datos del chofer / operador seleccionado
    const selChoOpt = choferSelect.options[choferSelect.selectedIndex];
    const choferVal = selChoOpt && selChoOpt.value 
        ? selChoOpt.value 
        : (choferSelect.value || 'Operador en Campo');

    // Sector asociado si se seleccionó uno
    const fncCodVal = sectorSelect && sectorSelect.value ? parseInt(sectorSelect.value) : null;

    const lat = parseFloat(document.getElementById('form-act-lat').value);
    const lng = parseFloat(document.getElementById('form-act-lng').value);
    const volumen = parseFloat(document.getElementById('form-act-volumen').value) || 0;
    const area = parseFloat(document.getElementById('form-act-area').value) || 0;
    const longitud = parseFloat(document.getElementById('form-act-longitud').value) || 0;
    const fecha = document.getElementById('form-act-fecha').value.replace('T', ' ');
    const obs = document.getElementById('form-act-obs').value;
    const estado = estadoSelect.value;
    const badge = estado === 'Completada' ? 'success' : (estado === 'En Progreso' ? 'warning' : 'info');

    const actIcono = document.getElementById('form-act-icono') ? document.getElementById('form-act-icono').value : 'fa-truck';
    const actColor = document.getElementById('form-act-color') ? document.getElementById('form-act-color').value : '#10b981';

    const payload = {
        id: 'ACT-' + Date.now(),
        tipo: tipoVal,
        tipo_label: tipoLabel,
        fnc_cod: fncCodVal,
        ubicacion_nombre: ubicacionNombre,
        volqueta_num: volquetaVal,
        placa: placaVal,
        chofer: choferVal,
        lat: lat,
        lng: lng,
        volumen_m3: volumen,
        area_m2: area,
        longitud_m: longitud,
        fecha: fecha,
        observaciones: obs,
        estado: estado,
        estado_badge: badge,
        icono: actIcono,
        color: actColor,
        geometria: MapeoState.geometriaTemporal,
        evidencias: Array.isArray(MapeoState.evidenciasTemporales) ? [...MapeoState.evidenciasTemporales] : []
    };

    if (!navigator.onLine) {
        guardarEnBufferOffline('actividad', payload);
        MapeoState.actividades.unshift(payload);
        limpiarLayerTemporal();
        cerrarModalActividad(true);
        aplicarFiltrosActividades();
        mostrarToast('Actividad guardada en modo offline.');
        return;
    }

    fetch('../LOGICA/map_log_mapeo.php?action=guardar_actividad', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            limpiarLayerTemporal();
            cerrarModalActividad(true);
            cargarActividades();
            mostrarToast('Actividad registrada correctamente en el mapa.');
        } else {
            alert('Error: ' + res.message);
        }
    })
    .catch(() => {
        guardarEnBufferOffline('actividad', payload);
        limpiarLayerTemporal();
        cerrarModalActividad(true);
        mostrarToast('Red inestable: actividad guardada en caché local.');
    });
}

function eliminarActividad(id) {
    if (!confirm('¿Desea eliminar este registro de actividad del mapa?')) return;

    fetch(`../LOGICA/map_log_mapeo.php?action=eliminar_actividad&id=${encodeURIComponent(id)}`)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                cargarActividades();
                mostrarToast('Actividad eliminada.');
            }
        });
}

function cargarManifiestosBD() {
    const cont = document.getElementById('lista-manifiestos-container');
    if (cont) cont.innerHTML = '<div style="text-align:center; padding:15px; color:#64748b;"><i class="fa fa-spinner fa-spin"></i> Consultando base de datos...</div>';

    fetch('../LOGICA/map_log_mapeo.php?action=get_manifiestos_hoy')
        .then(r => r.json())
        .then(res => {
            if (res.success && Array.isArray(res.manifiestos)) {
                MapeoState.manifiestos = res.manifiestos;
                renderManifiestosLista(res.origen_bd);
            }
        });
}

function renderManifiestosLista(esOrigenBD = false) {
    const cont = document.getElementById('lista-manifiestos-container');
    if (!cont) return;

    if (MapeoState.manifiestos.length === 0) {
        cont.innerHTML = '<div style="text-align:center; color:#94a3b8; padding:20px;">No hay manifiestos registrados para hoy.</div>';
        return;
    }

    let html = esOrigenBD ? '<div style="font-size:10px; color:#10b981; font-weight:700; margin-bottom:8px;"><i class="fa fa-check-circle"></i> CONECTADO CON MYSQL ERP</div>' : '';
    MapeoState.manifiestos.forEach(man => {
        html += `
            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:10px; margin-bottom:8px; font-size:12px;">
                <div style="display:flex; justify-content:space-between; font-weight:700; color:#1e293b;">
                    <span><i class="fa fa-file-text-o text-primary"></i> ${man.codigo}</span>
                    <span class="badge-act ${man.estado === 'Completado' ? 'success' : 'warning'}">${man.estado}</span>
                </div>
                <div style="color:#475569; margin:4px 0;">
                    <b>Placa:</b> ${man.placa} &bull; ${man.chofer}<br>
                    <b>Origen:</b> ${man.planta}<br>
                    <b>Pesaje:</b> ${man.peso_neto_ton} Ton (~${man.volumen_m3} m³)
                </div>
                <div style="font-size:10px; color:#94a3b8; text-align:right;">Hora: ${man.hora}</div>
            </div>
        `;
    });

    cont.innerHTML = html;
}

// ==========================================================================
// ALERTAS DE VELOCIDAD POR MICROZONAS & BOTÓN SOS
// ==========================================================================

function verificarMicrozonasYVelocidad(lat, lng, velocidadKmh) {
    const pt = turf.point([lng, lat]);
    let microzonaActual = null;
    let limite = 25;

    RELAVERA_CONFIG.microzonas.forEach(z => {
        const polyTurf = turf.polygon([z.coordenadas.map(p => [p[1], p[0]])]);
        if (turf.booleanPointInPolygon(pt, polyTurf)) {
            microzonaActual = z;
            limite = z.limiteVelocidadKmh;
        }
    });

    MapeoState.currentMicrozone = microzonaActual;
    MapeoState.currentSpeedLimit = limite;

    const limiteEl = document.getElementById('hud-gps-limite');
    if (limiteEl) limiteEl.textContent = `${limite} km/h`;

    const banner = document.getElementById('microzone-banner');
    const bannerText = document.getElementById('microzone-banner-text');

    if (velocidadKmh > limite) {
        if (banner && bannerText) {
            banner.style.display = 'flex';
            bannerText.textContent = `EXCESO DE VELOCIDAD EN ${microzonaActual ? microzonaActual.nombre.toUpperCase() : 'ZONA'}: ${velocidadKmh.toFixed(1)} km/h (MÁX ${limite} km/h)`;
        }
    } else {
        if (banner) banner.style.display = 'none';
    }
}

function abrirModalSOS() {
    const modal = document.getElementById('modal-sos');
    if (modal) modal.classList.add('active');
}

function cerrarModalSOS() {
    const modal = document.getElementById('modal-sos');
    if (modal) modal.classList.remove('active');
}

function enviarAlertaSOS() {
    const motivo = document.getElementById('form-sos-motivo').value;
    const detalles = document.getElementById('form-sos-detalles').value;

    const lat = MapeoState.stats.lastLat || RELAVERA_CONFIG.center[0];
    const lng = MapeoState.stats.lastLng || RELAVERA_CONFIG.center[1];

    const payload = {
        lat: lat,
        lng: lng,
        motivo: `${motivo} - ${detalles}`,
        chofer: 'Manuel Carrión (VOL-04)',
        volqueta: 'VOL-04 (OBA-7821)'
    };

    fetch('../LOGICA/map_log_mapeo.php?action=reportar_sos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            cerrarModalSOS();
            cargarActividades();
            cargarEmergenciasActivas();
            MapeoState.map.setView([lat, lng], 20, { animate: true });
            mostrarToast('ALERTA SOS TRANSMITIDA A SUPERVISIÓN Y CENTRAL.');
        }
    });
}

function cargarEmergenciasActivas() {
    fetch('../LOGICA/map_log_mapeo.php?action=get_emergencias')
        .then(r => r.json())
        .then(res => {
            if (res.success && Array.isArray(res.emergencias)) {
                MapeoState.overlayLayers.sos.clearLayers();
                res.emergencias.forEach(sos => {
                    const iconHtml = `<div class="volqueta-marker-icon sos"><i class="fa fa-exclamation-triangle"></i></div>`;
                    const icon = L.divIcon({ html: iconHtml, className: 'sos-div-icon', iconSize: [34, 34] });
                    const m = L.marker([sos.lat, sos.lng], { icon: icon }).bindPopup(`
                        <div style="font-family:'Segoe UI', sans-serif;">
                            <strong style="color:#dc2626; font-size:14px;"><i class="fa fa-bell"></i> ${sos.motivo}</strong><br>
                            <b>Vehículo:</b> ${sos.volqueta} &bull; ${sos.chofer}<br>
                            <b>Hora:</b> ${sos.fecha_hora}<br>
                            <span class="badge badge-danger">EMERGENCIA ACTIVA</span>
                        </div>
                    `);
                    MapeoState.overlayLayers.sos.addLayer(m);
                });
            }
        });
}

// ==========================================================================
// REPRODUCTOR HISTÓRICO DE RECORRIDOS (PLAYBACK TIMELINE)
// ==========================================================================

function togglePlaybackBar() {
    const bar = document.getElementById('playback-bar');
    if (!bar) return;

    if (bar.classList.contains('active')) {
        cerrarPlaybackBar();
    } else {
        abrirPlaybackBar();
    }
}

function abrirPlaybackBar() {
    const bar = document.getElementById('playback-bar');
    if (bar) bar.classList.add('active');
    
    const vehId = document.getElementById('playback-vehicle-select').value;
    cargarHistorialVehiculoPlayback(vehId);
}

function cerrarPlaybackBar() {
    const bar = document.getElementById('playback-bar');
    if (bar) bar.classList.remove('active');
    
    if (MapeoState.playback.timer) {
        clearInterval(MapeoState.playback.timer);
    }
    MapeoState.playback.playing = false;
    MapeoState.overlayLayers.playback.clearLayers();
}

function cargarHistorialVehiculoPlayback(vehId) {
    fetch(`../LOGICA/map_log_mapeo.php?action=get_gps_history&volqueta=${encodeURIComponent(vehId)}`)
        .then(r => r.json())
        .then(res => {
            let pts = res.puntos || [];
            
            if (pts.length < 5) {
                const volSim = MapeoState.simulacion.volquetas.find(v => v.id === vehId) || MapeoState.simulacion.volquetas[0];
                pts = volSim.path.map((coord, idx) => ({
                    lat: coord[0],
                    lng: coord[1],
                    speed_kmh: idx === 13 ? 0 : (idx % 2 === 0 ? 12.5 : 18.2),
                    timestamp: `2026-08-31 20:${10 + (idx % 40)}:00`
                }));
            }

            MapeoState.playback.trackPoints = pts;
            MapeoState.playback.currentIndex = 0;
            
            const slider = document.getElementById('playback-time-slider');
            if (slider) {
                slider.max = pts.length - 1;
                slider.value = 0;
            }

            renderPlaybackTrackEnMapa();
            actualizarEstadoPlayback();
        });
}

function renderPlaybackTrackEnMapa() {
    MapeoState.overlayLayers.playback.clearLayers();
    const pts = MapeoState.playback.trackPoints;
    if (pts.length === 0) return;

    for (let i = 0; i < pts.length - 1; i++) {
        const p1 = [pts[i].lat, pts[i].lng];
        const p2 = [pts[i+1].lat, pts[i+1].lng];
        const speed = pts[i].speed_kmh;
        
        let color = '#10b981';
        if (speed > 20) color = '#ef4444';
        else if (speed > 12) color = '#f59e0b';
        else if (speed === 0) color = '#3b82f6';

        L.polyline([p1, p2], { color: color, weight: 6, opacity: 0.85 }).addTo(MapeoState.overlayLayers.playback);
    }

    const iconHtml = `<div class="volqueta-marker-icon" style="background:#0284c7; color:#fff;"><i class="fa fa-truck"></i></div>`;
    const icon = L.divIcon({ html: iconHtml, className: 'pb-icon', iconSize: [34, 34] });
    MapeoState.playback.marker = L.marker([pts[0].lat, pts[0].lng], { icon: icon }).addTo(MapeoState.overlayLayers.playback);

    MapeoState.map.setView([pts[0].lat, pts[0].lng], 18);
}

function togglePlaybackPlay() {
    const btn = document.getElementById('btn-playback-play');
    if (MapeoState.playback.playing) {
        clearInterval(MapeoState.playback.timer);
        MapeoState.playback.playing = false;
        if (btn) btn.innerHTML = '<i class="fa fa-play"></i> Reproducir';
    } else {
        MapeoState.playback.playing = true;
        if (btn) btn.innerHTML = '<i class="fa fa-pause"></i> Pausar';
        MapeoState.playback.timer = setInterval(stepPlayback, 1000 / MapeoState.playback.speedMultiplier);
    }
}

function stepPlayback() {
    const pts = MapeoState.playback.trackPoints;
    if (pts.length === 0) return;

    MapeoState.playback.currentIndex++;
    if (MapeoState.playback.currentIndex >= pts.length) {
        MapeoState.playback.currentIndex = 0;
    }

    const slider = document.getElementById('playback-time-slider');
    if (slider) slider.value = MapeoState.playback.currentIndex;

    actualizarEstadoPlayback();
}

function onPlaybackSliderChange(val) {
    MapeoState.playback.currentIndex = parseInt(val);
    actualizarEstadoPlayback();
}

function actualizarEstadoPlayback() {
    const pts = MapeoState.playback.trackPoints;
    const idx = MapeoState.playback.currentIndex;
    if (!pts[idx]) return;

    const pt = pts[idx];
    if (MapeoState.playback.marker) {
        MapeoState.playback.marker.setLatLng([pt.lat, pt.lng]);
    }

    document.getElementById('playback-cur-time').textContent = `Punto: ${idx + 1}/${pts.length} (${pt.timestamp || ''})`;
    document.getElementById('playback-cur-speed').textContent = `Velocidad: ${pt.speed_kmh} km/h`;
}

function reiniciarPlayback() {
    MapeoState.playback.currentIndex = 0;
    const slider = document.getElementById('playback-time-slider');
    if (slider) slider.value = 0;
    actualizarEstadoPlayback();
}

function setPlaybackSpeed(spd) {
    MapeoState.playback.speedMultiplier = spd;
    if (MapeoState.playback.playing) {
        clearInterval(MapeoState.playback.timer);
        MapeoState.playback.timer = setInterval(stepPlayback, 1000 / spd);
    }
}

// ==========================================================================
// TRACKING GPS EN VIVO (TELÉFONO DE VOLQUETERO)
// ==========================================================================

function toggleLiveGpsTracking() {
    if (MapeoState.isGpsTracking) {
        stopLiveGpsTracking();
    } else {
        startLiveGpsTracking();
    }
}

function startLiveGpsTracking() {
    if (!('geolocation' in navigator)) {
        alert('Su dispositivo no soporta geolocalización GPS.');
        return;
    }

    const gpsBtn = document.getElementById('btn-toggle-gps');
    const statusDot = document.getElementById('gps-status-indicator');
    const statusText = document.getElementById('gps-status-text');

    MapeoState.isGpsTracking = true;
    MapeoState.stats.startTime = new Date();
    MapeoState.gpsTrailPoints = [];

    if (gpsBtn) {
        gpsBtn.style.background = '#ef4444';
        gpsBtn.innerHTML = '<i class="fa fa-stop-circle"></i> Detener GPS Móvil';
    }
    if (statusDot) statusDot.classList.add('active');
    if (statusText) statusText.textContent = 'GPS ACTIVO (CONECTADO)';

    if (MapeoState.gpsTrailPolyline) {
        MapeoState.overlayLayers.gps.removeLayer(MapeoState.gpsTrailPolyline);
    }
    MapeoState.gpsTrailPolyline = L.polyline([], { color: '#0284c7', weight: 5, opacity: 0.85 }).addTo(MapeoState.overlayLayers.gps);

    MapeoState.gpsWatchId = navigator.geolocation.watchPosition(
        onGpsPositionReceived,
        onGpsPositionError,
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );

    mostrarToast('Captura de GPS iniciada.');
}

function onGpsPositionReceived(pos) {
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;
    const accuracy = pos.coords.accuracy;
    const speed = pos.coords.speed !== null ? (pos.coords.speed * 3.6) : 0;
    const heading = pos.coords.heading || 0;
    const altitude = pos.coords.altitude || 680;

    const latLng = [lat, lng];

    MapeoState.stats.currentSpeedKmh = speed.toFixed(1);
    if (speed > MapeoState.stats.maxSpeedKmh) MapeoState.stats.maxSpeedKmh = speed.toFixed(1);

    if (MapeoState.stats.lastLat !== null) {
        const from = turf.point([MapeoState.stats.lastLng, MapeoState.stats.lastLat]);
        const to = turf.point([lng, lat]);
        const distKm = turf.distance(from, to, { units: 'kilometers' });
        MapeoState.stats.totalDistanceMeters += distKm * 1000;
    }
    MapeoState.stats.lastLat = lat;
    MapeoState.stats.lastLng = lng;
    MapeoState.stats.pointsLogged++;

    document.getElementById('hud-gps-speed').textContent = `${speed.toFixed(1)} km/h`;
    document.getElementById('hud-gps-distance').textContent = `${(MapeoState.stats.totalDistanceMeters / 1000).toFixed(2)} km`;
    document.getElementById('hud-gps-accuracy').textContent = `±${accuracy.toFixed(0)}m`;

    verificarMicrozonasYVelocidad(lat, lng, speed);

    const pt = turf.point([lng, lat]);
    const geocercaPolyTurf = turf.polygon([RELAVERA_CONFIG.geocerca.map(p => [p[1], p[0]])]);
    const dentroGeocerca = turf.booleanPointInPolygon(pt, geocercaPolyTurf);

    const geocercaBadge = document.getElementById('hud-gps-geofence');
    if (geocercaBadge) {
        geocercaBadge.textContent = dentroGeocerca ? 'DENTRO DE RELAVERA' : 'FUERA DE LÍMITE';
        geocercaBadge.className = dentroGeocerca ? 'badge badge-success' : 'badge badge-danger';
    }

    if (!MapeoState.currentLocationMarker) {
        const iconHtml = `<div class="volqueta-marker-icon movil" style="transform: rotate(${heading}deg);"><i class="fa fa-truck"></i></div>`;
        const icon = L.divIcon({ html: iconHtml, className: 'volqueta-div-icon', iconSize: [38, 38] });
        MapeoState.currentLocationMarker = L.marker(latLng, { icon: icon }).addTo(MapeoState.overlayLayers.gps);
    } else {
        MapeoState.currentLocationMarker.setLatLng(latLng);
    }

    MapeoState.gpsTrailPoints.push(latLng);
    MapeoState.gpsTrailPolyline.setLatLngs(MapeoState.gpsTrailPoints);

    if (MapeoState.autoFollowVehicle) {
        MapeoState.map.panTo(latLng, { animate: true });
    }

    const gpsPointData = {
        lat: lat,
        lng: lng,
        speed_kmh: speed,
        heading: heading,
        accuracy_m: accuracy,
        altitude_m: altitude,
        en_geocerca: dentroGeocerca
    };

    if (!navigator.onLine) {
        guardarEnBufferOffline('gps', gpsPointData);
    } else if (MapeoState.stats.pointsLogged % 4 === 0) {
        fetch('../LOGICA/map_log_mapeo.php?action=registrar_gps', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(gpsPointData)
        });
    }
}

function onGpsPositionError(err) {
    console.warn('Error GPS:', err.message);
    const statusText = document.getElementById('gps-status-text');
    if (statusText) statusText.textContent = 'SEÑAL GPS BUSCANDO...';
}

function stopLiveGpsTracking() {
    if (MapeoState.gpsWatchId !== null) {
        navigator.geolocation.clearWatch(MapeoState.gpsWatchId);
        MapeoState.gpsWatchId = null;
    }
    MapeoState.isGpsTracking = false;

    const gpsBtn = document.getElementById('btn-toggle-gps');
    const statusDot = document.getElementById('gps-status-indicator');
    const statusText = document.getElementById('gps-status-text');

    if (gpsBtn) {
        gpsBtn.style.background = '#10b981';
        gpsBtn.innerHTML = '<i class="fa fa-play-circle"></i> Iniciar GPS Móvil';
    }
    if (statusDot) statusDot.classList.remove('active');
    if (statusText) statusText.textContent = 'GPS INACTIVO';

    mostrarToast('Captura GPS finalizada.');
}

// ==========================================================================
// EXPORTACIÓN MULTIFORMATO
// ==========================================================================

function abrirModalExportacion() {
    const m = document.getElementById('modal-exportacion');
    if (m) m.classList.add('active');
}

function cerrarModalExportacion() {
    const m = document.getElementById('modal-exportacion');
    if (m) m.classList.remove('active');
}

function exportarGeoJSON() {
    const featureGroup = MapeoState.overlayLayers.vectores;
    const geojson = featureGroup.toGeoJSON();
    
    MapeoState.actividades.forEach(act => {
        geojson.features.push({
            type: 'Feature',
            geometry: act.geometria || {
                type: 'Point',
                coordinates: [parseFloat(act.lng), parseFloat(act.lat)]
            },
            properties: {
                id: act.id,
                tipo: act.tipo_label,
                volqueta: act.volqueta_num,
                chofer: act.chofer,
                fecha: act.fecha,
                volumen_m3: act.volumen_m3,
                estado: act.estado,
                observaciones: act.observaciones
            }
        });
    });

    descargarArchivo(`relavera_mapeo_${new Date().toISOString().slice(0, 10)}.geojson`, JSON.stringify(geojson, null, 2), 'application/json');
    cerrarModalExportacion();
    mostrarToast('Archivo GeoJSON descargado.');
}

function exportarKML() {
    let kml = `<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <name>Relavera Comunitaria El Tablón - Operaciones</name>
    <description>Mapeo de Actividades y Vertidos en Frente de Descarga</description>
`;

    MapeoState.actividades.forEach(act => {
        kml += `    <Placemark>
      <name>${act.tipo_label} - ${act.volqueta_num}</name>
      <description><![CDATA[Chofer: ${act.chofer}<br>Volumen: ${act.volumen_m3} m3<br>Fecha: ${act.fecha}<br>Notas: ${act.observaciones}]]></description>
      <Point>
        <coordinates>${act.lng},${act.lat},680</coordinates>
      </Point>
    </Placemark>
`;
    });

    kml += `  </Document>\n</kml>`;
    descargarArchivo(`relavera_google_earth_${new Date().toISOString().slice(0, 10)}.kml`, kml, 'application/vnd.google-earth.kml+xml');
    cerrarModalExportacion();
    mostrarToast('Archivo KML para Google Earth descargado.');
}

function exportarCSV() {
    let csv = "ID;Tipo;Volqueta;Placa;Chofer;Fecha;Latitud;Longitud;Volumen_m3;Estado;Observaciones\n";
    MapeoState.actividades.forEach(act => {
        csv += `"${act.id}";"${act.tipo_label}";"${act.volqueta_num}";"${act.placa}";"${act.chofer}";"${act.fecha}";${act.lat};${act.lng};${act.volumen_m3 || 0};"${act.estado}";"${(act.observaciones || '').replace(/"/g, '""')}"\n`;
    });

    descargarArchivo(`reporte_actividades_relavera_${new Date().toISOString().slice(0, 10)}.csv`, "\uFEFF" + csv, 'text/csv;charset=utf-8;');
    cerrarModalExportacion();
    mostrarToast('Planilla CSV / Excel descargada.');
}

function descargarArchivo(nombre, contenido, tipoMime) {
    const blob = new Blob([contenido], { type: tipoMime });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = nombre;
    a.click();
    URL.revokeObjectURL(url);
}

// ==========================================================================
// UTILIDADES GENERALES
// ==========================================================================

function cargarFlota() {
    fetch('../LOGICA/map_log_mapeo.php?action=get_flota')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                MapeoState.flota = Array.isArray(res.flota) ? res.flota : [];
                MapeoState.vehiculos = Array.isArray(res.vehiculos) ? res.vehiculos : MapeoState.flota;
                MapeoState.choferes = Array.isArray(res.choferes) ? res.choferes : [];
                poblarSelectoresFlota();
            }
        })
        .catch(err => {
            console.warn('Error al cargar flota desde BD:', err);
        });
}

function poblarSelectoresFlota() {
    const selVol = document.getElementById('form-act-volqueta');
    const selCho = document.getElementById('form-act-chofer');

    // 1. Poblar Volquetas / Maquinaria
    if (selVol && Array.isArray(MapeoState.vehiculos)) {
        selVol.innerHTML = '<option value="">-- Seleccione Maquinaria / Volqueta --</option>';
        MapeoState.vehiculos.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v.placa || v.codigo;
            opt.dataset.codigo = v.codigo;
            opt.dataset.placa = v.placa;
            opt.dataset.capacidad = v.capacidad_m3 || 16.0;
            opt.dataset.marca = v.marca || '';
            const descMarca = v.marca && v.marca !== 'Volqueta / Maquinaria' ? ` (${v.marca})` : '';
            opt.textContent = `${v.codigo} - Placa: ${v.placa}${descMarca}`;
            selVol.appendChild(opt);
        });

        // Evento cambio de volqueta para actualizar cubicaje por defecto
        selVol.onchange = function() {
            const selectedOpt = selVol.options[selVol.selectedIndex];
            if (selectedOpt && selectedOpt.dataset.capacidad) {
                const volInput = document.getElementById('form-act-volumen');
                if (volInput && (!volInput.value || volInput.value === '16.0' || volInput.value === '0')) {
                    volInput.value = selectedOpt.dataset.capacidad;
                }
            }
        };
    }

    // 2. Poblar Choferes / Operadores
    if (selCho && Array.isArray(MapeoState.choferes)) {
        selCho.innerHTML = '<option value="">-- Seleccione Chofer / Operador --</option>';
        MapeoState.choferes.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.nombre;
            opt.dataset.codigo = c.codigo;
            opt.dataset.cedula = c.cedula || '';
            const descCed = c.cedula ? ` (CI: ${c.cedula})` : '';
            opt.textContent = `${c.nombre}${descCed}`;
            selCho.appendChild(opt);
        });
    }

    // 3. Sincronizar también selector de Playback histórico
    const pbSel = document.getElementById('playback-vehicle-select');
    if (pbSel && Array.isArray(MapeoState.vehiculos) && MapeoState.vehiculos.length > 0) {
        pbSel.innerHTML = '';
        MapeoState.vehiculos.slice(0, 30).forEach(v => {
            const opt = document.createElement('option');
            opt.value = v.codigo;
            opt.textContent = `${v.codigo} (${v.placa})${v.marca ? ' - ' + v.marca : ''}`;
            pbSel.appendChild(opt);
        });
    }
}

function toggleLayerGroup(layerName, isChecked) {
    if (!MapeoState.overlayLayers[layerName]) return;
    if (isChecked) {
        MapeoState.overlayLayers[layerName].addTo(MapeoState.map);
    } else {
        MapeoState.map.removeLayer(MapeoState.overlayLayers[layerName]);
    }
}

function initEventHandlers() {
    document.querySelectorAll('.sidebar-tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.dataset.tab;
            document.querySelectorAll('.sidebar-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            const pane = document.getElementById(`tab-pane-${targetTab}`);
            if (pane) pane.classList.add('active');
        });
    });

    // Cierre seguro con tecla Escape y clic en el fondo del modal
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' || e.keyCode === 27) {
            const modal = document.getElementById('modal-registro-actividad');
            if (modal && modal.classList.contains('active')) {
                cerrarModalActividad(false);
            }
        }
    });

    const modalReg = document.getElementById('modal-registro-actividad');
    if (modalReg) {
        modalReg.addEventListener('click', e => {
            if (e.target === modalReg) {
                cerrarModalActividad(false);
            }
        });
    }
}

function mostrarToast(msg) {
    let toast = document.getElementById('mapeo-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'mapeo-toast';
        toast.style.cssText = `
            position: fixed; bottom: 20px; right: 20px; background: #1e293b; color: #fff;
            padding: 12px 20px; border-radius: 8px; font-size: 13px; z-index: 9999;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3); border-left: 4px solid #10b981;
            transition: opacity 0.3s, transform 0.3s;
        `;
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
    }, 3500);
}
function centrarRelavera() {
    MapeoState.map.setView(RELAVERA_CONFIG.center, 18, { animate: true });
}

function activarTabSidebar(tabName) {
    document.querySelectorAll('.sidebar-tab-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.tab === tabName);
    });
    document.querySelectorAll('.tab-pane').forEach(p => {
        p.classList.toggle('active', p.id === `tab-pane-${tabName}`);
    });
}
window.activarTabSidebar = activarTabSidebar;

function toggleSidebar(forceState) {
    const sb = document.getElementById('sidebar-mapeo');
    const ws = document.getElementById('mapeo-workspace');
    const sosBtn = document.getElementById('btn-sos-flotante') || document.querySelector('.floating-sos-btn');
    if (!sb) return;
    if (typeof forceState === 'boolean') {
        sb.classList.toggle('collapsed', forceState);
    } else {
        sb.classList.toggle('collapsed');
    }
    const isCollapsed = sb.classList.contains('collapsed');
    if (ws) ws.classList.toggle('sidebar-collapsed', isCollapsed);
    if (sosBtn) {
        sosBtn.style.right = isCollapsed ? '20px' : '400px';
    }
    setTimeout(() => {
        if (MapeoState.map) {
            MapeoState.map.invalidateSize();
        }
    }, 320);
}
window.toggleSidebar = toggleSidebar;

function toggleGpsHud(forceState) {
    const hud = document.getElementById('hud-gps-telemetria');
    if (!hud) return;
    if (typeof forceState === 'boolean') {
        hud.classList.toggle('collapsed', forceState);
    } else {
        hud.classList.toggle('collapsed');
    }
}
window.toggleGpsHud = toggleGpsHud;

function toggleSosBtn(e) {
    if (e) e.stopPropagation();
    const btn = document.getElementById('btn-sos-flotante') || document.querySelector('.floating-sos-btn');
    if (btn) btn.classList.toggle('collapsed');
}
window.toggleSosBtn = toggleSosBtn;

function handleSosClick(e) {
    if (e && e.target && e.target.closest('.btn-toggle-sos')) return;
    abrirModalSOS();
}
window.handleSosClick = handleSosClick;

/**
 * Funciones de Enlace y Comunicación con el Módulo de Labores (ban_alt_labores_relavera.php)
 */
window.asignarPuntoAlFormulario = function(lat, lng) {
    const msg = {
        type: 'COORDENADA_CAPTURADA',
        lat: parseFloat(lat),
        lng: parseFloat(lng)
    };
    if (window.parent && window.parent !== window) {
        window.parent.postMessage(msg, '*');
    } else if (window.opener) {
        window.opener.postMessage(msg, '*');
        window.close();
    } else {
        alert(`Coordenadas capturadas: ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
    }
};

function sanitizarTexto(str) {
    if (str === undefined || str === null) return '';
    var txt = String(str);
    if (txt.indexOf('%') !== -1) {
        try {
            txt = decodeURIComponent(txt);
        } catch(e) {
            try { txt = decodeURIComponent(escape(txt)); } catch(e2) {}
        }
    }
    txt = txt
        .replace(/\u00c3\u00a1/g, '\u00e1')
        .replace(/\u00c3\u00a9/g, '\u00e9')
        .replace(/\u00c3\u00ad/g, '\u00ed')
        .replace(/\u00c3\u00b3/g, '\u00f3')
        .replace(/\u00c3\u00ba/g, '\u00fa')
        .replace(/\u00c3\u00b1/g, '\u00f1')
        .replace(/\u00c3\u0081/g, '\u00c1')
        .replace(/\u00c3\u0089/g, '\u00c9')
        .replace(/\u00c3\u008d/g, '\u00cd')
        .replace(/\u00c3\u0093/g, '\u00d3')
        .replace(/\u00c3\u009a/g, '\u00da')
        .replace(/\u00c3\u0091/g, '\u00d1')
        .replace(/\u00dd/g, '\u00ed')
        .replace(/\u00be/g, '\u00f3')
        .replace(/&oacute;/gi, '\u00f3').replace(/&aacute;/gi, '\u00e1').replace(/&eacute;/gi, '\u00e9')
        .replace(/&iacute;/gi, '\u00ed').replace(/&uacute;/gi, '\u00fa').replace(/&ntilde;/gi, '\u00f1')
        .replace(/&Oacute;/gi, '\u00d3').replace(/&Aacute;/gi, '\u00c1').replace(/&Eacute;/gi, '\u00c9')
        .replace(/&Iacute;/gi, '\u00cd').replace(/&Uacute;/gi, '\u00da').replace(/&Ntilde;/gi, '\u00d1');
    return txt.replace(/<[^>]*>?/gm, '').trim();
}

window.seleccionarSectorParaFormulario = function(id, nombre, lat, lng, direccion) {
    const nomLimpio = sanitizarTexto(nombre);
    const dirLimpia = sanitizarTexto(direccion || '');
    const msg = {
        type: 'SECTOR_SELECCIONADO',
        id: id,
        nombre: nomLimpio,
        direccion: dirLimpia,
        lat: parseFloat(lat),
        lng: parseFloat(lng)
    };
    if (window.parent && window.parent !== window) {
        window.parent.postMessage(msg, '*');
    } else if (window.opener) {
        window.opener.postMessage(msg, '*');
        window.close();
    } else {
        alert(`Sector seleccionado: ${nomLimpio}`);
    }
};
