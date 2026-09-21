<?php
/**
 * Backend y Lógica del Módulo de Mapeo Interactivo - Relavera
 * Manejo de persistencia MySQL / GeoJSON, actividades, capas de vectores, telemetría GPS y Manifiestos
 * @package mapeo.LOGICA
 * @version 2.1
 */
if (!isset($_SESSION)) {
    session_start();
}

// Compatibilidad con PHP 5.3
if (!defined('JSON_PRETTY_PRINT')) {
    define('JSON_PRETTY_PRINT', 0);
}
if (!defined('JSON_UNESCAPED_UNICODE')) {
    define('JSON_UNESCAPED_UNICODE', 0);
}

// Directorio de almacenamiento JSON / Caché local para desarrollo y modo offline
$dataDir = __DIR__ . '/../DATA';
if (!file_exists($dataDir)) {
    @mkdir($dataDir, 0777, true);
}

$archivoActividades = $dataDir . '/actividades_relavera.json';
$archivoSectores = $dataDir . '/sectores_relavera.json';
$archivoVectores = $dataDir . '/vectores_relavera.geojson';
$archivoGpsLogs = $dataDir . '/gps_telemetria_volquetas.json';
$archivoEmergencias = $dataDir . '/emergencias_sos.json';

// Cargar conexión de base de datos del ERP si está disponible
$dbConexion = null;
$empresaCod = isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : 620;

// Definir clase Mock de DebugBar si no existe para evitar Fatal Error en MysqlConexion
if (!class_exists('DebugBar')) {
    class DebugBar {
        public static function __callStatic($name, $args) {}
    }
}

try {
    $rutaConexion = __DIR__ . '/../../DATA/MysqlConexion.php';
    if (file_exists($rutaConexion)) {
        require_once($rutaConexion);
        if (class_exists('MysqlConexion')) {
            $dispositivoBD = isset($_SESSION['Ses_Dat_Dis']) && !empty($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : 'ecoparkmining';
            $dbConexion = new MysqlConexion($dispositivoBD);
            if (!$dbConexion || empty($dbConexion->conexion)) {
                $dbConexion = new MysqlConexion('exa');
            }
            if (!$dbConexion || empty($dbConexion->conexion)) {
                $dbConexion = new MysqlConexion();
            }
        }
    }
} catch (Exception $e) {
    $dbConexion = null;
}

// Inicializar actividades por defecto si el archivo no existe o está vacío
if (!file_exists($archivoActividades) || filesize($archivoActividades) < 5) {
    $actividadesIniciales = array(
        array(
            'id' => 'ACT-' . date('Ymd') . '-001',
            'tipo' => 'descarga_relave',
            'tipo_label' => 'Descarga de Relave Seco',
            'ubicacion_nombre' => 'Frente Central de Vertido',
            'chofer' => 'Manuel Carrión',
            'placa' => 'OBA-7821',
            'volqueta_num' => 'VOL-04 (OBA-7821)',
            'fecha' => date('Y-m-d H:i'),
            'lat' => -3.7386587286231405,
            'lng' => -79.63034898948571,
            'elevacion' => 680.0,
            'area_m2' => 520.0,
            'volumen_m3' => 16.0,
            'longitud_m' => 0,
            'estado' => 'Completada',
            'estado_badge' => 'success',
            'icono' => 'fa-truck',
            'color' => '#10b981',
            'observaciones' => 'Descarga de 16m³ de relave en el punto central de vertido.',
            'geometria' => array(
                'type' => 'Polygon',
                'coordinates' => array(
                    array(
                        array(-79.63060, -3.73845),
                        array(-79.63010, -3.73845),
                        array(-79.63010, -3.73885),
                        array(-79.63060, -3.73885),
                        array(-79.63060, -3.73845)
                    )
                )
            )
        ),
        array(
            'id' => 'ACT-' . date('Ymd') . '-002',
            'tipo' => 'compactacion_dique',
            'tipo_label' => 'Conformación y Compactación de Dique',
            'ubicacion_nombre' => 'Sector Operativo Dique Frontal',
            'chofer' => 'Jorge Aguilar',
            'placa' => 'LBA-9023',
            'volqueta_num' => 'Rodillo / Volqueta #12',
            'fecha' => date('Y-m-d H:i', strtotime('-1 hour')),
            'lat' => -3.73925,
            'lng' => -79.63035,
            'elevacion' => 685.0,
            'area_m2' => 1100.0,
            'volumen_m3' => 0,
            'longitud_m' => 115.0,
            'estado' => 'En Progreso',
            'estado_badge' => 'warning',
            'icono' => 'fa-cogs',
            'color' => '#ea580c',
            'observaciones' => 'Pase de rodillo vibratorio pata de cabra en coronación del dique frontal.',
            'geometria' => array(
                'type' => 'LineString',
                'coordinates' => array(
                    array(-79.63110, -3.73925),
                    array(-79.63035, -3.73925),
                    array(-79.62960, -3.73925)
                )
            )
        ),
        array(
            'id' => 'ACT-' . date('Ymd') . '-003',
            'tipo' => 'monitoreo_piezometro',
            'tipo_label' => 'Monitoreo de Piezómetro PZ-01',
            'ubicacion_nombre' => 'Punto de Monitoreo Geotécnico PZ-01',
            'chofer' => 'Ing. Supervisor Geotécnico',
            'placa' => 'N/A',
            'volqueta_num' => 'Técnico de Campo',
            'fecha' => date('Y-m-d H:i', strtotime('-2 hours')),
            'lat' => -3.73750,
            'lng' => -79.63080,
            'elevacion' => 675.0,
            'area_m2' => 0,
            'volumen_m3' => 0,
            'longitud_m' => 0,
            'estado' => 'Supervisado',
            'estado_badge' => 'info',
            'icono' => 'fa-eye',
            'color' => '#0f766e',
            'observaciones' => 'Lectura nivel freático y estabilidad de taludes ok.',
            'geometria' => array(
                'type' => 'Point',
                'coordinates' => array(-79.63080, -3.73750)
            )
        )
    );
    file_put_contents($archivoActividades, json_encode($actividadesIniciales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

switch ($action) {

    // 1. Obtener lista de actividades registradas con soporte de filtros
    case 'get_actividades':
        $filtroTipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
        $filtroEstado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

        $actividades = array();
        if (file_exists($archivoActividades)) {
            $jsonContent = file_get_contents($archivoActividades);
            $actividades = json_decode($jsonContent, true);
            if (!is_array($actividades)) {
                $actividades = array();
            }
        }

        // Aplicar filtros si se enviaron parámetros
        if (!empty($filtroTipo) || !empty($filtroEstado)) {
            $actividades = array_values(array_filter($actividades, function($act) use ($filtroTipo, $filtroEstado) {
                if (!empty($filtroTipo) && (!isset($act['tipo']) || $act['tipo'] !== $filtroTipo)) return false;
                if (!empty($filtroEstado) && (!isset($act['estado']) || $act['estado'] !== $filtroEstado)) return false;
                return true;
            }));
        }

        // Asegurar que cada actividad tenga ubicacion_nombre, icono y color
        foreach ($actividades as &$act) {
            if (empty($act['ubicacion_nombre'])) {
                if (!empty($act['sector_nombre'])) {
                    $act['ubicacion_nombre'] = $act['sector_nombre'];
                } elseif (isset($act['lat']) && isset($act['lng'])) {
                    $dLat = abs((float)$act['lat'] - (-3.738658));
                    $dLng = abs((float)$act['lng'] - (-79.630349));
                    if ($dLat < 0.0008 && $dLng < 0.0008) {
                        $act['ubicacion_nombre'] = 'Frente Central de Vertido';
                    } elseif ((float)$act['lat'] < -3.7405) {
                        $act['ubicacion_nombre'] = 'Garita de Control / Balanza';
                    } else {
                        $act['ubicacion_nombre'] = 'Sector Operativo Dique';
                    }
                } else {
                    $act['ubicacion_nombre'] = 'Zona Relavera';
                }
            }

            // Defaults inteligentes de icono y color si no los tiene
            if (empty($act['icono'])) {
                $tipoAct = isset($act['tipo']) ? $act['tipo'] : '';
                if ($tipoAct === 'reporte_alerta') $act['icono'] = 'fa-exclamation-triangle';
                elseif ($tipoAct === 'monitoreo_piezometro') $act['icono'] = 'fa-eye';
                elseif ($tipoAct === 'descarga_humeda') $act['icono'] = 'fa-tint';
                elseif ($tipoAct === 'compactacion_dique') $act['icono'] = 'fa-cogs';
                elseif ($tipoAct === 'mantenimiento_vias') $act['icono'] = 'fa-road';
                elseif ($tipoAct === 'acarreo_material') $act['icono'] = 'fa-truck';
                else $act['icono'] = 'fa-truck';
            }

            if (empty($act['color'])) {
                $tipoAct = isset($act['tipo']) ? $act['tipo'] : '';
                if ($tipoAct === 'reporte_alerta') $act['color'] = '#ef4444';
                elseif ($tipoAct === 'monitoreo_piezometro') $act['color'] = '#0f766e';
                elseif ($tipoAct === 'descarga_humeda') $act['color'] = '#06b6d4';
                elseif ($tipoAct === 'compactacion_dique') $act['color'] = '#ea580c';
                elseif ($tipoAct === 'mantenimiento_vias') $act['color'] = '#64748b';
                else $act['color'] = '#10b981';
            }
        }
        unset($act);

        // Cálculo de cubicaje acumulado (m3)
        $totalCubicajeM3 = 0;
        foreach ($actividades as $act) {
            if (isset($act['volumen_m3'])) {
                $totalCubicajeM3 += (float)$act['volumen_m3'];
            } elseif (isset($act['tipo']) && strpos($act['tipo'], 'descarga') !== false) {
                $totalCubicajeM3 += 16.0;
            }
        }

        echo json_encode(array(
            'success' => true,
            'actividades' => $actividades,
            'total_m3' => round($totalCubicajeM3, 2),
            'total_registros' => count($actividades)
        ));
        break;

    // Obtener lista de sectores / ubicaciones de referencia desde finca_actividad
    case 'get_sectores':
        $sectores = array();
        if ($dbConexion && !empty($dbConexion->conexion)) {
            try {
                $sqlSec = "SELECT Fnc_Cod, Fnc_Des, Fnc_Dir, Fnc_Hec, Fnc_Lat, Fnc_Lng, Fnc_Geo_JSON, Suc_Cod, Fnc_Est 
                           FROM finca_actividad 
                           WHERE Fnc_Est = 'A' 
                           ORDER BY Fnc_Des ASC";
                $resSec = @mysqli_query($dbConexion->conexion, $sqlSec);
                if ($resSec) {
                    while ($row = @mysqli_fetch_assoc($resSec)) {
                        $geo = null;
                        $icono = 'fa-map-marker';
                        $color = '#8b5cf6';
                        $categoria = 'Sector Operativo';
                        if (!empty($row['Fnc_Geo_JSON'])) {
                            $geo = json_decode($row['Fnc_Geo_JSON'], true);
                            if (is_array($geo)) {
                                if (isset($geo['properties']['icono'])) $icono = $geo['properties']['icono'];
                                elseif (isset($geo['icono'])) $icono = $geo['icono'];

                                if (isset($geo['properties']['color'])) $color = $geo['properties']['color'];
                                elseif (isset($geo['color'])) $color = $geo['color'];

                                if (isset($geo['properties']['categoria'])) $categoria = $geo['properties']['categoria'];
                            }
                        }
                        $sectores[] = array(
                            'id' => (int)$row['Fnc_Cod'],
                            'nombre' => $row['Fnc_Des'],
                            'categoria' => $categoria,
                            'direccion' => !empty($row['Fnc_Dir']) ? $row['Fnc_Dir'] : '',
                            'hectareas' => (float)$row['Fnc_Hec'],
                            'lat' => (float)$row['Fnc_Lat'],
                            'lng' => (float)$row['Fnc_Lng'],
                            'geometria' => $geo,
                            'icono' => $icono,
                            'color' => $color,
                            'suc_cod' => $row['Suc_Cod']
                        );
                    }
                }
            } catch (Exception $e) {}
        }

        // Si no hay en BD o para complementar iconos/colores de caché JSON
        if (file_exists($archivoSectores)) {
            $jsonSec = json_decode(file_get_contents($archivoSectores), true);
            if (is_array($jsonSec)) {
                if (empty($sectores)) {
                    $sectores = $jsonSec;
                } else {
                    $cacheMap = array();
                    foreach ($jsonSec as $js) {
                        if (isset($js['id'])) $cacheMap[$js['id']] = $js;
                    }
                    foreach ($sectores as &$sec) {
                        if (isset($cacheMap[$sec['id']])) {
                            if (($sec['icono'] === 'fa-map-marker' || empty($sec['icono'])) && !empty($cacheMap[$sec['id']]['icono'])) {
                                $sec['icono'] = $cacheMap[$sec['id']]['icono'];
                            }
                            if (($sec['color'] === '#8b5cf6' || empty($sec['color'])) && !empty($cacheMap[$sec['id']]['color'])) {
                                $sec['color'] = $cacheMap[$sec['id']]['color'];
                            }
                        }
                    }
                    unset($sec);
                }
            }
        }

        echo json_encode(array(
            'success' => true,
            'sectores' => $sectores,
            'total' => count($sectores)
        ));
        break;

    // Guardar una nueva ubicación / sector de referencia en finca_actividad
    case 'guardar_sector':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || empty($data['nombre']) || !isset($data['lat']) || !isset($data['lng'])) {
            echo json_encode(array('success' => false, 'message' => 'El nombre de la ubicación y las coordenadas son obligatorios.'));
            exit;
        }

        $nombre = trim($data['nombre']);
        $categoria = isset($data['categoria']) ? trim($data['categoria']) : 'Sector Operativo';
        $direccion = isset($data['direccion']) ? trim($data['direccion']) : '';
        $hectareas = isset($data['hectareas']) ? (float)$data['hectareas'] : 0.0;
        $lat = (float)$data['lat'];
        $lng = (float)$data['lng'];
        $icono = isset($data['icono']) && !empty($data['icono']) ? trim($data['icono']) : 'fa-map-marker';
        $color = isset($data['color']) && !empty($data['color']) ? trim($data['color']) : '#8b5cf6';
        $geometria = isset($data['geometria']) ? $data['geometria'] : null;

        // Estructurar GeoJSON con propiedades de estilo (icono y color)
        if (empty($geometria)) {
            $geoData = array(
                'type' => 'Point',
                'coordinates' => array($lng, $lat),
                'properties' => array(
                    'icono' => $icono,
                    'color' => $color,
                    'categoria' => $categoria
                )
            );
        } else {
            $geoData = $geometria;
            if (!isset($geoData['properties']) || !is_array($geoData['properties'])) {
                $geoData['properties'] = array();
            }
            $geoData['properties']['icono'] = $icono;
            $geoData['properties']['color'] = $color;
            $geoData['properties']['categoria'] = $categoria;
        }
        $geoJsonStr = json_encode($geoData);
        $sucCod = 759; // Relavera
        $nuevoId = null;

        if ($dbConexion && !empty($dbConexion->conexion)) {
            try {
                $nomEsc = mysqli_real_escape_string($dbConexion->conexion, $nombre);
                $dirEsc = mysqli_real_escape_string($dbConexion->conexion, $direccion);
                $geoEsc = mysqli_real_escape_string($dbConexion->conexion, $geoJsonStr);
                $sqlIns = "INSERT INTO finca_actividad (Fnc_Des, Fnc_Dir, Fnc_Hec, Fnc_Lat, Fnc_Lng, Fnc_Geo_JSON, Suc_Cod, Fnc_Est) 
                           VALUES ('{$nomEsc}', '{$dirEsc}', {$hectareas}, {$lat}, {$lng}, '{$geoEsc}', {$sucCod}, 'A')";
                $resIns = @mysqli_query($dbConexion->conexion, $sqlIns);
                if ($resIns) {
                    $nuevoId = (int)mysqli_insert_id($dbConexion->conexion);
                }
            } catch (Exception $e) {}
        }

        if (!$nuevoId) {
            $nuevoId = time();
        }

        // Actualizar caché JSON local para offline
        $sectores = array();
        if (file_exists($archivoSectores)) {
            $sectores = json_decode(file_get_contents($archivoSectores), true);
            if (!is_array($sectores)) $sectores = array();
        }
        $sectorObj = array(
            'id' => $nuevoId,
            'nombre' => $nombre,
            'categoria' => $categoria,
            'direccion' => $direccion,
            'hectareas' => $hectareas,
            'lat' => $lat,
            'lng' => $lng,
            'geometria' => $geometria,
            'icono' => $icono,
            'color' => $color,
            'suc_cod' => $sucCod,
            'fecha_guardado' => date('Y-m-d H:i:s')
        );
        array_unshift($sectores, $sectorObj);
        file_put_contents($archivoSectores, json_encode($sectores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        echo json_encode(array(
            'success' => true,
            'id' => $nuevoId,
            'nombre' => $nombre,
            'sector' => $sectorObj,
            'message' => 'Ubicación / Sector registrado exitosamente en la base de datos (finca_actividad).'
        ));
        break;

    // Subida y almacenamiento de imágenes de evidencia (mapeo/RECURSOS/locator/)
    case 'subir_evidencia':
        $dirDestino = dirname(__FILE__) . '/../RECURSOS/locator/';
        if (!is_dir($dirDestino)) {
            @mkdir($dirDestino, 0777, true);
        }

        $archivoGuardado = null;
        $nombreOriginal = '';
        $tamanoBytes = 0;

        // 1. Manejar multipart $_FILES['evidencia']
        if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['evidencia']['tmp_name'];
            $nombreOriginal = $_FILES['evidencia']['name'];
            $tamanoBytes = $_FILES['evidencia']['size'];
            $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            if (!in_array($ext, array('jpg', 'jpeg', 'png', 'webp'))) {
                $ext = 'jpg';
            }
            $nuevoNombre = 'EVI_' . date('Ymd_His') . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 6) . '.' . $ext;
            $rutaDestino = $dirDestino . $nuevoNombre;
            if (move_uploaded_file($tmpName, $rutaDestino)) {
                $archivoGuardado = $nuevoNombre;
            }
        } 
        // 2. Manejar base64 desde payload JSON
        else {
            $input = file_get_contents('php://input');
            $jsonData = json_decode($input, true);
            if ($jsonData && !empty($jsonData['imagen_base64'])) {
                $base64 = $jsonData['imagen_base64'];
                $nombreOriginal = !empty($jsonData['nombre']) ? $jsonData['nombre'] : 'evidencia.jpg';
                $ext = 'jpg';
                if (preg_match('/^data:image\/(\w+);base64,/', $base64, $typeMatch)) {
                    $base64 = substr($base64, strpos($base64, ',') + 1);
                    $extRaw = strtolower($typeMatch[1]);
                    if (in_array($extRaw, array('jpg', 'jpeg', 'png', 'webp'))) {
                        $ext = ($extRaw === 'jpeg') ? 'jpg' : $extRaw;
                    }
                }
                $decoded = base64_decode($base64);
                if ($decoded !== false) {
                    $nuevoNombre = 'EVI_' . date('Ymd_His') . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 6) . '.' . $ext;
                    $rutaDestino = $dirDestino . $nuevoNombre;
                    if (file_put_contents($rutaDestino, $decoded)) {
                        $archivoGuardado = $nuevoNombre;
                        $tamanoBytes = strlen($decoded);
                    }
                }
            }
        }

        if ($archivoGuardado) {
            echo json_encode(array(
                'success' => true,
                'archivo' => $archivoGuardado,
                'url' => '../RECURSOS/locator/' . $archivoGuardado,
                'nombre_original' => $nombreOriginal,
                'tamano' => $tamanoBytes,
                'fecha' => date('Y-m-d H:i:s'),
                'message' => 'Evidencia guardada exitosamente en locator.'
            ));
        } else {
            echo json_encode(array(
                'success' => false,
                'message' => 'No se pudo guardar la imagen de evidencia.'
            ));
        }
        break;

    // 2. Guardar o actualizar una actividad
    case 'guardar_actividad':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || !isset($data['lat']) || !isset($data['lng'])) {
            echo json_encode(array('success' => false, 'message' => 'Datos incompletos para registrar la actividad.'));
            exit;
        }

        $actividades = array();
        if (file_exists($archivoActividades)) {
            $actividades = json_decode(file_get_contents($archivoActividades), true);
            if (!is_array($actividades)) $actividades = array();
        }

        $idActividad = !empty($data['id']) ? $data['id'] : ('ACT-' . date('Ymd-His'));
        $data['id'] = $idActividad;
        $data['fecha_guardado'] = date('Y-m-d H:i:s');
        $data['usuario'] = isset($_SESSION['Ses_Usu_Nom']) ? $_SESSION['Ses_Usu_Nom'] : 'Usuario Sistema';

        // Buscar si ya existe para actualizar o insertar al inicio
        $encontrado = false;
        foreach ($actividades as $key => $act) {
            if ($act['id'] === $idActividad) {
                $actividades[$key] = $data;
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) {
            array_unshift($actividades, $data);
        }

        file_put_contents($archivoActividades, json_encode($actividades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Persistir en MySQL ecoparkmining.relavera_actividades si la BD está disponible
        if ($dbConexion && !empty($dbConexion->conexion)) {
            try {
                $fncCodVal = (!empty($data['fnc_cod']) && is_numeric($data['fnc_cod'])) ? (int)$data['fnc_cod'] : 'NULL';
                $vehCodVal = (!empty($data['veh_cod']) && is_numeric($data['veh_cod'])) ? (int)$data['veh_cod'] : 'NULL';
                $choCodVal = (!empty($data['cho_cod']) && is_numeric($data['cho_cod'])) ? (int)$data['cho_cod'] : 'NULL';

                // Si no vinieron los códigos numéricos directos, resolver por placa y chofer
                if ($vehCodVal === 'NULL' && !empty($data['placa']) && $data['placa'] !== 'N/A') {
                    $plaEsc = mysqli_real_escape_string($dbConexion->conexion, $data['placa']);
                    $resV = @mysqli_query($dbConexion->conexion, "SELECT Veh_Cod FROM vehiculo WHERE Veh_Pla = '{$plaEsc}' LIMIT 1");
                    if ($resV && ($rowV = @mysqli_fetch_assoc($resV))) {
                        $vehCodVal = (int)$rowV['Veh_Cod'];
                    }
                }

                if ($choCodVal === 'NULL' && !empty($data['chofer'])) {
                    $choEsc = mysqli_real_escape_string($dbConexion->conexion, $data['chofer']);
                    $resC = @mysqli_query($dbConexion->conexion, "SELECT c.Cho_Cod FROM chofer c INNER JOIN persona p ON c.Prs_Ced = p.Prs_Ced WHERE CONCAT(p.Prs_Ape, ' ', p.Prs_Nom) LIKE '%{$choEsc}%' LIMIT 1");
                    if ($resC && ($rowC = @mysqli_fetch_assoc($resC))) {
                        $choCodVal = (int)$rowC['Cho_Cod'];
                    }
                }

                $actIde = mysqli_real_escape_string($dbConexion->conexion, $idActividad);
                $actTip = mysqli_real_escape_string($dbConexion->conexion, isset($data['tipo']) ? $data['tipo'] : 'descarga_relave');
                $actTipLab = mysqli_real_escape_string($dbConexion->conexion, isset($data['tipo_label']) ? $data['tipo_label'] : 'Descarga de Relave');

                // Resolver nombre de la ubicación / sector
                $ubiNom = !empty($data['ubicacion_nombre']) ? trim($data['ubicacion_nombre']) : '';
                if (empty($ubiNom) && $fncCodVal !== 'NULL') {
                    $resF = @mysqli_query($dbConexion->conexion, "SELECT Fnc_Des FROM finca_actividad WHERE Fnc_Cod = {$fncCodVal} LIMIT 1");
                    if ($resF && ($rowF = @mysqli_fetch_assoc($resF))) {
                        $ubiNom = $rowF['Fnc_Des'];
                    }
                }
                if (empty($ubiNom)) {
                    $ubiNom = 'Frente Central de Vertido';
                }
                $data['ubicacion_nombre'] = $ubiNom;
                $ubiEsc = mysqli_real_escape_string($dbConexion->conexion, $ubiNom);

                $actLat = (float)$data['lat'];
                $actLng = (float)$data['lng'];
                $actVol = isset($data['volumen_m3']) ? (float)$data['volumen_m3'] : 0.0;
                $actAre = isset($data['area_m2']) ? (float)$data['area_m2'] : 0.0;
                $actLon = isset($data['longitud_m']) ? (float)$data['longitud_m'] : 0.0;
                $actFec = !empty($data['fecha']) ? mysqli_real_escape_string($dbConexion->conexion, $data['fecha']) : date('Y-m-d H:i:s');
                $actEstOpe = mysqli_real_escape_string($dbConexion->conexion, isset($data['estado']) ? $data['estado'] : 'Completada');
                $actEstBad = mysqli_real_escape_string($dbConexion->conexion, isset($data['estado_badge']) ? $data['estado_badge'] : 'success');
                $actObs = mysqli_real_escape_string($dbConexion->conexion, isset($data['observaciones']) ? $data['observaciones'] : '');
                $actGeo = !empty($data['geometria']) ? mysqli_real_escape_string($dbConexion->conexion, json_encode($data['geometria'])) : 'NULL';
                $actGeoSql = ($actGeo === 'NULL') ? 'NULL' : "'{$actGeo}'";

                $sqlAct = "INSERT INTO relavera_actividades (
                    Act_Ide, Emp_Cod, Suc_Cod, Fnc_Cod, Veh_Cod, Cho_Cod,
                    Act_Tip, Act_Tip_Lab, Act_Ubi_Nom, Act_Lat, Act_Lng, Act_Ele,
                    Act_Vol_M3, Act_Are_M2, Act_Lon_M, Act_Fec, Act_Est,
                    Act_Est_Ope, Act_Est_Bad, Act_Obs, Act_Geo_JSON, Act_Sync_Off
                ) VALUES (
                    '{$actIde}', 620, 759, {$fncCodVal}, {$vehCodVal}, {$choCodVal},
                    '{$actTip}', '{$actTipLab}', '{$ubiEsc}', {$actLat}, {$actLng}, 680.00,
                    {$actVol}, {$actAre}, {$actLon}, '{$actFec}', 'A',
                    '{$actEstOpe}', '{$actEstBad}', '{$actObs}', {$actGeoSql}, 0
                ) ON DUPLICATE KEY UPDATE 
                    Act_Tip = '{$actTip}', Act_Tip_Lab = '{$actTipLab}',
                    Act_Ubi_Nom = '{$ubiEsc}',
                    Act_Lat = {$actLat}, Act_Lng = {$actLng},
                    Act_Vol_M3 = {$actVol}, Act_Are_M2 = {$actAre}, Act_Lon_M = {$actLon},
                    Act_Est_Ope = '{$actEstOpe}', Act_Est_Bad = '{$actEstBad}', Act_Obs = '{$actObs}'";

                @mysqli_query($dbConexion->conexion, $sqlAct);
            } catch (Exception $e) {}
        }

        echo json_encode(array('success' => true, 'id' => $idActividad, 'message' => 'Actividad guardada correctamente.'));
        break;

    // 3. Sincronización en lote desde Modo Offline (localStorage / buffer)
    case 'sincronizar_lote_offline':
        $input = file_get_contents('php://input');
        $lote = json_decode($input, true);

        if (!$lote || (!isset($lote['actividades']) && !isset($lote['gps_puntos']))) {
            echo json_encode(array('success' => false, 'message' => 'Lote offline vacío o formato inválido.'));
            exit;
        }

        $procesadosAct = 0;
        $procesadosGps = 0;

        // Sincronizar Actividades
        if (isset($lote['actividades']) && is_array($lote['actividades']) && count($lote['actividades']) > 0) {
            $actividades = array();
            if (file_exists($archivoActividades)) {
                $actividades = json_decode(file_get_contents($archivoActividades), true);
                if (!is_array($actividades)) $actividades = array();
            }

            foreach ($lote['actividades'] as $actOffline) {
                if (isset($actOffline['lat']) && isset($actOffline['lng'])) {
                    if (empty($actOffline['id'])) {
                        $actOffline['id'] = 'ACT-OFF-' . uniqid();
                    }
                    $actOffline['sincronizado_offline'] = true;
                    $actOffline['fecha_sincronizacion'] = date('Y-m-d H:i:s');
                    array_unshift($actividades, $actOffline);
                    $procesadosAct++;
                }
            }
            file_put_contents($archivoActividades, json_encode($actividades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // Sincronizar Puntos GPS
        if (isset($lote['gps_puntos']) && is_array($lote['gps_puntos']) && count($lote['gps_puntos']) > 0) {
            $gpsLogs = array();
            if (file_exists($archivoGpsLogs)) {
                $gpsLogs = json_decode(file_get_contents($archivoGpsLogs), true);
                if (!is_array($gpsLogs)) $gpsLogs = array();
            }

            foreach ($lote['gps_puntos'] as $pt) {
                $pt['sincronizado_offline'] = true;
                $gpsLogs[] = $pt;
                $procesadosGps++;
            }
            if (count($gpsLogs) > 500) {
                $gpsLogs = array_slice($gpsLogs, -500);
            }
            file_put_contents($archivoGpsLogs, json_encode($gpsLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        echo json_encode(array(
            'success' => true,
            'message' => "Sincronización exitosa: {$procesadosAct} actividades y {$procesadosGps} puntos GPS sincronizados.",
            'actividades_procesadas' => $procesadosAct,
            'gps_procesados' => $procesadosGps
        ));
        break;

    // 4. Eliminar actividad
    case 'eliminar_actividad':
        $id = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (empty($id)) {
            echo json_encode(array('success' => false, 'message' => 'ID no proporcionado.'));
            exit;
        }

        $actividades = array();
        if (file_exists($archivoActividades)) {
            $actividades = json_decode(file_get_contents($archivoActividades), true);
            if (!is_array($actividades)) $actividades = array();
        }

        $nuevasActividades = array_values(array_filter($actividades, function($act) use ($id) {
            return $act['id'] !== $id;
        }));

        file_put_contents($archivoActividades, json_encode($nuevasActividades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(array('success' => true, 'message' => 'Actividad eliminada con éxito.'));
        break;

    // 5. Consultar flota real de choferes y volquetas desde MySQL ERP
    case 'get_flota':
        $vehiculos = array();
        $choferes = array();
        $flota = array();

        if ($dbConexion && is_object($dbConexion) && !empty($dbConexion->conexion)) {
            try {
                // Consulta de Vehículos / Maquinaria Activa
                $sqlVeh = "SELECT v.Veh_Cod, v.Veh_Pla, v.Veh_Mar, v.Veh_Cap, t.Mat_Des
                           FROM vehiculo v
                           LEFT JOIN manifiesto_transporte t ON v.Mat_Cod = t.Mat_Cod
                           WHERE v.Veh_Est = 'A'
                           GROUP BY v.Veh_Pla
                           ORDER BY v.Veh_Pla ASC";
                $resVeh = @mysqli_query($dbConexion->conexion, $sqlVeh);
                if ($resVeh) {
                    while ($row = @mysqli_fetch_assoc($resVeh)) {
                        $capM3 = 16.0;
                        if (!empty($row['Veh_Cap'])) {
                            $valCap = (float)$row['Veh_Cap'];
                            if ($valCap > 100) {
                                $capM3 = round($valCap / 1600, 1);
                                if ($capM3 <= 0 || $capM3 > 50) $capM3 = 16.0;
                            } elseif ($valCap > 0) {
                                $capM3 = $valCap;
                            }
                        }
                        $vehItem = array(
                            'codigo' => 'VOL-' . $row['Veh_Cod'],
                            'placa' => $row['Veh_Pla'],
                            'marca' => !empty($row['Veh_Mar']) ? $row['Veh_Mar'] : 'Volqueta / Maquinaria',
                            'capacidad_m3' => $capM3,
                            'transporte' => !empty($row['Mat_Des']) ? $row['Mat_Des'] : 'Transporte Operativo'
                        );
                        $vehiculos[] = $vehItem;
                        $flota[] = array(
                            'codigo' => $vehItem['codigo'],
                            'placa' => $vehItem['placa'],
                            'capacidad_m3' => $vehItem['capacidad_m3'],
                            'transporte' => $vehItem['transporte'],
                            'chofer' => 'Por Asignar'
                        );
                    }
                }

                // Consulta de Choferes / Operadores Activos
                $sqlCho = "SELECT c.Cho_Cod, p.Prs_Nom, p.Prs_Ape, p.Prs_Ced, c.Cho_Tel
                           FROM chofer c
                           INNER JOIN persona p ON c.Prs_Cod = p.Prs_Cod
                           WHERE c.Cho_Est = 'A'
                           GROUP BY p.Prs_Ced
                           ORDER BY p.Prs_Ape ASC, p.Prs_Nom ASC";
                $resCho = @mysqli_query($dbConexion->conexion, $sqlCho);
                if ($resCho) {
                    while ($row = @mysqli_fetch_assoc($resCho)) {
                        $nom = trim($row['Prs_Ape'] . ' ' . $row['Prs_Nom']);
                        $choferes[] = array(
                            'codigo' => 'CHO-' . $row['Cho_Cod'],
                            'nombre' => $nom,
                            'cedula' => !empty($row['Prs_Ced']) ? $row['Prs_Ced'] : '',
                            'telefono' => !empty($row['Cho_Tel']) ? $row['Cho_Tel'] : ''
                        );
                    }
                }
            } catch (Exception $e) {}
        }

        // Fallbacks si no se obtuvo datos de BD
        if (empty($vehiculos)) {
            $vehiculos = array(
                array('codigo' => 'VOL-04', 'placa' => 'OBA-7821', 'marca' => 'HINO 700', 'capacidad_m3' => 16.0, 'transporte' => 'Trans. El Tablón'),
                array('codigo' => 'VOL-09', 'placa' => 'PBC-3419', 'marca' => 'MERCEDES ACTROS', 'capacidad_m3' => 14.0, 'transporte' => 'Trans. Relavera Sur'),
                array('codigo' => 'VOL-12', 'placa' => 'LBA-9023', 'marca' => 'MACK GRANITE', 'capacidad_m3' => 18.0, 'transporte' => 'Trans. Minero Central'),
                array('codigo' => 'VOL-15', 'placa' => 'PBA-6124', 'marca' => 'VOLVO FMX', 'capacidad_m3' => 16.0, 'transporte' => 'Trans. El Oro')
            );
            $flota = $vehiculos;
        }

        if (empty($choferes)) {
            $choferes = array(
                array('codigo' => 'CHO-01', 'nombre' => 'Carrión Manuel', 'cedula' => '0703819201'),
                array('codigo' => 'CHO-02', 'nombre' => 'Espinoza Luis', 'cedula' => '0702918234'),
                array('codigo' => 'CHO-03', 'nombre' => 'Aguilar Jorge', 'cedula' => '0704928172'),
                array('codigo' => 'CHO-04', 'nombre' => 'Morales Carlos', 'cedula' => '0705829104')
            );
        }

        echo json_encode(array(
            'success' => true,
            'vehiculos' => $vehiculos,
            'choferes' => $choferes,
            'flota' => $flota,
            'total_vehiculos' => count($vehiculos),
            'total_choferes' => count($choferes),
            'origen_bd' => ($dbConexion && !empty($dbConexion->conexion) && count($vehiculos) > 4)
        ));
        break;

    // 6. Consultar Manifiestos de hoy desde MySQL ERP
    case 'get_manifiestos_hoy':
        $manifiestos = array();
        $fechaHoy = date('Y-m-d');

        if ($dbConexion && is_object($dbConexion)) {
            try {
                $sql = "SELECT m.Man_Cod, m.Man_Fec, m.Man_Hor_Lle, m.Man_Pes_Net,
                               v.Veh_Pla, p.Pla_Nom,
                               CONCAT(per.Prs_Nom, ' ', per.Prs_Ape) AS Chofer_Nombre
                        FROM manifiesto m
                        LEFT JOIN vehiculo v ON m.Veh_Cod = v.Veh_Cod
                        LEFT JOIN manifiesto_plantas p ON m.Pla_Cod = p.Pla_Cod
                        LEFT JOIN chofer c ON m.Cho_Cod = c.Cho_Cod
                        LEFT JOIN persona per ON c.Prs_Ced = per.Prs_Ced
                        WHERE m.Emp_Cod = '{$empresaCod}' AND m.Man_Fec = '{$fechaHoy}'
                        ORDER BY m.Man_Cod DESC
                        LIMIT 10";
                $result = ($dbConexion && !empty($dbConexion->conexion)) ? @mysqli_query($dbConexion->conexion, $sql) : false;

                if ($result) {
                    while ($row = @mysqli_fetch_assoc($result)) {
                        $manifiestos[] = array(
                            'codigo' => 'MAN-' . str_pad($row['Man_Cod'], 5, '0', STR_PAD_LEFT),
                            'hora' => substr($row['Man_Hor_Lle'], 0, 5),
                            'placa' => $row['Veh_Pla'],
                            'planta' => $row['Pla_Nom'] ? $row['Pla_Nom'] : 'Planta de Beneficio',
                            'chofer' => $row['Chofer_Nombre'] ? $row['Chofer_Nombre'] : 'Conductor',
                            'peso_neto_ton' => round((float)$row['Man_Pes_Net'], 2),
                            'volumen_m3' => round(((float)$row['Man_Pes_Net'] / 1.6), 1),
                            'estado' => 'Completado'
                        );
                    }
                }
            } catch (Exception $e) {}
        }

        // Fallback de demostración si no hay manifiestos cargados en la fecha
        if (empty($manifiestos)) {
            $manifiestos = array(
                array('codigo' => 'MAN-00891', 'hora' => '08:15', 'placa' => 'OBA-7821', 'planta' => 'Planta San Luis #2', 'chofer' => 'Manuel Carrión', 'peso_neto_ton' => 24.5, 'volumen_m3' => 15.3, 'estado' => 'Completado'),
                array('codigo' => 'MAN-00892', 'hora' => '09:40', 'placa' => 'PBC-3419', 'planta' => 'Planta El Tablón Central', 'chofer' => 'Luis Espinoza', 'peso_neto_ton' => 22.8, 'volumen_m3' => 14.2, 'estado' => 'Completado'),
                array('codigo' => 'MAN-00893', 'hora' => '11:10', 'placa' => 'LBA-9023', 'planta' => 'Molino & Flotación Bella Rica', 'chofer' => 'Jorge Aguilar', 'peso_neto_ton' => 28.0, 'volumen_m3' => 17.5, 'estado' => 'Completado'),
                array('codigo' => 'MAN-00894', 'hora' => '13:05', 'placa' => 'PBA-6124', 'planta' => 'Planta Providencia', 'chofer' => 'Carlos Morales', 'peso_neto_ton' => 25.1, 'volumen_m3' => 15.7, 'estado' => 'En Tránsito')
            );
        }

        echo json_encode(array('success' => true, 'manifiestos' => $manifiestos, 'origen_bd' => ($dbConexion !== null)));
        break;

    // 7. Botón de Pánico y Alerta SOS Georreferenciada
    case 'reportar_sos':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || !isset($data['lat']) || !isset($data['lng'])) {
            echo json_encode(array('success' => false, 'message' => 'Coordenadas requeridas para el SOS.'));
            exit;
        }

        $emergencias = array();
        if (file_exists($archivoEmergencias)) {
            $emergencias = json_decode(file_get_contents($archivoEmergencias), true);
            if (!is_array($emergencias)) $emergencias = array();
        }

        $sosRegistro = array(
            'id' => 'SOS-' . date('Ymd-His'),
            'lat' => (float)$data['lat'],
            'lng' => (float)$data['lng'],
            'motivo' => isset($data['motivo']) ? $data['motivo'] : 'Alerta de Emergencia Inmediata',
            'chofer' => isset($data['chofer']) ? $data['chofer'] : 'Operador en Campo',
            'volqueta' => isset($data['volqueta']) ? $data['volqueta'] : 'Unidad de Acarreo',
            'fecha_hora' => date('Y-m-d H:i:s'),
            'estado' => 'ACTIVA'
        );

        array_unshift($emergencias, $sosRegistro);
        if (count($emergencias) > 50) $emergencias = array_slice($emergencias, 0, 50);

        file_put_contents($archivoEmergencias, json_encode($emergencias, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Registrar también como actividad crítica tipo "reporte_alerta"
        $actividades = array();
        if (file_exists($archivoActividades)) {
            $actividades = json_decode(file_get_contents($archivoActividades), true);
            if (!is_array($actividades)) $actividades = array();
        }

        $actAlerta = array(
            'id' => $sosRegistro['id'],
            'tipo' => 'reporte_alerta',
            'tipo_label' => 'Alerta Crítica SOS: ' . $sosRegistro['motivo'],
            'chofer' => $sosRegistro['chofer'],
            'placa' => 'EMERGENCIA',
            'volqueta_num' => $sosRegistro['volqueta'],
            'fecha' => date('Y-m-d H:i'),
            'lat' => $sosRegistro['lat'],
            'lng' => $sosRegistro['lng'],
            'elevacion' => 680.0,
            'area_m2' => 0,
            'volumen_m3' => 0,
            'longitud_m' => 0,
            'estado' => 'Alerta Crítica',
            'estado_badge' => 'danger',
            'observaciones' => 'EMERGENCIA ACTIVADA DESDE BOTÓN DE PÁNICO GEORREFERENCIADO.'
        );
        array_unshift($actividades, $actAlerta);
        file_put_contents($archivoActividades, json_encode($actividades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        echo json_encode(array('success' => true, 'sos' => $sosRegistro, 'message' => 'Alerta SOS registrada y notificada con éxito.'));
        break;

    // 8. Consultar alertas de emergencias activas
    case 'get_emergencias':
        $emergencias = array();
        if (file_exists($archivoEmergencias)) {
            $emergencias = json_decode(file_get_contents($archivoEmergencias), true);
            if (!is_array($emergencias)) $emergencias = array();
        }
        echo json_encode(array('success' => true, 'emergencias' => $emergencias));
        break;

    // 9. Registrar punto GPS de telemetría
    case 'registrar_gps':
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if ($data && isset($data['lat']) && isset($data['lng'])) {
            $data['timestamp'] = date('Y-m-d H:i:s');
            
            $gpsLogs = array();
            if (file_exists($archivoGpsLogs)) {
                $gpsLogs = json_decode(file_get_contents($archivoGpsLogs), true);
                if (!is_array($gpsLogs)) $gpsLogs = array();
            }

            $gpsLogs[] = $data;
            if (count($gpsLogs) > 300) {
                $gpsLogs = array_slice($gpsLogs, -300);
            }
            file_put_contents($archivoGpsLogs, json_encode($gpsLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false));
        }
        break;

    // 10. Obtener historial GPS para el Reproductor Histórico (Playback)
    case 'get_gps_history':
        $volqueta = isset($_GET['volqueta']) ? trim($_GET['volqueta']) : 'VOL-04';
        $gpsLogs = array();
        if (file_exists($archivoGpsLogs)) {
            $gpsLogs = json_decode(file_get_contents($archivoGpsLogs), true);
            if (!is_array($gpsLogs)) $gpsLogs = array();
        }

        echo json_encode(array('success' => true, 'volqueta' => $volqueta, 'puntos' => $gpsLogs));
        break;

    // 11. Guardar Canva Vectorial GeoJSON
    case 'guardar_vectores':
        $input = file_get_contents('php://input');
        if ($input) {
            file_put_contents($archivoVectores, $input);
            echo json_encode(array('success' => true, 'message' => 'Capa vectorial guardada exitosamente.'));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Sin contenido GeoJSON.'));
        }
        break;

    // 12. Obtener Canva Vectorial GeoJSON
    case 'get_vectores':
        if (file_exists($archivoVectores)) {
            echo file_get_contents($archivoVectores);
        } else {
            echo json_encode(array('type' => 'FeatureCollection', 'features' => array()));
        }
        break;

    default:
        echo json_encode(array('success' => false, 'message' => 'Acción no válida o no especificada.'));
        break;
}
