# Documentacion Tecnica: Modulo de Mapeo Interactivo Georreferenciado
## Sistema de Gestion Operativa - Relavera Comunitaria El Tablon

---

## 1. Resumen General del Modulo

El **Modulo de Mapeo Interactivo Georreferenciado** es un sistema GIS web integral disenado para la supervision en tiempo real, trazabilidad de transporte, cubicaje de descarga de relaves y control de emergencias en la **Relavera Comunitaria El Tablon** (Portovelo, Provincia de El Oro, Ecuador).

El sistema integra capas satelitales de alta resolucion con soporte de hasta **Zoom Nivel 22**, tracking GPS movil en vivo, simulacion cinematica geodesica sobre la red vial real de montana, zonificacion con alertas de velocidad, reproductor historico de rutas y sincronizacion directa con el ERP corporativo (MySQL) y funcionamiento tolerante a fallos en **Modo Offline**.

---

## 2. Arquitectura de Archivos y Componentes

El modulo sigue el estandar arquitectonico del proyecto (FRONT, LOGICA, VALIDACIONES, RECURSOS, DATA):

`
mapeo/
│
├── FRONT/
│   └── map_alt_mapeo_interactivo.php   # Vista principal responsiva, HUD de control y modales operativos
│
├── LOGICA/
│   └── map_log_mapeo.php               # Controlador API REST, conexion MySQL ERP y persistencia JSON
│
├── VALIDACIONES/
│   └── map_val_mapeo.js                # Logica de Leaflet, Geoman, Turf.js, cinematica y simulacion de flota
│
├── RECURSOS/
│   └── estilos_mapeo.css               # Estilos CSS, renderizado HD de teselas y animaciones direccionales
│
├── DATA/
│   └── actividades_relavera.json       # Persistencia local de actividades y registros georreferenciados
│
└── MANUAL_TECNICO_MAPEO.md             # Manual tecnico y especificacion de desarrollo del modulo
`

---

## 3. Parametros Geodesicos y Capas Satelitales HD

### 3.1 Puntos Criticos Georreferenciados

| Punto Operativo | Latitud | Longitud | Descripcion |
| :--- | :---: | :---: | :--- |
| **Frente Central de Vertido / Descarga** | -3.7386587286231405 | -79.63034898948571 | Punto exacto de descarga activa de desechos y relave por volquetas autorizadas. |
| **Garita Balanza / Acceso Principal** | -3.7418191000000000 | -79.63029030000000 | Punto de pesaje, registro de manifiestos y control de ingreso vial. |
| **Dique Principal de Contencion** | -3.7392000000000000 | -79.63080000000000 | Berma de contencion estructural y monitoreo geotecnico. |

### 3.2 Proveedores de Capas Satelitales (Ultra Zoom 22)

Para garantizar la maxima nitidez visual en el frente de trabajo, se configuraron teselas con detectRetina: true, maxNativeZoom: 19-20 y escalado visual hasta maxZoom: 22:

1. **Google Hibrido HD (google_hybrid):** Fotografia satelital nitida combinada con red vial y topografia.
   - Endpoint: https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}
2. **Google Satelite Puro HD (google_satelite):** Imagenes satelitales de alta resolucion sin superposiciones.
   - Endpoint: https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}
3. **Esri World Imagery Claridad (esri_satelite):** Ortofotografia de precision para analisis topografico y bermas.
   - Endpoint: https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}
4. **Google Maps Red Vial (google_roads):** Capa vectorial de carreteras y nomenclatura de transito.
   - Endpoint: https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}
5. **OpenTopoMap Relieve (elieve):** Curvas de nivel topograficas y elevaciones de cuenca.
6. **CartoDB Dark Matter (oscuro):** Modo nocturno de alto contraste para operaciones con baja iluminacion.

---

## 4. Simulacion Cinematica y Geodesica de Volquetas

### 4.1 Trazado Geometrico de Vias

El simulador utiliza datos vectoriales extraidos de la via real de acceso a El Tablon (via de montana Way 891821948) conectada a las vias internas de acarreo:

* **Via de Acceso Minero:** Sube por las curvas de nivel de la ladera hasta la garita de balanza en [-3.741819, -79.630290].
* **Via Central de Acarreo:** Desciende hacia el frente de vertido en [-3.7386587286231405, -79.63034898948571].
* **Bermas de Retorno y Dique:** Rutas de circulacion unidireccional para Berma Este y Berma Oeste / Dique de Contencion.

### 4.2 Calculo de Rumbo (Bearing) y Rotacion Dinamica

Para orientar el marcador del vehiculo de forma continua a lo largo de cada curva, se calcula el rumbo esferico hacia el siguiente punto de la via y se aplica rotacion angular instantanea CSS 	ransform: rotate(deg).

### 4.3 Ciclo Operativo de Descarga

Al arribar al frente de vertido, la volqueta detiene su marcha durante un intervalo configurable (simulacion de levante hidraulico del cajon basculante), actualiza su estado operativo a *En Frente de Descarga: Vaciando 16m3 de relave* y, una vez completado el vaciado, emprende el ascenso de retorno por la berma correspondiente.

---

## 5. Modulos Operativos Integrados

### 5.1 Sincronizacion MySQL ERP & Manifiestos
* **Consulta Directa:** Conecta con las tablas de pesaje y manifiestos de despacho (	bl_manifiestos, 	bl_contratos_relavera).
* **Indicador en Vivo:** Muestra el origen de los datos con validacion visual del enlace a la base de datos MySQL.
* **Trazabilidad de Carga:** Identificacion de codigo de ticket, planta minera de procedencia, chofer, placa y pesaje neto en toneladas y metros cubicos (m3).

### 5.2 Modo Offline y Buffer de Sincronizacion
* **Deteccion Automatica de Red:** Escucha los eventos del navegador window.addEventListener('online') y 'offline'.
* **Almacenamiento Seguro:** Al perder la conexion en campo, las actividades y trazas GPS se encolan en localStorage (elavera_offline_queue).
* **Sincronizacion Automatica / Manual:** Al recuperar cobertura celular o Wi-Fi, la cola se transmite por lotes al endpoint sincronizar_lote_offline de forma transparente.

### 5.3 Reproductor Historico (Playback Timeline)
* **Linea de Tiempo Interactiva:** Control deslizante de avance punto a punto con control de reproduccion (Play / Pausa / Reinicio) y multiplicadores de velocidad (1x, 2x, 4x, 8x).
* **Gradiente de Color por Velocidad:**
  - **Verde (<= 12 km/h):** Velocidad normal dentro de limites seguros.
  - **Ambar (13 a 20 km/h):** Precaucion en rampas o curvas.
  - **Rojo (> 20 km/h):** Exceso de velocidad no autorizado.
  - **Azul (0 km/h):** Vehiculo detenido o descargando.

### 5.4 Microzonas, Control Geodesico y Boton SOS
* **Deteccion Espacial de Poligonos:** Emplea algoritmos de punto en poligono (	urf.booleanPointInPolygon) para identificar la microzona actual del vehiculo en tiempo real.
* **Limites de Velocidad:**
  - Frente de Descarga: Maximo 10 km/h.
  - Embalse / Vaso Activo: Maximo 10 km/h.
  - Dique de Contencion: Maximo 15 km/h.
  - Garita Balanza y Via de Acarreo: Maximo 25 km/h.
* **Boton SOS de Emergencia:** Transmision georreferenciada inmediata de alertas criticas que alerta de forma visual en el mapa y notifica al panel central de supervision.

### 5.5 Filtros, Cubicaje y Exportacion Multiformato
* **Panel KPI:** Calculo acumulativo dinamico de actividades registradas, volumen total descargado en metros cubicos (m3) y kilometraje recorrido.
* **Exportacion de Datos:**
  - **GeoJSON:** Capas vectoriales georreferenciadas estandar para GIS.
  - **KML:** Formato nativo para visualizacion e inspeccion 3D en Google Earth.
  - **CSV / Excel:** Planilla con codificacion UTF-8 BOM para reportes administrativos y conciliacion de pesaje.

---

## 6. Especificacion de Endpoints (Backend API REST)

Todas las peticiones se gestionan a traves de mapeo/LOGICA/map_log_mapeo.php:

| Endpoint (ction=) | Metodo | Parametros / Payload | Descripcion |
| :--- | :---: | :--- | :--- |
| get_actividades | GET | Ninguno | Retorna la lista completa de actividades registradas en el mapa. |
| guardar_actividad | POST | JSON con datos de actividad y geometria | Registra una nueva actividad o vertido georreferenciado. |
| eliminar_actividad | GET | id de la actividad | Elimina un registro de actividad existente. |
| get_manifiestos_hoy | GET | Ninguno | Consulta los manifiestos de transporte registrados hoy en MySQL. |
| get_flota | GET | Ninguno | Retorna el catalogo de volquetas activas, choferes y capacidades. |
| egistrar_gps | POST | JSON con lat, lng, speed, heading | Registra un punto de telemetria GPS en la traza activa. |
| get_gps_history | GET | olqueta (codigo de vehiculo) | Recupera los puntos historicos de recorrido para el reproductor de playback. |
| sincronizar_lote_offline | POST | JSON { actividades: [...], gps_puntos: [...] } | Procesa y consolida en bloque los datos acumulados durante la desconexion. |
| eportar_sos | POST | JSON con coordenadas y motivo | Emite y registra una alerta critica de emergencia en el mapa. |
| get_emergencias | GET | Ninguno | Retorna las alertas de emergencia SOS actualmente activas. |
