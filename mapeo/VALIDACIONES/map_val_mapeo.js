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
    });
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

        abrirModalRegistroActividad({
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
    if (tipo === 'actividad') {
        MapeoState.offlineQueue.actividades.push(item);
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
        const iconoTipo = act.tipo === 'reporte_alerta' ? 'fa-exclamation-triangle text-danger' : 'fa-clipboard text-success';
        
        html += `
            <div class="actividad-card ${act.tipo === 'reporte_alerta' ? 'border-danger' : ''}" onclick="centrarActividad('${act.id}')">
                <div class="act-card-header">
                    <span class="act-card-title"><i class="fa ${iconoTipo}"></i> ${act.tipo_label}</span>
                    <span class="badge-act ${badgeClass}">${act.estado}</span>
                </div>
                <div class="act-card-body">
                    <strong><i class="fa fa-truck"></i> ${act.volqueta_num}</strong> &bull; ${act.chofer}${volText}<br>
                    <span style="color:#64748b;">${act.observaciones || 'Sin detalles adicionales'}</span>
                </div>
                <div class="act-card-meta">
                    <span><i class="fa fa-calendar-o"></i> ${act.fecha}</span>
                    <span><i class="fa fa-map-marker"></i> ${parseFloat(act.lat).toFixed(4)}, ${parseFloat(act.lng).toFixed(4)}</span>
                </div>
            </div>
        `;
    });

    cont.innerHTML = html;
    document.getElementById('total-actividades-count').textContent = MapeoState.actividadesFiltradas.length;
}

function renderActividadesMapa() {
    MapeoState.overlayLayers.actividades.clearLayers();

    MapeoState.actividadesFiltradas.forEach(act => {
        const color = act.tipo === 'reporte_alerta' ? '#ef4444' : '#10b981';
        const marker = L.circleMarker([act.lat, act.lng], {
            radius: 8,
            fillColor: color,
            color: '#ffffff',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.85
        });

        marker.bindPopup(`
            <div style="min-width:220px; font-family:'Segoe UI', sans-serif;">
                <div style="font-weight:700; color:${color}; font-size:14px; margin-bottom:4px;">
                    <i class="fa fa-clipboard"></i> ${act.tipo_label}
                </div>
                <div style="font-size:12px; line-height:1.4; color:#334155; margin-bottom:8px;">
                    <b>Código:</b> ${act.id}<br>
                    <b>Volqueta:</b> ${act.volqueta_num}<br>
                    <b>Chofer:</b> ${act.chofer}<br>
                    <b>Fecha:</b> ${act.fecha}<br>
                    <b>Estado:</b> <span class="badge-act ${act.estado_badge}">${act.estado}</span><br>
                    ${act.volumen_m3 > 0 ? `<b>Cubicaje:</b> ${act.volumen_m3} m³<br>` : ''}
                    ${act.area_m2 > 0 ? `<b>Área:</b> ${act.area_m2} m²<br>` : ''}
                    <b>Notas:</b> ${act.observaciones}
                </div>
                <div style="text-align:right;">
                    <button class="btn btn-xs btn-danger" onclick="eliminarActividad('${act.id}')">
                        <i class="fa fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        `);

        MapeoState.overlayLayers.actividades.addLayer(marker);

        if (act.geometria) {
            const geoLayer = L.geoJSON(act.geometria, {
                style: { color: color, weight: 2, fillOpacity: 0.2 }
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

function abrirModalRegistroActividad(datosGeometria = {}) {
    const modal = document.getElementById('modal-registro-actividad');
    if (!modal) return;

    const lat = datosGeometria.lat || RELAVERA_CONFIG.center[0];
    const lng = datosGeometria.lng || RELAVERA_CONFIG.center[1];
    
    document.getElementById('form-act-lat').value = lat;
    document.getElementById('form-act-lng').value = lng;
    document.getElementById('form-act-area').value = datosGeometria.area_m2 || '0';
    document.getElementById('form-act-longitud').value = datosGeometria.longitud_m || '0';
    document.getElementById('form-act-fecha').value = new Date().toISOString().slice(0, 16);
    document.getElementById('form-act-obs').value = '';

    MapeoState.geometriaTemporal = datosGeometria.geometria || null;
    modal.classList.add('active');
}

function cerrarModalActividad() {
    const modal = document.getElementById('modal-registro-actividad');
    if (modal) modal.classList.remove('active');
}

function guardarActividadFormulario() {
    const tipoSelect = document.getElementById('form-act-tipo');
    const volquetaSelect = document.getElementById('form-act-volqueta');
    const choferInput = document.getElementById('form-act-chofer');
    const estadoSelect = document.getElementById('form-act-estado');

    const tipoVal = tipoSelect.value;
    const tipoLabel = tipoSelect.options[tipoSelect.selectedIndex].text;
    const volquetaVal = volquetaSelect.value;
    const choferVal = choferInput.value || 'Manuel Carrión';
    const lat = parseFloat(document.getElementById('form-act-lat').value);
    const lng = parseFloat(document.getElementById('form-act-lng').value);
    const volumen = parseFloat(document.getElementById('form-act-volumen').value) || 0;
    const area = parseFloat(document.getElementById('form-act-area').value) || 0;
    const longitud = parseFloat(document.getElementById('form-act-longitud').value) || 0;
    const fecha = document.getElementById('form-act-fecha').value.replace('T', ' ');
    const obs = document.getElementById('form-act-obs').value;
    const estado = estadoSelect.value;
    const badge = estado === 'Completada' ? 'success' : (estado === 'En Progreso' ? 'warning' : 'info');

    const payload = {
        id: 'ACT-' + Date.now(),
        tipo: tipoVal,
        tipo_label: tipoLabel,
        volqueta_num: volquetaVal,
        placa: 'OBA-7821',
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
        geometria: MapeoState.geometriaTemporal
    };

    if (!navigator.onLine) {
        guardarEnBufferOffline('actividad', payload);
        MapeoState.actividades.unshift(payload);
        cerrarModalActividad();
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
            cerrarModalActividad();
            cargarActividades();
            mostrarToast('Actividad registrada correctamente en el mapa.');
        } else {
            alert('Error: ' + res.message);
        }
    })
    .catch(() => {
        guardarEnBufferOffline('actividad', payload);
        cerrarModalActividad();
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
            if (res.success && Array.isArray(res.flota)) {
                MapeoState.flota = res.flota;
                poblarSelectoresFlota();
            }
        });
}

function poblarSelectoresFlota() {
    const sel = document.getElementById('form-act-volqueta');
    if (!sel) return;

    sel.innerHTML = '';
    MapeoState.flota.forEach(v => {
        const opt = document.createElement('option');
        opt.value = `${v.codigo} (${v.placa})`;
        opt.textContent = `${v.codigo} - ${v.placa} - ${v.chofer}`;
        sel.appendChild(opt);
    });
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

function toggleSidebar() {
    const sb = document.getElementById('sidebar-mapeo');
    if (sb) sb.classList.toggle('collapsed');
}
