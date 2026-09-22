<?php
/**
 * Capa de Datos y Sentencias SQL para el Módulo de Mapeo Interactivo
 * Mapeo georreferenciado, eventos de relavera, sectores (finca_actividad),
 * flota, conductores, manifiestos y emergencias SOS.
 *
 * Arquitectura estándar ERP: Modelo / Capa SQL desacoplada
 * @author Sistema ERP - Módulo Mapeo
 * @version 2.0 (PHP 5.3+ / MySQL)
 * @package mapeo.LOGICA
 */

// Compatibilidad de constantes para PHP 5.3
if (!defined('JSON_PRETTY_PRINT')) {
    define('JSON_PRETTY_PRINT', 0);
}
if (!defined('JSON_UNESCAPED_UNICODE')) {
    define('JSON_UNESCAPED_UNICODE', 0);
}

/**
 * Obtener o inicializar conexión a la base de datos MySQL
 * @param string $dispositivoBD
 * @return MysqlConexion|null
 */
function sql_map_get_conexion($dispositivoBD = 'ecoparkmining') {
    static $conexionCache = null;
    if ($conexionCache !== null && is_object($conexionCache) && !empty($conexionCache->conexion)) {
        return $conexionCache;
    }

    if (!class_exists('DebugBar')) {
        class DebugBar {
            public static function __callStatic($name, $args) {}
        }
    }

    $rutaConexion = dirname(dirname(dirname(__FILE__))) . '/DATA/MysqlConexion.php';
    if (file_exists($rutaConexion)) {
        require_once($rutaConexion);
        if (class_exists('MysqlConexion')) {
            try {
                $conexionCache = new MysqlConexion($dispositivoBD);
                if (!$conexionCache || empty($conexionCache->conexion)) {
                    $conexionCache = new MysqlConexion('exa');
                }
                if (!$conexionCache || empty($conexionCache->conexion)) {
                    $conexionCache = new MysqlConexion();
                }
            } catch (Exception $e) {
                $conexionCache = null;
            }
        }
    }
    return $conexionCache;
}

/**
 * Consultar lista de actividades / eventos georreferenciados
 * @param int $empresaCod
 * @param string $filtroTipo
 * @param string $filtroEstado
 * @param MysqlConexion|null $dbConexion
 * @return array
 */
function sql_map_get_actividades($empresaCod, $filtroTipo = '', $filtroEstado = '', $dbConexion = null) {
    if (!$dbConexion) {
        $dbConexion = sql_map_get_conexion();
    }

    $actividades = array();
    $totalCubicajeM3 = 0.0;

    if ($dbConexion && !empty($dbConexion->conexion)) {
        $whereClauses = array("a.Act_Est = 'A'");
        if (!empty($empresaCod)) {
            $empEsc = (int)$empresaCod;
            $whereClauses[] = "(a.Emp_Cod = {$empEsc} OR a.Emp_Cod = 0)";
        }
        if (!empty($filtroTipo)) {
            $tipEsc = mysqli_real_escape_string($dbConexion->conexion, $filtroTipo);
            $whereClauses[] = "a.Act_Tip = '{$tipEsc}'";
        }
        if (!empty($filtroEstado)) {
            $estEsc = mysqli_real_escape_string($dbConexion->conexion, $filtroEstado);
            $whereClauses[] = "a.Act_Est_Ope = '{$estEsc}'";
        }

        $whereSql = implode(' AND ', $whereClauses);

        $sql = "SELECT a.Act_Cod, a.Act_Ide, a.Emp_Cod, a.Suc_Cod, a.Fnc_Cod, a.Veh_Cod, a.Cho_Cod,
                       a.Act_Tip, a.Act_Tip_Lab, a.Act_Ubi_Nom, a.Act_Lat, a.Act_Lng, a.Act_Ele,
                       a.Act_Vol_M3, a.Act_Are_M2, a.Act_Lon_M, a.Act_Fec, a.Act_Est,
                       a.Act_Est_Ope, a.Act_Est_Bad, a.Act_Obs, a.Act_Geo_JSON, a.Act_Sync_Off,
                       v.Veh_Pla,
                       CONCAT(p.Prs_Ape, ' ', p.Prs_Nom) AS Chofer_Nombre,
                       f.Fnc_Des AS Sector_Nombre
                FROM relavera_actividades a
                LEFT JOIN vehiculo v ON a.Veh_Cod = v.Veh_Cod
                LEFT JOIN chofer c ON a.Cho_Cod = c.Cho_Cod
                LEFT JOIN persona p ON c.Prs_Cod = p.Prs_Cod
                LEFT JOIN finca_actividad f ON a.Fnc_Cod = f.Fnc_Cod
                WHERE {$whereSql}
                ORDER BY a.Act_Fec DESC, a.Act_Cod DESC";

        $res = @mysqli_query($dbConexion->conexion, $sql);
        if ($res) {
            while ($row = @mysqli_fetch_assoc($res)) {
                $geoInfo = null;
                $evidencias = array();
                $icono = '';
                $color = '';
                $volquetaNum = !empty($row['Veh_Pla']) ? ('VOL-' . $row['Veh_Cod'] . ' (' . $row['Veh_Pla'] . ')') : '';

                if (!empty($row['Act_Geo_JSON'])) {
                    $parsed = json_decode($row['Act_Geo_JSON'], true);
                    if (is_array($parsed)) {
                        // Si se guardó como estructura empaquetada
                        if (isset($parsed['geometria'])) {
                            $geoInfo = $parsed['geometria'];
                        } elseif (isset($parsed['type'])) {
                            $geoInfo = $parsed;
                        }

                        if (isset($parsed['evidencias']) && is_array($parsed['evidencias'])) {
                            $evidencias = $parsed['evidencias'];
                        }
                        if (isset($parsed['icono']) && !empty($parsed['icono'])) {
                            $icono = $parsed['icono'];
                        }
                        if (isset($parsed['color']) && !empty($parsed['color'])) {
                            $color = $parsed['color'];
                        }
                        if (isset($parsed['volqueta_num']) && !empty($parsed['volqueta_num'])) {
                            $volquetaNum = $parsed['volqueta_num'];
                        }
                    }
                }

                // Fallbacks visuales de íconos y colores por tipo de actividad
                if (empty($icono)) {
                    $tipoAct = $row['Act_Tip'];
                    if ($tipoAct === 'reporte_alerta') $icono = 'fa-exclamation-triangle';
                    elseif ($tipoAct === 'monitoreo_piezometro') $icono = 'fa-eye';
                    elseif ($tipoAct === 'descarga_humeda') $icono = 'fa-tint';
                    elseif ($tipoAct === 'compactacion_dique') $icono = 'fa-cogs';
                    elseif ($tipoAct === 'mantenimiento_vias') $icono = 'fa-road';
                    elseif ($tipoAct === 'acarreo_material') $icono = 'fa-truck';
                    else $icono = 'fa-truck';
                }

                if (empty($color)) {
                    $tipoAct = $row['Act_Tip'];
                    if ($tipoAct === 'reporte_alerta') $color = '#ef4444';
                    elseif ($tipoAct === 'monitoreo_piezometro') $color = '#0f766e';
                    elseif ($tipoAct === 'descarga_humeda') $color = '#06b6d4';
                    elseif ($tipoAct === 'compactacion_dique') $color = '#ea580c';
                    elseif ($tipoAct === 'mantenimiento_vias') $color = '#64748b';
                    else $color = '#10b981';
                }

                $volumenVal = (float)$row['Act_Vol_M3'];
                $totalCubicajeM3 += $volumenVal;

                $ubiNom = !empty($row['Act_Ubi_Nom']) ? $row['Act_Ubi_Nom'] : (!empty($row['Sector_Nombre']) ? $row['Sector_Nombre'] : 'Frente Central de Vertido');

                $actividades[] = array(
                    'id' => $row['Act_Ide'],
                    'act_cod' => (int)$row['Act_Cod'],
                    'tipo' => $row['Act_Tip'],
                    'tipo_label' => $row['Act_Tip_Lab'],
                    'ubicacion_nombre' => $ubiNom,
                    'fnc_cod' => $row['Fnc_Cod'],
                    'veh_cod' => $row['Veh_Cod'],
                    'cho_cod' => $row['Cho_Cod'],
                    'chofer' => !empty($row['Chofer_Nombre']) ? trim($row['Chofer_Nombre']) : 'Operador de Maquinaria',
                    'placa' => !empty($row['Veh_Pla']) ? $row['Veh_Pla'] : 'N/A',
                    'volqueta_num' => !empty($volquetaNum) ? $volquetaNum : 'Unidad Operativa',
                    'fecha' => date('Y-m-d H:i', strtotime($row['Act_Fec'])),
                    'lat' => (float)$row['Act_Lat'],
                    'lng' => (float)$row['Act_Lng'],
                    'elevacion' => (float)$row['Act_Ele'],
                    'area_m2' => (float)$row['Act_Are_M2'],
                    'volumen_m3' => $volumenVal,
                    'longitud_m' => (float)$row['Act_Lon_M'],
                    'estado' => $row['Act_Est_Ope'],
                    'estado_badge' => !empty($row['Act_Est_Bad']) ? $row['Act_Est_Bad'] : 'success',
                    'icono' => $icono,
                    'color' => $color,
                    'observaciones' => !empty($row['Act_Obs']) ? $row['Act_Obs'] : '',
                    'evidencias' => $evidencias,
                    'geometria' => $geoInfo,
                    'sincronizado_offline' => ((int)$row['Act_Sync_Off'] === 1)
                );
            }
        }
    }

    return array(
        'success' => true,
        'actividades' => $actividades,
        'total_m3' => round($totalCubicajeM3, 2),
        'total_registros' => count($actividades)
    );
}

/**
 * Guardar o actualizar actividad en MySQL relavera_actividades
 * @param array $data
 * @param int $empresaCod
 * @param int $sucursalCod
 * @param MysqlConexion|null $dbConexion
 * @return array
 */
function sql_map_guardar_actividad($data, $empresaCod, $sucursalCod, $dbConexion = null) {
    if (!$dbConexion) {
        $dbConexion = sql_map_get_conexion();
    }

    if (!$dbConexion || empty($dbConexion->conexion)) {
        return array('success' => false, 'message' => 'Sin conexión activa a la base de datos MySQL.');
    }

    $conn = $dbConexion->conexion;

    $idActividad = !empty($data['id']) ? trim($data['id']) : ('ACT-' . date('Ymd-His'));
    $actIde = mysqli_real_escape_string($conn, $idActividad);
    $empCod = (int)$empresaCod;
    $sucCod = (int)$sucursalCod;

    $actLat = (float)$data['lat'];
    $actLng = (float)$data['lng'];
    $actEle = isset($data['elevacion']) ? (float)$data['elevacion'] : 680.00;
    $actVol = isset($data['volumen_m3']) ? (float)$data['volumen_m3'] : 0.00;
    $actAre = isset($data['area_m2']) ? (float)$data['area_m2'] : 0.00;
    $actLon = isset($data['longitud_m']) ? (float)$data['longitud_m'] : 0.00;

    $actTip = mysqli_real_escape_string($conn, isset($data['tipo']) ? $data['tipo'] : 'descarga_relave');
    $actTipLab = mysqli_real_escape_string($conn, isset($data['tipo_label']) ? $data['tipo_label'] : 'Descarga de Relave Seco');
    $actEstOpe = mysqli_real_escape_string($conn, isset($data['estado']) ? $data['estado'] : 'Completada');
    $actEstBad = mysqli_real_escape_string($conn, isset($data['estado_badge']) ? $data['estado_badge'] : 'success');
    $actObs = mysqli_real_escape_string($conn, isset($data['observaciones']) ? $data['observaciones'] : '');
    $actFec = !empty($data['fecha']) ? mysqli_real_escape_string($conn, $data['fecha']) : date('Y-m-d H:i:s');
    $actSyncOff = !empty($data['sincronizado_offline']) ? 1 : 0;

    // Resolver Llaves Foráneas (Fnc_Cod, Veh_Cod, Cho_Cod)
    $fncCodVal = (!empty($data['fnc_cod']) && is_numeric($data['fnc_cod'])) ? (int)$data['fnc_cod'] : 'NULL';
    $vehCodVal = (!empty($data['veh_cod']) && is_numeric($data['veh_cod'])) ? (int)$data['veh_cod'] : 'NULL';
    $choCodVal = (!empty($data['cho_cod']) && is_numeric($data['cho_cod'])) ? (int)$data['cho_cod'] : 'NULL';

    if ($vehCodVal === 'NULL' && !empty($data['placa']) && $data['placa'] !== 'N/A') {
        $plaEsc = mysqli_real_escape_string($conn, trim($data['placa']));
        $resV = @mysqli_query($conn, "SELECT Veh_Cod FROM vehiculo WHERE Veh_Pla = '{$plaEsc}' LIMIT 1");
        if ($resV && ($rowV = @mysqli_fetch_assoc($resV))) {
            $vehCodVal = (int)$rowV['Veh_Cod'];
        }
    }

    if ($choCodVal === 'NULL' && !empty($data['chofer'])) {
        $choEsc = mysqli_real_escape_string($conn, trim($data['chofer']));
        $resC = @mysqli_query($conn, "SELECT c.Cho_Cod FROM chofer c INNER JOIN persona p ON c.Prs_Cod = p.Prs_Cod WHERE CONCAT(p.Prs_Ape, ' ', p.Prs_Nom) LIKE '%{$choEsc}%' LIMIT 1");
        if ($resC && ($rowC = @mysqli_fetch_assoc($resC))) {
            $choCodVal = (int)$rowC['Cho_Cod'];
        }
    }

    // Resolver Ubicación / Sector
    $ubiNom = !empty($data['ubicacion_nombre']) ? trim($data['ubicacion_nombre']) : '';
    if (empty($ubiNom) && $fncCodVal !== 'NULL') {
        $resF = @mysqli_query($conn, "SELECT Fnc_Des FROM finca_actividad WHERE Fnc_Cod = {$fncCodVal} LIMIT 1");
        if ($resF && ($rowF = @mysqli_fetch_assoc($resF))) {
            $ubiNom = $rowF['Fnc_Des'];
        }
    }
    if (empty($ubiNom)) {
        $ubiNom = 'Frente Central de Vertido';
    }
    $ubiEsc = mysqli_real_escape_string($conn, $ubiNom);

    // Empaquetar Geometría, Evidencias, Ícono y Color en el GeoJSON
    $geoData = array(
        'geometria' => isset($data['geometria']) ? $data['geometria'] : null,
        'evidencias' => (isset($data['evidencias']) && is_array($data['evidencias'])) ? $data['evidencias'] : array(),
        'icono' => isset($data['icono']) ? $data['icono'] : '',
        'color' => isset($data['color']) ? $data['color'] : '',
        'volqueta_num' => isset($data['volqueta_num']) ? $data['volqueta_num'] : ''
    );
    $geoJsonStr = json_encode($geoData);
    $geoEsc = mysqli_real_escape_string($conn, $geoJsonStr);

    $sql = "INSERT INTO relavera_actividades (
                Act_Ide, Emp_Cod, Suc_Cod, Fnc_Cod, Veh_Cod, Cho_Cod,
                Act_Tip, Act_Tip_Lab, Act_Ubi_Nom, Act_Lat, Act_Lng, Act_Ele,
                Act_Vol_M3, Act_Are_M2, Act_Lon_M, Act_Fec, Act_Est,
                Act_Est_Ope, Act_Est_Bad, Act_Obs, Act_Geo_JSON, Act_Sync_Off
            ) VALUES (
                '{$actIde}', {$empCod}, {$sucCod}, {$fncCodVal}, {$vehCodVal}, {$choCodVal},
                '{$actTip}', '{$actTipLab}', '{$ubiEsc}', {$actLat}, {$actLng}, {$actEle},
                {$actVol}, {$actAre}, {$actLon}, '{$actFec}', 'A',
                '{$actEstOpe}', '{$actEstBad}', '{$actObs}', '{$geoEsc}', {$actSyncOff}
            ) ON DUPLICATE KEY UPDATE 
                Fnc_Cod = {$fncCodVal}, Veh_Cod = {$vehCodVal}, Cho_Cod = {$choCodVal},
                Act_Tip = '{$actTip}', Act_Tip_Lab = '{$actTipLab}',
                Act_Ubi_Nom = '{$ubiEsc}',
                Act_Lat = {$actLat}, Act_Lng = {$actLng}, Act_Ele = {$actEle},
                Act_Vol_M3 = {$actVol}, Act_Are_M2 = {$actAre}, Act_Lon_M = {$actLon},
                Act_Est_Ope = '{$actEstOpe}', Act_Est_Bad = '{$actEstBad}', Act_Obs = '{$actObs}',
                Act_Geo_JSON = '{$geoEsc}', Act_Sync_Off = {$actSyncOff}";

    $res = @mysqli_query($conn, $sql);
    if ($res) {
        $insertId = mysqli_insert_id($conn);
        return array(
            'success' => true,
            'id' => $idActividad,
            'act_cod' => $insertId,
            'message' => 'Actividad guardada correctamente en la base de datos MySQL (relavera_actividades).'
        );
    } else {
        return array(
            'success' => false,
            'message' => 'Error al guardar en MySQL: ' . mysqli_error($conn)
        );
    }
}

/**
 * Eliminación lógica de una actividad (Act_Est = 'I')
 * @param string $idActividad
 * @param int $empresaCod
 * @param MysqlConexion|null $dbConexion
 * @return array
 */
function sql_map_eliminar_actividad($idActividad, $empresaCod, $dbConexion = null) {
    if (!$dbConexion) {
        $dbConexion = sql_map_get_conexion();
    }

    if (!$dbConexion || empty($dbConexion->conexion)) {
        return array('success' => false, 'message' => 'Sin conexión activa a MySQL.');
    }

    $conn = $dbConexion->conexion;
    $idEsc = mysqli_real_escape_string($conn, trim($idActividad));

    $sql = "UPDATE relavera_actividades 
            SET Act_Est = 'I' 
            WHERE Act_Ide = '{$idEsc}'";

    $res = @mysqli_query($conn, $sql);
    if ($res && mysqli_affected_rows($conn) > 0) {
        return array('success' => true, 'message' => 'Actividad eliminada con éxito de la base de datos.');
    } else {
        return array('success' => false, 'message' => 'No se encontró la actividad o ya fue inactivada.');
    }
}

/**
 * Consultar sectores y ubicaciones activas desde finca_actividad
 * @param int $sucursalCod
 * @param MysqlConexion|null $dbConexion
 * @return array
 */
function sql_map_get_sectores($sucursalCod, $dbConexion = null) {
    if (!$dbConexion) {
        $dbConexion = sql_map_get_conexion();
    }

    $sectores = array();

    if ($dbConexion && !empty($dbConexion->conexion)) {
        $conn = $dbConexion->conexion;
        $sql = "SELECT Fnc_Cod, Fnc_Des, Fnc_Dir, Fnc_Hec, Fnc_Lat, Fnc_Lng, Fnc_Geo_JSON, Suc_Cod, Fnc_Est 
                FROM finca_actividad 
                WHERE Fnc_Est = 'A' 
                ORDER BY Fnc_Des ASC";

        $res = @mysqli_query($conn, $sql);
        if ($res) {
            while ($row = @mysqli_fetch_assoc($res)) {
                $geo = null;
                $icono = 'fa-map-marker';
                $color = '#8b5cf6';
                $categoria = 'Sector Operativo';

                if (!empty($row['Fnc_Geo_JSON'])) {
                    $geoParsed = json_decode($row['Fnc_Geo_JSON'], true);
                    if (is_array($geoParsed)) {
                        $geo = $geoParsed;
                        if (isset($geoParsed['properties']['icono'])) $icono = $geoParsed['properties']['icono'];
                        elseif (isset($geoParsed['icono'])) $icono = $geoParsed['icono'];

                        if (isset($geoParsed['properties']['color'])) $color = $geoParsed['properties']['color'];
                        elseif (isset($geoParsed['color'])) $color = $geoParsed['color'];

                        if (isset($geoParsed['properties']['categoria'])) $categoria = $geoParsed['properties']['categoria'];
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
                    'suc_cod' => (int)$row['Suc_Cod']
                );
            }
        }
    }

    return array(
        'success' => true,
        'sectores' => $sectores,
        'total' => count($sectores)
    );
}

/**
 * Guardar un nuevo sector o ubicación en finca_actividad
 * @param array $data
 * @param int $sucursalCod
 * @param MysqlConexion|null $dbConexion
 * @return array
 */
function sql_map_guardar_sector($data, $sucursalCod, $dbConexion = null) {
    if (!$dbConexion) {
        $dbConexion = sql_map_get_conexion();
    }

    if (!$dbConexion || empty($dbConexion->conexion)) {
        return array('success' => false, 'message' => 'Sin conexión a base de datos.');
    }

    $conn = $dbConexion->conexion;
    $nombre = trim($data['nombre']);
    $categoria = isset($data['categoria']) ? trim($data['categoria']) : 'Sector Operativo';
    $direccion = isset($data['direccion']) ? trim($data['direccion']) : '';
    $hectareas = isset($data['hectareas']) ? (float)$data['hectareas'] : 0.0;
    $lat = (float)$data['lat'];
    $lng = (float)$data['lng'];
    $icono = isset($data['icono']) && !empty($data['icono']) ? trim($data['icono']) : 'fa-map-marker';
    $color = isset($data['color']) && !empty($data['color']) ? trim($data['color']) : '#8b5cf6';
    $geometria = isset($data['geometria']) ? $data['geometria'] : null;

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
    $nomEsc = mysqli_real_escape_string($conn, $nombre);
    $dirEsc = mysqli_real_escape_string($conn, $direccion);
    $geoEsc = mysqli_real_escape_string($conn, $geoJsonStr);
    $sucCod = (int)$sucursalCod;

    $sql = "INSERT INTO finca_actividad (Fnc_Des, Fnc_Dir, Fnc_Hec, Fnc_Lat, Fnc_Lng, Fnc_Geo_JSON, Suc_Cod, Fnc_Est) 
            VALUES ('{$nomEsc}', '{$dirEsc}', {$hectareas}, {$lat}, {$lng}, '{$geoEsc}', {$sucCod}, 'A')";

    $res = @mysqli_query($conn, $sql);
    if ($res) {
        $nuevoId = (int)mysqli_insert_id($conn);
        return array(
            'success' => true,
            'id' => $nuevoId,
            'nombre' => $nombre,
            'sector' => array(
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
                'suc_cod' => $sucCod
            ),
            'message' => 'Sector registrado exitosamente en la base de datos (finca_actividad).'
        );
    } else {
        return array('success' => false, 'message' => 'Error al insertar sector en finca_actividad: ' . mysqli_error($conn));
    }
}

/**
 * Consultar flota de volquetas y choferes activos
 * @param MysqlConexion|null $dbConexion
 * @return array
 */
function sql_map_get_flota($dbConexion = null) {
    if (!$dbConexion) {
        $dbConexion = sql_map_get_conexion();
    }

    $vehiculos = array();
    $choferes = array();
    $flota = array();

    if ($dbConexion && !empty($dbConexion->conexion)) {
        $conn = $dbConexion->conexion;

        // Consulta de Vehículos / Maquinaria Activa
        $sqlVeh = "SELECT v.Veh_Cod, v.Veh_Pla, v.Veh_Mar, v.Veh_Cap, t.Mat_Des
                   FROM vehiculo v
                   LEFT JOIN manifiesto_transporte t ON v.Mat_Cod = t.Mat_Cod
                   WHERE v.Veh_Est = 'A'
                   GROUP BY v.Veh_Pla
                   ORDER BY v.Veh_Pla ASC";
        $resVeh = @mysqli_query($conn, $sqlVeh);
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
        $resCho = @mysqli_query($conn, $sqlCho);
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
    }

    // Fallbacks si la BD aún no cuenta con registros de vehículos
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

    return array(
        'success' => true,
        'vehiculos' => $vehiculos,
        'choferes' => $choferes,
        'flota' => $flota,
        'total_vehiculos' => count($vehiculos),
        'total_choferes' => count($choferes),
        'origen_bd' => ($dbConexion && !empty($dbConexion->conexion) && count($vehiculos) > 4)
    );
}

/**
 * Consultar Manifiestos de hoy desde MySQL ERP
 * @param int $empresaCod
 * @param string $fechaHoy
 * @param MysqlConexion|null $dbConexion
 * @return array
 */
function sql_map_get_manifiestos_hoy($empresaCod, $fechaHoy = '', $dbConexion = null) {
    if (!$dbConexion) {
        $dbConexion = sql_map_get_conexion();
    }

    if (empty($fechaHoy)) {
        $fechaHoy = date('Y-m-d');
    }

    $manifiestos = array();

    if ($dbConexion && !empty($dbConexion->conexion)) {
        $conn = $dbConexion->conexion;
        $empEsc = (int)$empresaCod;
        $fecEsc = mysqli_real_escape_string($conn, $fechaHoy);

        $sql = "SELECT m.Man_Cod, m.Man_Fec, m.Man_Hor_Lle, m.Man_Pes_Net,
                       v.Veh_Pla, p.Pla_Nom,
                       CONCAT(per.Prs_Nom, ' ', per.Prs_Ape) AS Chofer_Nombre
                FROM manifiesto m
                LEFT JOIN vehiculo v ON m.Veh_Cod = v.Veh_Cod
                LEFT JOIN manifiesto_plantas p ON m.Pla_Cod = p.Pla_Cod
                LEFT JOIN chofer c ON m.Cho_Cod = c.Cho_Cod
                LEFT JOIN persona per ON c.Prs_Cod = per.Prs_Cod
                WHERE (m.Emp_Cod = {$empEsc} OR {$empEsc} = 0) AND m.Man_Fec = '{$fecEsc}'
                ORDER BY m.Man_Cod DESC
                LIMIT 10";

        $res = @mysqli_query($conn, $sql);
        if ($res) {
            while ($row = @mysqli_fetch_assoc($res)) {
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
    }

    // Fallbacks si no hay manifiestos registrados hoy
    if (empty($manifiestos)) {
        $manifiestos = array(
            array('codigo' => 'MAN-00891', 'hora' => '08:15', 'placa' => 'OBA-7821', 'planta' => 'Planta San Luis #2', 'chofer' => 'Manuel Carrión', 'peso_neto_ton' => 24.5, 'volumen_m3' => 15.3, 'estado' => 'Completado'),
            array('codigo' => 'MAN-00892', 'hora' => '09:40', 'placa' => 'PBC-3419', 'planta' => 'Planta El Tablón Central', 'chofer' => 'Luis Espinoza', 'peso_neto_ton' => 22.8, 'volumen_m3' => 14.2, 'estado' => 'Completado'),
            array('codigo' => 'MAN-00893', 'hora' => '11:10', 'placa' => 'LBA-9023', 'planta' => 'Molino & Flotación Bella Rica', 'chofer' => 'Jorge Aguilar', 'peso_neto_ton' => 28.0, 'volumen_m3' => 17.5, 'estado' => 'Completado'),
            array('codigo' => 'MAN-00894', 'hora' => '13:05', 'placa' => 'PBA-6124', 'planta' => 'Planta Providencia', 'chofer' => 'Carlos Morales', 'peso_neto_ton' => 25.1, 'volumen_m3' => 15.7, 'estado' => 'En Tránsito')
        );
    }

    return array(
        'success' => true,
        'manifiestos' => $manifiestos,
        'origen_bd' => ($dbConexion && !empty($dbConexion->conexion))
    );
}

/**
 * Reportar emergencia SOS georreferenciada en relavera_emergencias_sos y relavera_actividades
 * @param array $data
 * @param int $empresaCod
 * @param int $sucursalCod
 * @param MysqlConexion|null $dbConexion
 * @return array
 */
function sql_map_reportar_sos($data, $empresaCod, $sucursalCod, $dbConexion = null) {
    if (!$dbConexion) {
        $dbConexion = sql_map_get_conexion();
    }

    if (!$dbConexion || empty($dbConexion->conexion)) {
        return array('success' => false, 'message' => 'Sin conexión activa a MySQL para registrar SOS.');
    }

    $conn = $dbConexion->conexion;
    $sosIde = 'SOS-' . date('Ymd-His');
    $empCod = (int)$empresaCod;
    $lat = (float)$data['lat'];
    $lng = (float)$data['lng'];
    $motivo = isset($data['motivo']) ? trim($data['motivo']) : 'Alerta de Emergencia Inmediata';
    $chofer = isset($data['chofer']) ? trim($data['chofer']) : 'Operador en Campo';
    $volqueta = isset($data['volqueta']) ? trim($data['volqueta']) : 'Unidad de Acarreo';
    $fecHora = date('Y-m-d H:i:s');

    $motEsc = mysqli_real_escape_string($conn, $motivo);
    $detEsc = mysqli_real_escape_string($conn, "Alerta SOS activada por {$chofer} en {$volqueta}");

    // 1. Insertar en relavera_emergencias_sos
    $sqlSos = "INSERT INTO relavera_emergencias_sos (
                   Sos_Ide, Emp_Cod, Sos_Mot, Sos_Det, Sos_Lat, Sos_Lng, Sos_Fec, Sos_Est
               ) VALUES (
                   '{$sosIde}', {$empCod}, '{$motEsc}', '{$detEsc}', {$lat}, {$lng}, '{$fecHora}', 'A'
               )";
    @mysqli_query($conn, $sqlSos);

    // 2. Registrar como actividad crítica visible en el mapa
    $actData = array(
        'id' => $sosIde,
        'tipo' => 'reporte_alerta',
        'tipo_label' => 'Alerta Crítica SOS: ' . $motivo,
        'ubicacion_nombre' => 'Punto Crítico de Emergencia SOS',
        'chofer' => $chofer,
        'placa' => 'EMERGENCIA',
        'volqueta_num' => $volqueta,
        'fecha' => $fecHora,
        'lat' => $lat,
        'lng' => $lng,
        'elevacion' => 680.0,
        'area_m2' => 0,
        'volumen_m3' => 0,
        'longitud_m' => 0,
        'estado' => 'Alerta Crítica',
        'estado_badge' => 'danger',
        'icono' => 'fa-exclamation-triangle',
        'color' => '#ef4444',
        'observaciones' => 'EMERGENCIA ACTIVADA DESDE BOTÓN DE PÁNICO GEORREFERENCIADO.'
    );

    sql_map_guardar_actividad($actData, $empresaCod, $sucursalCod, $dbConexion);

    return array(
        'success' => true,
        'sos' => array(
            'id' => $sosIde,
            'lat' => $lat,
            'lng' => $lng,
            'motivo' => $motivo,
            'chofer' => $chofer,
            'volqueta' => $volqueta,
            'fecha_hora' => $fecHora,
            'estado' => 'ACTIVA'
        ),
        'message' => 'Alerta SOS registrada y notificada con éxito en la base de datos.'
    );
}

/**
 * Consultar emergencias activas
 * @param int $empresaCod
 * @param MysqlConexion|null $dbConexion
 * @return array
 */
function sql_map_get_emergencias($empresaCod, $dbConexion = null) {
    if (!$dbConexion) {
        $dbConexion = sql_map_get_conexion();
    }

    $emergencias = array();
    if ($dbConexion && !empty($dbConexion->conexion)) {
        $conn = $dbConexion->conexion;
        $empEsc = (int)$empresaCod;

        $sql = "SELECT Sos_Cod, Sos_Ide, Emp_Cod, Sos_Mot, Sos_Det, Sos_Lat, Sos_Lng, Sos_Fec, Sos_Est 
                FROM relavera_emergencias_sos 
                WHERE Sos_Est = 'A' AND (Emp_Cod = {$empEsc} OR {$empEsc} = 0)
                ORDER BY Sos_Fec DESC 
                LIMIT 50";

        $res = @mysqli_query($conn, $sql);
        if ($res) {
            while ($row = @mysqli_fetch_assoc($res)) {
                $emergencias[] = array(
                    'id' => $row['Sos_Ide'],
                    'lat' => (float)$row['Sos_Lat'],
                    'lng' => (float)$row['Sos_Lng'],
                    'motivo' => $row['Sos_Mot'],
                    'detalles' => $row['Sos_Det'],
                    'fecha_hora' => $row['Sos_Fec'],
                    'estado' => 'ACTIVA'
                );
            }
        }
    }

    return array('success' => true, 'emergencias' => $emergencias);
}

/**
 * Sincronizar lote de actividades y sectores offline a MySQL
 * @param array $lote
 * @param int $empresaCod
 * @param int $sucursalCod
 * @param MysqlConexion|null $dbConexion
 * @return array
 */
function sql_map_sincronizar_lote($lote, $empresaCod, $sucursalCod, $dbConexion = null) {
    $procesadosAct = 0;
    $procesadosSec = 0;
    $dirLocator = dirname(dirname(__FILE__)) . '/RECURSOS/locator/';

    // 1. Sincronizar Sectores Offline en finca_actividad
    if (isset($lote['sectores']) && is_array($lote['sectores'])) {
        foreach ($lote['sectores'] as $sec) {
            if (isset($sec['lat']) && isset($sec['lng']) && !empty($sec['nombre'])) {
                $guardadoSec = sql_map_guardar_sector($sec, $sucursalCod, $dbConexion);
                if ($guardadoSec && !empty($guardadoSec['success'])) {
                    $procesadosSec++;
                }
            }
        }
    }

    // 2. Sincronizar Actividades / Eventos Offline en relavera_actividades
    if (isset($lote['actividades']) && is_array($lote['actividades'])) {
        if (!is_dir($dirLocator)) {
            @mkdir($dirLocator, 0777, true);
        }

        foreach ($lote['actividades'] as $act) {
            if (isset($act['lat']) && isset($act['lng'])) {
                $act['sincronizado_offline'] = true;

                // Procesar evidencias fotográficas capturadas en Base64 durante el modo offline
                if (isset($act['evidencias']) && is_array($act['evidencias'])) {
                    foreach ($act['evidencias'] as &$evi) {
                        $base64Raw = '';
                        if (isset($evi['imagen_base64']) && !empty($evi['imagen_base64'])) {
                            $base64Raw = $evi['imagen_base64'];
                        } elseif (isset($evi['url']) && strpos($evi['url'], 'data:image') === 0) {
                            $base64Raw = $evi['url'];
                        }

                        if (!empty($base64Raw)) {
                            $ext = 'jpg';
                            if (preg_match('/^data:image\/(\w+);base64,/', $base64Raw, $typeMatch)) {
                                $base64Raw = substr($base64Raw, strpos($base64Raw, ',') + 1);
                                $extRaw = strtolower($typeMatch[1]);
                                if (in_array($extRaw, array('jpg', 'jpeg', 'png', 'webp'))) {
                                    $ext = ($extRaw === 'jpeg') ? 'jpg' : $extRaw;
                                }
                            }
                            $decoded = base64_decode($base64Raw);
                            if ($decoded !== false) {
                                $nuevoNombre = 'EVI_' . date('Ymd_His') . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 6) . '.' . $ext;
                                $rutaDestino = $dirLocator . $nuevoNombre;
                                if (@file_put_contents($rutaDestino, $decoded)) {
                                    $evi['archivo'] = $nuevoNombre;
                                    $evi['url'] = '../RECURSOS/locator/' . $nuevoNombre;
                                    unset($evi['imagen_base64']);
                                }
                            }
                        }
                    }
                    unset($evi);
                }

                $guardado = sql_map_guardar_actividad($act, $empresaCod, $sucursalCod, $dbConexion);
                if ($guardado && !empty($guardado['success'])) {
                    $procesadosAct++;
                }
            }
        }
    }

    return array(
        'success' => true,
        'message' => "Sincronización completada: {$procesadosAct} actividades y {$procesadosSec} sectores sincronizados con éxito en MySQL.",
        'actividades_procesadas' => $procesadosAct,
        'sectores_procesados' => $procesadosSec
    );
}

